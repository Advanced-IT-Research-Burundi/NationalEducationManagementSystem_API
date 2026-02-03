<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCollineRequest;
use App\Http\Requests\UpdateCollineRequest;
use App\Models\Colline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollineController extends Controller
{
    /**
     * Display a listing of collines (hills).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Colline::with(['zone', 'commune', 'province', 'ministere', 'pays']);

        if ($request->filled('zone_id')) {
            $query->byZone($request->zone_id);
        }

        if ($request->filled('commune_id')) {
            $query->byCommune($request->commune_id);
        }

        if ($request->filled('province_id')) {
            $query->byProvince($request->province_id);
        }

        if ($request->filled('ministere_id')) {
            $query->byMinistere($request->ministere_id);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $collines = $query->paginate(10);

        return response()->json($collines);
    }

    /**
     * Store a newly created colline.
     */
    public function store(StoreCollineRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Auto-populate hierarchy
        $zone = \App\Models\Zone::with('commune.province')->findOrFail($validated['zone_id']);
        $validated['commune_id'] = $zone->commune_id;
        $validated['province_id'] = $zone->province_id;
        $validated['ministere_id'] = $zone->ministere_id;
        $validated['pays_id'] = $zone->pays_id;

        $colline = Colline::create($validated);

        return response()->json($colline->load(['zone', 'commune', 'province', 'ministere', 'pays']), 201);
    }

    /**
     * Display the specified colline.
     */
    public function show(string $id): JsonResponse
    {
        $colline = Colline::with(['zone', 'commune', 'province', 'ministere', 'pays', 'schools'])->findOrFail($id);

        return response()->json($colline);
    }

    /**
     * Update the specified colline.
     */
    public function update(UpdateCollineRequest $request, string $id): JsonResponse
    {
        $colline = Colline::findOrFail($id);
        $validated = $request->validated();

        if (isset($validated['zone_id'])) {
            $zone = \App\Models\Zone::findOrFail($validated['zone_id']);
            $validated['commune_id'] = $zone->commune_id;
            $validated['province_id'] = $zone->province_id;
            $validated['ministere_id'] = $zone->ministere_id;
            $validated['pays_id'] = $zone->pays_id;
        }

        $colline->update($validated);

        return response()->json($colline->load(['zone', 'commune', 'province', 'ministere', 'pays']));
    }

    /**
     * Remove the specified colline.
     */
    public function destroy(string $id): JsonResponse
    {
        $colline = Colline::findOrFail($id);
        $colline->delete();

        return response()->json(null, 204);
    }
}
