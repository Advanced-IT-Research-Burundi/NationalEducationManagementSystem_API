<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Strategic Plan Controller
 */
class StrategicPlanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Strategic plans - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create plan - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show plan - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update plan - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete plan - pending implementation'], 501);
    }

    public function objectives(string $id): JsonResponse
    {
        return response()->json(['message' => 'Plan objectives - pending implementation'], 501);
    }

    public function progress(string $id): JsonResponse
    {
        return response()->json(['message' => 'Plan progress - pending implementation'], 501);
    }
}
