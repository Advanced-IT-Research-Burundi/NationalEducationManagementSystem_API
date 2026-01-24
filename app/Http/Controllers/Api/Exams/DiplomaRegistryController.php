<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Diploma Registry Controller
 */
class DiplomaRegistryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Diploma registry - pending implementation', 'data' => []], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show diploma - pending implementation'], 501);
    }

    public function search(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Search diplomas - pending implementation'], 501);
    }

    public function verify(string $id): JsonResponse
    {
        return response()->json(['message' => 'Verify diploma - pending implementation'], 501);
    }
}
