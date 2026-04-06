<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Http\Resources\EvaluationResource;


class EvaluationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Evaluation::with(['classe', 'eleve', 'matiere', 'enseignant']);

        if ($request->filled('classe_id')) {
            $query->byClasse($request->classe_id);
        }

        if ($request->filled('eleve_id')) {
            $query->byEleve($request->eleve_id);
        }

        if ($request->filled('matiere_id')) {
            $query->where('matiere_id', $request->matiere_id);
        }

        if ($request->filled('trimestre')) {
            $query->byTrimestre($request->trimestre);
        }

        if ($request->filled('categorie')) {
            $query->byCategorie($request->categorie);
        }

        if ($request->filled('enseignant_id')) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        $evaluations = $query->latest()->paginate($request->get('per_page', 15));

        return sendResponse(EvaluationResource::collection($evaluations), 'Évaluations récupérées avec succès');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'eleve_id' => ['required', 'exists:eleves,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
            'enseignant_id' => ['nullable', 'exists:enseignants,id'],
            'trimestre' => ['required', Rule::in(Evaluation::TRIMESTRES)],
            'categorie' => ['required', Rule::in(Evaluation::CATEGORIES)],
            'ponderation' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'note' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validated['note'] > $validated['ponderation']) {
            return response()->json([
                'message' => 'La note ne peut pas dépasser la pondération.',
                'errors' => ['note' => ['La note ne peut pas dépasser la pondération.']],
            ], 422);
        }

        $existing = Evaluation::where('eleve_id', $validated['eleve_id'])
            ->where('matiere_id', $validated['matiere_id'])
            ->where('trimestre', $validated['trimestre'])
            ->where('categorie', $validated['categorie'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Une évaluation existe déjà pour cet élève dans cette matière, ce trimestre et cette catégorie.',
                'errors' => ['eleve_id' => ['Doublon détecté.']],
            ], 422);
        }

        $evaluation = Evaluation::create($validated);

        return response()->json([
            'message' => 'Évaluation enregistrée avec succès',
            'data' => $evaluation->load(['classe', 'eleve', 'matiere', 'enseignant']),
        ], 201);
    }

    public function show(Evaluation $evaluation): JsonResponse
    {
        return response()->json([
            'data' => $evaluation->load(['classe', 'eleve', 'matiere', 'enseignant']),
        ]);
    }

    public function update(Request $request, Evaluation $evaluation): JsonResponse
    {
        $validated = $request->validate([
            'classe_id' => ['sometimes', 'exists:classes,id'],
            'eleve_id' => ['sometimes', 'exists:eleves,id'],
            'matiere_id' => ['sometimes', 'exists:matieres,id'],
            'enseignant_id' => ['nullable', 'exists:enseignants,id'],
            'trimestre' => ['sometimes', Rule::in(Evaluation::TRIMESTRES)],
            'categorie' => ['sometimes', Rule::in(Evaluation::CATEGORIES)],
            'ponderation' => ['sometimes', 'numeric', 'min:0', 'max:999.99'],
            'note' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $ponderation = $validated['ponderation'] ?? $evaluation->ponderation;
        $note = $validated['note'] ?? $evaluation->note;

        if ($note > $ponderation) {
            return response()->json([
                'message' => 'La note ne peut pas dépasser la pondération.',
                'errors' => ['note' => ['La note ne peut pas dépasser la pondération.']],
            ], 422);
        }

        $eleveId = $validated['eleve_id'] ?? $evaluation->eleve_id;
        $matiereId = $validated['matiere_id'] ?? $evaluation->matiere_id;
        $trimestre = $validated['trimestre'] ?? $evaluation->trimestre;
        $categorie = $validated['categorie'] ?? $evaluation->categorie;

        $duplicate = Evaluation::where('eleve_id', $eleveId)
            ->where('matiere_id', $matiereId)
            ->where('trimestre', $trimestre)
            ->where('categorie', $categorie)
            ->where('id', '!=', $evaluation->id)
            ->first();

        if ($duplicate) {
            return response()->json([
                'message' => 'Une évaluation existe déjà pour cet élève dans cette matière, ce trimestre et cette catégorie.',
                'errors' => ['eleve_id' => ['Doublon détecté.']],
            ], 422);
        }

        $evaluation->update($validated);

        return response()->json([
            'message' => 'Évaluation mise à jour avec succès',
            'data' => $evaluation->load(['classe', 'eleve', 'matiere', 'enseignant']),
        ]);
    }

    public function destroy(Evaluation $evaluation): JsonResponse
    {
        $evaluation->delete();

        return response()->json([
            'message' => 'Évaluation supprimée avec succès',
        ]);
    }

    public function byClasse(Request $request, int $classeId): JsonResponse
    {
        $query = Evaluation::with(['eleve', 'matiere', 'enseignant'])
            ->byClasse($classeId);

        if ($request->filled('trimestre')) {
            $query->byTrimestre($request->trimestre);
        }

        if ($request->filled('categorie')) {
            $query->byCategorie($request->categorie);
        }

        if ($request->filled('matiere_id')) {
            $query->where('matiere_id', $request->matiere_id);
        }

        $evaluations = $query->latest()->get();

        return response()->json($evaluations);
    }

    public function byEleve(Request $request, int $eleveId): JsonResponse
    {
        $query = Evaluation::with(['classe', 'matiere', 'enseignant'])
            ->byEleve($eleveId);

        if ($request->filled('trimestre')) {
            $query->byTrimestre($request->trimestre);
        }

        $evaluations = $query->latest()->get();

        return response()->json($evaluations);
    }
}
