<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard Controller
 */
class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Dashboards - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create dashboard - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show dashboard - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update dashboard - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete dashboard - pending implementation'], 501);
    }

    public function data(string $id): JsonResponse
    {
        return response()->json(['message' => 'Dashboard data - pending implementation'], 501);
    }

    public function export(string $id): JsonResponse
    {
        return response()->json(['message' => 'Export dashboard - pending implementation'], 501);
    }

    public function national(): JsonResponse
    {
        return response()->json(['message' => 'National dashboard - pending implementation'], 501);
    }

    public function provincial(string $province): JsonResponse
    {
        return response()->json(['message' => 'Provincial dashboard - pending implementation'], 501);
    }

    public function communal(string $commune): JsonResponse
    {
        return response()->json(['message' => 'Communal dashboard - pending implementation'], 501);
    }
}
