<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Teacher Assignment Controller
 */
class TeacherAssignmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Assignments - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create assignment - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show assignment - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update assignment - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete assignment - pending implementation'], 501);
    }

    public function history(string $teacher): JsonResponse
    {
        return response()->json(['message' => 'Assignment history - pending implementation'], 501);
    }

    public function bySchool(string $school): JsonResponse
    {
        return response()->json(['message' => 'Assignments by school - pending implementation'], 501);
    }

    public function current(): JsonResponse
    {
        return response()->json(['message' => 'Current assignments - pending implementation'], 501);
    }
}
