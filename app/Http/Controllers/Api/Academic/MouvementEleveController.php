<?php

namespace App\Http\Controllers\Api\Academic;

use App\Enums\StatutMouvement;
use App\Enums\TypeMouvement;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMouvementEleveRequest;
use App\Http\Requests\UpdateMouvementEleveRequest;
use App\Models\Eleve;
use App\Models\MouvementEleve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MouvementEleveController extends Controller
{
    /**
     * Display a listing of mouvements.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MouvementEleve::with([
            'eleve:id,matricule,nom,prenom,sexe,statut_global',
            'anneeScolaire:id,code,libelle',
            'ecoleOrigine:id,name,code_ecole',
            'ecoleDestination:id,name,code_ecole',
            'classeOrigine:id,code,libelle',
            'creator:id,name',
            'validePar:id,name',
        ]);

        // Apply forCurrentUser scope for hierarchical filtering
        $query->forCurrentUser();

        // Search filter (by eleve name or matricule)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('eleve', function ($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                    ->orWhere('prenom', 'LIKE', "%{$search}%")
                    ->orWhere('matricule', 'LIKE', "%{$search}%");
            });
        }

        // Filter by année scolaire
        if ($request->filled('annee_scolaire_id')) {
            $query->byAnneeScolaire($request->annee_scolaire_id);
        }

        // Filter by type
        if ($request->filled('type_mouvement')) {
            $query->byType($request->type_mouvement);
        }

        // Filter by statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filter by école
        if ($request->filled('ecole_id')) {
            $ecoleId = $request->ecole_id;
            $query->where(function ($q) use ($ecoleId) {
                $q->where('ecole_origine_id', $ecoleId)
                    ->orWhere('ecole_destination_id', $ecoleId);
            });
        }

        // Filter by eleve
        if ($request->filled('eleve_id')) {
            $query->byEleve($request->eleve_id);
        }

        $mouvements = $query->orderByDesc('date_mouvement')
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json($mouvements);
    }

    /**
     * Store a newly created mouvement.
     */
    public function store(StoreMouvementEleveRequest $request): JsonResponse
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $eleve = Eleve::findOrFail($data['eleve_id']);

            // Get current inscription for ecole_origine_id if not provided
            if (empty($data['ecole_origine_id'])) {
                $activeInscription = $eleve->inscriptionsEleves()
                    ->where('statut', 'valide')
                    ->latest()
                    ->first();

                if ($activeInscription) {
                    $data['ecole_origine_id'] = $activeInscription->ecole_id;
                    $data['classe_origine_id'] = $activeInscription->classe_id;
                }
            }

            $data['created_by'] = Auth::id();

            // Create the mouvement
            $mouvement = MouvementEleve::create($data);

            // Auto-validate certain types that don't require hierarchical validation
            $type = TypeMouvement::tryFrom($data['type_mouvement']);
            if ($type && ! $type->requiresValidation()) {
                $mouvement->validate();
            }

            DB::commit();

            return response()->json([
                'message' => 'Mouvement enregistré avec succès',
                'mouvement' => $mouvement->load([
                    'eleve', 'anneeScolaire', 'ecoleOrigine', 'ecoleDestination',
                ]),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified mouvement.
     */
    public function show(MouvementEleve $mouvementEleve): JsonResponse
    {
        return response()->json(
            $mouvementEleve->load([
                'eleve',
                'anneeScolaire',
                'ecoleOrigine',
                'ecoleDestination',
                'classeOrigine.niveau',
                'creator',
                'validePar',
            ])
        );
    }

    /**
     * Update the specified mouvement.
     */
    public function update(UpdateMouvementEleveRequest $request, MouvementEleve $mouvementEleve): JsonResponse
    {
        if (! $mouvementEleve->canModify()) {
            return response()->json([
                'message' => 'Ce mouvement ne peut plus être modifié.',
            ], 422);
        }

        $mouvementEleve->update($request->validated());

        return response()->json([
            'message' => 'Mouvement mis à jour avec succès',
            'mouvement' => $mouvementEleve->load(['eleve', 'anneeScolaire', 'ecoleOrigine']),
        ]);
    }

    /**
     * Remove the specified mouvement.
     */
    public function destroy(MouvementEleve $mouvementEleve): JsonResponse
    {
        if (! $mouvementEleve->canModify()) {
            return response()->json([
                'message' => 'Ce mouvement ne peut plus être supprimé.',
            ], 422);
        }

        $mouvementEleve->delete();

        return response()->json(['message' => 'Mouvement supprimé avec succès']);
    }

    /**
     * Validate a mouvement.
     */
    public function validate(Request $request, MouvementEleve $mouvementEleve): JsonResponse
    {
        if (! $mouvementEleve->canValidate()) {
            return response()->json([
                'message' => 'Ce mouvement ne peut pas être validé.',
            ], 422);
        }

        $observations = $request->input('observations');

        if ($mouvementEleve->validate($observations)) {
            return response()->json([
                'message' => 'Mouvement validé avec succès',
                'mouvement' => $mouvementEleve->fresh(['eleve', 'anneeScolaire']),
            ]);
        }

        return response()->json([
            'message' => 'Erreur lors de la validation du mouvement.',
        ], 500);
    }

    /**
     * Reject a mouvement.
     */
    public function reject(Request $request, MouvementEleve $mouvementEleve): JsonResponse
    {
        $request->validate([
            'motif' => ['required', 'string', 'min:10'],
        ]);

        if (! $mouvementEleve->canReject()) {
            return response()->json([
                'message' => 'Ce mouvement ne peut pas être rejeté.',
            ], 422);
        }

        if ($mouvementEleve->reject($request->motif)) {
            return response()->json([
                'message' => 'Mouvement rejeté',
                'mouvement' => $mouvementEleve->fresh(['eleve', 'anneeScolaire']),
            ]);
        }

        return response()->json([
            'message' => 'Erreur lors du rejet du mouvement.',
        ], 500);
    }

    /**
     * Get mouvements by eleve.
     */
    public function byEleve(Request $request, int $eleveId): JsonResponse
    {
        $query = MouvementEleve::with([
            'anneeScolaire:id,code,libelle',
            'ecoleOrigine:id,name',
            'ecoleDestination:id,name',
        ])
            ->byEleve($eleveId)
            ->orderByDesc('date_mouvement');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        return response()->json($query->get());
    }

    /**
     * Get mouvements by school.
     */
    public function bySchool(Request $request, int $schoolId): JsonResponse
    {
        $query = MouvementEleve::with([
            'eleve:id,matricule,nom,prenom',
            'anneeScolaire:id,code,libelle',
        ])
            ->where(function ($q) use ($schoolId) {
                $q->where('ecole_origine_id', $schoolId)
                    ->orWhere('ecole_destination_id', $schoolId);
            })
            ->orderByDesc('date_mouvement');

        if ($request->filled('type_mouvement')) {
            $query->byType($request->type_mouvement);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * Get mouvements by type.
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        $query = MouvementEleve::with([
            'eleve:id,matricule,nom,prenom',
            'ecoleOrigine:id,name',
            'ecoleDestination:id,name',
        ])
            ->forCurrentUser()
            ->byType($type)
            ->orderByDesc('date_mouvement');

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * Get mouvement statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $query = MouvementEleve::forCurrentUser();

        if ($request->filled('ecole_id')) {
            $ecoleId = $request->ecole_id;
            $query->where(function ($q) use ($ecoleId) {
                $q->where('ecole_origine_id', $ecoleId)
                    ->orWhere('ecole_destination_id', $ecoleId);
            });
        }

        if ($request->filled('annee_scolaire_id')) {
            $query->byAnneeScolaire($request->annee_scolaire_id);
        }

        // Get counts by type
        $byType = [];
        foreach (TypeMouvement::cases() as $type) {
            $byType[$type->value] = (clone $query)->byType($type)->count();
        }

        // Get counts by status
        $byStatus = [];
        foreach (StatutMouvement::cases() as $statut) {
            $byStatus[$statut->value] = (clone $query)->where('statut', $statut->value)->count();
        }

        return response()->json([
            'total' => $query->count(),
            'by_type' => $byType,
            'by_status' => $byStatus,
            'recent' => (clone $query)->recent(30)->count(),
            'pending' => (clone $query)->enAttente()->count(),
        ]);
    }

    /**
     * Get available types with labels.
     */
    public function types(): JsonResponse
    {
        $types = [];
        foreach (TypeMouvement::cases() as $type) {
            $types[] = [
                'value' => $type->value,
                'label' => $type->label(),
                'color' => $type->color(),
                'requires_validation' => $type->requiresValidation(),
            ];
        }

        return response()->json($types);
    }
}
