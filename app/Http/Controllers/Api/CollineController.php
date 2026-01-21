<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Colline;
use Illuminate\Http\Request;

class CollineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $zoneId = request('zone_id');
        $communeId = request('commune_id');
        $limit = request('limit', 10);

        $query = Colline::with(['zone', 'commune', 'province']);

        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        } elseif ($communeId) {
            $query->where('commune_id', $communeId);
        }

        $collines = $query->paginate($limit);
        
        return sendResponse($collines, 'Collines retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'zone_id' => 'required|exists:zones,id',
            'commune_id' => 'nullable|exists:communes,id',
            'province_id' => 'nullable|exists:provinces,id',
            'ministere_id' => 'nullable|exists:ministeres,id',
            'pays_id' => 'nullable|exists:pays,id',
        ]);

        $colline = Colline::create($validated);

        return sendResponse($colline, 'Colline created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $colline = Colline::with(['zone', 'commune', 'province', 'schools'])->findOrFail($id);
        return sendResponse($colline, 'Colline retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $colline = Colline::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'zone_id' => 'sometimes|required|exists:zones,id',
            'commune_id' => 'nullable|exists:communes,id',
            'province_id' => 'nullable|exists:provinces,id',
            'ministere_id' => 'nullable|exists:ministeres,id',
            'pays_id' => 'nullable|exists:pays,id',
        ]);

        $colline->update($validated);

        return sendResponse($colline, 'Colline updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $colline = Colline::findOrFail($id);
        $colline->delete();

        return response()->json(null, 204);
    }
}
