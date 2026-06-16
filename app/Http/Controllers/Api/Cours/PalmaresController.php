<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\NoteConduite;
use App\Models\Trimestre;
use App\Scopes\AcademicYearScope;
use App\Services\ConduiteConfigService;
use App\Services\CurrentAcademicContextService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PalmaresController extends Controller
{
    use \App\Traits\ResolvesAnneeScolaire;

    /** Même libellés que le bulletin / colonne `evaluations.trimestre`. */
    private const TRIMESTRES = ['1er Trimestre', '2e Trimestre', '3e Trimestre'];

    public function __construct(
        private readonly CurrentAcademicContextService $academicContextService
    ) {
    }
    private function authorizePalmaresAccess(Request $request, bool $forPdf = false): void
    {
        $user = $request->user();

        abort_unless($user, 401, 'Non authentifié.');

        $allowed = $forPdf
            ? $user->hasAnyPermissionName(['manage_grades', 'generate_reports', 'print_bulletins'])
            : $user->hasAnyPermissionName(['view_data', 'manage_grades', 'view_reports', 'view_bulletin', 'view_any_bulletin', 'print_bulletins']);

        abort_unless($allowed, 403, 'Vous n’êtes pas autorisé à accéder au palmarès.');
    }

    private static function palmaresCoursCode(Matiere $matiere): string
    {
        $raw = (string) ($matiere->code ?: $matiere->nom ?: '');
        $raw = Str::upper($raw);
        $raw = preg_replace('/\d+/', '', $raw);        // remove numbers
        $raw = preg_replace('/[^A-Z]/', '', $raw);     // keep letters only

        if (!empty($raw)) {
            return Str::substr($raw, 0, 6);
        }

        $fallback = Str::upper(Str::substr(preg_replace('/\s+/', '', (string) ($matiere->nom ?? '')), 0, 6));

        return $fallback ?: 'COURS';
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizePalmaresAccess($request);

        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'mode' => ['nullable', 'in:current,annual'],
            'trimestre' => ['nullable', Rule::in(self::TRIMESTRES)],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
            'type' => ['nullable', 'in:simple,detaille'],
        ]);

        $classeId = $request->integer('classe_id');
        $mode = $request->string('mode')->toString() ?: 'current';
        $anneeScolaireId = $this->resolveAnneeScolaireId($request);
        $requestedTrimestre = $request->filled('trimestre') ? $request->string('trimestre')->toString() : null;
        $type = $request->string('type')->toString() ?: 'simple';
        $currentTrimestre = null;
        $trimestre = null;

        if (!$anneeScolaireId) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $classe = Classe::withoutGlobalScope(AcademicYearScope::class)
            ->with(['school:id,name', 'niveau:id,nom,ordre', 'section:id,nom'])
            ->findOrFail($classeId);
        $conduiteConfig = ConduiteConfigService::resolveForClasse($classe);
        $conduiteMax = $conduiteConfig['max_note'];
        $eleves = $classe->eleves()->orderBy('nom')->orderBy('prenom')->get();

        if ($mode !== 'annual') {
            if ($requestedTrimestre) {
                $currentTrimestre = Trimestre::query()
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->where('nom', $requestedTrimestre)
                    ->first();
                abort_unless($currentTrimestre, 422, 'Le trimestre demandé n\'existe pas pour cette année scolaire.');
            } else {
                $currentTrimestre = $this->academicContextService->requireCurrentTrimestre();
            }

            $trimestre = $currentTrimestre?->nom;
        }

        // Get evaluations
        $evaluationsQuery = Evaluation::with(['notes', 'trimestreModel:id,nom'])
            ->where('classe_id', $classeId)
            ->where('annee_scolaire_id', $anneeScolaireId);

        if ($currentTrimestre) {
            $evaluationsQuery->matchingTrimestre($currentTrimestre);
        }

        $evaluations = $evaluationsQuery->get();

        $cours = Matiere::query()
            ->forClasse($classe)
            ->get()
            ->reject(fn (Matiere $matiere) => $this->isEducationMoraleCourse($matiere))
            ->values();

        $coursMeta = $cours->map(function ($matiere) {
            $code = self::palmaresCoursCode($matiere);
            $meta = [
                'id' => $matiere->id,
                'nom' => $matiere->nom,
                'code' => $code,
                'ponderation_tj' => $matiere->ponderation_tj,
                'ponderation_examen' => $matiere->ponderation_examen,
            ];
            if (Schema::hasColumn('matieres', 'ponderation_competence')) {
                $meta['ponderation_competence'] = $matiere->ponderation_competence;
            }

            return $meta;
        })->values();

        // Get notes conduite
        $notesConduiteQuery = NoteConduite::with('trimestreModel:id,nom')
            ->where('classe_id', $classeId)
            ->where('annee_scolaire_id', $anneeScolaireId);
        if ($currentTrimestre) {
            $notesConduiteQuery->matchingTrimestre($currentTrimestre);
        }
        $notesConduite = $notesConduiteQuery->get();

        $trimestreLabels = $mode === 'annual'
            ? self::TRIMESTRES
            : ($trimestre ? [$trimestre] : self::TRIMESTRES);

        // Calculate totals per student
        $classement = [];
        foreach ($eleves as $eleve) {
            $totalPoints = 0;
            $totalMax = 0;
            $coursPoints = [];
            $coursMaxima = [];
            $coursEchecs = [];
            $hasIncomplete = false;

            foreach ($cours as $matiere) {
                $coursEvals = $evaluations->where('cours_id', $matiere->id);

                $courseTotalAccum = 0.0;
                $courseMaxAccum = 0.0;

                foreach ($trimestreLabels as $trimLabel) {
                    $trimEvals = $this->filterByTrimestreLabel($coursEvals, $trimLabel);
                    [$totalTrim, $maxTotalTrim, $trimIsComplete, $trimHasExpectedNotes] = $this->matiereScoresPourEvaluations(
                        $matiere,
                        $eleve->id,
                        $trimEvals
                    );
                    if ($trimIsComplete) {
                        $courseTotalAccum += $totalTrim;
                    }
                    $courseMaxAccum += $maxTotalTrim;
                    if ($trimHasExpectedNotes && !$trimIsComplete) {
                        $hasIncomplete = true;
                    }
                }

                $totalPoints += $courseTotalAccum;
                $totalMax += $courseMaxAccum;

                if ($type === 'detaille') {
                    $code = self::palmaresCoursCode($matiere);

                    $coursPoints[$code] = round($courseTotalAccum, 2);
                    $coursMaxima[$code] = round($courseMaxAccum, 2);

                    $moitie = $courseMaxAccum > 0 ? ($courseMaxAccum / 2) : 0;
                    if ($moitie > 0 && $courseTotalAccum < $moitie) {
                        $coursEchecs[$code] = round($moitie - $courseTotalAccum, 2);
                    }
                }
            }

            $isComplete = !$hasIncomplete;
            $pourcentageCours = ($isComplete && $totalMax > 0) ? round(($totalPoints / $totalMax) * 100, 1) : null;

            if ($mode === 'annual') {
                $noteConduiteValue = 0.0;
                foreach (self::TRIMESTRES as $trimLabel) {
                    $noteC = $notesConduite->where('eleve_id', $eleve->id)
                        ->first(fn ($note) => $this->getTrimestreLabel($note) === $trimLabel);
                    $noteConduiteValue += $noteC ? (float) $noteC->note : (float) $conduiteMax;
                }
                $conduiteMaxPourEleve = count(self::TRIMESTRES) * $conduiteMax;
                $moyenneConduitePourAppreciation = count(self::TRIMESTRES) > 0
                    ? $noteConduiteValue / count(self::TRIMESTRES)
                    : $noteConduiteValue;
                $appreciationConduite = ConduiteConfigService::buildAppreciation(
                    $moyenneConduitePourAppreciation,
                    $conduiteMax
                );
            } else {
                $noteC = $notesConduite->where('eleve_id', $eleve->id)->first();
                $noteConduiteValue = $noteC ? (float) $noteC->note : (float) $conduiteMax;
                $conduiteMaxPourEleve = $conduiteMax;
                $appreciationConduite = ConduiteConfigService::buildAppreciation($noteConduiteValue, $conduiteMax);
            }

            $globalPoints = $isComplete ? round($totalPoints + $noteConduiteValue, 2) : null;
            $globalMax = round($totalMax + $conduiteMaxPourEleve, 2);
            $pourcentage = ($isComplete && $globalMax > 0) ? round(($globalPoints / $globalMax) * 100, 1) : null;

            $entry = [
                'eleve' => [
                    'id' => $eleve->id,
                    'nom' => $eleve->nom,
                    'prenom' => $eleve->prenom,
                    'matricule' => $eleve->matricule,
                    'sexe' => $eleve->sexe ?? null,
                ],
                // Totaux affichés = cours + conduite (cohérence avec bulletin PDF)
                'total_points' => $globalPoints,
                'total_max' => $globalMax,
                'pourcentage' => $pourcentage,
                // Détail "cours seul" — disponible pour debug ou affichage ultérieur
                'total_points_cours' => round($totalPoints, 2),
                'total_max_cours' => round($totalMax, 2),
                'pourcentage_cours' => $pourcentageCours,
                'is_complete' => $isComplete,
                'classement' => $isComplete ? 'classé' : 'non_classe',
                'conduite' => [
                    'note' => $noteConduiteValue,
                    'max' => $conduiteMaxPourEleve,
                    'appreciation' => $appreciationConduite,
                ],
            ];

            if ($type === 'detaille') {
                $entry['cours_points'] = $coursPoints;
                $entry['cours_max'] = $coursMaxima;
                $entry['echecs'] = $coursEchecs; // points manquants pour atteindre la moitié
                $entry['decision_jury'] = '';
                $entry['nombre_echecs'] = count($coursEchecs);
            }

            $classement[] = $entry;
        }

        // Sort by total descending
        usort($classement, fn($a, $b) => ($b['total_points'] ?? -INF) <=> ($a['total_points'] ?? -INF));

        $classementTousEleves = $classement;

        // Assign ranks
        $rank = 1;
        foreach ($classement as &$entry) {
            $entry['rang'] = $rank++;
        }
        unset($entry);

        $nonClasses = array_values(array_filter(
            $classement,
            fn(array $entry) => !($entry['is_complete'] ?? false)
        ));
        $classement = array_values(array_filter(
            $classement,
            fn(array $entry) => ($entry['is_complete'] ?? false)
        ));

        usort($nonClasses, function (array $a, array $b) {
            return [$a['eleve']['nom'] ?? '', $a['eleve']['prenom'] ?? '']
                <=> [$b['eleve']['nom'] ?? '', $b['eleve']['prenom'] ?? ''];
        });

        $nombreElevesReussis = count(array_filter(
            $classement,
            fn(array $entry) => ($entry['pourcentage'] ?? 0) >= 50
        ));
        $tauxReussite = count($eleves) > 0
            ? round(($nombreElevesReussis / count($eleves)) * 100, 1)
            : null;
        $moyenneClasse = count($classementTousEleves) > 0
            ? round(array_sum(array_map(
                fn (array $entry) => (float) ($entry['pourcentage'] ?? 0),
                $classementTousEleves
            )) / count($classementTousEleves), 1)
            : null;

        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

        return response()->json([
            'data' => [
                'classe' => $classe,
                'annee_scolaire' => $anneeScolaire,
                'trimestre' => $trimestre,
                'trimestre_meta' => $currentTrimestre ? [
                    'id' => $currentTrimestre->id,
                    'nom' => $currentTrimestre->nom,
                    'date_debut' => optional($currentTrimestre->date_debut)?->toDateString(),
                    'date_fin' => optional($currentTrimestre->date_fin)?->toDateString(),
                    'actif' => (bool) $currentTrimestre->actif,
                    'verrouille' => (bool) $currentTrimestre->verrouille,
                ] : null,
                'nombre_eleves' => count($eleves),
                'nombre_eleves_reussis' => $nombreElevesReussis,
                'taux_reussite' => $tauxReussite,
                'moyenne_classe' => $moyenneClasse,
                'mode' => $mode,
                'type' => $type,
                'cours' => $type === 'detaille' ? $coursMeta : null,
                'classement' => $classement,
                'non_classes' => $nonClasses,
            ],
        ]);
    }

    /**
     * Generate a PDF Palmares.
     */
    public function pdf(Request $request)
    {
        $this->authorizePalmaresAccess($request, true);

        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'mode' => ['nullable', 'in:current,annual'],
            'trimestre' => ['nullable', Rule::in(self::TRIMESTRES)],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
            'type' => ['nullable', 'in:simple,detaille'],
        ]);

        $palmaresResponse = $this->index($request);
        $palmaresData = json_decode($palmaresResponse->getContent(), true)['data'];

        $view = ($palmaresData['type'] ?? 'simple') === 'detaille'
            ? 'bulletin.palmares_detaille'
            : 'bulletin.palmares_pdf_non_detaille';

        $pdf = Pdf::loadView($view, [
            'data' => $palmaresData,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'palmares_' . ($palmaresData['classe']['nom'] ?? 'classe') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Display printable palmares HTML.
     */
    public function html(Request $request)
    {
        $this->authorizePalmaresAccess($request);

        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'mode' => ['nullable', 'in:current,annual'],
            'trimestre' => ['nullable', Rule::in(self::TRIMESTRES)],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
            'type' => ['nullable', 'in:simple,detaille'],
        ]);

        $palmaresResponse = $this->index($request);
        $palmaresData = json_decode($palmaresResponse->getContent(), true)['data'];

        $view = ($palmaresData['type'] ?? 'simple') === 'detaille'
            ? 'bulletin.palmares_detaille'
            : 'bulletin.palmares_pdf_non_detaille';

        return response()
            ->view($view, ['data' => $palmaresData])
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Points et barème pour une matière sur un sous-ensemble d'évaluations (ex. un trimestre).
     *
     * @return array{0: float, 1: float, 2: bool, 3: bool} [total, max_total, is_complete, has_expected_notes]
     */
    private function matiereScoresPourEvaluations(Matiere $matiere, int $eleveId, Collection $coursEvals): array
    {
        $ponderationComp = Schema::hasColumn('matieres', 'ponderation_competence')
            ? (float) ($matiere->ponderation_competence ?? 0)
            : 0.0;

        $tjEvals = $coursEvals->whereIn('type_evaluation', ['TJ', 'Interrogation', 'Devoir', 'TP']);
        $compEvals = $ponderationComp > 0
            ? $coursEvals->where('type_evaluation', 'Compétence')
            : collect();
        $examEvals = $coursEvals->where('type_evaluation', 'Examen');

        $tjNote = 0.0;
        $tjMax = 0.0;
        $tjComplete = true;
        foreach ($tjEvals as $eval) {
            $tjMax += (float) $eval->note_maximale;
            $note = $eval->notes->where('eleve_id', $eleveId)->first();
            if ($note) {
                $tjNote += (float) $note->note;
            } else {
                $tjComplete = false;
            }
        }

        $compNote = 0.0;
        $compMax = 0.0;
        $compComplete = true;
        foreach ($compEvals as $eval) {
            $compMax += (float) $eval->note_maximale;
            $note = $eval->notes->where('eleve_id', $eleveId)->first();
            if ($note) {
                $compNote += (float) $note->note;
            } else {
                $compComplete = false;
            }
        }

        $examNote = 0.0;
        $examMax = 0.0;
        $examComplete = true;
        foreach ($examEvals as $eval) {
            $examMax += (float) $eval->note_maximale;
            $note = $eval->notes->where('eleve_id', $eleveId)->first();
            if ($note) {
                $examNote += (float) $note->note;
            } else {
                $examComplete = false;
            }
        }

        $ponderationTj = (float) ($matiere->ponderation_tj ?? 0);
        $ponderationExam = (float) ($matiere->ponderation_examen ?? 0);

        $isTjExpected = $ponderationTj > 0 || $tjMax > 0;
        $isCompExpected = $ponderationComp > 0 || $compMax > 0;
        $isExamExpected = $ponderationExam > 0 || $examMax > 0;

        $tjIncomplete = $isTjExpected && (!$tjComplete || $tjMax <= 0);
        $compIncomplete = $isCompExpected && (!$compComplete || $compMax <= 0);
        $examIncomplete = $isExamExpected && (!$examComplete || $examMax <= 0);
        $isComplete = !$tjIncomplete && !$compIncomplete && !$examIncomplete;

        $scaledTj = $ponderationTj > 0 && $tjMax > 0
            ? round(($tjNote / $tjMax) * $matiere->ponderation_tj, 2)
            : $tjNote;
        $scaledComp = 0.0;
        if ($ponderationComp > 0 && $compMax > 0) {
            $scaledComp = round(($compNote / $compMax) * $ponderationComp, 2);
        }
        $scaledExam = $matiere->ponderation_examen > 0 && $examMax > 0
            ? round(($examNote / $examMax) * $matiere->ponderation_examen, 2)
            : $examNote;

        $maxComp = $ponderationComp > 0 ? $ponderationComp : 0;
        $total = $isComplete ? $scaledTj + $scaledComp + $scaledExam : 0.0;
        $maxTotal = ($matiere->ponderation_tj ?: $tjMax) + $maxComp + ($matiere->ponderation_examen ?: $examMax);
        $hasExpectedNotes = $isTjExpected || $isCompExpected || $isExamExpected;

        return [(float) $total, (float) $maxTotal, $isComplete, $hasExpectedNotes];
    }

    private function filterByTrimestreLabel(Collection $items, string $trimestre): Collection
    {
        return $items
            ->filter(fn ($item) => $this->getTrimestreLabel($item) === $trimestre)
            ->values();
    }

    private function getTrimestreLabel($item): ?string
    {
        return $item->trimestreModel?->nom
            ?? $item->trimestre_label
            ?? $item->trimestre
            ?? null;
    }

    private function isEducationMoraleCourse(Matiere $matiere): bool
    {
        $name = Str::of((string) ($matiere->nom ?? ''))
            ->ascii()
            ->lower()
            ->squish()
            ->toString();

        return $name === 'education morale';
    }
}
