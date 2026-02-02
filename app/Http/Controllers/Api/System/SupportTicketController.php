<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Support Ticket Controller
 */
class SupportTicketController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Support tickets - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create ticket - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show ticket - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update ticket - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete ticket - pending implementation'], 501);
    }

    public function respond(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Respond to ticket - pending implementation'], 501);
    }

    public function assign(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Assign ticket - pending implementation'], 501);
    }

    public function close(string $id): JsonResponse
    {
        return response()->json(['message' => 'Close ticket - pending implementation'], 501);
    }

    public function reopen(string $id): JsonResponse
    {
        return response()->json(['message' => 'Reopen ticket - pending implementation'], 501);
    }

    public function open(): JsonResponse
    {
        return response()->json(['message' => 'Open tickets - pending implementation'], 501);
    }

    public function byStatus(string $status): JsonResponse
    {
        return response()->json(['message' => 'Tickets by status - pending implementation'], 501);
    }
}
