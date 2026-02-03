<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Enums\CampagneStatut;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampagneInscriptionRequest;
use App\Http\Requests\UpdateCampagneInscriptionRequest;
use App\Models\CampagneInscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampagneInscriptionController extends Controller
{
    /**
     * Display a listing of campagnes.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CampagneInscription::with(['anneeScolaire', 'ecole']);

        // Search filter
        if ($request->filled('search')) {
            $query->whereHas('ecole', function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%");
            });
        }

        // Filter by année scolaire
        if ($request->filled('annee_scolaire_id')) {
            $query->byAnneeScolaire($request->annee_scolaire_id);
        }

        // Filter by école
        if ($request->filled('ecole_id')) {
            $query->byEcole($request->ecole_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $campagnes = $query->orderByDesc('date_ouverture')
            ->paginate($request->get('per_page', 15));

        return response()->json($campagnes);
    }

    /**
     * Store a newly created campagne.
     */
    public function store(StoreCampagneInscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['statut'] = CampagneStatut::Planifiee;

        $campagne = CampagneInscription::create($data);
        $campagne->load(['anneeScolaire', 'ecole']);

        return response()->json([
            'message' => 'Campagne d\'inscription créée avec succès',
            'campagne' => $campagne,
        ], 201);
    }

    /**
     * Display the specified campagne.
     */
    public function show(CampagneInscription $campagne): JsonResponse
    {
        $campagne->load(['anneeScolaire', 'ecole', 'createdBy']);
        $campagne->loadCount('inscriptions');

        return response()->json($campagne);
    }

    /**
     * Update the specified campagne.
     */
    public function update(UpdateCampagneInscriptionRequest $request, CampagneInscription $campagne): JsonResponse
    {
        // Can only update if not cloturee
        if ($campagne->statut === CampagneStatut::Cloturee) {
            return response()->json([
                'message' => 'Impossible de modifier une campagne clôturée.',
            ], 422);
        }

        $campagne->update($request->validated());
        $campagne->load(['anneeScolaire', 'ecole']);

        return response()->json([
            'message' => 'Campagne mise à jour avec succès',
            'campagne' => $campagne,
        ]);
    }

    /**
     * Remove the specified campagne.
     */
    public function destroy(CampagneInscription $campagne): JsonResponse
    {
        // Can only delete if planifiee and no inscriptions
        if ($campagne->statut !== CampagneStatut::Planifiee) {
            return response()->json([
                'message' => 'Seules les campagnes planifiées peuvent être supprimées.',
            ], 422);
        }

        if ($campagne->inscriptions()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une campagne contenant des inscriptions.',
            ], 422);
        }

        $campagne->delete();

        return response()->json(['message' => 'Campagne supprimée avec succès']);
    }

    /**
     * Open a campagne.
     */
    public function ouvrir(CampagneInscription $campagne): JsonResponse
    {
        if (! $campagne->canOpen()) {
            return response()->json([
                'message' => 'Cette campagne ne peut pas être ouverte.',
            ], 422);
        }

        $campagne->ouvrir();
        $campagne->load(['anneeScolaire', 'ecole']);

        return response()->json([
            'message' => 'Campagne ouverte avec succès',
            'campagne' => $campagne,
        ]);
    }

    /**
     * Close a campagne.
     */
    public function cloturer(CampagneInscription $campagne): JsonResponse
    {
        if (! $campagne->canClose()) {
            return response()->json([
                'message' => 'Cette campagne ne peut pas être clôturée.',
            ], 422);
        }

        $campagne->cloturer();
        $campagne->load(['anneeScolaire', 'ecole']);

        return response()->json([
            'message' => 'Campagne clôturée avec succès',
            'campagne' => $campagne,
        ]);
    }

    /**
     * Get campagnes for a specific school (for dropdowns).
     */
    public function byEcole(int $ecoleId): JsonResponse
    {
        $campagnes = CampagneInscription::with('anneeScolaire')
            ->byEcole($ecoleId)
            ->ouverte()
            ->orderByDesc('date_ouverture')
            ->get();

        return response()->json($campagnes);
    }

    /**
     * Get statistics for campagnes.
     */
    public function statistics(Request $request): JsonResponse
    {
        $query = CampagneInscription::query();

        if ($request->filled('annee_scolaire_id')) {
            $query->byAnneeScolaire($request->annee_scolaire_id);
        }

        if ($request->filled('ecole_id')) {
            $query->byEcole($request->ecole_id);
        }

        $stats = [
            'total' => $query->count(),
            'by_statut' => [
                'planifiee' => (clone $query)->planifiee()->count(),
                'ouverte' => (clone $query)->ouverte()->count(),
                'cloturee' => (clone $query)->cloturee()->count(),
            ],
            'by_type' => [
                'nouvelle' => (clone $query)->byType('nouvelle')->count(),
                'reinscription' => (clone $query)->byType('reinscription')->count(),
            ],
        ];

        return response()->json($stats);
    }
}
