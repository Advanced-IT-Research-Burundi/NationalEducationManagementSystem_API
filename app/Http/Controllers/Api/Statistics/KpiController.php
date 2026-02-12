<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * KPI Controller - Key Performance Indicators
 */
class KpiController extends Controller
{
    protected StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * List all KPIs with current values
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);

        try {
            $kpis = $this->statisticsService->getNationalKpis($filters);
            $global = $this->statisticsService->getGlobalStats($filters);
            $inscription = $this->statisticsService->getInscriptionStats($filters);

            $kpiList = [
                [
                    'id' => 'total_ecoles',
                    'name' => 'Total Établissements',
                    'category' => 'global',
                    'value' => $kpis['total_ecoles'],
                    'unit' => 'nombre',
                    'description' => 'Nombre total d\'établissements scolaires actifs',
                ],
                [
                    'id' => 'total_eleves',
                    'name' => 'Total Élèves',
                    'category' => 'global',
                    'value' => $kpis['total_eleves'],
                    'unit' => 'nombre',
                    'description' => 'Nombre total d\'élèves actifs',
                ],
                [
                    'id' => 'total_enseignants',
                    'name' => 'Total Enseignants',
                    'category' => 'global',
                    'value' => $kpis['total_enseignants'],
                    'unit' => 'nombre',
                    'description' => 'Nombre total d\'enseignants actifs',
                ],
                [
                    'id' => 'ratio_eleve_enseignant',
                    'name' => 'Ratio Élève/Enseignant',
                    'category' => 'national',
                    'value' => $kpis['ratio_eleve_enseignant'],
                    'unit' => 'ratio',
                    'description' => 'Nombre moyen d\'élèves par enseignant',
                ],
                [
                    'id' => 'taux_reussite',
                    'name' => 'Taux de Réussite',
                    'category' => 'performance',
                    'value' => $kpis['taux_reussite'],
                    'unit' => 'pourcentage',
                    'description' => 'Pourcentage d\'élèves ayant réussi les examens',
                ],
                [
                    'id' => 'pourcentage_filles',
                    'name' => 'Pourcentage de Filles',
                    'category' => 'inscription',
                    'value' => $kpis['pourcentage_filles'],
                    'unit' => 'pourcentage',
                    'description' => 'Proportion de filles parmi les élèves',
                ],
                [
                    'id' => 'taux_scolarisation',
                    'name' => 'Taux de Scolarisation',
                    'category' => 'national',
                    'value' => $kpis['taux_scolarisation'],
                    'unit' => 'pourcentage',
                    'description' => 'Taux de scolarisation national (nécessite données population)',
                ],
                [
                    'id' => 'ecoles_par_type',
                    'name' => 'Écoles par Type',
                    'category' => 'global',
                    'value' => $global['ecoles_par_type'],
                    'unit' => 'répartition',
                    'description' => 'Répartition des écoles par type (publique, privée, etc.)',
                ],
                [
                    'id' => 'repartition_genre',
                    'name' => 'Répartition par Genre',
                    'category' => 'inscription',
                    'value' => $inscription['repartition_genre'],
                    'unit' => 'répartition',
                    'description' => 'Répartition garçons/filles',
                ],
            ];

            return response()->json(['data' => $kpiList]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du calcul des KPIs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a specific KPI value
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $filters = $this->extractFilters($request);

        try {
            $kpis = $this->statisticsService->getNationalKpis($filters);

            if (isset($kpis[$id])) {
                return response()->json([
                    'data' => [
                        'id' => $id,
                        'value' => $kpis[$id],
                    ],
                ]);
            }

            return response()->json(['message' => 'KPI non trouvé'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new KPI (placeholder)
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Custom KPIs - coming soon'], 501);
    }

    /**
     * Update a KPI (placeholder)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update KPI - coming soon'], 501);
    }

    /**
     * Delete a KPI (placeholder)
     */
    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete KPI - coming soon'], 501);
    }

    /**
     * Get KPI values over time
     */
    public function values(Request $request, string $id): JsonResponse
    {
        $filters = $this->extractFilters($request);

        try {
            $evolution = $this->statisticsService->getEvolutionEffectifs($filters);

            return response()->json([
                'data' => [
                    'kpi_id' => $id,
                    'evolution' => $evolution,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Trigger KPI calculation
     */
    public function calculate(Request $request, string $id): JsonResponse
    {
        $filters = $this->extractFilters($request);

        try {
            // Force recalculation by clearing cache first
            $this->statisticsService->clearCache();
            $kpis = $this->statisticsService->getNationalKpis($filters);

            return response()->json([
                'message' => 'KPIs recalculés avec succès',
                'data' => $kpis,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors du calcul', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get KPIs filtered by category
     */
    public function byCategory(Request $request, string $category): JsonResponse
    {
        $filters = $this->extractFilters($request);

        try {
            $allKpis = $this->index($request)->getData(true);
            $kpiList = $allKpis['data'] ?? [];

            $filtered = array_values(array_filter($kpiList, fn($kpi) => $kpi['category'] === $category));

            if (empty($filtered)) {
                return response()->json([
                    'message' => "Aucun KPI trouvé pour la catégorie '{$category}'",
                    'categories_disponibles' => ['global', 'national', 'performance', 'inscription'],
                ], 404);
            }

            return response()->json(['data' => $filtered]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Extract filters from request
     */
    protected function extractFilters(Request $request): array
    {
        $filters = [];

        if ($request->filled('annee_scolaire_id')) {
            $filters['annee_scolaire_id'] = $request->input('annee_scolaire_id');
        }
        if ($request->filled('province_id')) {
            $filters['province_id'] = $request->input('province_id');
        }
        if ($request->filled('commune_id')) {
            $filters['commune_id'] = $request->input('commune_id');
        }
        if ($request->filled('ecole_id')) {
            $filters['ecole_id'] = $request->input('ecole_id');
        }
        if ($request->filled('niveau')) {
            $filters['niveau'] = $request->input('niveau');
        }

        return $filters;
    }
}
