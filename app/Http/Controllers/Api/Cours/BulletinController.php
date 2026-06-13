<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\NoteConduite;
use App\Models\Trimestre;
use App\Scopes\AcademicYearScope;
use App\Services\CurrentAcademicContextService;
use App\Models\Role;
use App\Services\ConduiteConfigService;
use App\Support\AcademicCycleHelper;
use App\Support\BulletinCourseLayout;
use App\Traits\ResolvesAnneeScolaire;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BulletinController extends Controller
{
    use ResolvesAnneeScolaire;

    public function __construct(
        private readonly CurrentAcademicContextService $academicContextService
    ) {
    }

    private const TRIMESTRES = ['1er Trimestre', '2e Trimestre', '3e Trimestre'];

    /**
     * Generate bulletin data for a student or a class.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'mode' => ['nullable', 'in:current,annual'],
            'trimestre' => ['nullable', 'string', 'max:100'],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
            'eleve_id' => ['nullable', 'exists:eleves,id'],
        ]);

        $classeId = $request->integer('classe_id');
        $mode = $request->string('mode')->toString() ?: 'current';
        $anneeScolaireId = $this->resolveAnneeScolaireId($request);
        $requestedEleveId = $request->filled('eleve_id') ? $request->integer('eleve_id') : null;

        if ($request->user()?->hasRole(Role::PARENT)) {
            $anneeScolaireId = AnneeScolaire::withoutGlobalScopes()->active()->value('id')
                ?? $anneeScolaireId;
            if ($anneeScolaireId) {
                \App\Services\AcademicYearService::setCurrent($anneeScolaireId);
            }
        }

        if (!$anneeScolaireId) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $this->authorizeBulletinRequest($request, $classeId, $requestedEleveId, $anneeScolaireId);

        $isParent = $request->user()?->hasRole(Role::PARENT);
        [$trimestre, $trimestreModel] = $this->resolveBulletinPeriod($request, $anneeScolaireId, $mode, $isParent);

        $displayTrimestres = $this->resolveBulletinTrimestres($trimestre, $trimestreModel, $anneeScolaireId);
        $cachePeriod = $trimestre ? "current:{$trimestre}" : 'annual';
        $cacheKey = "bulletin:{$classeId}:{$anneeScolaireId}:{$cachePeriod}:" . implode(',', $displayTrimestres);
        $ttl = 600;

        $data = Cache::remember(
            $cacheKey,
            $ttl,
            fn() => $this->buildBulletinData($classeId, $trimestre, $anneeScolaireId, $trimestreModel, $displayTrimestres)
        );

        if ($requestedEleveId) {
            $data['bulletins'] = array_values(array_filter(
                $data['bulletins'],
                fn($bulletin) => ($bulletin['eleve']['id'] ?? null) === $requestedEleveId
            ));
        }

        $data['mode'] = $mode;
        $data['trimestre_meta'] = $trimestreModel ? [
            'id' => $trimestreModel->id,
            'nom' => $trimestreModel->nom,
            'date_debut' => optional($trimestreModel->date_debut)?->toDateString(),
            'date_fin' => optional($trimestreModel->date_fin)?->toDateString(),
            'actif' => (bool) $trimestreModel->actif,
            'verrouille' => (bool) $trimestreModel->verrouille,
        ] : null;

        return response()->json(['data' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBulletinData(
        int $classeId,
        ?string $trimestre,
        int $anneeScolaireId,
        ?Trimestre $trimestreModel = null,
        ?array $displayTrimestres = null
    ): array
    {
        $classe = Classe::withoutGlobalScope(AcademicYearScope::class)
            ->with(['school:id,name', 'niveau:id,nom,ordre', 'section:id,nom'])
            ->findOrFail($classeId);

        $conduiteConfig = ConduiteConfigService::resolveForClasse($classe);
        $conduiteMax = $conduiteConfig['max_note'];

        $eleves = $classe->eleves()->orderBy('nom')->orderBy('prenom')->get();

        $hasCategorieCours = Schema::hasColumn('matieres', 'categorie_cours_id');
        $hasPonderationTj = Schema::hasColumn('matieres', 'ponderation_tj');
        $hasPonderationComp = Schema::hasColumn('matieres', 'ponderation_competence');
        $hasPonderationExam = Schema::hasColumn('matieres', 'ponderation_examen');

        $coursQuery = Matiere::query()->forClasse($classe);
        if ($hasCategorieCours) {
            $coursQuery->with('categorieCours');
        }
        $cours = $coursQuery->get();

        // Get evaluations for this class/year
        $evaluationsQuery = Evaluation::with(['notes', 'trimestreModel:id,nom'])
            ->where('classe_id', $classeId)
            ->where('annee_scolaire_id', $anneeScolaireId);

        $evaluations = $evaluationsQuery->get();

        // Get notes conduite
        $notesConduiteQuery = NoteConduite::with('trimestreModel:id,nom')
            ->where('classe_id', $classeId)
            ->where('annee_scolaire_id', $anneeScolaireId);
        $notesConduite = $notesConduiteQuery->get();

        $requestedTrimestres = $displayTrimestres
            ?: $this->resolveBulletinTrimestres($trimestre, $trimestreModel, $anneeScolaireId);
        $annualTrimestreCount = count(self::TRIMESTRES);

        // Build bulletin data per student
        $bulletins = [];
        foreach ($eleves as $eleve) {
            $coursData = [];
            $trimestreSummaries = [];
            $annualCourseTotalMax = 0;

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
                        $this->filterByTrimestreLabel($coursEvals, $currentTrimestre),
                        $eleve->id,
                        $matiere,
                        $hasPonderationTj,
                        $hasPonderationComp,
                        $hasPonderationExam
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
                $annualSummary = $this->buildAnnualCourseSummary($coursTrimestres, $baseSummary, $annualTrimestreCount);
                $annualCourseTotalMax += $annualSummary['max_total'] ?? 0;
                $displayNotesSummary = $trimestre
                    ? ($coursTrimestres[$trimestre] ?? $this->emptySummary())
                    : $annualSummary;

                $coursData[] = [
                    'cours_id' => $matiere->id,
                    'nom' => $matiere->nom,
                    'categorie' => $hasCategorieCours ? $matiere->categorieCours?->nom : null,
                    'categorie_ordre' => $hasCategorieCours ? ($matiere->categorieCours?->ordre ?? 99) : 99,
                    'display_group' => BulletinCourseLayout::isStandaloneCategory(
                        $hasCategorieCours ? $matiere->categorieCours?->nom : null
                    ) ? 'standalone' : 'grouped',
                    'max_tj' => $baseSummary['max_tj'],
                    'max_competence' => $baseSummary['max_competence'],
                    'has_competence_track' => $baseSummary['has_competence_track'],
                    'max_examen' => $baseSummary['max_examen'],
                    'max_total' => $baseSummary['max_total'],
                    'note_tj' => $displayNotesSummary['note_tj'],
                    'note_competence' => $displayNotesSummary['note_competence'],
                    'note_examen' => $displayNotesSummary['note_examen'],
                    'note_total' => $displayNotesSummary['note_total'],
                    'credit_heures' => (int) $matiere->credit_heures,
                    'trimestres' => $coursTrimestres,
                    'annuel' => $annualSummary,
                ];
            }

            $bulletinTrimestres = [];
            $annualTotalPoints = 0;
            $annualTotalMax = $annualCourseTotalMax;
            $annualHasIncomplete = false;
            $notesConduiteByTrimestre = $this->keyNotesConduiteByTrimestreLabel(
                $notesConduite->where('eleve_id', $eleve->id)
            );

            foreach ($requestedTrimestres as $currentTrimestre) {
                $summary = $trimestreSummaries[$currentTrimestre];
                $isComplete = !$summary['has_incomplete'];
                $noteConduite = $notesConduiteByTrimestre->get($currentTrimestre);
                $conduiteValue = $noteConduite ? $noteConduite->note : $conduiteMax;

                $globalPoints = $isComplete
                    ? round($summary['total_points'] + $conduiteValue, 2)
                    : null;
                $globalMaxTrimestre = round($summary['total_max'] + $conduiteMax, 2);
                $globalPourcentage = ($isComplete && $globalMaxTrimestre > 0)
                    ? round(($globalPoints / $globalMaxTrimestre) * 100, 1)
                    : null;

                $bulletinTrimestres[$currentTrimestre] = [
                    'total_points' => $isComplete ? round($summary['total_points'], 2) : null,
                    'total_max' => round($summary['total_max'], 2),
                    'pourcentage' => ($isComplete && $summary['total_max'] > 0)
                        ? round(($summary['total_points'] / $summary['total_max']) * 100, 1)
                        : null,
                    'total_points_cours' => $isComplete ? round($summary['total_points'], 2) : null,
                    'total_max_cours' => round($summary['total_max'], 2),
                    'pourcentage_cours' => ($isComplete && $summary['total_max'] > 0)
                        ? round(($summary['total_points'] / $summary['total_max']) * 100, 1)
                        : null,
                    'total_points_global' => $globalPoints,
                    'total_max_global' => $globalMaxTrimestre,
                    'pourcentage_global' => $globalPourcentage,
                    'is_complete' => $isComplete,
                    'classement' => $isComplete ? 'classé' : 'non_classe',
                    'conduite' => [
                        'note' => $conduiteValue,
                        'max' => $conduiteMax,
                        'appreciation' => ConduiteConfigService::buildAppreciation($conduiteValue, $conduiteMax),
                    ],
                ];

                $annualHasIncomplete = $annualHasIncomplete || !$isComplete;

                if ($isComplete) {
                    $annualTotalPoints += $summary['total_points'];
                }
            }

            $annualConduiteNote = collect($requestedTrimestres)
                ->sum(fn($currentTrimestre) => $bulletinTrimestres[$currentTrimestre]['conduite']['note'] ?? $conduiteMax);
            $annualConduiteMax = $annualTrimestreCount * $conduiteMax;
            $annualIsComplete = !$annualHasIncomplete && count($requestedTrimestres) === $annualTrimestreCount;
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
                'classement' => ($trimestre
                    ? ($displayBulletin['is_complete'] ?? false)
                    : $annualIsComplete) ? 'classé' : 'non_classe',
                'conduite' => [
                    'note' => $trimestre
                        ? ($displayBulletin['conduite']['note'] ?? $conduiteMax)
                        : $annualConduiteNote,
                    'max' => $trimestre ? $conduiteMax : $annualConduiteMax,
                    'appreciation' => $trimestre
                        ? ($displayBulletin['conduite']['appreciation'] ?? ConduiteConfigService::buildAppreciation($conduiteMax, $conduiteMax))
                        : ConduiteConfigService::buildAppreciation(
                            count($requestedTrimestres) > 0 ? ($annualConduiteNote / count($requestedTrimestres)) : $conduiteMax,
                            $conduiteMax
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
                    'classement' => $annualIsComplete ? 'classé' : 'non_classe',
                    'conduite' => [
                        'note' => $annualConduiteNote,
                        'max' => $annualConduiteMax,
                        'appreciation' => ConduiteConfigService::buildAppreciation(
                            count($requestedTrimestres) > 0 ? ($annualConduiteNote / count($requestedTrimestres)) : $conduiteMax,
                            $conduiteMax
                        ),
                    ],
                ],
            ];
        }

        $annualRanks = $this->buildRanks($bulletins, fn($bulletin) => $bulletin['annuel']);
        $trimestreRanks = [];
        foreach ($requestedTrimestres as $currentTrimestre) {
            $trimestreRanks[$currentTrimestre] = $this->buildRanks(
                $bulletins,
                fn($bulletin) => $bulletin['trimestres'][$currentTrimestre] ?? null
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

        $anneeScolaire = AnneeScolaire::find($anneeScolaireId);

        return [
            'classe' => $classe,
            'annee_scolaire' => $anneeScolaire,
            'trimestre' => $trimestre,
            'nombre_eleves' => $effectifClasse,
            'bulletins' => $bulletins,
        ];
    }

    /**
     * Generate a PDF bulletin.
     */
    public function pdf(Request $request)
    {
        if ($request->user()?->hasRole(Role::PARENT)) {
            abort(403, 'Le téléchargement du bulletin PDF n\'est pas disponible pour les comptes parent.');
        }

        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'mode' => ['nullable', 'in:current,annual'],
            'trimestre' => ['nullable', 'string', 'max:100'],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
            'eleve_id' => ['nullable', 'exists:eleves,id'],
        ]);

        [$bulletinData, $viewName] = $this->preparePrintableBulletin($request);

        $pdf = Pdf::loadView($viewName, ['data' => $bulletinData]);
        $pdf->setPaper('A4', 'portrait');

        $eleveNom = 'eleve';
        $elevePrenom = 'eleve';
        if ($request->filled('eleve_id') && !empty($bulletinData['bulletins'])) {
            $eleveNom = $bulletinData['bulletins'][0]['eleve']['nom'] ?? 'eleve';
            $elevePrenom = $bulletinData['bulletins'][0]['eleve']['prenom'] ?? 'eleve';
        }
        $filename = 'bulletin_' . ($bulletinData['classe']['nom'] ?? 'classe') . '_' . $eleveNom . '_' . $elevePrenom . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Display printable bulletin HTML.
     */
    public function html(Request $request)
    {
        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'mode' => ['nullable', 'in:current,annual'],
            'trimestre' => ['nullable', 'string', 'max:100'],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
            'eleve_id' => ['nullable', 'exists:eleves,id'],
        ]);

        [$bulletinData, $viewName] = $this->preparePrintableBulletin($request);

        return response()
            ->view($viewName, ['data' => $bulletinData])
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * @return array{0: array, 1: string}
     */
    private function preparePrintableBulletin(Request $request): array
    {
        $classeId = $request->integer('classe_id');
        $mode = $request->string('mode')->toString() ?: 'current';
        $anneeScolaireId = $this->resolveAnneeScolaireId($request);

        if (!$anneeScolaireId) {
            abort(422, 'Aucune année scolaire active.');
        }

        $requestedEleveIdForAuth = $request->filled('eleve_id') ? $request->integer('eleve_id') : null;
        $this->authorizeBulletinRequest($request, $classeId, $requestedEleveIdForAuth, $anneeScolaireId);

        $isParent = $request->user()?->hasRole(Role::PARENT);
        [$trimestre, $trimestreModel] = $this->resolveBulletinPeriod($request, $anneeScolaireId, $mode, $isParent);

        $displayTrimestres = $this->resolveBulletinTrimestres($trimestre, $trimestreModel, $anneeScolaireId);
        $cachePeriod = $trimestre ? "current:{$trimestre}" : 'annual';
        $cacheKey = "bulletin:{$classeId}:{$anneeScolaireId}:{$cachePeriod}:" . implode(',', $displayTrimestres);
        $bulletinData = Cache::remember(
            $cacheKey,
            600,
            fn() => $this->buildBulletinData($classeId, $trimestre, $anneeScolaireId, $trimestreModel, $displayTrimestres)
        );

        if ($request->filled('eleve_id')) {
            $requestedEleveId = $request->integer('eleve_id');
            $bulletinData['bulletins'] = array_values(array_filter(
                $bulletinData['bulletins'],
                fn($b) => ($b['eleve']['id'] ?? null) === $requestedEleveId
            ));
        }

        $classe = Classe::withoutGlobalScope(AcademicYearScope::class)
            ->with(['niveau.cycleScolaire'])
            ->find($classeId);

        $viewName = AcademicCycleHelper::usesPostFondamentalBulletinLayout($classe->niveau)
            ? 'bulletin.bulletin_pdf_post_fondamental'
            : 'bulletin.bulletin_pdf_fondamental';

        return [$bulletinData, $viewName];
    }

    /**
     * @return array{0: ?string, 1: ?Trimestre}
     */
    private function resolveBulletinPeriod(Request $request, int $anneeScolaireId, string $mode, bool $isParent = false): array
    {
        if ($mode === 'annual') {
            return [null, null];
        }

        if (!$isParent && $request->filled('trimestre')) {
            $nom = $request->string('trimestre')->toString();
            $model = Trimestre::query()
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->where('nom', $nom)
                ->first();

            return [$nom, $model];
        }

        Context::add('annee_scolaire_id', $anneeScolaireId);

        try {
            $current = $this->academicContextService->requireCurrentTrimestre();

            return [$current->nom, $current];
        } catch (UnprocessableEntityHttpException) {
            return [null, null];
        }
    }

    private function authorizeBulletinRequest(Request $request, int $classeId, ?int $eleveId, int $anneeScolaireId): void
    {
        $user = $request->user();

        abort_unless(
            $user && $user->hasAnyPermissionName([
                'view_data',
                'view_bulletin',
                'view_any_bulletin',
                'manage_grades',
            ]),
            403
        );

        if ($user->hasRole(Role::PARENT)) {
            abort_if($eleveId === null, 403, 'Accès bulletin parent : paramètre eleve_id requis.');
            abort_unless($user->isLinkedParentOfEleve($eleveId), 403);
            $matchesInscription = Inscription::withoutGlobalScopes()
                ->where('eleve_id', $eleveId)
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->whereHas('affectation', function ($q) use ($classeId) {
                    $q->where('classe_id', $classeId);
                })
                ->exists();
            abort_unless($matchesInscription, 403, 'Classe ou année scolaire incompatible avec cet élève.');
        }
    }

    private function buildCourseSummary(
        Collection $evaluations,
        int $eleveId,
        Matiere $matiere,
        bool $hasPonderationTj = true,
        bool $hasPonderationComp = false,
        bool $hasPonderationExam = true
    ): array {
        $ponderationTj = $hasPonderationTj ? (float) ($matiere->ponderation_tj ?? 0) : 0;
        $ponderationComp = $hasPonderationComp ? (float) ($matiere->ponderation_competence ?? 0) : 0;
        $ponderationExam = $hasPonderationExam ? (float) ($matiere->ponderation_examen ?? 0) : 0;
        $trackCompetence = $ponderationComp > 0;

        $tjEvals = $evaluations->whereIn('type_evaluation', ['TJ', 'Interrogation', 'Devoir', 'TP']);
        $compEvals = $trackCompetence
            ? $evaluations->where('type_evaluation', 'Compétence')
            : collect();
        $examEvals = $evaluations->where('type_evaluation', 'Examen');

        $tjNote = 0;
        $tjMax = 0;
        $tjComplete = true;
        foreach ($tjEvals as $eval) {
            $tjMax += (float) $eval->note_maximale;
            $note = $eval->notes->where('eleve_id', $eleveId)->first();
            if (is_null($note)) {
                $tjComplete = false;
            } else {
                $tjNote += (float) $note->note;
            }
        }
        if (!$tjComplete) {
            $tjNote = null;
        }

        $compNote = 0;
        $compMax = 0;
        $compComplete = true;
        foreach ($compEvals as $eval) {
            $compMax += (float) $eval->note_maximale;
            $note = $eval->notes->where('eleve_id', $eleveId)->first();
            if (is_null($note)) {
                $compComplete = false;
            } else {
                $compNote += (float) $note->note;
            }
        }
        if ($trackCompetence && !$compEvals->isEmpty() && !$compComplete) {
            $compNote = null;
        }

        $examNote = 0;
        $examMax = 0;
        $examComplete = true;
        foreach ($examEvals as $eval) {
            $examMax += (float) $eval->note_maximale;
            $note = $eval->notes->where('eleve_id', $eleveId)->first();
            if (is_null($note)) {
                $examComplete = false;
            } else {
                $examNote += (float) $note->note;
            }
        }
        if (!$examComplete) {
            $examNote = null;
        }

        $scaledTj = ($ponderationTj > 0 && $tjMax > 0 && !is_null($tjNote))
            ? round(($tjNote / $tjMax) * $ponderationTj, 2)
            : (($ponderationTj <= 0 && $tjMax > 0) ? $tjNote : $tjNote);

        $scaledComp = null;
        if ($trackCompetence) {
            $scaledComp = ($ponderationComp > 0 && $compMax > 0 && !is_null($compNote))
                ? round(($compNote / $compMax) * $ponderationComp, 2)
                : (($ponderationComp <= 0 && $compMax > 0) ? $compNote : $compNote);
            if ($compEvals->isEmpty()) {
                $scaledComp = null;
            }
        }

        $scaledExam = ($ponderationExam > 0 && $examMax > 0 && !is_null($examNote))
            ? round(($examNote / $examMax) * $ponderationExam, 2)
            : (($ponderationExam <= 0 && $examMax > 0) ? $examNote : $examNote);

        $maxTj = $ponderationTj > 0 ? $ponderationTj : $tjMax;
        $maxComp = $trackCompetence ? ($ponderationComp > 0 ? $ponderationComp : $compMax) : 0;
        $maxExam = $ponderationExam > 0 ? $ponderationExam : $examMax;
        $maxTotal = $maxTj + $maxComp + $maxExam;

        $isTjExpected = ($ponderationTj > 0 || $tjMax > 0);
        $isCompExpected = $trackCompetence && ($ponderationComp > 0 || $compMax > 0);
        $isExamExpected = ($ponderationExam > 0 || $examMax > 0);

        if ($isTjExpected && $tjMax === 0) {
            $scaledTj = null;
        }
        if ($isExamExpected && $examMax === 0) {
            $scaledExam = null;
        }
        if ($isCompExpected && $compMax === 0 && $compEvals->isEmpty()) {
            $scaledComp = null;
        }

        $total = null;
        $tjIncomplete = $isTjExpected && is_null($scaledTj);
        $compIncomplete = $isCompExpected && is_null($scaledComp);
        $examIncomplete = $isExamExpected && is_null($scaledExam);

        if ($tjIncomplete || $compIncomplete || $examIncomplete) {
            $total = null;
        } elseif ($isTjExpected || $isCompExpected || $isExamExpected) {
            $total = round(($scaledTj ?? 0) + ($scaledComp ?? 0) + ($scaledExam ?? 0), 2);
        }

        return [
            'has_competence_track' => $trackCompetence,
            'max_tj' => $maxTj,
            'max_competence' => $maxComp,
            'max_examen' => $maxExam,
            'max_total' => $maxTotal,
            'note_tj' => $scaledTj,
            'note_competence' => $trackCompetence ? $scaledComp : null,
            'note_examen' => $scaledExam,
            'note_total' => $total,
            'is_complete' => !$tjIncomplete && !$compIncomplete && !$examIncomplete,
            'has_expected_notes' => $isTjExpected || $isCompExpected || $isExamExpected,
        ];
    }

    private function buildAnnualCourseSummary(array $trimestreSummaries, array $baseSummary, int $trimestreCount): array
    {
        $annual = [
            'max_tj' => ($baseSummary['max_tj'] ?? 0) * $trimestreCount,
            'max_competence' => ($baseSummary['max_competence'] ?? 0) * $trimestreCount,
            'max_examen' => ($baseSummary['max_examen'] ?? 0) * $trimestreCount,
            'max_total' => ($baseSummary['max_total'] ?? 0) * $trimestreCount,
            'note_tj' => 0,
            'note_examen' => 0,
            'note_total' => 0,
            'is_complete' => true,
            'has_expected_notes' => false,
            'has_competence_track' => $baseSummary['has_competence_track'] ?? false,
        ];

        if ($annual['has_competence_track']) {
            $annual['note_competence'] = 0;
        }

        foreach ($trimestreSummaries as $summary) {
            $annual['has_expected_notes'] = $annual['has_expected_notes'] || ($summary['has_expected_notes'] ?? false);

            if (!is_null($summary['note_tj'])) {
                $annual['note_tj'] += $summary['note_tj'];
            } elseif (($summary['has_expected_notes'] ?? false) && ($summary['max_tj'] ?? 0) > 0) {
                $annual['note_tj'] = null;
            }

            if ($annual['has_competence_track'] && ($summary['has_competence_track'] ?? false)) {
                if (!is_null($summary['note_competence'] ?? null)) {
                    $annual['note_competence'] += $summary['note_competence'];
                } elseif (($summary['has_expected_notes'] ?? false) && ($summary['max_competence'] ?? 0) > 0) {
                    $annual['note_competence'] = null;
                }
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
            $annual['note_competence'] = null;
            $annual['note_examen'] = null;
            $annual['note_total'] = null;
        } elseif (!$annual['has_competence_track']) {
            $annual['note_competence'] = null;
        }

        return $annual;
    }

    private function resolveBaseCourseSummary(array $trimestreSummaries): array
    {
        foreach ($trimestreSummaries as $summary) {
            if (
                ($summary['max_total'] ?? 0) > 0
                || ($summary['max_tj'] ?? 0) > 0
                || ($summary['max_competence'] ?? 0) > 0
                || ($summary['max_examen'] ?? 0) > 0
            ) {
                return $summary;
            }
        }

        return $this->emptySummary();
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

    /**
     * En bulletin courant, on imprime le trimestre demandé/actuel et les trimestres
     * précédents déjà verrouillés. Les autres restent absents pour que le PDF garde
     * les colonnes vides au lieu de les marquer "Non classé".
     *
     * @return list<string>
     */
    private function resolveBulletinTrimestres(?string $trimestre, ?Trimestre $trimestreModel, int $anneeScolaireId): array
    {
        if ($trimestre === null) {
            return self::TRIMESTRES;
        }

        $currentIndex = array_search($trimestre, self::TRIMESTRES, true);
        if ($currentIndex === false) {
            return [$trimestre];
        }

        if (! $trimestreModel) {
            return [$trimestre];
        }

        $lockedTrimestres = Trimestre::query()
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('verrouille', true)
            ->pluck('nom')
            ->all();

        return array_values(array_filter(
            self::TRIMESTRES,
            fn (string $label, int $index) => $label === $trimestre
                || ($index < $currentIndex && in_array($label, $lockedTrimestres, true)),
            ARRAY_FILTER_USE_BOTH
        ));
    }

    private function filterByTrimestreLabel(Collection $items, string $trimestre): Collection
    {
        return $items
            ->filter(fn ($item) => $this->getTrimestreLabel($item) === $trimestre)
            ->values();
    }

    private function keyNotesConduiteByTrimestreLabel(Collection $notesConduite): Collection
    {
        $keyed = collect();

        foreach ($notesConduite as $noteConduite) {
            $label = $this->getTrimestreLabel($noteConduite);
            if ($label) {
                $keyed->put($label, $noteConduite);
            }
        }

        return $keyed;
    }

    private function getTrimestreLabel($item): ?string
    {
        return $item->trimestreModel?->nom
            ?? $item->trimestre_label
            ?? $item->trimestre
            ?? null;
    }

    private function emptySummary(): array
    {
        return [
            'has_competence_track' => false,
            'max_tj' => 0,
            'max_competence' => 0,
            'max_examen' => 0,
            'max_total' => 0,
            'note_tj' => null,
            'note_competence' => null,
            'note_examen' => null,
            'note_total' => null,
            'is_complete' => false,
            'has_expected_notes' => false,
        ];
    }
}
