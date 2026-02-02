<?php

namespace App\Http\Controllers\Api\Pedagogy;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pedagogical Note Controller
 *
 * Manages pedagogical notes and observations.
 */
class PedagogicalNoteController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Pedagogical notes - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create note - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show note - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update note - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete note - pending implementation'], 501);
    }

    public function byTeacher(string $teacher): JsonResponse
    {
        return response()->json(['message' => 'Notes by teacher - pending implementation'], 501);
    }

    public function bySchool(string $school): JsonResponse
    {
        return response()->json(['message' => 'Notes by school - pending implementation'], 501);
    }
}
