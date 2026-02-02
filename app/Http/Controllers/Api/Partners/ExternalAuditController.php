<?php

namespace App\Http\Controllers\Api\Partners;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * External Audit Controller
 *
 * Temporary extended access for external auditors
 */
class ExternalAuditController extends Controller
{
    public function data(): JsonResponse
    {
        return response()->json(['message' => 'Audit data - pending implementation', 'data' => []], 501);
    }

    public function schools(): JsonResponse
    {
        return response()->json(['message' => 'Audit schools data - pending implementation', 'data' => []], 501);
    }

    public function users(): JsonResponse
    {
        return response()->json(['message' => 'Audit users data - pending implementation', 'data' => []], 501);
    }

    public function transactions(): JsonResponse
    {
        return response()->json(['message' => 'Audit transactions - pending implementation', 'data' => []], 501);
    }

    public function logs(): JsonResponse
    {
        return response()->json(['message' => 'Audit logs - pending implementation', 'data' => []], 501);
    }

    public function logsByDateRange(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Logs by date range - pending implementation', 'data' => []], 501);
    }

    public function systemConfig(): JsonResponse
    {
        return response()->json(['message' => 'System config for audit - pending implementation', 'data' => []], 501);
    }

    public function createReport(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create audit report - pending implementation'], 501);
    }

    public function showReport(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show audit report - pending implementation'], 501);
    }
}
