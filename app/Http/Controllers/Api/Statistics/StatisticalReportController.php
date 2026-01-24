<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Statistical Report Controller
 */
class StatisticalReportController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Statistical reports - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create report - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show report - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update report - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete report - pending implementation'], 501);
    }

    public function generate(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Generate report - pending implementation'], 501);
    }

    public function export(string $id): JsonResponse
    {
        return response()->json(['message' => 'Export report - pending implementation'], 501);
    }

    public function annual(string $year): JsonResponse
    {
        return response()->json(['message' => 'Annual report - pending implementation'], 501);
    }
}
