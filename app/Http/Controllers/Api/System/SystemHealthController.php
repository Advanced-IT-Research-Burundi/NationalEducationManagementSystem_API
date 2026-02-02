<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * System Health Controller
 */
class SystemHealthController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    public function database(): JsonResponse
    {
        try {
            DB::connection()->getPdo();

            return response()->json([
                'status' => 'connected',
                'driver' => config('database.default'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Database connection failed',
            ], 500);
        }
    }

    public function storage(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'disk' => config('filesystems.default'),
        ]);
    }

    public function queue(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'driver' => config('queue.default'),
        ]);
    }
}
