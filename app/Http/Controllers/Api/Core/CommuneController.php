<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommuneResource;
use App\Models\Commune;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommuneController extends Controller
{
    /**
     * Display a listing of communes.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Commune::with(['province', 'ministere', 'pays']);

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        if ($request->filled('ministere_id')) {
            $query->where('ministere_id', $request->ministere_id);
        }

        if ($request->filled('pays_id')) {
            $query->where('pays_id', $request->pays_id);
        }

        $communes = $query->paginate(10);

        return CommuneResource::collection($communes)->response();
    }

    /**
     * Store a newly created commune.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
            'province_id' => 'required|exists:provinces,id',
        ]);

        // Auto-populate hierarchy
        $province = Province::findOrFail($validated['province_id']);
        $validated['ministere_id'] = $province->ministere_id;
        $validated['pays_id'] = $province->pays_id;

        $commune = Commune::create($validated);

        return (new CommuneResource($commune->load(['province', 'ministere', 'pays'])))
            ->additional(['message' => 'Commune created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified commune.
     */
    public function show(string $id): JsonResponse
    {
        $commune = Commune::with(['province', 'ministere', 'pays', 'zones'])->findOrFail($id);

        return (new CommuneResource($commune))->response();
    }

    /**
     * Update the specified commune.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $commune = Commune::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:10',
            'province_id' => 'sometimes|required|exists:provinces,id',
        ]);

        if (isset($validated['province_id'])) {
            $province = Province::findOrFail($validated['province_id']);
            $validated['ministere_id'] = $province->ministere_id;
            $validated['pays_id'] = $province->pays_id;
        }

        $commune->update($validated);

        return (new CommuneResource($commune->load(['province', 'ministere', 'pays'])))
            ->additional(['message' => 'Commune updated successfully.'])
            ->response();
    }

    /**
     * Remove the specified commune.
     */
    public function destroy(string $id): JsonResponse
    {
        $commune = Commune::findOrFail($id);
        $commune->delete();

        return response()->json(null, 204);
    }
}
