<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exam Result Controller
 */
class ExamResultController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Exam results - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create result - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show result - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update result - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete result - pending implementation'], 501);
    }

    public function import(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Import results - pending implementation'], 501);
    }

    public function validateResult(string $id): JsonResponse
    {
        return response()->json(['message' => 'Validate result - pending implementation'], 501);
    }

    public function bySession(string $session): JsonResponse
    {
        return response()->json(['message' => 'Results by session - pending implementation'], 501);
    }

    public function byCenter(string $center): JsonResponse
    {
        return response()->json(['message' => 'Results by center - pending implementation'], 501);
    }

    public function statistics(string $session): JsonResponse
    {
        return response()->json(['message' => 'Results statistics - pending implementation'], 501);
    }
}
