<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\NoteConduite;
use App\Scopes\AcademicYearScope;
use App\Services\ConduiteConfigService;
use App\Services\CurrentAcademicContextService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PalmaresController extends Controller
{
    use \App\Traits\ResolvesAnneeScolaire;

    public function __construct(
        private readonly CurrentAcademicContextService $academicContextService
    ) {}

    private static function palmaresCoursCode(Matiere $matiere): string
    {
        $raw = (string) ($matiere->code ?: $matiere->nom ?: '');
        $raw = Str::upper($raw);
        $raw = preg_replace('/\d+/', '', $raw);        // remove numbers
        $raw = preg_replace('/[^A-Z]/', '', $raw);     // keep letters only

        if (! empty($raw)) {
            return Str::substr($raw, 0, 6);
        }

        $fallback = Str::upper(Str::substr(preg_replace('/\s+/', '', (string) ($matiere->nom ?? '')), 0, 6));

        return $fallback ?: 'COURS';
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'mode' => ['nullable', 'in:current,annual'],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
            'type' => ['nullable', 'in:simple,detaille'],
        ]);

        $classeId = $request->integer('classe_id');
        $mode = $request->string('mode')->toString() ?: 'current';
        $anneeScolaireId = $this->resolveAnneeScolaireId($request);
        $type = $request->string('type')->toString() ?: 'simple';
        $currentTrimestre = $mode === 'annual' ? null : $this->academicContextService->requireCurrentTrimestre();
        $trimestre = $currentTrimestre?->nom;

        if (! $anneeScolaireId) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $classe = Classe::withoutGlobalScope(AcademicYearScope::class)
            ->with(['school:id,name', 'niveau:id,nom,ordre', 'section:id,nom'])
            ->findOrFail($classeId);
        $conduiteConfig = ConduiteConfigService::resolveForClasse($classe);
        $conduiteMax = $conduiteConfig['max_note'];
        $eleves = $classe->eleves()->orderBy('nom')->orderBy('prenom')->get();

        // Get evaluations
        $evaluationsQuery = Evaluation::with('notes')
            ->where('classe_id', $classeId)
            ->where('annee_scolaire_id', $anneeScolaireId);

        if ($currentTrimestre) {
            $evaluationsQuery->matchingTrimestre($currentTrimestre);
        }

        $evaluations = $evaluationsQuery->get();

        $cours = Matiere::where('actif', true)
            ->where(function ($q) use ($classe) {
                $q->where('niveau_id', $classe->niveau_id)
                    ->orWhereNull('niveau_id');
            })
            ->get();

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
        $notesConduiteQuery = NoteConduite::where('classe_id', $classeId)
            ->where('annee_scolaire_id', $anneeScolaireId);
        if ($currentTrimestre) {
            $notesConduiteQuery->matchingTrimestre($currentTrimestre);
        }
        $notesConduite = $notesConduiteQuery->get();

        // Calculate totals per student
        $classement = [];
        foreach ($eleves as $eleve) {
            $totalPoints = 0;
            $totalMax = 0;
            $coursPoints = [];
            $coursMaxima = [];
            $coursEchecs = [];

            foreach ($cours as $matiere) {
                $coursEvals = $evaluations->where('cours_id', $matiere->id);

                $tjEvals = $coursEvals->whereIn('type_evaluation', ['TJ', 'Interrogation', 'Devoir', 'TP']);
                $ponderationComp = Schema::hasColumn('matieres', 'ponderation_competence')
                    ? (float) ($matiere->ponderation_competence ?? 0)
                    : 0.0;
                $compEvals = $ponderationComp > 0
                    ? $coursEvals->where('type_evaluation', 'Compétence')
                    : collect();
                $examEvals = $coursEvals->where('type_evaluation', 'Examen');

                $tjNote = 0;
                $tjMax = 0;
                foreach ($tjEvals as $eval) {
                    $note = $eval->notes->where('eleve_id', $eleve->id)->first();
                    if ($note) {
                        $tjNote += $note->note;
                        $tjMax += $eval->note_maximale;
                    }
                }

                $compNote = 0;
                $compMax = 0;
                foreach ($compEvals as $eval) {
                    $note = $eval->notes->where('eleve_id', $eleve->id)->first();
                    if ($note) {
                        $compNote += $note->note;
                        $compMax += $eval->note_maximale;
                    }
                }

                $examNote = 0;
                $examMax = 0;
                foreach ($examEvals as $eval) {
                    $note = $eval->notes->where('eleve_id', $eleve->id)->first();
                    if ($note) {
                        $examNote += $note->note;
                        $examMax += $eval->note_maximale;
                    }
                }

                $scaledTj = $matiere->ponderation_tj > 0 && $tjMax > 0
                    ? round(($tjNote / $tjMax) * $matiere->ponderation_tj, 2)
                    : $tjNote;
                $scaledComp = 0;
                if ($ponderationComp > 0 && $compMax > 0) {
                    $scaledComp = round(($compNote / $compMax) * $ponderationComp, 2);
                }
                $scaledExam = $matiere->ponderation_examen > 0 && $examMax > 0
                    ? round(($examNote / $examMax) * $matiere->ponderation_examen, 2)
                    : $examNote;

                $maxComp = $ponderationComp > 0 ? $ponderationComp : 0;
                $total = $scaledTj + $scaledComp + $scaledExam;
                $maxTotal = ($matiere->ponderation_tj ?: $tjMax) + $maxComp + ($matiere->ponderation_examen ?: $examMax);

                $totalPoints += $total;
                $totalMax += $maxTotal;

                if ($type === 'detaille') {
                    $code = self::palmaresCoursCode($matiere);

                    $coursPoints[$code] = round($total, 2);
                    $coursMaxima[$code] = round($maxTotal, 2);

                    $moitie = $maxTotal > 0 ? ($maxTotal / 2) : 0;
                    if ($moitie > 0 && $total < $moitie) {
                        $coursEchecs[$code] = round($moitie - $total, 2);
                    }
                }
            }

            $pourcentageCours = $totalMax > 0 ? round(($totalPoints / $totalMax) * 100, 1) : 0;

            $noteC = $notesConduite->where('eleve_id', $eleve->id)->first();
            $noteConduiteValue = $noteC ? $noteC->note : $conduiteMax;
            $appreciationConduite = ConduiteConfigService::buildAppreciation($noteConduiteValue, $conduiteMax);

            $globalPoints = round($totalPoints + $noteConduiteValue, 2);
            $globalMax = round($totalMax + $conduiteMax, 2);
            $pourcentage = $globalMax > 0 ? round(($globalPoints / $globalMax) * 100, 1) : 0;

            $decision = null;
            if ($type === 'detaille') {
                $nbEchecs = count($coursEchecs);
                if ($pourcentage >= 50 && $nbEchecs === 0) {
                    $decision = 'Admis';
                } elseif ($pourcentage >= 50 && $nbEchecs > 0) {
                    $decision = 'Admis (avec échecs)';
                } else {
                    $decision = 'Ajourné';
                }
            }

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
                'conduite' => [
                    'note' => $noteConduiteValue,
                    'max' => $conduiteMax,
                    'appreciation' => $appreciationConduite,
                ],
            ];

            if ($type === 'detaille') {
                $entry['cours_points'] = $coursPoints;
                $entry['cours_max'] = $coursMaxima;
                $entry['echecs'] = $coursEchecs; // points manquants pour atteindre la moitié
                $entry['decision_jury'] = $decision;
                $entry['nombre_echecs'] = count($coursEchecs);
            }

            $classement[] = $entry;
        }

        // Sort by total descending
        usort($classement, fn ($a, $b) => $b['total_points'] <=> $a['total_points']);

        // Assign ranks
        $rank = 1;
        foreach ($classement as &$entry) {
            $entry['rang'] = $rank++;
        }

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
                'mode' => $mode,
                'type' => $type,
                'cours' => $type === 'detaille' ? $coursMeta : null,
                'classement' => $classement,
            ],
        ]);
    }

    /**
     * Generate a PDF Palmares.
     */
    public function pdf(Request $request)
    {
        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'mode' => ['nullable', 'in:current,annual'],
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

        $filename = 'palmares_'.($palmaresData['classe']['nom'] ?? 'classe').'.pdf';

        return $pdf->download($filename);
    }
}
