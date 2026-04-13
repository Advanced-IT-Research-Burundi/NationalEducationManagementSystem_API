<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\CategorieCours;
use App\Models\NoteConduite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;

class BulletinController extends Controller
{
    /**
     * Generate bulletin data for a student or a class.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'trimestre' => ['nullable', 'string'],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
            'eleve_id' => ['nullable', 'exists:eleves,id'],
        ]);

        $classeId = $request->integer('classe_id');
        $trimestre = $request->trimestre;
        $anneeScolaireId = $request->integer('annee_scolaire_id') ?: AnneeScolaire::current()?->id;

        if (!$anneeScolaireId) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $classe = Classe::with(['school:id,name', 'niveau:id,nom', 'section:id,nom'])->findOrFail($classeId);

        // Get students
        $elevesQuery = $classe->eleves()->orderBy('nom')->orderBy('prenom');
        if ($request->filled('eleve_id')) {
            $elevesQuery->where('eleves.id', $request->integer('eleve_id'));
        }
        $eleves = $elevesQuery->get();

        // Get all courses for this class's section/niveau
        $coursQuery = Matiere::query()->where('actif', true);
        if (Schema::hasColumn('matieres', 'categorie_cours_id')) {
            $coursQuery->with('categorieCours');
        }
        if (Schema::hasColumn('matieres', 'niveau_id')) {
            $coursQuery->where(function ($q) use ($classe) {
                $q->where('niveau_id', $classe->niveau_id)
                    ->orWhereNull('niveau_id');
            });
        }
        if (Schema::hasColumn('matieres', 'categorie_cours_id')) {
            $coursQuery->orderBy('categorie_cours_id');
        }
        $cours = $coursQuery->orderBy('nom')->get();

        // Get evaluations for this class/year
        $evaluationsQuery = Evaluation::with('notes')
            ->where('classe_id', $classeId)
            ->where('annee_scolaire_id', $anneeScolaireId);

        if ($trimestre) {
            $evaluationsQuery->where('trimestre', $trimestre);
        }

        $evaluations = $evaluationsQuery->get();

        // Get notes conduite
        $notesConduiteQuery = NoteConduite::where('classe_id', $classeId)
            ->where('annee_scolaire_id', $anneeScolaireId);
        if ($trimestre) {
            $notesConduiteQuery->where('trimestre', $trimestre);
        }
        $notesConduite = $notesConduiteQuery->get();

        // Build bulletin data per student
        $bulletins = [];
        foreach ($eleves as $eleve) {
            $coursData = [];
            $totalPoints = 0;
            $totalMax = 0;

            foreach ($cours as $matiere) {
                $coursEvals = $evaluations->where('cours_id', $matiere->id);

                // Separate TJ and Examen
                $tjEvals = $coursEvals->whereIn('type_evaluation', ['TJ', 'Interrogation', 'Devoir', 'TP']);
                $examEvals = $coursEvals->where('type_evaluation', 'Examen');

                $tjNote = null;
                $tjMax = 0;
                foreach ($tjEvals as $eval) {
                    $tjMax += $eval->note_maximale;
                    $note = $eval->notes->where('eleve_id', $eleve->id)->first();
                    if (!is_null($note)) {
                        $tjNote = ($tjNote ?? 0) + $note->note;
                    }
                }

                $examNote = null;
                $examMax = 0;
                foreach ($examEvals as $eval) {
                    $examMax += $eval->note_maximale;
                    $note = $eval->notes->where('eleve_id', $eleve->id)->first();
                    if (!is_null($note)) {
                        $examNote = ($examNote ?? 0) + $note->note;
                    }
                }

                // Scale to ponderation if configured
                $ponderationTj = Schema::hasColumn('matieres', 'ponderation_tj') ? (float) ($matiere->ponderation_tj ?? 0) : null;
                $ponderationExam = Schema::hasColumn('matieres', 'ponderation_examen') ? (float) ($matiere->ponderation_examen ?? 0) : null;

                $scaledTj = ($ponderationTj > 0 && $tjMax > 0 && !is_null($tjNote))
                    ? round(($tjNote / $tjMax) * $ponderationTj, 2)
                    : $tjNote;
                $scaledExam = ($ponderationExam > 0 && $examMax > 0 && !is_null($examNote))
                    ? round(($examNote / $examMax) * $ponderationExam, 2)
                    : $examNote;

                $isTjExpected = ($ponderationTj > 0 || $tjMax > 0);
                $isExamExpected = ($ponderationExam > 0 || $examMax > 0);
                
                $total = null;
                if (($isTjExpected && is_null($scaledTj)) || ($isExamExpected && is_null($scaledExam))) {
                    $total = null;
                } elseif ($isTjExpected || $isExamExpected) {
                    $total = ($scaledTj ?? 0) + ($scaledExam ?? 0);
                }

                $maxTotal = ($ponderationTj ?: $tjMax) + ($ponderationExam ?: $examMax);

                if (!is_null($total)) {
                    $totalPoints += $total;
                }
                $totalMax += $maxTotal;

                $coursData[] = [
                    'cours_id' => $matiere->id,
                    'nom' => $matiere->nom,
                    'categorie' => Schema::hasColumn('matieres', 'categorie_cours_id') ? $matiere->categorieCours?->nom : null,
                    'categorie_ordre' => Schema::hasColumn('matieres', 'categorie_cours_id') ? ($matiere->categorieCours?->ordre ?? 99) : 99,
                    'max_tj' => $ponderationTj ?: $tjMax,
                    'max_examen' => $ponderationExam ?: $examMax,
                    'max_total' => $maxTotal,
                    'note_tj' => $scaledTj,
                    'note_examen' => $scaledExam,
                    'note_total' => $total,
                ];
            }

            // Check if all evaluations have notes for this student
            $isComplete = true;
            foreach ($evaluations as $eval) {
                if ($eval->notes->where('eleve_id', $eleve->id)->isEmpty()) {
                    $isComplete = false;
                    break;
                }
            }

            $pourcentage = ($isComplete && $totalMax > 0) ? round(($totalPoints / $totalMax) * 100, 1) : null;

            // Note de conduite
            $noteC = $notesConduite->where('eleve_id', $eleve->id)->first();
            if ($isComplete && !$noteC) {
                // If marks are otherwise complete but conduite is missing, we might consider it incomplete
                // but the code previously defaulted to 60. Let's keep the default for now but maybe it should be considered incomplete?
                // User said "if the marks are not completed".
            }
            
            $noteConduiteValue = $noteC ? $noteC->note : 60;
            $appreciationConduite = 'Très mauvais';
            if ($noteConduiteValue >= 50) $appreciationConduite = 'Excellent';
            elseif ($noteConduiteValue >= 40) $appreciationConduite = 'Bon';
            elseif ($noteConduiteValue >= 30) $appreciationConduite = 'Passable';
            elseif ($noteConduiteValue >= 20) $appreciationConduite = 'Mauvais';

            $bulletins[] = [
                'eleve' => [
                    'id' => $eleve->id,
                    'nom' => $eleve->nom,
                    'prenom' => $eleve->prenom,
                    'matricule' => $eleve->matricule,
                ],
                'cours' => $coursData,
                'total_points' => $isComplete ? $totalPoints : null,
                'total_max' => $totalMax,
                'pourcentage' => $pourcentage,
                'is_complete' => $isComplete,
                'conduite' => [
                    'note' => $noteConduiteValue,
                    'max' => 60,
                    'appreciation' => $appreciationConduite
                ]
            ];
        }

        // Calculate rankings only for completed marks
        $completeBulletins = array_filter($bulletins, fn($b) => $b['is_complete']);
        usort($completeBulletins, fn($a, $b) => $b['total_points'] <=> $a['total_points']);
        
        $ranks = [];
        $rank = 1;
        foreach ($completeBulletins as $cb) {
            $ranks[$cb['eleve']['id']] = $rank++;
        }

        foreach ($bulletins as &$bulletin) {
            $bulletin['rang'] = $ranks[$bulletin['eleve']['id']] ?? null;
        }

        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

        return response()->json([
            'data' => [
                'classe' => $classe,
                'annee_scolaire' => $anneeScolaire,
                'trimestre' => $trimestre,
                'nombre_eleves' => count($eleves),
                'bulletins' => $bulletins,
            ],
        ]);
    }

    /**
     * Generate a PDF bulletin.
     */
    public function pdf(Request $request)
    {
        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'trimestre' => ['nullable', 'string'],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
            'eleve_id' => ['nullable', 'exists:eleves,id'],
        ]);

        // Reuse generate logic
        // Reuse generate logic

        $bulletinResponse = $this->generate($request);
        $bulletinData = json_decode($bulletinResponse->getContent(), true)['data'];

        // Determine view based on school level type_id
        $classe = Classe::with('niveau.typeScolaire')->find($request->integer('classe_id'));
        $viewName = 'bulletin.bulletin_pdf_post_fondamental';
        
        if ($classe && $classe->niveau && $classe->niveau->typeScolaire) {
            $typeScolaireNom = strtoupper($classe->niveau->typeScolaire->nom);
            if (str_contains($typeScolaireNom, 'FONDAMENTAL') && !str_contains($typeScolaireNom, 'POST')) {
                $viewName = 'bulletin.bulletin_pdf_fondamental';
            }
        }

        $pdf = Pdf::loadView($viewName, [
            'data' => $bulletinData,
        ]);

        $pdf->setPaper('A4', 'portrait');

        //student name on the file name
        $eleve = Eleve::find($request->integer('eleve_id'));
        $filename = 'bulletin_' . ($bulletinData['classe']['nom'] ?? 'classe') . '_' . ($eleve->nom ?? 'eleve') . '_' . ($eleve->prenom ?? 'eleve') . '.pdf';

        return $pdf->download($filename);
    }
}
