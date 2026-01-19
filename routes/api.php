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
    
    // Example: User with generic view permission
    Route::get('/schools', function(Request $request) {
        // Here we would implement the Scope trait logic (e.g., School::scopeForUser($request->user())->get())
        return response()->json(['message' => 'List of schools scoped to user']);
    })->middleware('permission:VIEW_DATA');

});
