<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $zones = Zone::with('commune')->get();
        return response()->json($zones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'commune_id' => 'required|exists:communes,id',
        ]);

        $zone = Zone::create($validated);

        return response()->json($zone, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $zone = Zone::with('commune')->findOrFail($id);
        return response()->json($zone);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $zone = Zone::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'commune_id' => 'sometimes|required|exists:communes,id',
        ]);

        $zone->update($validated);

        return response()->json($zone);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $zone = Zone::findOrFail($id);
        $zone->delete();

        return response()->json(null, 204);
    }
}
