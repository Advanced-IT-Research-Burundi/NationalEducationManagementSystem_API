<?php

namespace App\Http\Controllers\Api\Partners;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Project Indicator Controller
 *
 * READ-ONLY access for PTF
 */
class ProjectIndicatorController extends Controller
{
    public function index(string $project): JsonResponse
    {
        return response()->json(['message' => 'Project indicators - pending implementation', 'data' => []], 501);
    }

    public function all(): JsonResponse
    {
        return response()->json(['message' => 'All indicators - pending implementation', 'data' => []], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show indicator - pending implementation'], 501);
    }

    public function values(string $id): JsonResponse
    {
        return response()->json(['message' => 'Indicator values - pending implementation'], 501);
    }
}
