<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    /**
     * Display a listing of provinces.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Province::with(['ministere', 'pays']);

        if ($request->filled('ministere_id')) {
            $query->where('ministere_id', $request->ministere_id);
        }

        if ($request->filled('pays_id')) {
            $query->where('pays_id', $request->pays_id);
        }

        $provinces = $query->get();

        return response()->json($provinces);
    }

    /**
     * Store a newly created province.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
            'ministere_id' => 'nullable|exists:ministeres,id',
            'pays_id' => 'required|exists:pays,id',
        ]);

        $province = Province::create($validated);

        return response()->json($province->load(['ministere', 'pays']), 201);
    }

    /**
     * Display the specified province.
     */
    public function show(string $id): JsonResponse
    {
        $province = Province::with(['ministere', 'pays', 'communes'])->findOrFail($id);

        return response()->json($province);
    }

    /**
     * Update the specified province.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $province = Province::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:10',
            'ministere_id' => 'nullable|exists:ministeres,id',
            'pays_id' => 'sometimes|required|exists:pays,id',
        ]);

        $province->update($validated);

        return response()->json($province->load(['ministere', 'pays']));
    }

    /**
     * Remove the specified province.
     */
    public function destroy(string $id): JsonResponse
    {
        $province = Province::findOrFail($id);
        $province->delete();

        return response()->json(null, 204);
    }
}
