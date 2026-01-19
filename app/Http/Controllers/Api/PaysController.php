<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pays;
use Illuminate\Http\Request;

class PaysController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pays = Pays::all();
        return response()->json($pays);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10',
        ]);

        $pays = Pays::create($validated);

        return response()->json($pays, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pays = Pays::findOrFail($id);
        return response()->json($pays);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pays = Pays::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:10',
        ]);

        $pays->update($validated);

        return response()->json($pays);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pays = Pays::findOrFail($id);
        $pays->delete();

        return response()->json(null, 204);
    }
}
