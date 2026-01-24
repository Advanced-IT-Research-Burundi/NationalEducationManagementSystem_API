<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Equipment Movement Controller
 */
class EquipmentMovementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Equipment movements - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create movement - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show movement - pending implementation'], 501);
    }

    public function byEquipment(string $equipment): JsonResponse
    {
        return response()->json(['message' => 'Movements by equipment - pending implementation'], 501);
    }
}
