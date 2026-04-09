<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Models\NoteConduite;
use App\Models\SanctionEleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConduiteController extends Controller
{
    public function modifierConduite(Request $request)
    {
        $validated = $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'classe_id' => 'required|exists:classes,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'trimestre' => 'required',
            'reglement_id' => 'nullable|exists:reglement_scolaires,id',
            'points_retires' => 'required|numeric|min:0',
            'date_sanction' => 'required|date',
            'observation' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $sanction = SanctionEleve::create([
                'eleve_id' => $validated['eleve_id'],
                'classe_id' => $validated['classe_id'],
                'reglement_id' => $validated['reglement_id'] ?? null,
                'annee_scolaire_id' => $validated['annee_scolaire_id'],
                'user_id' => auth()->id(),
                'trimestre' => $validated['trimestre'],
                'date_sanction' => $validated['date_sanction'],
                'points_retires' => $validated['points_retires'],
                'observation' => $validated['observation'] ?? null,
            ]);

            $noteConduite = NoteConduite::firstOrCreate(
                [
                    'eleve_id' => $validated['eleve_id'],
                    'classe_id' => $validated['classe_id'],
                    'annee_scolaire_id' => $validated['annee_scolaire_id'],
                    'trimestre' => $validated['trimestre'],
                ],
                ['note' => 60] // Initialisation à 60
            );

            // Soustraire et éviter que ce soit négatif (min 0)
            $nouvelleNote = max(0, $noteConduite->note - $validated['points_retires']);
            $noteConduite->update(['note' => $nouvelleNote]);

            DB::commit();

            return response()->json([
                'message' => 'Sanction enregistrée avec succès.',
                'note_actuelle' => $nouvelleNote,
                'sanction' => $sanction
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
    }

    public function bulkSanction(Request $request)
    {
        $validated = $request->validate([
            'eleve_ids' => 'required|array',
            'eleve_ids.*' => 'exists:eleves,id',
            'classe_id' => 'required|exists:classes,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'trimestre' => 'required',
            'reglement_id' => 'nullable|exists:reglement_scolaires,id',
            'points_retires' => 'required|numeric|min:0',
            'date_sanction' => 'required|date',
            'observation' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $sanctions = [];
            foreach ($validated['eleve_ids'] as $eleveId) {
                $sanction = SanctionEleve::create([
                    'eleve_id' => $eleveId,
                    'classe_id' => $validated['classe_id'],
                    'reglement_id' => $validated['reglement_id'] ?? null,
                    'annee_scolaire_id' => $validated['annee_scolaire_id'],
                    'user_id' => auth()->id(),
                    'trimestre' => $validated['trimestre'],
                    'date_sanction' => $validated['date_sanction'],
                    'points_retires' => $validated['points_retires'],
                    'observation' => $validated['observation'] ?? null,
                ]);

                $noteConduite = NoteConduite::firstOrCreate(
                    [
                        'eleve_id' => $eleveId,
                        'classe_id' => $validated['classe_id'],
                        'annee_scolaire_id' => $validated['annee_scolaire_id'],
                        'trimestre' => $validated['trimestre'],
                    ],
                    ['note' => 60]
                );

                $nouvelleNote = max(0, $noteConduite->note - $validated['points_retires']);
                $noteConduite->update(['note' => $nouvelleNote]);

                $sanctions[] = $sanction;
            }

            DB::commit();

            return response()->json([
                'message' => count($sanctions) . ' sanctions enregistrées avec succès.',
                'count' => count($sanctions)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de l\'enregistrement groupé: ' . $e->getMessage()], 500);
        }
    }

    public function getNotesByClasse(Request $request)
    {
         $validated = $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'trimestre' => 'required'
         ]);

         $notes = NoteConduite::with('eleve')
            ->where('classe_id', $validated['classe_id'])
            ->where('annee_scolaire_id', $validated['annee_scolaire_id'])
            ->where('trimestre', $validated['trimestre'])
            ->get();

          return response()->json($notes);
    }

    public function historiqueSanctions(Request $request, $eleve_id)
    {
         $sanctions = SanctionEleve::with(['reglement', 'user', 'anneeScolaire'])
            ->where('eleve_id', $eleve_id)
            ->orderBy('date_sanction', 'desc')
            ->get();

         return response()->json($sanctions);
    }
}
