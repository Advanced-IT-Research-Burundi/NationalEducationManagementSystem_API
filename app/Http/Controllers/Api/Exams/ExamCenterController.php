<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exam Center Controller
 */
class ExamCenterController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Exam centers - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create center - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show center - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update center - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete center - pending implementation'], 501);
    }

    public function assignSchools(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Assign schools - pending implementation'], 501);
    }

    public function candidates(string $id): JsonResponse
    {
        return response()->json(['message' => 'Center candidates - pending implementation'], 501);
    }

    public function activate(string $id): JsonResponse
    {
        return response()->json(['message' => 'Activate center - pending implementation'], 501);
    }

    public function deactivate(string $id): JsonResponse
    {
        return response()->json(['message' => 'Deactivate center - pending implementation'], 501);
    }
}
