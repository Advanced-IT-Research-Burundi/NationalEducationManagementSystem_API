<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Maintenance Request Controller
 */
class MaintenanceRequestController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Maintenance requests - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create request - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show request - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update request - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete request - pending implementation'], 501);
    }

    public function assign(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Assign request - pending implementation'], 501);
    }

    public function start(string $id): JsonResponse
    {
        return response()->json(['message' => 'Start maintenance - pending implementation'], 501);
    }

    public function complete(string $id): JsonResponse
    {
        return response()->json(['message' => 'Complete maintenance - pending implementation'], 501);
    }

    public function pending(): JsonResponse
    {
        return response()->json(['message' => 'Pending requests - pending implementation'], 501);
    }
}
