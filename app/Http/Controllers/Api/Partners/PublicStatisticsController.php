<?php

namespace App\Http\Controllers\Api\Partners;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Public Statistics Controller
 *
 * READ-ONLY access for NGO and public access
 */
class PublicStatisticsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Public statistics - pending implementation', 'data' => []], 501);
    }

    public function educationIndicators(): JsonResponse
    {
        return response()->json(['message' => 'Education indicators - pending implementation', 'data' => []], 501);
    }

    public function schoolCoverage(): JsonResponse
    {
        return response()->json(['message' => 'School coverage - pending implementation', 'data' => []], 501);
    }

    public function enrollment(): JsonResponse
    {
        return response()->json(['message' => 'Enrollment statistics - pending implementation', 'data' => []], 501);
    }

    public function byProvince(string $province): JsonResponse
    {
        return response()->json(['message' => 'Statistics by province - pending implementation'], 501);
    }

    public function trends(): JsonResponse
    {
        return response()->json(['message' => 'Statistical trends - pending implementation', 'data' => []], 501);
    }
}
