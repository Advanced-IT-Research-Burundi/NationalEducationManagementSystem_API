<?php

/**
 * Module Core Routes
 *
 * Authentication, Authorization, Users, Roles, Permissions, Geographic Hierarchy
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Core\AuthController;
use App\Http\Controllers\Api\Core\UserController;
use App\Http\Controllers\Api\Core\RoleController;
use App\Http\Controllers\Api\Core\PermissionController;
use App\Http\Controllers\Api\Core\PaysController;
use App\Http\Controllers\Api\Core\MinistereController;
use App\Http\Controllers\Api\Core\ProvinceController;
use App\Http\Controllers\Api\Core\CommuneController;
use App\Http\Controllers\Api\Core\ZoneController;
use App\Http\Controllers\Api\Core\CollineController;

/*
|--------------------------------------------------------------------------
| Authentication Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

/*
|--------------------------------------------------------------------------
| Protected Core Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    });

    // User Management
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // Roles & Permissions
    Route::apiResource('roles', RoleController::class);
    Route::post('roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->name('roles.sync-permissions');
    Route::apiResource('permissions', PermissionController::class);

    // Geographic Hierarchy
    Route::prefix('geo')->name('geo.')->group(function () {
        Route::apiResource('pays', PaysController::class);
        Route::apiResource('ministeres', MinistereController::class);
        Route::apiResource('provinces', ProvinceController::class);
        Route::apiResource('communes', CommuneController::class);
        Route::apiResource('zones', ZoneController::class);
        Route::apiResource('collines', CollineController::class);
    });

    // Legacy routes (backward compatibility)
    Route::apiResource('pays', PaysController::class);
    Route::apiResource('ministeres', MinistereController::class);
    Route::apiResource('provinces', ProvinceController::class);
    Route::apiResource('communes', CommuneController::class);
    Route::apiResource('zones', ZoneController::class);
    Route::apiResource('collines', CollineController::class);
});
