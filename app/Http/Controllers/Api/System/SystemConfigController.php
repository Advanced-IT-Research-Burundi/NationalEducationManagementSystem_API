<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * System Config Controller
 */
class SystemConfigController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'System config - pending implementation', 'data' => []], 501);
    }

    public function show(string $key): JsonResponse
    {
        return response()->json(['message' => 'Show config - pending implementation'], 501);
    }

    public function update(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Update config - pending implementation'], 501);
    }

    public function resetUserPassword(Request $request, string $user): JsonResponse
    {
        return response()->json(['message' => 'Reset user password - pending implementation'], 501);
    }
}
