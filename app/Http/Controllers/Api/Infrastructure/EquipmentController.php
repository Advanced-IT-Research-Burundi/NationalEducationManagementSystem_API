<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipementRequest;
use App\Http\Requests\UpdateEquipementRequest;
use App\Models\Equipement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Equipment Controller
 */
class EquipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Equipement::class);

        $query = Equipement::query()->with(['salle', 'ecole']);

        // Apply filters
        if ($request->has('ecole_id')) {
            $query->where('ecole_id', $request->ecole_id);
        }

        if ($request->has('salle_id')) {
            $query->where('salle_id', $request->salle_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('etat')) {
            $query->where('etat', $request->etat);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'like', '%'.$request->search.'%')
                    ->orWhere('marque', 'like', '%'.$request->search.'%')
                    ->orWhere('numero_serie', 'like', '%'.$request->search.'%');
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $equipements = $query->paginate($perPage);

        return response()->json($equipements);
    }

    public function store(StoreEquipementRequest $request): JsonResponse
    {
        $equipement = Equipement::create($request->validated());
        $equipement->load(['salle', 'ecole']);

        return response()->json([
            'message' => 'Équipement créé avec succès',
            'data' => $equipement,
        ], 201);
    }

    public function show(Equipement $equipement): JsonResponse
    {
        $this->authorize('view', $equipement);

        $equipement->load(['salle', 'ecole', 'maintenances']);

        return response()->json(['data' => $equipement]);
    }

    public function update(UpdateEquipementRequest $request, Equipement $equipement): JsonResponse
    {
        $equipement->update($request->validated());
        $equipement->load(['salle', 'ecole']);

        return response()->json([
            'message' => 'Équipement mis à jour avec succès',
            'data' => $equipement,
        ]);
    }

    public function destroy(Equipement $equipement): JsonResponse
    {
        $this->authorize('delete', $equipement);

        $equipement->delete();

        return response()->json([
            'message' => 'Équipement supprimé avec succès',
        ]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Equipement::class);

        $query = Equipement::query()->with(['salle', 'ecole']);

        if ($request->has('ecole_id')) {
            $query->where('ecole_id', $request->ecole_id);
        }

        $perPage = $request->get('per_page', 50);
        $inventory = $query->paginate($perPage);

        return response()->json($inventory);
    }

    public function bySchool(string $school): JsonResponse
    {
        $this->authorize('viewAny', Equipement::class);

        $equipements = Equipement::query()
            ->where('ecole_id', $school)
            ->with('salle')
            ->paginate(20);

        return response()->json($equipements);
    }

    public function byCategory(string $category): JsonResponse
    {
        $this->authorize('viewAny', Equipement::class);

        $equipements = Equipement::query()
            ->where('type', strtoupper($category))
            ->with(['salle', 'ecole'])
            ->paginate(20);

        return response()->json($equipements);
    }

    public function transfer(Request $request, Equipement $equipement): JsonResponse
    {
        $this->authorize('update', $equipement);

        $request->validate([
            'salle_id' => ['required', 'exists:salles,id'],
        ]);

        $equipement->update([
            'salle_id' => $request->salle_id,
        ]);

        $equipement->load(['salle', 'ecole']);

        return response()->json([
            'message' => 'Équipement transféré avec succès',
            'data' => $equipement,
        ]);
    }
}
