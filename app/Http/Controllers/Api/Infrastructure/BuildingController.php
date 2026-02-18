<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBatimentRequest;
use App\Http\Requests\UpdateBatimentRequest;
use App\Models\Batiment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Building Controller
 */
class BuildingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Batiment::class);

        $query = Batiment::query()->with(['ecole']);

        // Apply filters
        if ($request->has('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('etat')) {
            $query->where('etat', $request->etat);
        }

        if ($request->has('search')) {
            $query->where('nom', 'like', '%'.$request->search.'%');
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $batiments = $query->paginate($perPage);

        return response()->json($batiments);
    }

    public function store(StoreBatimentRequest $request): JsonResponse
    {
        $batiment = Batiment::create($request->validated());
        $batiment->load('ecole');

        return response()->json([
            'message' => 'Bâtiment créé avec succès',
            'data' => $batiment,
        ], 201);
    }

    public function show(Batiment $batiment): JsonResponse
    {
        $this->authorize('view', $batiment);

        $batiment->load(['ecole', 'salles', 'maintenances']);

        return response()->json(['data' => $batiment]);
    }

    public function update(UpdateBatimentRequest $request, Batiment $batiment): JsonResponse
    {
        $batiment->update($request->validated());
        $batiment->load('ecole');

        return response()->json([
            'message' => 'Bâtiment mis à jour avec succès',
            'data' => $batiment,
        ]);
    }

    public function destroy(Batiment $batiment): JsonResponse
    {
        $this->authorize('delete', $batiment);

        $batiment->delete();

        return response()->json([
            'message' => 'Bâtiment supprimé avec succès',
        ]);
    }

    public function bySchool(string $school): JsonResponse
    {
        $this->authorize('viewAny', Batiment::class);

        $batiments = Batiment::query()
            ->where('school_id', $school)
            ->with('salles')
            ->paginate(20);

        return response()->json($batiments);
    }

    public function byCondition(string $condition): JsonResponse
    {
        $this->authorize('viewAny', Batiment::class);

        $batiments = Batiment::query()
            ->where('etat', strtoupper($condition))
            ->with('ecole')
            ->paginate(20);

        return response()->json($batiments);
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Batiment::class);

        $stats = [
            'total' => Batiment::count(),
            'by_type' => Batiment::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get(),
            'by_etat' => Batiment::select('etat', DB::raw('count(*) as count'))
                ->groupBy('etat')
                ->get(),
            'total_superficie' => Batiment::sum('superficie'),
            'avg_superficie' => Batiment::avg('superficie'),
        ];

        return response()->json(['data' => $stats]);
    }
}
