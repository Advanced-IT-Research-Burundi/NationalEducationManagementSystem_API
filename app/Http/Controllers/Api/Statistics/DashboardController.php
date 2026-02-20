<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard Controller - Statistics dashboards by administrative level
 */
class DashboardController extends Controller
{
    protected StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Extract common filters from request
     */
    protected function extractFilters(Request $request): array
    {
        $filters = [];

        if ($request->filled('annee_scolaire_id')) {
            $filters['annee_scolaire_id'] = $request->input('annee_scolaire_id');
        }
        if ($request->filled('niveau')) {
            $filters['niveau'] = $request->input('niveau');
        }

        return $filters;
    }

    /**
     * List dashboards (placeholder for custom dashboard management)
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['id' => 'national', 'name' => 'Tableau de Bord National', 'type' => 'national'],
                ['id' => 'provincial', 'name' => 'Tableau de Bord Provincial', 'type' => 'provincial'],
                ['id' => 'communal', 'name' => 'Tableau de Bord Communal', 'type' => 'communal'],
                ['id' => 'ecole', 'name' => 'Tableau de Bord École', 'type' => 'ecole'],
            ],
        ]);
    }

    /**
     * Store a new dashboard config (placeholder)
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Custom dashboards - coming soon'], 501);
    }

    /**
     * Show a specific dashboard config (placeholder)
     */
    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Dashboard detail - coming soon'], 501);
    }

    /**
     * Update a dashboard config (placeholder)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update dashboard - coming soon'], 501);
    }

    /**
     * Delete a dashboard config (placeholder)
     */
    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete dashboard - coming soon'], 501);
    }

    /**
     * Get dashboard data for a specific dashboard
     */
    public function data(Request $request, string $id): JsonResponse
    {
        $filters = $this->extractFilters($request);

        try {
            $data = $this->statisticsService->getDashboardData($filters);
            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors du calcul des statistiques', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export dashboard data (placeholder)
     */
    public function export(string $id): JsonResponse
    {
        return response()->json(['message' => 'Export dashboard - coming soon'], 501);
    }

    /**
     * National dashboard - Full country statistics
     */
    public function national(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);

        try {
            $data = $this->statisticsService->getDashboardData($filters);
            return response()->json([
                'niveau' => 'national',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du calcul des statistiques nationales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Provincial dashboard - Statistics filtered by province
     */
    public function provincial(Request $request, string $province): JsonResponse
    {
        $filters = array_merge($this->extractFilters($request), [
            'province_id' => $province,
        ]);

        try {
            $data = $this->statisticsService->getDashboardData($filters);
            return response()->json([
                'niveau' => 'provincial',
                'province_id' => $province,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du calcul des statistiques provinciales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Communal dashboard - Statistics filtered by commune
     */
    public function communal(Request $request, string $commune): JsonResponse
    {
        $filters = array_merge($this->extractFilters($request), [
            'commune_id' => $commune,
        ]);

        try {
            $data = $this->statisticsService->getDashboardData($filters);
            return response()->json([
                'niveau' => 'communal',
                'commune_id' => $commune,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du calcul des statistiques communales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * School-level dashboard - Statistics for a single school
     */
    public function ecole(Request $request, string $ecole): JsonResponse
    {
        $filters = array_merge($this->extractFilters($request), [
            'school_id' => $ecole,
        ]);

        try {
            $data = $this->statisticsService->getDashboardData($filters);
            return response()->json([
                'niveau' => 'ecole',
                'school_id' => $ecole,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du calcul des statistiques de l\'école',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear statistics cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->statisticsService->clearCache();
            return response()->json(['message' => 'Cache des statistiques vidé avec succès']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors du vidage du cache', 'error' => $e->getMessage()], 500);
        }
    }
}
