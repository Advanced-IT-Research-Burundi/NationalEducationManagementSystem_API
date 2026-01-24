<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Building Controller
 */
class BuildingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Buildings - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create building - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show building - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update building - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete building - pending implementation'], 501);
    }

    public function bySchool(string $school): JsonResponse
    {
        return response()->json(['message' => 'Buildings by school - pending implementation'], 501);
    }

    public function byCondition(string $condition): JsonResponse
    {
        return response()->json(['message' => 'Buildings by condition - pending implementation'], 501);
    }

    public function statistics(): JsonResponse
    {
        return response()->json(['message' => 'Building statistics - pending implementation'], 501);
    }
}
