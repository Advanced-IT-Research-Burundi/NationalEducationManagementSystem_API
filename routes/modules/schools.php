<?php

/**
 * Module Schools Routes
 *
 * School Management, School Workflow, School Directory
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Schools\SchoolController;
use App\Http\Controllers\Api\Schools\SchoolTypeController;
use App\Http\Controllers\Api\Schools\SchoolWorkflowController;

/*
|--------------------------------------------------------------------------
| Protected Schools Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {

    // Schools CRUD
    Route::apiResource('schools', SchoolController::class);

    // School Workflow Actions
    Route::prefix('schools/{school}')->name('schools.')->group(function () {
        Route::post('/submit', [SchoolWorkflowController::class, 'submit'])->name('submit');
        Route::post('/validate', [SchoolWorkflowController::class, 'validate'])->name('validate');
        Route::post('/deactivate', [SchoolWorkflowController::class, 'deactivate'])->name('deactivate');
        Route::post('/reactivate', [SchoolWorkflowController::class, 'reactivate'])->name('reactivate');
    });

    // School Types (reference data)
    Route::apiResource('school-types', SchoolTypeController::class)->only(['index', 'show']);

    // School Statistics
    Route::get('schools-stats', [SchoolController::class, 'statistics'])->name('schools.statistics');
    Route::get('schools-by-status', [SchoolController::class, 'byStatus'])->name('schools.by-status');
});
