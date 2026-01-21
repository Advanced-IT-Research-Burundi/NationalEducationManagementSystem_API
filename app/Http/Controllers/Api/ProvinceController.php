<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $paysId = request('pays_id');
        $ministereId = request('ministere_id');
        $limit = request('limit', 10);
        if ($paysId) {
            $provinces = Province::where('pays_id', $paysId)->with('pays', 'ministere')->paginate($limit);
        } elseif ($ministereId) {
            $provinces = Province::where('ministere_id', $ministereId)->with('pays', 'ministere')->paginate($limit);
        } else {
            $provinces = Province::with('pays', 'ministere')->paginate($limit);
        }
        return sendResponse($provinces, 'Provinces retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $province = Province::create($validated);

        return response()->json($province, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $province = Province::findOrFail($id);
        return response()->json($province);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $province = Province::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $province->update($validated);

        return response()->json($province);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $province = Province::findOrFail($id);
        $province->delete();

        return response()->json(null, 204);
    }
}
