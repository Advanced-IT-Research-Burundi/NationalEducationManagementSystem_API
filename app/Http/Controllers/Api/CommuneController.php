<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commune;
use Illuminate\Http\Request;

class CommuneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $communes = Commune::with('province')->paginate(10);
        return sendResponse($communes, 'Communes retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'province_id' => 'required|exists:provinces,id',
        ]);

        $commune = Commune::create($validated);

        return response()->json($commune, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $commune = Commune::with('province')->findOrFail($id);
        return response()->json($commune);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $commune = Commune::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'province_id' => 'sometimes|required|exists:provinces,id',
        ]);

        $commune->update($validated);

        return response()->json($commune);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $commune = Commune::findOrFail($id);
        $commune->delete();

        return response()->json(null, 204);
    }
}
