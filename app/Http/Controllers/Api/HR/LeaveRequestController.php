<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Leave Request Controller
 */
class LeaveRequestController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Leave requests - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create request - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show request - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update request - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete request - pending implementation'], 501);
    }

    public function approve(string $id): JsonResponse
    {
        return response()->json(['message' => 'Approve request - pending implementation'], 501);
    }

    public function reject(string $id): JsonResponse
    {
        return response()->json(['message' => 'Reject request - pending implementation'], 501);
    }

    public function byTeacher(string $teacher): JsonResponse
    {
        return response()->json(['message' => 'Requests by teacher - pending implementation'], 501);
    }

    public function pending(): JsonResponse
    {
        return response()->json(['message' => 'Pending requests - pending implementation'], 501);
    }
}
