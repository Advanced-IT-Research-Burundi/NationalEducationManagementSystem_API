<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Http\Resources\MinistereResource;
use App\Models\Ministere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MinistereController extends Controller
{
    /**
     * Display a listing of ministries.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ministere::with('pays');

        if ($request->filled('pays_id')) {
            $query->where('pays_id', $request->pays_id);
        }

        $ministeres = $query->paginate(10);

        return MinistereResource::collection($ministeres)->response();
    }

    /**
     * Store a newly created ministry.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
            'pays_id' => 'required|exists:pays,id',
        ]);

        $ministere = Ministere::create($validated);

        return (new MinistereResource($ministere->load('pays')))
            ->additional(['message' => 'Ministere created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified ministry.
     */
    public function show(string $id): JsonResponse
    {
        $ministere = Ministere::with('pays')->findOrFail($id);

        return (new MinistereResource($ministere))->response();
    }

    /**
     * Update the specified ministry.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $ministere = Ministere::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:10',
            'pays_id' => 'sometimes|required|exists:pays,id',
        ]);

        $ministere->update($validated);

        return (new MinistereResource($ministere->load('pays')))
            ->additional(['message' => 'Ministere updated successfully.'])
            ->response();
    }

    /**
     * Remove the specified ministry.
     */
    public function destroy(string $id): JsonResponse
    {
        $ministere = Ministere::findOrFail($id);
        $ministere->delete();

        return response()->json(null, 204);
    }
}
