<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $schools = School::with(['pays', 'ministere', 'province', 'commune', 'zone', 'colline'])->paginate(10);
        return sendResponse($schools, 'Schools retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'colline_id' => 'required|exists:collines,id',
            'zone_id' => 'nullable|exists:zones,id',
            'commune_id' => 'nullable|exists:communes,id',
            'province_id' => 'nullable|exists:provinces,id',
            'ministere_id' => 'nullable|exists:ministeres,id',
            'pays_id' => 'nullable|exists:pays,id',
        ]);

        $school = School::create($validated);

        return response()->json($school, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $school = School::with(['pays', 'ministere', 'province', 'commune', 'zone', 'colline'])->findOrFail($id);
        return response()->json($school);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $school = School::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'colline_id' => 'sometimes|required|exists:collines,id',
            'zone_id' => 'nullable|exists:zones,id',
            'commune_id' => 'nullable|exists:communes,id',
            'province_id' => 'nullable|exists:provinces,id',
            'ministere_id' => 'nullable|exists:ministeres,id',
            'pays_id' => 'nullable|exists:pays,id',
        ]);

        $school->update($validated);

        return response()->json($school);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $school = School::findOrFail($id);
        $school->delete();

        return response()->json(null, 204);
    }
}
