<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * KPI Controller
 */
class KpiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'KPIs - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create KPI - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show KPI - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update KPI - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete KPI - pending implementation'], 501);
    }

    public function values(string $id): JsonResponse
    {
        return response()->json(['message' => 'KPI values - pending implementation'], 501);
    }

    public function calculate(string $id): JsonResponse
    {
        return response()->json(['message' => 'Calculate KPI - pending implementation'], 501);
    }

    public function byCategory(string $category): JsonResponse
    {
        return response()->json(['message' => 'KPIs by category - pending implementation'], 501);
    }
}
