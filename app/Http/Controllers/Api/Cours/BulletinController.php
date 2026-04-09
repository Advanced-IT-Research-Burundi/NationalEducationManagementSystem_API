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

                // Scale to ponderation if configured
                $ponderationTj = Schema::hasColumn('matieres', 'ponderation_tj') ? (float) ($matiere->ponderation_tj ?? 0) : 0;
                $ponderationExam = Schema::hasColumn('matieres', 'ponderation_examen') ? (float) ($matiere->ponderation_examen ?? 0) : 0;

                $scaledTj = $ponderationTj > 0 && $tjMax > 0
                    ? round(($tjNote / $tjMax) * $matiere->ponderation_tj, 2)
                    : $tjNote;
                $scaledExam = $ponderationExam > 0 && $examMax > 0
                    ? round(($examNote / $examMax) * $matiere->ponderation_examen, 2)
                    : $examNote;

                $total = $scaledTj + $scaledExam;
                $maxTotal = ($ponderationTj ?: $tjMax) + ($ponderationExam ?: $examMax);

                $totalPoints += $total;
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

            $pourcentage = $totalMax > 0 ? round(($totalPoints / $totalMax) * 100, 1) : 0;

            // Note de conduite
            $noteC = $notesConduite->where('eleve_id', $eleve->id)->first();
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
                'total_points' => $totalPoints,
                'total_max' => $totalMax,
                'pourcentage' => $pourcentage,
                'conduite' => [
                    'note' => $noteConduiteValue,
                    'max' => 60,
                    'appreciation' => $appreciationConduite
                ]
            ];
        }

        // Calculate rankings
        usort($bulletins, fn($a, $b) => $b['total_points'] <=> $a['total_points']);
        $rank = 1;
        foreach ($bulletins as &$bulletin) {
            $bulletin['rang'] = $rank++;
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
        $bulletinResponse = $this->generate($request);
        $bulletinData = json_decode($bulletinResponse->getContent(), true)['data'];

        $pdf = Pdf::loadView('bulletin.bulletin_pdf', [
            'data' => $bulletinData,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'bulletin_' . ($bulletinData['classe']['nom'] ?? 'classe') . '.pdf';

        return $pdf->download($filename);
    }
}
