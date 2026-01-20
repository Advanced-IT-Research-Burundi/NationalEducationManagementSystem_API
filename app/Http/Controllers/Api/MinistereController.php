<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ministere;
use Illuminate\Http\Request;

class MinistereController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ministeres = Ministere::with('pays')->paginate(10); // Assuming relationship to Pays exists
        return sendResponse($ministeres, 'Ministeres retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'pays_id' => 'required|exists:pays,id',
        ]);

        $ministere = Ministere::create($validated);

        return sendResponse($ministere, 'Ministere created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ministere = Ministere::with('pays')->findOrFail($id);
        return sendResponse($ministere, 'Ministere retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ministere = Ministere::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'pays_id' => 'sometimes|required|exists:pays,id',
        ]);

        $ministere->update($validated);

        return sendResponse($ministere, 'Ministere updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ministere = Ministere::findOrFail($id);
        $ministere->delete();

        return sendResponse(null, 'Ministere deleted successfully');
    }
}
