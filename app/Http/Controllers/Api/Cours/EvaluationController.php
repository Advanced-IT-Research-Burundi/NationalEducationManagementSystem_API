<?php

namespace App\Http\Controllers\Api\Cours;

use App\Exports\NotesTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\Note;
use App\Scopes\AcademicYearScope;
use App\Services\CurrentAcademicContextService;
use App\Support\AcademicCycleHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class EvaluationController extends Controller
{
    use \App\Traits\ResolvesAnneeScolaire;

    public function __construct(
        private readonly CurrentAcademicContextService $academicContextService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $currentTrimestre = $this->academicContextService->requireCurrentTrimestre();

        $query = Evaluation::with(['classe:id,nom,code', 'cours:id,nom,code', 'anneeScolaire:id,libelle,code', 'creator:id,name', 'trimestreModel'])
            ->withCount('notes');

        if ($request->filled('classe_id')) {
            $query->byClasse($request->integer('classe_id'));
        }

        if ($request->filled('cours_id')) {
            $query->byCours($request->integer('cours_id'));
        }

        if ($request->filled('type_evaluation')) {
            $query->byType($request->type_evaluation);
        }

        $anneeScolaireId = $this->resolveAnneeScolaireId($request);
        if ($anneeScolaireId) {
            $query->byAnneeScolaire($anneeScolaireId);
        }

        $query->byTrimestreId($currentTrimestre->id);

        if ($request->filled('school_id')) {
            $query->bySchool($request->integer('school_id'));
        } elseif (auth()->check() && auth()->user()->school_id) {
            $query->bySchool(auth()->user()->school_id);
        }

        if (auth()->check() && auth()->user()->hasRole('Enseignant')) {
            $enseignantId = auth()->user()->enseignant->id ?? null;
            if ($enseignantId) {
                $query->whereHas('classe.enseignants', function ($q) use ($enseignantId) {
                    $q->where('enseignants.id', $enseignantId);
                })
                    ->whereHas('cours.enseignants', function ($q) use ($enseignantId) {
                        $q->where('enseignants.id', $enseignantId);
                    });
            }
        }

        $evaluations = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($evaluations);
    }

    public function store(Request $request): JsonResponse
    {

        $validated = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'cours_id' => ['required', 'exists:matieres,id'],
            'type_evaluation' => ['required', Rule::in(Evaluation::TYPES_EVALUATION)],
            'date_passation' => ['required', 'date'],
            'note_maximale' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
        ]);

        $currentTrimestre = $this->academicContextService->ensureCurrentTrimestreNotLocked();

        $anneeScolaireId = $this->resolveAnneeScolaireId($request);
        if (! $anneeScolaireId) {
            return response()->json([
                'message' => 'Aucune année scolaire active trouvée. Veuillez activer une année scolaire.',
            ], 422);
        }

        if ($validated['type_evaluation'] === 'Compétence') {
            $err = $this->validateCompetenceEvaluationContext(
                (int) $validated['classe_id'],
                (int) $validated['cours_id']
            );
            if ($err !== null) {
                return response()->json(['message' => $err], 422);
            }
        }

        $validated['annee_scolaire_id'] = $anneeScolaireId;
        $validated['trimestre_id'] = $currentTrimestre->id;
        $validated['trimestre'] = $currentTrimestre->nom;
        $validated['created_by'] = Auth::id();

        $evaluation = Evaluation::create($validated);

        return response()->json([
            'message' => 'Évaluation créée avec succès',
            'data' => $evaluation->load(['classe', 'cours', 'anneeScolaire', 'trimestreModel']),
        ], 201);
    }

    public function show(Evaluation $evaluation): JsonResponse
    {
        return response()->json([
            'data' => $evaluation->load([
                'classe:id,nom,code',
                'cours:id,nom,code',
                'anneeScolaire:id,libelle,code',
                'trimestreModel',
                'notes.eleve:id,nom,prenom,matricule',
            ]),
        ]);
    }

    public function update(Request $request, Evaluation $evaluation): JsonResponse
    {
        $validated = $request->validate([
            'classe_id' => ['sometimes', 'exists:classes,id'],
            'cours_id' => ['sometimes', 'exists:matieres,id'],
            'type_evaluation' => ['sometimes', Rule::in(Evaluation::TYPES_EVALUATION)],
            'date_passation' => ['sometimes', 'date'],
            'note_maximale' => ['sometimes', 'numeric', 'min:0.01', 'max:999.99'],
        ]);

        $this->academicContextService->ensureEntityBelongsToCurrentTrimestre($evaluation);
        $this->academicContextService->ensureCurrentTrimestreNotLocked($evaluation->trimestreModel);

        $nextType = $validated['type_evaluation'] ?? $evaluation->type_evaluation;
        if ($nextType === 'Compétence') {
            $classeId = isset($validated['classe_id']) ? (int) $validated['classe_id'] : (int) $evaluation->classe_id;
            $coursId = isset($validated['cours_id']) ? (int) $validated['cours_id'] : (int) $evaluation->cours_id;
            $err = $this->validateCompetenceEvaluationContext($classeId, $coursId);
            if ($err !== null) {
                return response()->json(['message' => $err], 422);
            }
        }

        $evaluation->update($validated);

        return response()->json([
            'message' => 'Évaluation mise à jour avec succès',
            'data' => $evaluation->load(['classe', 'cours', 'anneeScolaire', 'trimestreModel']),
        ]);
    }

    public function destroy(Evaluation $evaluation): JsonResponse
    {
        $this->academicContextService->ensureEntityBelongsToCurrentTrimestre($evaluation);
        $this->academicContextService->ensureCurrentTrimestreNotLocked($evaluation->trimestreModel);
        $evaluation->notes()->delete();
        $evaluation->delete();

        return response()->json(['message' => 'Évaluation supprimée avec succès']);
    }

    /**
     * Store notes for all students in an evaluation (batch).
     */
    public function storeNotes(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->academicContextService->ensureEntityBelongsToCurrentTrimestre($evaluation);
        $this->academicContextService->ensureCurrentTrimestreNotLocked($evaluation->trimestreModel);

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
            'trimestreModel',
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
        $classe = Classe::withoutGlobalScope(AcademicYearScope::class)->with(['eleves' => function ($q) {
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
        $this->academicContextService->ensureEntityBelongsToCurrentTrimestre($evaluation);
        $this->academicContextService->ensureCurrentTrimestreNotLocked($evaluation->trimestreModel);

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
                    $errors[] = 'Ligne '.($index + 2).": note ({$note}) dépasse le maximum ({$evaluation->note_maximale}).";

                    continue;
                }
                $notes[] = ['eleve_id' => (int) $eleveId, 'note' => (float) $note];
            }
        }

        if (! empty($errors)) {
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
            'message' => count($notes).' notes importées avec succès.',
            'data' => $evaluation->load('notes.eleve:id,nom,prenom,matricule'),
        ]);
    }

    /**
     * @return string|null Error message or null if OK
     */
    private function validateCompetenceEvaluationContext(int $classeId, int $coursId): ?string
    {
        $classe = Classe::with(['niveau.cycleScolaire', 'niveau.typeScolaire'])->find($classeId);
        if (! $classe || ! AcademicCycleHelper::isPostFondamental($classe->niveau)) {
            return "Le type 'Compétence' ne peut être utilisé que pour les classes post-fondamentales.";
        }

        $matiere = Matiere::find($coursId);
        if (! $matiere || (float) ($matiere->ponderation_competence ?? 0) <= 0) {
            return "Ce cours n'a pas de pondération compétences : impossible d'utiliser le type 'Compétence'. Définissez un maximum compétences sur la fiche cours.";
        }

        return null;
    }
}
