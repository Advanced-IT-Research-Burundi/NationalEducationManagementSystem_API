<?php

/**
 * Module Pedagogy Routes
 *
 * Inspection Management, Quality Standards, Pedagogical Support, Teacher Training
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Pedagogy\InspectionController;
use App\Http\Controllers\Api\Pedagogy\InspectionReportController;
use App\Http\Controllers\Api\Pedagogy\QualityStandardController;
use App\Http\Controllers\Api\Pedagogy\TrainingSessionController;
use App\Http\Controllers\Api\Pedagogy\PedagogicalNoteController;

/*
|--------------------------------------------------------------------------
| Protected Pedagogy Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('pedagogy')->name('pedagogy.')->group(function () {

    // Inspections
    Route::apiResource('inspections', InspectionController::class);
    Route::post('inspections/{inspection}/validate', [InspectionController::class, 'validateInspection'])
        ->name('inspections.validate');
    Route::get('inspections/{inspection}/history', [InspectionController::class, 'history'])
        ->name('inspections.history');

    // Inspection Reports
    Route::apiResource('inspection-reports', InspectionReportController::class);
    Route::post('inspection-reports/{report}/submit', [InspectionReportController::class, 'submit'])
        ->name('inspection-reports.submit');
    Route::post('inspection-reports/{report}/approve', [InspectionReportController::class, 'approve'])
        ->name('inspection-reports.approve');

    // Quality Standards
    Route::apiResource('quality-standards', QualityStandardController::class);
    Route::get('quality-standards/{standard}/criteria', [QualityStandardController::class, 'criteria'])
        ->name('quality-standards.criteria');

    // Training Sessions
    Route::apiResource('training-sessions', TrainingSessionController::class);
    Route::post('training-sessions/{session}/register', [TrainingSessionController::class, 'register'])
        ->name('training-sessions.register');
    Route::get('training-sessions/{session}/participants', [TrainingSessionController::class, 'participants'])
        ->name('training-sessions.participants');
    Route::post('training-sessions/{session}/complete', [TrainingSessionController::class, 'complete'])
        ->name('training-sessions.complete');

    // Pedagogical Notes
    Route::apiResource('pedagogical-notes', PedagogicalNoteController::class);
    Route::get('pedagogical-notes/by-teacher/{teacher}', [PedagogicalNoteController::class, 'byTeacher'])
        ->name('pedagogical-notes.by-teacher');
    Route::get('pedagogical-notes/by-school/{school}', [PedagogicalNoteController::class, 'bySchool'])
        ->name('pedagogical-notes.by-school');
});
