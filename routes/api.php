<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/auth/login', [AuthController::class, 'login']);
// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // --- Examples of Role & Permission Protection ---

    // Admin National Dashboard
    Route::get('/admin/dashboard', function () {
        return response()->json(['message' => 'Welcome National Admin', 'scope' => 'All Data']);
    })->middleware('role:admin_national');

    // Provincial Director Dashboard
    Route::get('/province/dashboard', function () {
        return response()->json(['message' => 'Welcome Provincial Director', 'scope' => 'Province Data']);
    })->middleware('role:directeur_provincial');

    // Action requiring specific permission
    Route::post('/data/export', function () {
        return response()->json(['message' => 'Data Exported Successfully']);
    })->middleware('permission:EXPORT_DATA');

    // --- Core CRUD Routes ---
    require __DIR__ . '/localite.php';

    Route::apiResource('schools', \App\Http\Controllers\Api\SchoolController::class);
    Route::apiResource('roles', \App\Http\Controllers\Api\RoleController::class);
    Route::apiResource('permissions', \App\Http\Controllers\Api\PermissionController::class);

});

