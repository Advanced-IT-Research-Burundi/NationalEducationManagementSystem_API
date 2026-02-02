<?php

namespace App\Http\Controllers\Api\Ecole;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEcoleRequest;
use App\Http\Requests\UpdateEcoleRequest;
use App\Models\Ecole;

class EcoleController extends Controller
{
     /**
     * Display a listing of schools.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ecole::class);

        $query = Ecole::with(['colline', 'zone', 'commune', 'province', 'creator']);

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Type filter
        if ($request->filled('type_ecole')) {
            $query->byType($request->type_ecole);
        }

        // Niveau filter
        if ($request->filled('niveau')) {
            $query->byNiveau($request->niveau);
        }

        // Province filter
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        // Commune filter
        if ($request->filled('commune_id')) {
            $query->where('commune_id', $request->commune_id);
        }

        // Zone filter
        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $schools = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($schools);
    }

    /**
     * Store a newly created school.
     */
    public function store(StoreEcoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Auto-localization logic
        $colline = Colline::with(['zone.commune.province.ministere.pays'])->findOrFail($data['colline_id']);

        $data['zone_id'] = $colline->zone_id;
        $data['commune_id'] = $colline->zone->commune_id ?? null;
        $data['province_id'] = $colline->zone->commune->province_id ?? null;
        $data['ministere_id'] = $colline->zone->commune->province->ministere_id ?? null;
        $data['pays_id'] = $colline->zone->commune->province->pays_id ?? 1;

        $data['created_by'] = Auth::id();
        $data['statut'] = Ecole::STATUS_BROUILLON;

        $school = Ecole::create($data);

        return response()->json([
            'message' => 'School created successfully',
            'school' => $school,
        ], 201);
    }

    /**
     * Display the specified school.
     */
    public function show(Ecole $school): JsonResponse
    {
        $this->authorize('view', $school);

        return response()->json($school->load(['colline', 'zone', 'commune', 'province', 'creator', 'validator']));
    }

    /**
     * Update the specified school.
     */
    public function update(UpdateEcoleRequest $request, Ecole $school): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['colline_id']) && $data['colline_id'] != $school->colline_id) {
            // Re-localize if colline changed
            $colline = Colline::with(['zone.commune.province'])->findOrFail($data['colline_id']);
            $data['zone_id'] = $colline->zone_id;
            $data['commune_id'] = $colline->zone->commune_id;
            $data['province_id'] = $colline->zone->commune->province_id;
        }

        $school->update($data);

        return response()->json([
            'message' => 'School updated successfully',
            'school' => $school,
        ]);
    }

    /**
     * Remove the specified school.
     */
    public function destroy(School $school): JsonResponse
    {
        $this->authorize('delete', $school);

        $school->delete();

        return response()->json(['message' => 'School deleted successfully']);
    }

    /**
     * Get school statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', School::class);

        $stats = [
            'total' => School::count(),
            'by_status' => [
                'brouillon' => School::draft()->count(),
                'en_attente' => School::pending()->count(),
                'active' => School::active()->count(),
                'inactive' => School::inactive()->count(),
            ],
            'by_type' => School::selectRaw('type_ecole, COUNT(*) as count')
                ->groupBy('type_ecole')
                ->pluck('count', 'type_ecole'),
            'by_niveau' => School::selectRaw('niveau, COUNT(*) as count')
                ->groupBy('niveau')
                ->pluck('count', 'niveau'),
        ];

        return response()->json($stats);
    }

    /**
     * Get schools grouped by status.
     */
    public function byStatus(Request $request): JsonResponse
    {
        $this->authorize('viewAny', School::class);

        $status = $request->get('status', School::STATUS_ACTIVE);

        $schools = School::where('statut', $status)
            ->with(['colline', 'zone', 'commune', 'province'])
            ->paginate($request->get('per_page', 15));

        return response()->json($schools);
    }
}
