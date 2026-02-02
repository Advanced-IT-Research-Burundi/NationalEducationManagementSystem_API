<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Building Assessment Controller
 */
class BuildingAssessmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Assessments - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create assessment - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show assessment - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update assessment - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete assessment - pending implementation'], 501);
    }

    public function byBuilding(string $building): JsonResponse
    {
        return response()->json(['message' => 'Assessments by building - pending implementation'], 501);
    }

    public function submit(string $id): JsonResponse
    {
        return response()->json(['message' => 'Submit assessment - pending implementation'], 501);
    }
}
