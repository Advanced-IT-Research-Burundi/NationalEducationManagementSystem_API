<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    /**
     * Display a listing of zones.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Zone::with(['commune', 'province', 'ministere', 'pays']);

        if ($request->filled('commune_id')) {
            $query->where('commune_id', $request->commune_id);
        }

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        if ($request->filled('ministere_id')) {
            $query->where('ministere_id', $request->ministere_id);
        }

        if ($request->filled('pays_id')) {
            $query->where('pays_id', $request->pays_id);
        }

        $zones = $query->paginate(10);

        return sendResponse($zones, 'Zones retrieved successfully.');
    }

    /**
     * Store a newly created zone.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
            'commune_id' => 'required|exists:communes,id',
        ]);

        // Auto-populate hierarchy
        $commune = \App\Models\Commune::with('province')->findOrFail($validated['commune_id']);
        $validated['province_id'] = $commune->province_id;
        $validated['ministere_id'] = $commune->ministere_id;
        $validated['pays_id'] = $commune->pays_id;

        $zone = Zone::create($validated);

        return response()->json($zone->load(['commune', 'province', 'ministere', 'pays']), 201);
    }

    /**
     * Display the specified zone.
     */
    public function show(string $id): JsonResponse
    {
        $zone = Zone::with(['commune', 'province', 'ministere', 'pays', 'collines'])->findOrFail($id);

        return response()->json($zone);
    }

    /**
     * Update the specified zone.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $zone = Zone::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:10',
            'commune_id' => 'sometimes|required|exists:communes,id',
        ]);

        if (isset($validated['commune_id'])) {
            $commune = \App\Models\Commune::findOrFail($validated['commune_id']);
            $validated['province_id'] = $commune->province_id;
            $validated['ministere_id'] = $commune->ministere_id;
            $validated['pays_id'] = $commune->pays_id;
        }

        $zone->update($validated);

        return response()->json($zone->load(['commune', 'province', 'ministere', 'pays']));
    }

    /**
     * Remove the specified zone.
     */
    public function destroy(string $id): JsonResponse
    {
        $zone = Zone::findOrFail($id);
        $zone->delete();

        return response()->json(null, 204);
    }
}
