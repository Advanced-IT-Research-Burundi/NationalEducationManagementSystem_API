<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Teacher Qualification Controller
 */
class TeacherQualificationController extends Controller
{
    public function index(string $teacherId): JsonResponse
    {
        return response()->json(['message' => 'Qualifications - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request, string $teacherId): JsonResponse
    {
        return response()->json(['message' => 'Create qualification - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show qualification - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update qualification - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete qualification - pending implementation'], 501);
    }

    public function verify(string $id): JsonResponse
    {
        return response()->json(['message' => 'Verify qualification - pending implementation'], 501);
    }
}
