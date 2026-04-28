<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaysResource;
use App\Models\Pays;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaysController extends Controller
{
    /**
     * Display a listing of countries.
     */
    public function index(): JsonResponse
    {
        $pays = Pays::all();

        return PaysResource::collection($pays)->response();
    }

    /**
     * Store a newly created country.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
        ]);

        $pays = Pays::create($validated);

        return (new PaysResource($pays))
            ->additional(['message' => 'Pays created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified country.
     */
    public function show(string $id): JsonResponse
    {
        $pays = Pays::findOrFail($id);

        return (new PaysResource($pays))->response();
    }

    /**
     * Update the specified country.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $pays = Pays::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:10',
        ]);

        $pays->update($validated);

        return (new PaysResource($pays))
            ->additional(['message' => 'Pays updated successfully.'])
            ->response();
    }

    /**
     * Remove the specified country.
     */
    public function destroy(string $id): JsonResponse
    {
        $pays = Pays::findOrFail($id);
        $pays->delete();

        return response()->json(null, 204);
    }
}
