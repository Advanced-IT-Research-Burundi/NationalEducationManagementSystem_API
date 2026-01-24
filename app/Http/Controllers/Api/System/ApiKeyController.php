<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Key Controller
 */
class ApiKeyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'API keys - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create API key - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show API key - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update API key - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete API key - pending implementation'], 501);
    }

    public function regenerate(string $id): JsonResponse
    {
        return response()->json(['message' => 'Regenerate API key - pending implementation'], 501);
    }

    public function revoke(string $id): JsonResponse
    {
        return response()->json(['message' => 'Revoke API key - pending implementation'], 501);
    }
}
