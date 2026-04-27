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
            'classe_ids' => 'nullable|array',
            'classe_ids.*' => 'exists:classes,id',
        ]);

        $validated['created_by'] = Auth::id();
        $results = [];
        $classeIds = $request->input('classe_ids', []);

        $matiereIds = $request->input('matiere_ids', []);
        if (empty($matiereIds) && $request->input('matiere_id')) {
            $matiereIds = [$request->input('matiere_id')];
        }

        $conflicts = $this->detectConflicts($validated['enseignant_id'], $matiereIds, $classeIds);
        if (! empty($conflicts)) {
            return response()->json([
                'message' => 'Conflit d\'affectation : ' . implode(' | ', $conflicts),
            ], 422);
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
            
            $this->syncWithClasseAssignments($affectation, $classeIds);
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
            'classe_ids' => 'nullable|array',
            'classe_ids.*' => 'exists:classes,id',
        ]);

        $affectation = AffectationMatiere::findOrFail($id);
        $classeIds = $request->input('classe_ids', []);

        $conflicts = $this->detectConflicts(
            $validated['enseignant_id'],
            [$validated['matiere_id']],
            $classeIds,
            $affectation->id
        );
        if (! empty($conflicts)) {
            return response()->json([
                'message' => 'Conflit d\'affectation : ' . implode(' | ', $conflicts),
            ], 422);
        }

        $affectation->update($validated);
        $this->syncWithClasseAssignments($affectation, $classeIds);

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
     * Check if another teacher is already assigned to the same subject in any of the given classes.
     */
    protected function detectConflicts(int $enseignantId, array $matiereIds, array $classeIds, ?int $excludeAffectationId = null): array
    {
        if (empty($classeIds) || empty($matiereIds)) {
            return [];
        }

        $matiereNames = Matiere::whereIn('id', $matiereIds)->pluck('nom', 'id');
        $classeNames = Classe::whereIn('id', $classeIds)->pluck('nom', 'id');
        $conflicts = [];

        foreach ($matiereIds as $matiereId) {
            $matiereName = $matiereNames[$matiereId] ?? null;
            if (! $matiereName) {
                continue;
            }

            foreach ($classeIds as $classeId) {
                $existing = AffectationEnseignant::where('classe_id', $classeId)
                    ->where('matiere', $matiereName)
                    ->where('enseignant_id', '!=', $enseignantId)
                    ->where('statut', 'ACTIVE')
                    ->with('enseignant.user')
                    ->first();

                if ($existing) {
                    $teacherName = $existing->enseignant?->user?->name ?? 'Un autre enseignant';
                    $className = $classeNames[$classeId] ?? 'classe';
                    $conflicts[] = "{$teacherName} enseigne déjà « {$matiereName} » dans {$className}";
                }
            }
        }

        return $conflicts;
    }

    /**
     * Synchronize teacher subject assignment with class assignments.
     */
    protected function syncWithClasseAssignments(AffectationMatiere $affectation, array $classeIds = [])
    {
        if ($affectation->statut !== 'ACTIVE') {
            return;
        }

        if (empty($classeIds)) {
            return; // No classes selected, skip syncing
        }

        $matiere = Matiere::find($affectation->matiere_id);
        if (!$matiere) {
            return;
        }

        $classes = Classe::whereIn('id', $classeIds)->get();

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
