<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exam Session Controller
 *
 * Manages exam sessions.
 */
class ExamSessionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Exam sessions - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create session - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show session - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update session - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete session - pending implementation'], 501);
    }

    public function open(string $id): JsonResponse
    {
        return response()->json(['message' => 'Open session - pending implementation'], 501);
    }

    public function close(string $id): JsonResponse
    {
        return response()->json(['message' => 'Close session - pending implementation'], 501);
    }

    public function statistics(string $id): JsonResponse
    {
        return response()->json(['message' => 'Session statistics - pending implementation'], 501);
    }
}
