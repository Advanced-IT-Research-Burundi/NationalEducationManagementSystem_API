<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClasseRequest;
use App\Http\Requests\UpdateClasseRequest;
use App\Models\AffectationClasse;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClasseController extends Controller
{
    /**
     * Display a listing of classes.
     */
    use \App\Traits\ResolvesAnneeScolaire;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Classe::class);

        // Sync Context with the explicit param so AcademicYearScope aligns.
        $this->resolveAnneeScolaireId($request);

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

        // Status filter
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $classes = $query->latest()->paginate($request->get('per_page', 15));

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

    public function bySchool(Request $request, int $school): JsonResponse
    {
        Log::info('Fetching classes for school', [
            'school_id' => $school,
            'annee_scolaire_id' => $request->input('annee_scolaire_id'),
            'user_id' => auth()->id(),
        ]);

        $this->resolveAnneeScolaireId($request);

        $query = Classe::bySchool($school)->with(['niveau', 'section']);

        $anneeId = $request->input('annee_scolaire_id') ?? $request->input('annee_scolaire');
        if ($anneeId) {
            $query->byAnneeScolaire((int) $anneeId);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        } elseif (! $request->boolean('pour_consultation')) {
            $query->active();
        }

        if (auth()->check() && auth()->user()->hasRole('Enseignant')) {
            $enseignantId = auth()->user()->enseignant->id ?? null;
            if ($enseignantId) {
                $query->whereHas('enseignants', function ($q) use ($enseignantId) {
                    $q->where('enseignants.id', $enseignantId);
                });
            }
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
    public function eleves(Request $request, Classe $classe): JsonResponse
    {
        $this->authorize('view', $classe);

        $query = $classe->eleves();
        if (! $request->boolean('pour_consultation')) {
            $query->wherePivot('statut', 'ACTIVE');
        }

        return response()->json($query->get());
    }

    /**
     * Add an eleve to a classe. Creates an Inscription + AffectationClasse automatically.
     */
    public function addEleve(Request $request, Classe $classe): JsonResponse
    {
        $this->authorize('update', $classe);

        $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
        ]);

        $eleve = Eleve::findOrFail($request->eleve_id);

        if (! $eleve->canEnroll()) {
            return response()->json([
                'message' => 'Cet élève ne peut pas être inscrit (statut: '.($eleve->statut_global ?? 'inconnu').').',
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

        $anneeScolaire = AnneeScolaire::current();

        if (! $anneeScolaire) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        DB::transaction(function () use ($eleve, $classe, $anneeScolaire) {
            // Find or create inscription for this student in this year+school
            $inscription = Inscription::withoutGlobalScopes()
                ->where('eleve_id', $eleve->id)
                ->where('annee_scolaire_id', $anneeScolaire->id)
                ->where('school_id', $classe->school_id)
                ->where('statut_academique', 'en_cours')
                ->first();

            if (! $inscription) {
                $prefix = 'INS';
                $year = $anneeScolaire->date_debut?->format('Y') ?? date('Y');
                $sequence = Inscription::withoutGlobalScopes()->count() + 1;

                $inscription = Inscription::withoutGlobalScopes()->create([
                    'numero_inscription' => sprintf('%s%s%06d', $prefix, $year, $sequence),
                    'eleve_id' => $eleve->id,
                    'annee_scolaire_id' => $anneeScolaire->id,
                    'school_id' => $classe->school_id,
                    'niveau_demande_id' => $classe->niveau_id,
                    'type_inscription' => 'nouvelle',
                    'statut' => 'valide',
                    'statut_academique' => 'en_cours',
                    'date_inscription' => now()->toDateString(),
                    'date_soumission' => now(),
                    'date_validation' => now(),
                    'est_redoublant' => $eleve->est_redoublant ?? false,
                    'created_by' => Auth::id(),
                    'valide_par' => Auth::id(),
                ]);
            }

            // Deactivate any existing affectation for this inscription
            AffectationClasse::where('inscription_id', $inscription->id)
                ->where('est_active', true)
                ->update(['est_active' => false, 'date_fin' => now()]);

            AffectationClasse::create([
                'inscription_id' => $inscription->id,
                'classe_id' => $classe->id,
                'date_affectation' => now(),
                'est_active' => true,
                'affecte_par' => Auth::id(),
            ]);

            // Keep eleve_class pivot in sync for backward compatibility
            $classe->eleves()->syncWithoutDetaching([
                $eleve->id => [
                    'annee_scolaire' => $anneeScolaire->code,
                    'date_inscription' => now(),
                    'statut' => 'ACTIVE',
                ],
            ]);

            $newEffectif = DB::table('eleve_class')
                ->where('classe_id', $classe->id)
                ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                ->count();
            $classe->update(['effectif' => $newEffectif]);

            if (in_array($eleve->statut_global, [null, '', 'inactif'])) {
                $eleve->update(['statut_global' => 'actif']);
            }

            // Sync convenience columns
            $eleve->update([
                'school_id' => $classe->school_id,
                'niveau_id' => $classe->niveau_id,
            ]);
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
                ->whereHas('inscription', fn ($q) => $q->where('eleve_id', $request->eleve_id))
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
