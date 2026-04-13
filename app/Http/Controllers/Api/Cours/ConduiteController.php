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

            if ($noteConduite->note - $validated['points_retires'] < 0) {
                $noteConduite->update(['note' => 0]);
                return response()->json([
                    'message' => 'La note de conduite de l\'élève a atteint 0 et merite un renvoi.',
                    'note_actuelle' => 0,
                ]);
            } else {
                $noteConduite->update(['note' => $noteConduite->note - $validated['points_retires']]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Sanction enregistrée avec succès.',
                'note_actuelle' => $noteConduite->note,
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

    public function getStats(Request $request)
    {
        $query = SanctionEleve::query()
            ->join('eleves', 'sanction_eleves.eleve_id', '=', 'eleves.id')
            ->join('classes', 'sanction_eleves.classe_id', '=', 'classes.id')
            ->join('schools', 'classes.school_id', '=', 'schools.id')
            ->select('sanction_eleves.*');

        // Apply filters
        if ($request->filled('province_id')) {
            $query->where('schools.province_id', $request->province_id);
        }
        if ($request->filled('commune_id')) {
            $query->where('schools.commune_id', $request->commune_id);
        }
        if ($request->filled('school_id')) {
            $query->where('classes.school_id', $request->school_id);
        }
        if ($request->filled('classe_id')) {
            $query->where('sanction_eleves.classe_id', $request->classe_id);
        }
        if ($request->filled('eleve_id')) {
            $query->where('sanction_eleves.eleve_id', $request->eleve_id);
        }
        if ($request->filled('annee_scolaire_id')) {
            $query->where('sanction_eleves.annee_scolaire_id', $request->annee_scolaire_id);
        }
        if ($request->filled('trimestre')) {
            $query->where('sanction_eleves.trimestre', $request->trimestre);
        }

        $type = $request->input('type', 'details');

        switch ($type) {
            case 'points_par_eleve':
                $data = $query->select('sanction_eleves.eleve_id', DB::raw('SUM(points_retires) as total_points'))
                    ->with('eleve:id,nom,prenom,matricule')
                    ->groupBy('sanction_eleves.eleve_id')
                    ->orderByDesc('total_points')
                    ->get();
                break;

            case 'retraits_par_eleve':
                $data = $query->select('sanction_eleves.eleve_id', DB::raw('COUNT(*) as total_retraits'))
                    ->with('eleve:id,nom,prenom,matricule')
                    ->groupBy('sanction_eleves.eleve_id')
                    ->orderByDesc('total_retraits')
                    ->get();
                break;

            case 'eleves_meconduits_par_classe':
                $data = $query->select('sanction_eleves.classe_id', DB::raw('COUNT(DISTINCT eleve_id) as total_eleves'))
                    ->with('classe:id,nom')
                    ->groupBy('sanction_eleves.classe_id')
                    ->get();
                break;

            case 'fautes_par_classe':
                $data = $query->select('sanction_eleves.classe_id', DB::raw('COUNT(*) as total_fautes'))
                    ->with('classe:id,nom')
                    ->groupBy('sanction_eleves.classe_id')
                    ->get();
                break;

            case 'total_fautes':
                $data = [
                    'total' => $query->count(),
                    'points_total' => $query->sum('points_retires')
                ];
                break;

            case 'details':
            default:
                $data = $query->with(['eleve:id,nom,prenom,matricule', 'classe:id,nom', 'reglement:id,intitule'])
                    ->orderBy('date_sanction', 'desc')
                    ->get();
                break;
        }

        return response()->json($data);
    }
}
