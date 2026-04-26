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
use Illuminate\Support\Collection;

class BulletinController extends Controller
{
    private const TRIMESTRES = ['1er Trimestre', '2e Trimestre', '3e Trimestre'];

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

        // Toujours charger TOUS les élèves de la classe pour calculer correctement
        // les rangs et l'effectif. Le filtre eleve_id sera appliqué sur la sortie finale.
        $eleves = $classe->eleves()->orderBy('nom')->orderBy('prenom')->get();
        $requestedEleveId = $request->filled('eleve_id') ? $request->integer('eleve_id') : null;

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

        $requestedTrimestres = $trimestre ? [$trimestre] : self::TRIMESTRES;

        // Build bulletin data per student
        $bulletins = [];
        foreach ($eleves as $eleve) {
            $coursData = [];
            $trimestreSummaries = [];

            foreach ($requestedTrimestres as $currentTrimestre) {
                $trimestreSummaries[$currentTrimestre] = [
                    'total_points' => 0,
                    'total_max' => 0,
                    'has_incomplete' => false,
                ];
            }

            foreach ($cours as $matiere) {
                $coursEvals = $evaluations->where('cours_id', $matiere->id);
                $coursTrimestres = [];

                foreach ($requestedTrimestres as $currentTrimestre) {
                    $summary = $this->buildCourseSummary(
                        $coursEvals->where('trimestre', $currentTrimestre),
                        $eleve->id,
                        $matiere
                    );

                    $coursTrimestres[$currentTrimestre] = $summary;
                    $trimestreSummaries[$currentTrimestre]['total_max'] += $summary['max_total'];

                    if (!is_null($summary['note_total'])) {
                        $trimestreSummaries[$currentTrimestre]['total_points'] += $summary['note_total'];
                    }

                    if ($summary['has_expected_notes'] && !$summary['is_complete']) {
                        $trimestreSummaries[$currentTrimestre]['has_incomplete'] = true;
                    }
                }

                $baseSummary = $this->resolveBaseCourseSummary($coursTrimestres);
                $annualSummary = $this->buildAnnualCourseSummary($coursTrimestres, $baseSummary, count($requestedTrimestres));
                $displayNotesSummary = $trimestre
                    ? ($coursTrimestres[$trimestre] ?? $this->emptySummary())
                    : $annualSummary;

                $coursData[] = [
                    'cours_id' => $matiere->id,
                    'nom' => $matiere->nom,
                    'categorie' => Schema::hasColumn('matieres', 'categorie_cours_id') ? $matiere->categorieCours?->nom : null,
                    'categorie_ordre' => Schema::hasColumn('matieres', 'categorie_cours_id') ? ($matiere->categorieCours?->ordre ?? 99) : 99,
                    'max_tj' => $baseSummary['max_tj'],
                    'max_examen' => $baseSummary['max_examen'],
                    'max_total' => $baseSummary['max_total'],
                    'note_tj' => $displayNotesSummary['note_tj'],
                    'note_examen' => $displayNotesSummary['note_examen'],
                    'note_total' => $displayNotesSummary['note_total'],
                    'credit_heures' => (int)$matiere->credit_heures,
                    'trimestres' => $coursTrimestres,
                    'annuel' => $annualSummary,
                ];
            }

            $bulletinTrimestres = [];
            $annualTotalPoints = 0;
            $annualTotalMax = 0;
            $annualHasIncomplete = false;
            $notesConduiteByTrimestre = $notesConduite
                ->where('eleve_id', $eleve->id)
                ->keyBy('trimestre');

            foreach ($requestedTrimestres as $currentTrimestre) {
                $summary = $trimestreSummaries[$currentTrimestre];
                $isComplete = !$summary['has_incomplete'];
                $noteConduite = $notesConduiteByTrimestre->get($currentTrimestre);
                $conduiteValue = $noteConduite ? $noteConduite->note : 60;
                $conduiteMax = 60;

                $globalPoints = $isComplete
                    ? round($summary['total_points'] + $conduiteValue, 2)
                    : null;
                $globalMax = round($summary['total_max'] + $conduiteMax, 2);
                $globalPourcentage = ($isComplete && $globalMax > 0)
                    ? round(($globalPoints / $globalMax) * 100, 1)
                    : null;

                $bulletinTrimestres[$currentTrimestre] = [
                    // Cours seuls (sans conduite) — utilisés par les vues PDF
                    'total_points' => $isComplete ? round($summary['total_points'], 2) : null,
                    'total_max' => round($summary['total_max'], 2),
                    'pourcentage' => ($isComplete && $summary['total_max'] > 0)
                        ? round(($summary['total_points'] / $summary['total_max']) * 100, 1)
                        : null,
                    // Aliases explicites (mêmes valeurs, nommage non ambigu)
                    'total_points_cours' => $isComplete ? round($summary['total_points'], 2) : null,
                    'total_max_cours' => round($summary['total_max'], 2),
                    'pourcentage_cours' => ($isComplete && $summary['total_max'] > 0)
                        ? round(($summary['total_points'] / $summary['total_max']) * 100, 1)
                        : null,
                    // Totaux GLOBAUX (cours + conduite) — affichés dans l'UI et le grand total PDF
                    'total_points_global' => $globalPoints,
                    'total_max_global' => $globalMax,
                    'pourcentage_global' => $globalPourcentage,
                    'is_complete' => $isComplete,
                    'conduite' => [
                        'note' => $conduiteValue,
                        'max' => $conduiteMax,
                        'appreciation' => $this->buildConduiteAppreciation($conduiteValue),
                    ],
                ];

                $annualTotalMax += $summary['total_max'];
                $annualHasIncomplete = $annualHasIncomplete || !$isComplete;

                if ($isComplete) {
                    $annualTotalPoints += $summary['total_points'];
                }
            }

            $annualConduiteNote = collect($requestedTrimestres)
                ->sum(fn ($currentTrimestre) => $bulletinTrimestres[$currentTrimestre]['conduite']['note'] ?? 60);
            $annualConduiteMax = count($requestedTrimestres) * 60;
            $annualIsComplete = !$annualHasIncomplete;
            $annualDisplayPoints = $annualIsComplete ? round($annualTotalPoints, 2) : null;
            $annualGlobalPoints = $annualIsComplete
                ? round($annualTotalPoints + $annualConduiteNote, 2)
                : null;
            $annualGlobalMax = round($annualTotalMax + $annualConduiteMax, 2);
            $annualGlobalPourcentage = ($annualIsComplete && $annualGlobalMax > 0)
                ? round(($annualGlobalPoints / $annualGlobalMax) * 100, 1)
                : null;
            $displayBulletin = $trimestre
                ? ($bulletinTrimestres[$trimestre] ?? null)
                : null;

            $bulletins[] = [
                'eleve' => [
                    'id' => $eleve->id,
                    'nom' => $eleve->nom,
                    'prenom' => $eleve->prenom,
                    'matricule' => $eleve->matricule,
                ],
                'cours' => $coursData,
                // Cours seuls (sans conduite) — conservés pour rétrocompatibilité PDF
                'total_points' => $trimestre
                    ? ($displayBulletin['total_points'] ?? null)
                    : $annualDisplayPoints,
                'total_max' => $trimestre
                    ? ($displayBulletin['total_max'] ?? 0)
                    : round($annualTotalMax, 2),
                'pourcentage' => $trimestre
                    ? ($displayBulletin['pourcentage'] ?? null)
                    : (($annualIsComplete && $annualTotalMax > 0) ? round(($annualTotalPoints / $annualTotalMax) * 100, 1) : null),
                // Totaux GLOBAUX (cours + conduite) — utilisés par l'UI et cohérents avec le grand total du PDF
                'total_points_global' => $trimestre
                    ? ($displayBulletin['total_points_global'] ?? null)
                    : $annualGlobalPoints,
                'total_max_global' => $trimestre
                    ? ($displayBulletin['total_max_global'] ?? 0)
                    : $annualGlobalMax,
                'pourcentage_global' => $trimestre
                    ? ($displayBulletin['pourcentage_global'] ?? null)
                    : $annualGlobalPourcentage,
                'is_complete' => $trimestre
                    ? ($displayBulletin['is_complete'] ?? false)
                    : $annualIsComplete,
                'conduite' => [
                    'note' => $trimestre
                        ? ($displayBulletin['conduite']['note'] ?? 60)
                        : $annualConduiteNote,
                    'max' => $trimestre ? 60 : $annualConduiteMax,
                    'appreciation' => $trimestre
                        ? ($displayBulletin['conduite']['appreciation'] ?? $this->buildConduiteAppreciation(60))
                        : $this->buildConduiteAppreciation(
                            count($requestedTrimestres) > 0 ? ($annualConduiteNote / count($requestedTrimestres)) : 60
                        ),
                ],
                'trimestres' => $bulletinTrimestres,
                'annuel' => [
                    'total_points' => $annualDisplayPoints,
                    'total_max' => round($annualTotalMax, 2),
                    'pourcentage' => ($annualIsComplete && $annualTotalMax > 0)
                        ? round(($annualTotalPoints / $annualTotalMax) * 100, 1)
                        : null,
                    'total_points_cours' => $annualDisplayPoints,
                    'total_max_cours' => round($annualTotalMax, 2),
                    'pourcentage_cours' => ($annualIsComplete && $annualTotalMax > 0)
                        ? round(($annualTotalPoints / $annualTotalMax) * 100, 1)
                        : null,
                    'total_points_global' => $annualGlobalPoints,
                    'total_max_global' => $annualGlobalMax,
                    'pourcentage_global' => $annualGlobalPourcentage,
                    'is_complete' => $annualIsComplete,
                    'conduite' => [
                        'note' => $annualConduiteNote,
                        'max' => $annualConduiteMax,
                        'appreciation' => $this->buildConduiteAppreciation(
                            count($requestedTrimestres) > 0 ? ($annualConduiteNote / count($requestedTrimestres)) : 60
                        ),
                    ],
                ],
            ];
        }

        $annualRanks = $this->buildRanks($bulletins, fn ($bulletin) => $bulletin['annuel']);
        $trimestreRanks = [];
        foreach ($requestedTrimestres as $currentTrimestre) {
            $trimestreRanks[$currentTrimestre] = $this->buildRanks(
                $bulletins,
                fn ($bulletin) => $bulletin['trimestres'][$currentTrimestre] ?? null
            );
        }

        foreach ($bulletins as &$bulletin) {
            foreach ($requestedTrimestres as $currentTrimestre) {
                if (isset($bulletin['trimestres'][$currentTrimestre])) {
                    $bulletin['trimestres'][$currentTrimestre]['rang'] =
                        $trimestreRanks[$currentTrimestre][$bulletin['eleve']['id']] ?? null;
                }
            }

            $bulletin['annuel']['rang'] = $annualRanks[$bulletin['eleve']['id']] ?? null;
            $bulletin['rang'] = $trimestre
                ? ($bulletin['trimestres'][$trimestre]['rang'] ?? null)
                : $bulletin['annuel']['rang'];
        }
        unset($bulletin);

        // Effectif total de la classe (gardé même quand on extrait un seul bulletin
        // afin de conserver l'information "rang X / N").
        $effectifClasse = count($eleves);

        // Si un élève précis est demandé, on filtre la sortie APRÈS calcul des rangs
        // pour que le rang reflète bien la position dans toute la classe.
        if ($requestedEleveId) {
            $bulletins = array_values(array_filter(
                $bulletins,
                fn ($bulletin) => ($bulletin['eleve']['id'] ?? null) === $requestedEleveId
            ));
        }

        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

        return response()->json([
            'data' => [
                'classe' => $classe,
                'annee_scolaire' => $anneeScolaire,
                'trimestre' => $trimestre,
                'nombre_eleves' => $effectifClasse,
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

    private function buildCourseSummary(Collection $evaluations, int $eleveId, Matiere $matiere): array
    {
        $tjEvals = $evaluations->whereIn('type_evaluation', ['TJ', 'Interrogation', 'Devoir', 'TP']);
        $examEvals = $evaluations->where('type_evaluation', 'Examen');

        $tjNote = 0;
        $tjMax = 0;
        $tjComplete = true;

        foreach ($tjEvals as $eval) {
            $tjMax += $eval->note_maximale;
            $note = $eval->notes->where('eleve_id', $eleveId)->first();

            if (is_null($note)) {
                $tjComplete = false;
            } else {
                $tjNote += $note->note;
            }
        }

        if (!$tjComplete) {
            $tjNote = null;
        }

        $examNote = 0;
        $examMax = 0;
        $examComplete = true;

        foreach ($examEvals as $eval) {
            $examMax += $eval->note_maximale;
            $note = $eval->notes->where('eleve_id', $eleveId)->first();

            if (is_null($note)) {
                $examComplete = false;
            } else {
                $examNote += $note->note;
            }
        }

        if (!$examComplete) {
            $examNote = null;
        }

        $ponderationTj = Schema::hasColumn('matieres', 'ponderation_tj') ? (float) ($matiere->ponderation_tj ?? 0) : 0;
        $ponderationExam = Schema::hasColumn('matieres', 'ponderation_examen') ? (float) ($matiere->ponderation_examen ?? 0) : 0;

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

        return [
            'max_tj' => $ponderationTj ?: $tjMax,
            'max_examen' => $ponderationExam ?: $examMax,
            'max_total' => ($ponderationTj ?: $tjMax) + ($ponderationExam ?: $examMax),
            'note_tj' => $scaledTj,
            'note_examen' => $scaledExam,
            'note_total' => $total,
            'is_complete' => !($isTjExpected && is_null($scaledTj)) && !($isExamExpected && is_null($scaledExam)),
            'has_expected_notes' => $isTjExpected || $isExamExpected,
        ];
    }

    private function buildAnnualCourseSummary(array $trimestreSummaries, array $baseSummary, int $trimestreCount): array
    {
        $annual = [
            'max_tj' => ($baseSummary['max_tj'] ?? 0) * $trimestreCount,
            'max_examen' => ($baseSummary['max_examen'] ?? 0) * $trimestreCount,
            'max_total' => ($baseSummary['max_total'] ?? 0) * $trimestreCount,
            'note_tj' => 0,
            'note_examen' => 0,
            'note_total' => 0,
            'is_complete' => true,
            'has_expected_notes' => false,
        ];

        foreach ($trimestreSummaries as $summary) {
            $annual['has_expected_notes'] = $annual['has_expected_notes'] || ($summary['has_expected_notes'] ?? false);

            if (!is_null($summary['note_tj'])) {
                $annual['note_tj'] += $summary['note_tj'];
            } elseif (($summary['has_expected_notes'] ?? false) && ($summary['max_tj'] ?? 0) > 0) {
                $annual['note_tj'] = null;
            }

            if (!is_null($summary['note_examen'])) {
                $annual['note_examen'] += $summary['note_examen'];
            } elseif (($summary['has_expected_notes'] ?? false) && ($summary['max_examen'] ?? 0) > 0) {
                $annual['note_examen'] = null;
            }

            if (!is_null($summary['note_total'])) {
                $annual['note_total'] += $summary['note_total'];
            } elseif ($summary['has_expected_notes'] ?? false) {
                $annual['note_total'] = null;
                $annual['is_complete'] = false;
            }

            if (($summary['has_expected_notes'] ?? false) && !($summary['is_complete'] ?? false)) {
                $annual['is_complete'] = false;
            }
        }

        if (!$annual['has_expected_notes']) {
            $annual['note_tj'] = null;
            $annual['note_examen'] = null;
            $annual['note_total'] = null;
        }

        return $annual;
    }

    private function resolveBaseCourseSummary(array $trimestreSummaries): array
    {
        foreach ($trimestreSummaries as $summary) {
            if (($summary['max_total'] ?? 0) > 0 || ($summary['max_tj'] ?? 0) > 0 || ($summary['max_examen'] ?? 0) > 0) {
                return $summary;
            }
        }

        return $this->emptySummary();
    }

    private function buildConduiteAppreciation(float|int $noteConduiteValue): string
    {
        if ($noteConduiteValue >= 50) {
            return 'Excellent';
        }
        if ($noteConduiteValue >= 40) {
            return 'Bon';
        }
        if ($noteConduiteValue >= 30) {
            return 'Passable';
        }
        if ($noteConduiteValue >= 20) {
            return 'Mauvais';
        }

        return 'Très mauvais';
    }

    private function buildRanks(array $bulletins, callable $extractor): array
    {
        $extractRankPoints = static function (?array $summary): ?float {
            if (!$summary) {
                return null;
            }

            return $summary['total_points_global']
                ?? $summary['total_points']
                ?? null;
        };

        $rankable = array_values(array_filter($bulletins, function ($bulletin) use ($extractor, $extractRankPoints) {
            $summary = $extractor($bulletin);

            return $summary
                && ($summary['is_complete'] ?? false)
                && !is_null($extractRankPoints($summary));
        }));

        usort($rankable, function ($a, $b) use ($extractor, $extractRankPoints) {
            return ($extractRankPoints($extractor($b)) ?? 0)
                <=> ($extractRankPoints($extractor($a)) ?? 0);
        });

        $ranks = [];
        $rank = 1;
        foreach ($rankable as $bulletin) {
            $ranks[$bulletin['eleve']['id']] = $rank++;
        }

        return $ranks;
    }

    private function emptySummary(): array
    {
        return [
            'max_tj' => 0,
            'max_examen' => 0,
            'max_total' => 0,
            'note_tj' => null,
            'note_examen' => null,
            'note_total' => null,
            'is_complete' => false,
            'has_expected_notes' => false,
        ];
    }
}
