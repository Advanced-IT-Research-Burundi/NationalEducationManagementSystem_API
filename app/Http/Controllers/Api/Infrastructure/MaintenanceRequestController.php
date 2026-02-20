<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Maintenance Request Controller
 */
class MaintenanceRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Maintenance::class);

        $query = Maintenance::query()->with(['maintenable', 'demandeur', 'technicien']);

        // Apply filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('maintenable_type')) {
            $query->where('maintenable_type', $request->maintenable_type);
        }

        if ($request->has('technicien_id')) {
            $query->where('technicien_id', $request->technicien_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $maintenances = $query->orderBy('date_demande', 'desc')->paginate($perPage);

        return response()->json($maintenances);
    }

    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['demandeur_id'] = auth()->id();

        $maintenance = Maintenance::create($data);
        $maintenance->load(['maintenable', 'demandeur', 'technicien']);

        return response()->json([
            'message' => 'Demande de maintenance créée avec succès',
            'data' => $maintenance,
        ], 201);
    }

    public function show(Maintenance $maintenance): JsonResponse
    {
        $this->authorize('view', $maintenance);

        $maintenance->load(['maintenable', 'demandeur', 'technicien']);

        return response()->json(['data' => $maintenance]);
    }

    public function update(UpdateMaintenanceRequest $request, Maintenance $maintenance): JsonResponse
    {
        $maintenance->update($request->validated());
        $maintenance->load(['maintenable', 'demandeur', 'technicien']);

        return response()->json([
            'message' => 'Maintenance mise à jour avec succès',
            'data' => $maintenance,
        ]);
    }

    public function destroy(Maintenance $maintenance): JsonResponse
    {
        $this->authorize('delete', $maintenance);

        $maintenance->delete();

        return response()->json([
            'message' => 'Maintenance supprimée avec succès',
        ]);
    }

    public function assign(Request $request, Maintenance $maintenance): JsonResponse
    {
        $this->authorize('update', $maintenance);

        $request->validate([
            'technicien_id' => ['required', 'exists:users,id'],
        ]);

        $maintenance->update([
            'technicien_id' => $request->technicien_id,
            'statut' => 'EN_COURS',
        ]);

        $maintenance->load(['maintenable', 'demandeur', 'technicien']);

        return response()->json([
            'message' => 'Technicien assigné avec succès',
            'data' => $maintenance,
        ]);
    }

    public function start(Maintenance $maintenance): JsonResponse
    {
        $this->authorize('update', $maintenance);

        $maintenance->update([
            'statut' => 'EN_COURS',
            'date_intervention' => now(),
        ]);

        $maintenance->load(['maintenable', 'demandeur', 'technicien']);

        return response()->json([
            'message' => 'Maintenance démarrée',
            'data' => $maintenance,
        ]);
    }

    public function complete(Request $request, Maintenance $maintenance): JsonResponse
    {
        $this->authorize('update', $maintenance);

        $request->validate([
            'rapport' => ['required', 'string'],
            'cout' => ['nullable', 'numeric', 'min:0'],
        ]);

        $maintenance->update([
            'statut' => 'TERMINE',
            'date_fin' => now(),
            'rapport' => $request->rapport,
            'cout' => $request->cout,
        ]);

        $maintenance->load(['maintenable', 'demandeur', 'technicien']);

        return response()->json([
            'message' => 'Maintenance terminée',
            'data' => $maintenance,
        ]);
    }

    public function pending(): JsonResponse
    {
        $this->authorize('viewAny', Maintenance::class);

        $maintenances = Maintenance::query()
            ->where('statut', 'DEMANDE')
            ->with(['maintenable', 'demandeur'])
            ->orderBy('date_demande', 'asc')
            ->paginate(20);

        return response()->json($maintenances);
    }
}
