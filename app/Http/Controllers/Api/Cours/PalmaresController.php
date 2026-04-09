<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\Matiere;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PalmaresController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'trimestre' => ['nullable', 'string'],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
        ]);

        $classeId = $request->integer('classe_id');
        $trimestre = $request->trimestre;
        $anneeScolaireId = $request->integer('annee_scolaire_id') ?: AnneeScolaire::current()?->id;

        if (!$anneeScolaireId) {
            return response()->json(['message' => 'Aucune année scolaire active.'], 422);
        }

        $classe = Classe::with(['school:id,name', 'niveau:id,nom', 'section:id,nom'])->findOrFail($classeId);
        $eleves = $classe->eleves()->orderBy('nom')->orderBy('prenom')->get();

        // Get evaluations
        $evaluationsQuery = Evaluation::with('notes')
            ->where('classe_id', $classeId)
            ->where('annee_scolaire_id', $anneeScolaireId);

        if ($trimestre) {
            $evaluationsQuery->where('trimestre', $trimestre);
        }

        $evaluations = $evaluationsQuery->get();

        // Get courses
        $cours = Matiere::where('actif', true)
            ->where(function ($q) use ($classe) {
                $q->where('niveau_id', $classe->niveau_id)
                    ->orWhereNull('niveau_id');
            })
            ->get();

        // Calculate totals per student
        $classement = [];
        foreach ($eleves as $eleve) {
            $totalPoints = 0;
            $totalMax = 0;

            foreach ($cours as $matiere) {
                $coursEvals = $evaluations->where('cours_id', $matiere->id);

                $tjEvals = $coursEvals->whereIn('type_evaluation', ['TJ', 'Interrogation', 'Devoir', 'TP']);
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
                $scaledExam = $matiere->ponderation_examen > 0 && $examMax > 0
                    ? round(($examNote / $examMax) * $matiere->ponderation_examen, 2)
                    : $examNote;

                $total = $scaledTj + $scaledExam;
                $maxTotal = ($matiere->ponderation_tj ?: $tjMax) + ($matiere->ponderation_examen ?: $examMax);

                $totalPoints += $total;
                $totalMax += $maxTotal;
            }

            $pourcentage = $totalMax > 0 ? round(($totalPoints / $totalMax) * 100, 1) : 0;

            $classement[] = [
                'eleve' => [
                    'id' => $eleve->id,
                    'nom' => $eleve->nom,
                    'prenom' => $eleve->prenom,
                    'matricule' => $eleve->matricule,
                ],
                'total_points' => $totalPoints,
                'total_max' => $totalMax,
                'pourcentage' => $pourcentage,
            ];
        }

        // Sort by total descending
        usort($classement, fn($a, $b) => $b['total_points'] <=> $a['total_points']);

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
                'nombre_eleves' => count($eleves),
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
            'trimestre' => ['nullable', 'string'],
            'annee_scolaire_id' => ['nullable', 'exists:annee_scolaires,id'],
        ]);

        $palmaresResponse = $this->index($request);
        $palmaresData = json_decode($palmaresResponse->getContent(), true)['data'];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('bulletin.palmares_pdf', [
            'data' => $palmaresData,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'palmares_' . ($palmaresData['classe']['nom'] ?? 'classe') . '.pdf';

        return $pdf->download($filename);
    }
}
