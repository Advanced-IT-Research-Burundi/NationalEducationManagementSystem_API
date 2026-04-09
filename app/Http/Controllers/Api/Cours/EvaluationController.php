<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Evaluation;
use App\Models\Note;
use App\Models\Classe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\NotesTemplateExport;

class EvaluationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Evaluation::with(['classe:id,nom,code', 'cours:id,nom,code', 'anneeScolaire:id,libelle,code', 'creator:id,name'])
            ->withCount('notes');

        if ($request->filled('classe_id')) {
            $query->byClasse($request->integer('classe_id'));
        }

        if ($request->filled('cours_id')) {
            $query->byCours($request->integer('cours_id'));
        }

        if ($request->filled('trimestre')) {
            $query->byTrimestre($request->trimestre);
        }

        if ($request->filled('type_evaluation')) {
            $query->byType($request->type_evaluation);
        }

        if ($request->filled('annee_scolaire_id')) {
            $query->byAnneeScolaire($request->integer('annee_scolaire_id'));
        }

        $evaluations = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($evaluations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'cours_id' => ['required', 'exists:matieres,id'],
            'trimestre' => ['required', Rule::in(Evaluation::TRIMESTRES)],
            'type_evaluation' => ['required', Rule::in(Evaluation::TYPES_EVALUATION)],
            'date_passation' => ['required', 'date'],
            'note_maximale' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
        ]);

        // Auto-detect current school year
        $anneeScolaire = AnneeScolaire::current();
        if (!$anneeScolaire) {
            return response()->json([
                'message' => "Aucune année scolaire active trouvée. Veuillez activer une année scolaire.",
            ], 422);
        }

        $validated['annee_scolaire_id'] = $anneeScolaire->id;
        $validated['created_by'] = Auth::id();

        $evaluation = Evaluation::create($validated);

        return response()->json([
            'message' => 'Évaluation créée avec succès',
            'data' => $evaluation->load(['classe', 'cours', 'anneeScolaire']),
        ], 201);
    }

    public function show(Evaluation $evaluation): JsonResponse
    {
        return response()->json([
            'data' => $evaluation->load([
                'classe:id,nom,code',
                'cours:id,nom,code',
                'anneeScolaire:id,libelle,code',
                'notes.eleve:id,nom,prenom,matricule',
            ]),
        ]);
    }

    public function update(Request $request, Evaluation $evaluation): JsonResponse
    {
        $validated = $request->validate([
            'classe_id' => ['sometimes', 'exists:classes,id'],
            'cours_id' => ['sometimes', 'exists:matieres,id'],
            'trimestre' => ['sometimes', Rule::in(Evaluation::TRIMESTRES)],
            'type_evaluation' => ['sometimes', Rule::in(Evaluation::TYPES_EVALUATION)],
            'date_passation' => ['sometimes', 'date'],
            'note_maximale' => ['sometimes', 'numeric', 'min:0.01', 'max:999.99'],
        ]);

        $evaluation->update($validated);

        return response()->json([
            'message' => 'Évaluation mise à jour avec succès',
            'data' => $evaluation->load(['classe', 'cours', 'anneeScolaire']),
        ]);
    }

    public function destroy(Evaluation $evaluation): JsonResponse
    {
        $evaluation->notes()->delete();
        $evaluation->delete();

        return response()->json(['message' => 'Évaluation supprimée avec succès']);
    }

    /**
     * Store notes for all students in an evaluation (batch).
     */
    public function storeNotes(Request $request, Evaluation $evaluation): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'array', 'min:1'],
            'notes.*.eleve_id' => ['required', 'exists:eleves,id'],
            'notes.*.note' => ['required', 'numeric', 'min:0'],
        ]);

        // Validate no note exceeds note_maximale
        foreach ($validated['notes'] as $entry) {
            if ($entry['note'] > $evaluation->note_maximale) {
                return response()->json([
                    'message' => "La note de l'élève #{$entry['eleve_id']} ({$entry['note']}) dépasse la note maximale ({$evaluation->note_maximale}).",
                ], 422);
            }
        }

        DB::transaction(function () use ($evaluation, $validated) {
            foreach ($validated['notes'] as $entry) {
                Note::updateOrCreate(
                    [
                        'evaluation_id' => $evaluation->id,
                        'eleve_id' => $entry['eleve_id'],
                    ],
                    [
                        'note' => $entry['note'],
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Notes enregistrées avec succès',
            'data' => $evaluation->load('notes.eleve:id,nom,prenom,matricule'),
        ]);
    }

    /**
     * Show notes for an evaluation.
     */
    public function showNotes(Evaluation $evaluation): JsonResponse
    {
        $evaluation->load([
            'notes.eleve:id,nom,prenom,matricule',
            'classe:id,nom',
            'cours:id,nom',
        ]);

        return response()->json([
            'data' => $evaluation,
        ]);
    }

    /**
     * Export a template Excel for grade entry.
     */
    public function exportTemplate(Evaluation $evaluation)
    {
        $classe = Classe::with(['eleves' => function ($q) {
            $q->orderBy('nom')->orderBy('prenom');
        }])->findOrFail($evaluation->classe_id);

        $filename = "notes_template_{$classe->nom}_{$evaluation->cours->nom}.xlsx";

        if (ob_get_length() > 0) {
            ob_end_clean();
        }

        return Excel::download(
            new NotesTemplateExport($classe->eleves, $evaluation),
            $filename
        );
    }

    /**
     * Import notes from an Excel file.
     */
    public function importNotes(Request $request, Evaluation $evaluation): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $file = $request->file('file');
        $data = Excel::toArray(null, $file);

        if (empty($data) || empty($data[0])) {
            return response()->json(['message' => 'Le fichier est vide.'], 422);
        }

        $rows = $data[0];
        $notes = [];
        $errors = [];

        // Skip header row (index 0)
        foreach (array_slice($rows, 1) as $index => $row) {
            $eleveId = $row[0] ?? null; // Column A: eleve_id
            $note = $row[3] ?? null;     // Column D: note (after N°, Matricule, Nom Complet)

            if ($eleveId && is_numeric($note)) {
                if ($note > $evaluation->note_maximale) {
                    $errors[] = "Ligne " . ($index + 2) . ": note ({$note}) dépasse le maximum ({$evaluation->note_maximale}).";
                    continue;
                }
                $notes[] = ['eleve_id' => (int) $eleveId, 'note' => (float) $note];
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Des erreurs ont été trouvées dans le fichier.',
                'errors' => $errors,
            ], 422);
        }

        if (empty($notes)) {
            return response()->json(['message' => 'Aucune note valide trouvée dans le fichier.'], 422);
        }

        DB::transaction(function () use ($evaluation, $notes) {
            foreach ($notes as $entry) {
                Note::updateOrCreate(
                    [
                        'evaluation_id' => $evaluation->id,
                        'eleve_id' => $entry['eleve_id'],
                    ],
                    [
                        'note' => $entry['note'],
                    ]
                );
            }
        });

        return response()->json([
            'message' => count($notes) . ' notes importées avec succès.',
            'data' => $evaluation->load('notes.eleve:id,nom,prenom,matricule'),
        ]);
    }
}
