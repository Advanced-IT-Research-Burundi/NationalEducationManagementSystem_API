<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Teacher Controller
 */
class TeacherController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Teachers - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create teacher - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show teacher - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update teacher - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete teacher - pending implementation'], 501);
    }

    public function bySchool(string $school): JsonResponse
    {
        return response()->json(['message' => 'Teachers by school - pending implementation'], 501);
    }

    public function byQualification(string $qualification): JsonResponse
    {
        return response()->json(['message' => 'Teachers by qualification - pending implementation'], 501);
    }

    public function statistics(): JsonResponse
    {
        return response()->json(['message' => 'Teacher statistics - pending implementation'], 501);
    }
}
