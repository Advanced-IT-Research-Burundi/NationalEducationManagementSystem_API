<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Enums\CampagneStatut;
use App\Http\Controllers\Controller;
use App\Models\CampagneInscription;
use App\Models\Eleve;
use App\Models\InscriptionEleve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InscriptionController extends Controller
{
    /**
     * GET /api/inscriptions-eleves
     * Liste des inscriptions filtrées par l'école de l'utilisateur
     */
    public function index(Request $request): JsonResponse
    {
        $query = InscriptionEleve::with([
            'eleve',
            'classe',
            'ecole',
            'anneeScolaire',
            'niveauDemande',
            'campagne',
        ])->forCurrentUser()->latest();

        // Filtres optionnels
        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        if ($request->filled('ecole_id')) {
            $query->where('ecole_id', $request->ecole_id);
        }

        if ($request->filled('campagne_id')) {
            $query->where('campagne_id', $request->campagne_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('type')) {
            $query->where('type_inscription', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_inscription', 'LIKE', "%{$search}%")
                    ->orWhereHas('eleve', function ($q) use ($search) {
                        $q->where('nom', 'LIKE', "%{$search}%")
                            ->orWhere('prenom', 'LIKE', "%{$search}%")
                            ->orWhere('matricule', 'LIKE', "%{$search}%");
                    });
            });
        }

        $perPage = $request->input('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    /**
     * POST /api/inscriptions-eleves
     * Créer une inscription - l'élève est lié à l'école de l'utilisateur connecté
     * et à la campagne active de cette école
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Déterminer l'école
        $ecoleId = $this->getUserSchoolId($user);

        if (! $ecoleId) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être associé à une école pour créer une inscription.',
            ], 403);
        }

        // Trouver la campagne active pour cette école
        $campagneActive = CampagneInscription::where('ecole_id', $ecoleId)
            ->where('statut', CampagneStatut::Ouverte)
            ->first();

        if (! $campagneActive) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune campagne d\'inscription n\'est ouverte pour votre école.',
            ], 422);
        }

        // Validation
        $validated = $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'classe_id' => 'nullable|exists:classes,id',
            'niveau_demande_id' => 'required|exists:niveaux,id',
            'type_inscription' => 'required|in:nouvelle,reinscription,transfert_entrant',
            'date_inscription' => 'nullable|date',
            'est_redoublant' => 'boolean',
            'observations' => 'nullable|string|max:1000',
        ]);

        // Vérifier que l'élève appartient à l'école
        $eleve = Eleve::find($validated['eleve_id']);
        if ($eleve->school_id && $eleve->school_id !== $ecoleId) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève n\'appartient pas à votre école.',
            ], 422);
        }

        // Vérifier si l'élève n'est pas déjà inscrit dans cette campagne
        $existingInscription = InscriptionEleve::where('eleve_id', $validated['eleve_id'])
            ->where('campagne_id', $campagneActive->id)
            ->whereNotIn('statut', [InscriptionEleve::STATUS_ANNULE, InscriptionEleve::STATUS_REJETE])
            ->exists();

        if ($existingInscription) {
            return response()->json([
                'success' => false,
                'message' => 'Cet élève a déjà une inscription en cours dans cette campagne.',
            ], 422);
        }

        // Créer l'inscription
        $inscription = DB::transaction(function () use ($validated, $ecoleId, $campagneActive, $eleve) {
            // Lier l'élève à l'école si ce n'est pas déjà fait
            if (! $eleve->school_id) {
                $eleve->update(['school_id' => $ecoleId]);
            }

            return InscriptionEleve::create([
                'eleve_id' => $validated['eleve_id'],
                'ecole_id' => $ecoleId,
                'campagne_id' => $campagneActive->id,
                'annee_scolaire_id' => $campagneActive->annee_scolaire_id,
                'classe_id' => $validated['classe_id'] ?? null,
                'niveau_demande_id' => $validated['niveau_demande_id'],
                'type_inscription' => $validated['type_inscription'],
                'date_inscription' => $validated['date_inscription'] ?? now(),
                'est_redoublant' => $validated['est_redoublant'] ?? false,
                'observations' => $validated['observations'] ?? null,
                'statut' => InscriptionEleve::STATUS_BROUILLON,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Inscription créée avec succès',
            'data' => $inscription->load([
                'eleve',
                'classe',
                'ecole',
                'anneeScolaire',
                'niveauDemande',
                'campagne',
            ]),
        ], 201);
    }

    /**
     * GET /api/inscriptions-eleves/{inscription}
     */
    public function show(InscriptionEleve $inscriptionsElefe): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $inscriptionsElefe->load([
                'eleve',
                'classe',
                'ecole',
                'anneeScolaire',
                'niveauDemande',
                'campagne',
                'creator',
                'validePar',
                'affectations.classe',
            ]),
        ]);
    }

    /**
     * PUT /api/inscriptions-eleves/{inscription}
     */
    public function update(Request $request, InscriptionEleve $inscriptionsElefe): JsonResponse
    {
        // Seules les inscriptions en brouillon peuvent être modifiées
        if ($inscriptionsElefe->statut !== InscriptionEleve::STATUS_BROUILLON) {
            return response()->json([
                'success' => false,
                'message' => 'Seules les inscriptions en brouillon peuvent être modifiées.',
            ], 422);
        }

        $validated = $request->validate([
            'classe_id' => 'nullable|exists:classes,id',
            'niveau_demande_id' => 'sometimes|exists:niveaux,id',
            'type_inscription' => 'sometimes|in:nouvelle,reinscription,transfert_entrant',
            'est_redoublant' => 'boolean',
            'observations' => 'nullable|string|max:1000',
        ]);

        $inscriptionsElefe->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inscription mise à jour',
            'data' => $inscriptionsElefe->fresh([
                'eleve',
                'classe',
                'ecole',
                'anneeScolaire',
                'niveauDemande',
            ]),
        ]);
    }

    /**
     * DELETE /api/inscriptions-eleves/{inscription}
     */
    public function destroy(InscriptionEleve $inscriptionsElefe): JsonResponse
    {
        if (! $inscriptionsElefe->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette inscription ne peut pas être supprimée.',
            ], 422);
        }

        $inscriptionsElefe->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Inscription annulée',
        ]);
    }

    /**
     * POST /api/inscriptions-eleves/{inscription}/submit
     * Soumettre une inscription pour validation
     */
    public function submit(InscriptionEleve $inscription): JsonResponse
    {
        if (! $inscription->submit()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette inscription ne peut pas être soumise.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inscription soumise pour validation',
            'data' => $inscription->fresh(),
        ]);
    }

    /**
     * POST /api/inscriptions-eleves/{inscription}/validate
     * Valider une inscription
     */
    public function validateInscription(InscriptionEleve $inscription): JsonResponse
    {
        if (! $inscription->validate()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette inscription ne peut pas être validée.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inscription validée avec succès',
            'data' => $inscription->fresh(['eleve', 'classe']),
        ]);
    }

    /**
     * POST /api/inscriptions-eleves/{inscription}/reject
     * Rejeter une inscription
     */
    public function reject(Request $request, InscriptionEleve $inscription): JsonResponse
    {
        $validated = $request->validate([
            'motif_rejet' => 'required|string|max:500',
        ]);

        if (! $inscription->reject($validated['motif_rejet'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette inscription ne peut pas être rejetée.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inscription rejetée',
            'data' => $inscription->fresh(),
        ]);
    }

    /**
     * POST /api/inscriptions-eleves/{inscription}/cancel
     * Annuler une inscription
     */
    public function cancel(InscriptionEleve $inscription): JsonResponse
    {
        if (! $inscription->cancel()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette inscription ne peut pas être annulée.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inscription annulée',
        ]);
    }

    /**
     * GET /api/inscriptions-eleves/statistics
     * Statistiques des inscriptions
     */
    public function statistics(Request $request): JsonResponse
    {
        $query = InscriptionEleve::query()->forCurrentUser();

        if ($request->filled('ecole_id')) {
            $query->where('ecole_id', $request->ecole_id);
        }

        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        if ($request->filled('campagne_id')) {
            $query->where('campagne_id', $request->campagne_id);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'by_status' => [
                'brouillon' => (clone $query)->where('statut', 'brouillon')->count(),
                'soumis' => (clone $query)->where('statut', 'soumis')->count(),
                'valide' => (clone $query)->where('statut', 'valide')->count(),
                'rejete' => (clone $query)->where('statut', 'rejete')->count(),
                'annule' => (clone $query)->where('statut', 'annule')->count(),
            ],
            'by_type' => [
                'nouvelle' => (clone $query)->where('type_inscription', 'nouvelle')->count(),
                'reinscription' => (clone $query)->where('type_inscription', 'reinscription')->count(),
                'transfert_entrant' => (clone $query)->where('type_inscription', 'transfert_entrant')->count(),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * GET /api/inscriptions-eleves/campagne-active
     * Obtenir la campagne active pour l'école de l'utilisateur
     */
    public function campagneActive(): JsonResponse
    {
        $user = Auth::user();
        $ecoleId = $this->getUserSchoolId($user);

        if (! $ecoleId) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être associé à une école.',
                'data' => null,
            ]);
        }

        $campagne = CampagneInscription::with(['ecole', 'anneeScolaire'])
            ->where('ecole_id', $ecoleId)
            ->where('statut', CampagneStatut::Ouverte)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $campagne,
        ]);
    }

    /**
     * Helper: Obtenir l'ID de l'école de l'utilisateur
     */
    private function getUserSchoolId($user): ?int
    {
        // Si l'utilisateur est de niveau ECOLE
        if ($user->admin_level === 'ECOLE' && $user->admin_entity_id) {
            return $user->admin_entity_id;
        }

        // Si l'utilisateur a un school_id direct
        if ($user->school_id) {
            return $user->school_id;
        }

        return null;
    }
}
