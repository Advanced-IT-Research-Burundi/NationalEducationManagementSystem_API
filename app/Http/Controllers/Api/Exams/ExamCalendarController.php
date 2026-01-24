<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exam Calendar Controller
 */
class ExamCalendarController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Exam calendar - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create calendar entry - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show calendar entry - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update calendar entry - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete calendar entry - pending implementation'], 501);
    }

    public function upcoming(): JsonResponse
    {
        return response()->json(['message' => 'Upcoming exams - pending implementation'], 501);
    }

    public function byYear(string $year): JsonResponse
    {
        return response()->json(['message' => 'Exams by year - pending implementation'], 501);
    }
}
