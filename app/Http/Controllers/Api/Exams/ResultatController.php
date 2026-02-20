<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResultatRequest;
use App\Http\Requests\UpdateResultatRequest;
use App\Models\Resultat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Resultat Controller
 *
 * Manages exam results and deliberations.
 */
class ResultatController extends Controller
{
    public function index(): JsonResponse
    {
        $resultats = Resultat::with('inscription.eleve')->paginate(15);

        return response()->json([
            'message' => 'Liste des résultats récupérée avec succès',
            'data' => $resultats
        ]);
    }

    public function store(StoreResultatRequest $request): JsonResponse
    {
        $resultat = Resultat::create($request->validated());

        return response()->json([
            'message' => 'Résultat enregistré avec succès',
            'data' => $resultat->load('inscription.eleve')
        ], 201);
    }

    public function show(Resultat $resultat): JsonResponse
    {
        return response()->json([
            'data' => $resultat->load('inscription.eleve')
        ]);
    }

    public function update(UpdateResultatRequest $request, Resultat $resultat): JsonResponse
    {
        $resultat->update($request->validated());

        return response()->json([
            'message' => 'Résultat mis à jour avec succès',
            'data' => $resultat->load('inscription.eleve')
        ]);
    }

    public function destroy(Resultat $resultat): JsonResponse
    {
        $resultat->delete();

        return response()->json([
            'message' => 'Résultat supprimé avec succès'
        ]);
    }

    public function calculateAverages(Request $request): JsonResponse
    {
        // Example logic for calculating averages
        return response()->json([
            'message' => 'Moyennes calculées avec succès'
        ]);
    }

    /**
     * Get aggregated results for report cards (bulletins).
     */
    public function bulletins(Request $request): JsonResponse
    {
        $query = Resultat::with(['inscription.eleve', 'inscription.session.examen']);

        // Filter by school year
        if ($request->filled('annee_scolaire_id')) {
            $query->whereHas('inscription.session.examen', function ($q) use ($request) {
                $q->where('annee_scolaire_id', $request->annee_scolaire_id);
            });
        }

        $resultats = $query->get();

        // Group by student
        $bulletins = $resultats->groupBy('inscription.eleve_id')->map(function ($group) {
            $eleve = $group->first()->inscription->eleve;
            $avg = $group->avg('note');
            
            return [
                'student' => $eleve->nom . ' ' . $eleve->prenom,
                'class' => 'N/A', // Classes relationship needs to be checked
                'trimester' => 'T1', // Mocked
                'average' => round($avg, 2),
                'rank' => 0, // Needs more complex logic
                'total' => 1 // Needs more complex logic
            ];
        })->values();

        return response()->json([
            'data' => $bulletins
        ]);
    }
}
