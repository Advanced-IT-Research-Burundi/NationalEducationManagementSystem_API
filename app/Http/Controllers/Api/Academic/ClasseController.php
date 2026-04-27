<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClasseRequest;
use App\Http\Requests\UpdateClasseRequest;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\AffectationClasse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClasseController extends Controller
{
    /**
     * Display a listing of classes.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Classe::class);

        $query = Classe::with(['niveau', 'school', 'creator', 'section']);

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // School filter
        if ($request->filled('school_id') && $request->school_id !== '_all') {
            $query->bySchool((int) $request->school_id);
        }

        // Niveau filter
        if ($request->filled('niveau_id') && $request->niveau_id !== '_all') {
            $query->byNiveau((int) $request->niveau_id);
        }

        // Année scolaire filter
        if ($request->filled('annee_scolaire')) {
            $query->byAnneeScolaire($request->annee_scolaire);
        }

        // Status filter
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $classes = $query->latest()->paginate($request->get('per_page', 15));

        //cache them for 24 hours
        Cache::put('classes', $classes, 24 * 60 * 60);

        return response()->json($classes);
    }

    /**
     * Store a newly created classe.
     */
    public function store(StoreClasseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $classe = Classe::create($data);

        return response()->json([
            'message' => 'Classe créée avec succès',
            'classe' => $classe->load(['niveau', 'school']),
        ], 201);
    }

    /**
     * Display the specified classe.
     */
    public function show(Classe $classe): JsonResponse
    {
        $this->authorize('view', $classe);

        return response()->json(
            $classe->load(['niveau', 'school', 'creator', 'enseignants', 'eleves'])
        );
    }

    /**
     * Update the specified classe.
     */
    public function update(UpdateClasseRequest $request, Classe $classe): JsonResponse
    {
        $classe->update($request->validated());

        return response()->json([
            'message' => 'Classe mise à jour avec succès',
            'classe' => $classe->load(['niveau', 'school']),
        ]);
    }

    /**
     * Remove the specified classe.
     */
    public function destroy(Classe $classe): JsonResponse
    {
        $this->authorize('delete', $classe);

        // Check if classe has inscriptions
        if ($classe->inscriptions()->where('statut', 'ACTIVE')->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette classe car elle contient des élèves inscrits.',
            ], 422);
        }

        $classe->delete();

        return response()->json(['message' => 'Classe supprimée avec succès']);
    }

    /**
     * Get classes for a specific school.
     */
    public function bySchool(Request $request, int $schoolId): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('Fetching classes for school', [
            'school_id' => $schoolId,
            'annee_scolaire_id' => $request->input('annee_scolaire_id'),
            'user_id' => auth()->id()
        ]);
        $query = Classe::bySchool($schoolId)->with(['niveau', 'section']);

        $anneeId = $request->input('annee_scolaire_id') ?? $request->input('annee_scolaire');
        if ($anneeId) {
            $query->byAnneeScolaire((int) $anneeId);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        } else {
            $query->active();
        }

        $classes = $query->get();

        return response()->json($classes);
    }

    /**
     * Get enseignants assigned to a classe.
     */
    public function enseignants(Classe $classe): JsonResponse
    {
        $this->authorize('view', $classe);

        $enseignants = $classe->enseignants()
            ->wherePivot('statut', 'ACTIVE')
            ->with('user')
            ->get();

        return response()->json($enseignants);
    }

    /**
     * Get élèves enrolled in a classe.
     */
    public function eleves(Classe $classe): JsonResponse
    {
        $this->authorize('view', $classe);

        $eleves = $classe->eleves()
            ->wherePivot('statut', 'ACTIVE')
            ->get();

        return response()->json($eleves);
    }

    /**
     * Add an eleve to a classe using pivot table inscriptions_eleves
     */
    public function addEleve(Request $request, Classe $classe): JsonResponse
{
    $this->authorize('update', $classe);

    $request->validate([
        'eleve_id' => 'required|exists:eleves,id',
        'annee_scolaire' => 'required|string',
    ]);

    $eleve = Eleve::findOrFail($request->eleve_id);

    // ✅ Fix: bloquer uniquement les statuts vraiment bloquants
    $blockedStatuts = ['DIPLOME', 'TRANSFERE', 'EXCLU', 'SUSPENDU'];
    if (in_array($eleve->statut, $blockedStatuts)) {
        return response()->json([
            'message' => 'Cet élève ne peut pas être inscrit (statut: '.$eleve->statut.').',
        ], 422);
    }

    if ($eleve->isEnrolledInClass($classe->id)) {
        return response()->json([
            'message' => 'Cet élève est déjà inscrit dans cette classe.',
        ], 422);
    }

    if (! $classe->hasCapacity()) {
        return response()->json([
            'message' => 'La classe est pleine. Capacité maximale atteinte.',
        ], 422);
    }

    if ($eleve->school_id !== $classe->school_id) {
        return response()->json([
            'message' => 'L\'élève et la classe doivent appartenir à la même école.',
        ], 422);
    }

    DB::transaction(function () use ($eleve, $classe, $request) {
        $classe->eleves()->syncWithoutDetaching([
            $eleve->id => [
                'annee_scolaire' => $request->annee_scolaire,
                'date_inscription' => now(),
                'statut' => 'ACTIVE',
            ]
        ]);

        $inscription = $eleve->activeInscription;
        if ($inscription) {
            AffectationClasse::firstOrCreate([
                'inscription_id' => $inscription->id,
                'classe_id' => $classe->id,
                'est_active' => true,
            ], [
                'date_affectation' => now(),
                'affecte_par' => Auth::id(),
            ]);
        }

        // Mettre à jour le statut élève si nécessaire
        if (empty($eleve->statut) || in_array($eleve->statut, ['INSCRIT', 'ARCHIVE'])) {
            $eleve->update(['statut' => 'ACTIF']);
        }

        // ✅ Recalculer l'effectif après ajout
        $newEffectif = DB::table('eleve_class')
            ->where('classe_id', $classe->id)
            ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
            ->count();

        $classe->update(['effectif' => $newEffectif]);
    });

    return response()->json([
        'message' => 'Élève inscrit avec succès dans la classe',
        'classe' => $classe->fresh()->load('eleves'),
    ], 201);
}

    /**
     * Get classe statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Classe::class);

        $query = Classe::query();

        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('annee_scolaire')) {
            $query->byAnneeScolaire($request->annee_scolaire);
        }

        $stats = [
            'total' => $query->count(),
            'by_status' => [
                'active' => (clone $query)->active()->count(),
                'inactive' => (clone $query)->where('statut', 'INACTIVE')->count(),
                'archivee' => (clone $query)->where('statut', 'ARCHIVEE')->count(),
            ],
            'by_niveau' => (clone $query)
                ->selectRaw('niveau_id, COUNT(*) as count')
                ->groupBy('niveau_id')
                ->with('niveau:id,nom')
                ->get()
                ->pluck('count', 'niveau.nom'),
        ];

        return response()->json($stats);
    }

    /**
 * Remove an eleve from a classe and update effectif.
 */
public function removeEleve(Request $request, Classe $classe): JsonResponse
{
    $this->authorize('update', $classe);

    $request->validate([
        'eleve_id' => 'required|exists:eleves,id',
    ]);

    DB::transaction(function () use ($request, $classe) {
        // Désactiver dans le pivot
        $classe->eleves()->updateExistingPivot($request->eleve_id, [
            'statut' => 'INACTIVE',
        ]);

        // Désactiver les AffectationClasse liées
        AffectationClasse::where('classe_id', $classe->id)
            ->whereHas('inscription', fn($q) => $q->where('eleve_id', $request->eleve_id))
            ->update(['est_active' => false]);

        // Recalculer l'effectif
        $classe->update([
            'effectif' => $classe->eleves()->wherePivot('statut', 'ACTIVE')->count(),
        ]);
    });

    return response()->json([
        'message' => 'Élève retiré de la classe',
        'effectif' => $classe->fresh()->effectif,
    ]);
}
}



