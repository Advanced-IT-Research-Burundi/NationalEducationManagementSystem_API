<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Training Material Controller
 */
class TrainingMaterialController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Training materials - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create material - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show material - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update material - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete material - pending implementation'], 501);
    }

    public function byCategory(string $category): JsonResponse
    {
        return response()->json(['message' => 'Materials by category - pending implementation'], 501);
    }

    public function download(string $id): JsonResponse
    {
        return response()->json(['message' => 'Download material - pending implementation'], 501);
    }
}
