<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalleRequest;
use App\Http\Requests\UpdateSalleRequest;
use App\Models\Salle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Room Controller
 */
class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Salle::class);

        $query = Salle::query()->with(['batiment.ecole']);

        // Apply filters
        if ($request->has('batiment_id')) {
            $query->where('batiment_id', $request->batiment_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('etat')) {
            $query->where('etat', $request->etat);
        }

        if ($request->has('accessible_handicap')) {
            $query->where('accessible_handicap', $request->boolean('accessible_handicap'));
        }

        if ($request->has('search')) {
            $query->where('numero', 'like', '%'.$request->search.'%');
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $salles = $query->paginate($perPage);

        return response()->json($salles);
    }

    public function store(StoreSalleRequest $request): JsonResponse
    {
        $salle = Salle::create($request->validated());
        $salle->load('batiment.ecole');

        return response()->json([
            'message' => 'Salle créée avec succès',
            'data' => $salle,
        ], 201);
    }

    public function show(Salle $salle): JsonResponse
    {
        $this->authorize('view', $salle);

        $salle->load(['batiment.ecole', 'equipements']);

        return response()->json(['data' => $salle]);
    }

    public function update(UpdateSalleRequest $request, Salle $salle): JsonResponse
    {
        $salle->update($request->validated());
        $salle->load('batiment.ecole');

        return response()->json([
            'message' => 'Salle mise à jour avec succès',
            'data' => $salle,
        ]);
    }

    public function destroy(Salle $salle): JsonResponse
    {
        $this->authorize('delete', $salle);

        $salle->delete();

        return response()->json([
            'message' => 'Salle supprimée avec succès',
        ]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Salle::class);

        $stats = [
            'total' => Salle::count(),
            'by_type' => Salle::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get(),
            'by_etat' => Salle::select('etat', DB::raw('count(*) as count'))
                ->groupBy('etat')
                ->get(),
            'accessible_handicap' => Salle::where('accessible_handicap', true)->count(),
            'total_capacite' => Salle::sum('capacite'),
            'avg_capacite' => Salle::avg('capacite'),
        ];

        return response()->json(['data' => $stats]);
    }
}
