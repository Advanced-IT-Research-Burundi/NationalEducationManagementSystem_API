<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\AffectationMatiere;
use App\Models\AffectationEnseignant;
use App\Models\Classe;
use App\Models\Matiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AffectationMatiereController extends Controller
{
    public function index(Request $request)
    {
        $query = AffectationMatiere::with(['enseignant.user', 'matiere', 'school', 'anneeScolaire']);

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $affectations = $query->latest()->paginate($request->get('per_page', 15));

        return sendResponse($affectations, 'Affectations récupérées avec succès');
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
            $affectation = AffectationMatiere::withTrashed()->updateOrCreate(
                [
                    'enseignant_id' => $validated['enseignant_id'],
                    'matiere_id' => $matiereId,
                    'annee_scolaire_id' => $validated['annee_scolaire_id'] ?? null,
                ],
                array_merge($validated, ['matiere_id' => $matiereId, 'deleted_at' => null])
            );
            
            $this->syncWithClasseAssignments($affectation);
            $results[] = $affectation;
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
        
        $this->syncWithClasseAssignments($affectation);

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

    /**
     * Synchronize teacher subject assignment with class assignments.
     */
    protected function syncWithClasseAssignments(AffectationMatiere $affectation)
    {
        if ($affectation->statut !== 'ACTIVE') {
            return;
        }

        $matiere = Matiere::find($affectation->matiere_id);
        if (!$matiere || !$affectation->annee_scolaire_id) {
            return;
        }

        // We find ALL schools associated with this teacher to be safe, 
        // as the subject assignment might be intended for any of them.
        $schoolIds = [];
        $enseignant = \App\Models\Enseignant::find($affectation->enseignant_id);
        if ($enseignant) {
            if ($enseignant->school_id) {
                $schoolIds[] = $enseignant->school_id;
            }
            $pivotSchools = $enseignant->ecoles()->pluck('schools.id')->toArray();
            $schoolIds = array_unique(array_merge($schoolIds, $pivotSchools));
        }

        if (empty($schoolIds)) {
            // Fallback to the affectation's school_id if teacher has no schools linked (unlikely)
            if ($affectation->school_id) {
                $schoolIds = [$affectation->school_id];
            } else {
                return;
            }
        }

        // Find matching classes based on all available school contexts
        $classes = Classe::whereIn('school_id', $schoolIds)
            ->where('annee_scolaire_id', $affectation->annee_scolaire_id)
            ->where('niveau_id', $matiere->niveau_id)
            ->where('section_id', $matiere->section_id)
            ->get();

        foreach ($classes as $classe) {
            AffectationEnseignant::withTrashed()->updateOrCreate(
                [
                    'enseignant_id' => $affectation->enseignant_id,
                    'classe_id' => $classe->id,
                    'matiere' => $matiere->nom, // Store subject name as string in class assignment
                ],
                [
                    'date_debut' => now(),
                    'statut' => 'ACTIVE',
                    'created_by' => Auth::id(),
                    'deleted_at' => null,
                ]
            );
        }
    }
}
