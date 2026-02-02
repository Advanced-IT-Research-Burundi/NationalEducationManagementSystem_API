<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Career Controller
 */
class CareerController extends Controller
{
    public function show(string $teacher): JsonResponse
    {
        return response()->json(['message' => 'Teacher career - pending implementation'], 501);
    }

    public function promote(Request $request, string $teacher): JsonResponse
    {
        return response()->json(['message' => 'Promote teacher - pending implementation'], 501);
    }

    public function history(string $teacher): JsonResponse
    {
        return response()->json(['message' => 'Career history - pending implementation'], 501);
    }

    public function grades(): JsonResponse
    {
        return response()->json(['message' => 'Career grades - pending implementation'], 501);
    }
}
