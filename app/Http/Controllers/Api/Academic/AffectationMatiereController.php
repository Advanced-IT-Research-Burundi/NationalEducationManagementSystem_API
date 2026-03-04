<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\AffectationMatiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffectationMatiereController extends Controller
{
    public function index(Request $request)
    {
        $query = AffectationMatiere::with(['enseignant.user', 'matiere', 'school', 'anneeScolaire']);

        if ($request->has('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->has('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        if ($request->has('enseignant_id')) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'matiere_id' => 'nullable|exists:matieres,id',
            'matiere_ids' => 'nullable|array',
            'matiere_ids.*' => 'exists:matieres,id',
            'school_id' => 'nullable|exists:schools,id',
            'annee_scolaire_id' => 'nullable|exists:annee_scolaires,id',
            'statut' => 'nullable|in:ACTIVE,INACTIVE',
        ]);

        $validated['created_by'] = Auth::id();
        $results = [];

        $matiereIds = $request->input('matiere_ids', []);
        if (empty($matiereIds) && $request->input('matiere_id')) {
            $matiereIds = [$request->input('matiere_id')];
        }

        foreach ($matiereIds as $matiereId) {
            $results[] = AffectationMatiere::updateOrCreate(
                [
                    'enseignant_id' => $validated['enseignant_id'],
                    'matiere_id' => $matiereId,
                    'annee_scolaire_id' => $validated['annee_scolaire_id'] ?? null,
                ],
                array_merge($validated, ['matiere_id' => $matiereId])
            );
        }

        return response()->json($results, 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'enseignant_id' => 'required|exists:enseignants,id',
            'matiere_id' => 'required|exists:matieres,id',
            'school_id' => 'nullable|exists:schools,id',
            'annee_scolaire_id' => 'nullable|exists:annee_scolaires,id',
            'statut' => 'nullable|in:ACTIVE,INACTIVE',
        ]);

        $affectation = AffectationMatiere::findOrFail($id);
        $affectation->update($validated);

        return response()->json($affectation);
    }

    public function updateStatut(Request $request, $id)
    {
        $validated = $request->validate([
            'statut' => 'required|in:ACTIVE,INACTIVE',
        ]);

        $affectation = AffectationMatiere::findOrFail($id);
        $affectation->update(['statut' => $validated['statut']]);

        return response()->json($affectation);
    }

    public function destroy($id)
    {
        $affectation = AffectationMatiere::findOrFail($id);
        $affectation->delete();

        return response()->json(null, 204);
    }
}
