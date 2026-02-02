<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Alert Controller
 */
class AlertController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Alerts - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create alert - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show alert - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update alert - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete alert - pending implementation'], 501);
    }

    public function acknowledge(string $id): JsonResponse
    {
        return response()->json(['message' => 'Acknowledge alert - pending implementation'], 501);
    }

    public function resolve(string $id): JsonResponse
    {
        return response()->json(['message' => 'Resolve alert - pending implementation'], 501);
    }

    public function pending(): JsonResponse
    {
        return response()->json(['message' => 'Pending alerts - pending implementation'], 501);
    }
}
