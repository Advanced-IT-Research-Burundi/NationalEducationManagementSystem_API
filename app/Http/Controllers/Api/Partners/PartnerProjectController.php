<?php

namespace App\Http\Controllers\Api\Partners;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Partner Project Controller
 *
 * READ-ONLY access for PTF (Partenaires Techniques et Financiers)
 */
class PartnerProjectController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Partner projects - pending implementation', 'data' => []], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show project - pending implementation'], 501);
    }

    public function reports(string $id): JsonResponse
    {
        return response()->json(['message' => 'Project reports - pending implementation'], 501);
    }

    public function progress(string $id): JsonResponse
    {
        return response()->json(['message' => 'Project progress - pending implementation'], 501);
    }
}
