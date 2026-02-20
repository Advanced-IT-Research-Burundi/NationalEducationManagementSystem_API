<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use App\Models\CampagneCollecte;
use App\Models\FormulaireCollecte;
use App\Models\ReponseCollecte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampagneCollecteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CampagneCollecte::with(['formulaires', 'anneeScolaire'])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->latest();

        $campagnes = $query->paginate($request->get('per_page', 15));

        return response()->json($campagnes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'niveau_validation' => 'in:zone,commune,province',
            'annee_scolaire_id' => 'nullable|exists:annee_scolaires,id',
        ]);

        $validated['statut'] = CampagneCollecte::STATUT_BROUILLON;
        $validated['created_by'] = auth()->id();

        $campagne = CampagneCollecte::create($validated);

        return response()->json([
            'message' => 'Campagne créée avec succès',
            'data' => $campagne->load('formulaires'),
        ], 201);
    }

    public function show(CampagneCollecte $campagne): JsonResponse
    {
        $campagne->load(['formulaires', 'anneeScolaire']);

        return response()->json(['data' => $campagne]);
    }

    public function update(Request $request, CampagneCollecte $campagne): JsonResponse
    {
        if ($campagne->statut !== CampagneCollecte::STATUT_BROUILLON) {
            return response()->json(['message' => 'Seules les campagnes en brouillon peuvent être modifiées'], 422);
        }

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after_or_equal:date_debut',
            'niveau_validation' => 'sometimes|in:zone,commune,province',
            'annee_scolaire_id' => 'nullable|exists:annee_scolaires,id',
        ]);

        $campagne->update($validated);

        return response()->json([
            'message' => 'Campagne mise à jour',
            'data' => $campagne->load('formulaires'),
        ]);
    }

    public function destroy(CampagneCollecte $campagne): JsonResponse
    {
        if ($campagne->statut !== CampagneCollecte::STATUT_BROUILLON) {
            return response()->json(['message' => 'Seules les campagnes en brouillon peuvent être supprimées'], 422);
        }

        $campagne->delete();

        return response()->json(['message' => 'Campagne supprimée']);
    }

    public function start(CampagneCollecte $campagne): JsonResponse
    {
        if ($campagne->statut !== CampagneCollecte::STATUT_BROUILLON) {
            return response()->json(['message' => 'Campagne déjà ouverte ou fermée'], 422);
        }

        if ($campagne->formulaires()->count() === 0) {
            return response()->json(['message' => 'Ajoutez au moins un formulaire avant d\'ouvrir'], 422);
        }

        $campagne->update(['statut' => CampagneCollecte::STATUT_OUVERTE]);

        return response()->json([
            'message' => 'Campagne ouverte',
            'data' => $campagne->load('formulaires'),
        ]);
    }

    public function close(CampagneCollecte $campagne): JsonResponse
    {
        if ($campagne->statut !== CampagneCollecte::STATUT_OUVERTE) {
            return response()->json(['message' => 'Seule une campagne ouverte peut être fermée'], 422);
        }

        $campagne->update(['statut' => CampagneCollecte::STATUT_FERMEE]);

        return response()->json([
            'message' => 'Campagne fermée',
            'data' => $campagne->load('formulaires'),
        ]);
    }

    public function progress(CampagneCollecte $campagne): JsonResponse
    {
        $totalEcoles = \App\Models\School::where('statut', 'ACTIVE')->count();
        // Écoles ayant au moins une réponse soumise (exclut brouillon)
        $schoolsRepondu = $campagne->reponses()
            ->where('statut', '!=', ReponseCollecte::STATUT_BROUILLON)
            ->distinct()
            ->count('school_id');
        $tauxReponse = $totalEcoles > 0 ? round(($schoolsRepondu / $totalEcoles) * 100, 1) : 0;

        $parStatut = $campagne->reponses()
            ->selectRaw('statut, count(*) as count')
            ->groupBy('statut')
            ->pluck('count', 'statut');

        return response()->json([
            'data' => [
                'total_schools' => $totalEcoles,
                'schools_repondu' => $schoolsRepondu,
                'taux_reponse' => $tauxReponse,
                'par_statut' => $parStatut,
            ],
        ]);
    }

    /**
     * Liste des écoles ayant répondu à la campagne
     */
    public function schoolsRepondus(CampagneCollecte $campagne): JsonResponse
    {
        $reponses = $campagne->reponses()
            ->with(['school:id,name,code_ecole,province_id', 'school.province:id,name', 'formulaire:id,titre', 'soumisPar:id,name,email'])
            ->where('statut', '!=', ReponseCollecte::STATUT_BROUILLON)
            ->orderByDesc('soumis_at')
            ->get();

        $parEcole = $reponses->groupBy('school_id')->map(function ($items, $ecoleId) {
            $premiere = $items->first();
            $ecole = $premiere->school;
            return [
                'school_id' => (int) $ecoleId,
                'ecole_name' => $ecole->name ?? $ecole->code_ecole ?? 'École #' . $ecoleId,
                'code_ecole' => $ecole->code_ecole ?? null,
                'province' => $ecole->province->name ?? null,
                'formulaires' => $items->map(fn ($r) => [
                    'formulaire' => $r->formulaire->titre ?? null,
                    'statut' => $r->statut,
                    'soumis_at' => $r->soumis_at?->toIso8601String(),
                    'soumis_par' => $r->soumisPar?->name ?? null,
                ])->values()->all(),
                'soumis_at' => $items->max('soumis_at')?->toIso8601String(),
            ];
        })->values();

        return response()->json(['data' => $parEcole]);
    }
}
