<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEleveRequest;
use App\Http\Requests\StoreInscriptionEleveRequest;
use App\Http\Requests\UpdateEleveRequest;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\InscriptionEleve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EleveController extends Controller
{
    /**
     * Display a listing of eleves.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Eleve::class);

        $query = Eleve::with(['school', 'creator']);

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // School filter
        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        // Sexe filter
        if ($request->filled('sexe')) {
            $query->bySexe($request->sexe);
        }

        // Status filter
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Classe filter (via inscriptions)
        if ($request->filled('classe_id')) {
            $query->whereHas('inscriptions', function ($q) use ($request) {
                $q->where('classe_id', $request->classe_id)
                    ->where('statut', 'ACTIVE');
            });
        }

        $eleves = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($eleves);
    }

    /**
     * Store a newly created eleve.
     */
    public function store(StoreEleveRequest $request): JsonResponse
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $data['created_by'] = Auth::id();

            // Extract inscription data if provided
            $classeId = $data['classe_id'] ?? null;
            $anneeScolaire = $data['annee_scolaire'] ?? null;
            unset($data['classe_id'], $data['annee_scolaire']);

            $eleve = Eleve::create($data);

            // Auto-enroll if classe_id provided
            if ($classeId && $anneeScolaire) {
                $classe = Classe::findOrFail($classeId);

                // Check capacity
                if (! $classe->hasCapacity()) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'La classe est pleine. Capacité maximale atteinte.',
                    ], 422);
                }

                InscriptionEleve::create([
                    'eleve_id' => $eleve->id,
                    'classe_id' => $classeId,
                    'annee_scolaire' => $anneeScolaire,
                    'date_inscription' => now(),
                    'statut' => 'ACTIVE',
                    'created_by' => Auth::id(),
                ]);

                // Update eleve status to ACTIF
                $eleve->update(['statut' => 'ACTIF']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Élève créé avec succès',
                'eleve' => $eleve->load(['school', 'classes']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified eleve.
     */
    public function show(Eleve $eleve): JsonResponse
    {
        $this->authorize('view', $eleve);

        return response()->json(
            $eleve->load(['school', 'creator', 'classes', 'inscriptions.classe.niveau'])
        );
    }

    /**
     * Update the specified eleve.
     */
    public function update(UpdateEleveRequest $request, Eleve $eleve): JsonResponse
    {
        $eleve->update($request->validated());

        return response()->json([
            'message' => 'Élève mis à jour avec succès',
            'eleve' => $eleve->load(['school']),
        ]);
    }

    /**
     * Remove the specified eleve.
     */
    public function destroy(Eleve $eleve): JsonResponse
    {
        $this->authorize('delete', $eleve);

        // Check if eleve has active inscriptions
        if ($eleve->inscriptions()->where('statut', 'ACTIVE')->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cet élève car il a des inscriptions actives.',
            ], 422);
        }

        $eleve->delete();

        return response()->json(['message' => 'Élève supprimé avec succès']);
    }

    /**
     * Get eleves for a specific school.
     */
    public function bySchool(Request $request, int $schoolId): JsonResponse
    {
        $query = Eleve::bySchool($schoolId);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        } else {
            $query->actif();
        }

        $eleves = $query->get();

        return response()->json($eleves);
    }

    /**
     * Get eleves for a specific classe.
     */
    public function byClasse(Request $request, int $classeId): JsonResponse
    {
        $classe = Classe::findOrFail($classeId);
        $this->authorize('view', $classe);

        $eleves = $classe->eleves()
            ->wherePivot('statut', 'ACTIVE')
            ->get();

        return response()->json($eleves);
    }

    /**
     * Enroll an eleve in a classe.
     */
    public function enroll(StoreInscriptionEleveRequest $request): JsonResponse
    {
        $data = $request->validated();

        $eleve = Eleve::findOrFail($data['eleve_id']);
        $classe = Classe::findOrFail($data['classe_id']);

        // Check if eleve can enroll
        if (! $eleve->canEnroll()) {
            return response()->json([
                'message' => 'Cet élève ne peut pas être inscrit (statut: '.$eleve->statut.').',
            ], 422);
        }

        // Check if already enrolled in this classe
        if ($eleve->isEnrolledInClass($data['classe_id'])) {
            return response()->json([
                'message' => 'Cet élève est déjà inscrit dans cette classe.',
            ], 422);
        }

        // Check capacity
        if (! $classe->hasCapacity()) {
            return response()->json([
                'message' => 'La classe est pleine. Capacité maximale atteinte.',
            ], 422);
        }

        // Check if schools match
        if ($eleve->school_id !== $classe->school_id) {
            return response()->json([
                'message' => 'L\'élève et la classe doivent appartenir à la même école.',
            ], 422);
        }

        $data['created_by'] = Auth::id();
        $inscription = InscriptionEleve::create($data);

        // Update eleve status if needed
        if ($eleve->statut === 'INSCRIT') {
            $eleve->update(['statut' => 'ACTIF']);
        }

        return response()->json([
            'message' => 'Élève inscrit avec succès',
            'inscription' => $inscription->load(['eleve', 'classe']),
        ], 201);
    }

    /**
     * Unenroll an eleve from a classe.
     */
    public function unenroll(InscriptionEleve $inscription): JsonResponse
    {
        $this->authorize('delete', $inscription);

        if ($inscription->statut !== 'ACTIVE') {
            return response()->json([
                'message' => 'Cette inscription n\'est pas active.',
            ], 422);
        }

        $inscription->update(['statut' => 'ANNULEE']);

        return response()->json(['message' => 'Inscription annulée avec succès']);
    }

    /**
     * Transfer an eleve to another classe.
     */
    public function transfer(Request $request, InscriptionEleve $inscription): JsonResponse
    {
        $this->authorize('update', $inscription);

        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'annee_scolaire' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $newClasse = Classe::findOrFail($request->classe_id);

        // Check capacity
        if (! $newClasse->hasCapacity()) {
            return response()->json([
                'message' => 'La nouvelle classe est pleine. Capacité maximale atteinte.',
            ], 422);
        }

        $newInscription = $inscription->transfer($request->classe_id, $request->annee_scolaire);

        if (! $newInscription) {
            return response()->json([
                'message' => 'Impossible d\'effectuer le transfert.',
            ], 422);
        }

        return response()->json([
            'message' => 'Élève transféré avec succès',
            'inscription' => $newInscription->load(['eleve', 'classe']),
        ]);
    }

    /**
     * Get eleve statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Eleve::class);

        $query = Eleve::query();

        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        $stats = [
            'total' => $query->count(),
            'by_status' => [
                'actif' => (clone $query)->actif()->count(),
                'suspendu' => (clone $query)->where('statut', 'SUSPENDU')->count(),
                'transfere' => (clone $query)->where('statut', 'TRANSFERE')->count(),
                'diplome' => (clone $query)->where('statut', 'DIPLOME')->count(),
            ],
            'by_sexe' => [
                'garcons' => (clone $query)->bySexe('M')->count(),
                'filles' => (clone $query)->bySexe('F')->count(),
            ],
        ];

        return response()->json($stats);
    }
}
