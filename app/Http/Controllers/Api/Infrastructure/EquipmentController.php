<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Equipment Controller
 */
class EquipmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Equipment - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create equipment - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show equipment - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update equipment - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete equipment - pending implementation'], 501);
    }

    public function inventory(): JsonResponse
    {
        return response()->json(['message' => 'Equipment inventory - pending implementation'], 501);
    }

    public function bySchool(string $school): JsonResponse
    {
        return response()->json(['message' => 'Equipment by school - pending implementation'], 501);
    }

    public function byCategory(string $category): JsonResponse
    {
        return response()->json(['message' => 'Equipment by category - pending implementation'], 501);
    }

    public function transfer(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Transfer equipment - pending implementation'], 501);
    }
}
