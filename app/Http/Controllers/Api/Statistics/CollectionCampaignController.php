<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Collection Campaign Controller
 */
class CollectionCampaignController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Collection campaigns - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create campaign - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show campaign - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update campaign - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete campaign - pending implementation'], 501);
    }

    public function start(string $id): JsonResponse
    {
        return response()->json(['message' => 'Start campaign - pending implementation'], 501);
    }

    public function close(string $id): JsonResponse
    {
        return response()->json(['message' => 'Close campaign - pending implementation'], 501);
    }

    public function progress(string $id): JsonResponse
    {
        return response()->json(['message' => 'Campaign progress - pending implementation'], 501);
    }
}
