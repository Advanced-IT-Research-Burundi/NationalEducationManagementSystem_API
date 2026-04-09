<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Models\ReglementScolaire;
use Illuminate\Http\Request;

class ReglementScolaireController extends Controller
{
    public function index(Request $request)
    {
        $query = ReglementScolaire::query();
        if ($request->has('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'nullable|exists:schools,id',
            'article_number' => 'nullable|string',
            'intitule' => 'required|string',
            'description' => 'nullable|string',
            'gravite' => 'required|in:Faible,Moyenne,Grave',
            'points_retires' => 'required|integer|min:0',
            'sanction' => 'nullable|string',
        ]);

        $reglement = ReglementScolaire::create($validated);
        return response()->json($reglement, 201);
    }

    public function show(ReglementScolaire $reglementScolaire)
    {
        return response()->json($reglementScolaire);
    }

    public function update(Request $request, ReglementScolaire $reglementScolaire)
    {
        $validated = $request->validate([
            'school_id' => 'nullable|exists:schools,id',
            'article_number' => 'nullable|string',
            'intitule' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'gravite' => 'sometimes|required|in:Faible,Moyenne,Grave',
            'points_retires' => 'sometimes|required|integer|min:0',
            'sanction' => 'nullable|string',
        ]);

        $reglementScolaire->update($validated);
        return response()->json($reglementScolaire);
    }

    public function destroy(ReglementScolaire $reglementScolaire)
    {
        $reglementScolaire->delete();
        return response()->json(null, 204);
    }
}
