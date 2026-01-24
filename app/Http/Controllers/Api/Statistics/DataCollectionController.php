<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Data Collection Controller
 */
class DataCollectionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Data collections - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create collection - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show collection - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update collection - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete collection - pending implementation'], 501);
    }

    public function validateData(string $id): JsonResponse
    {
        return response()->json(['message' => 'Validate data - pending implementation'], 501);
    }

    public function flagIssue(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Flag issue - pending implementation'], 501);
    }

    public function bySchool(string $school): JsonResponse
    {
        return response()->json(['message' => 'Collections by school - pending implementation'], 501);
    }
}
