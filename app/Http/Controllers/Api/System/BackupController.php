<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backup Controller
 */
class BackupController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Backups - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create backup - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show backup - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete backup - pending implementation'], 501);
    }

    public function restore(string $id): JsonResponse
    {
        return response()->json(['message' => 'Restore backup - pending implementation'], 501);
    }

    public function download(string $id): JsonResponse
    {
        return response()->json(['message' => 'Download backup - pending implementation'], 501);
    }
}
