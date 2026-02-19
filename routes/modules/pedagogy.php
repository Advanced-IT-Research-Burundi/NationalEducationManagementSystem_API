<?php

/**
 * Module Pedagogy Routes
 *
 * Inspection Management, Quality Standards, Pedagogical Support, Teacher Training
 */

use App\Http\Controllers\Api\Pedagogy\FormationController;
use App\Http\Controllers\Api\Pedagogy\InspectionController;
use App\Http\Controllers\Api\Pedagogy\InspectionReportController;
use App\Http\Controllers\Api\Pedagogy\PedagogicalNoteController;
use App\Http\Controllers\Api\Pedagogy\StandardQualiteController;
use Illuminate\Support\Facades\Route;

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
    Route::get('inspections/ecole/{ecole}/history', [InspectionController::class, 'history'])
        ->name('inspections.history');

    // Inspection Reports
    Route::apiResource('inspection-reports', InspectionReportController::class);
    Route::post('inspection-reports/{report}/submit', [InspectionReportController::class, 'submit'])
        ->name('inspection-reports.submit');
    Route::post('inspection-reports/{report}/approve', [InspectionReportController::class, 'approve'])
        ->name('inspection-reports.approve');

    // Quality Standards (Standards de Qualité)
    Route::apiResource('standards-qualite', StandardQualiteController::class)
        ->parameters(['standards-qualite' => 'standardQualite']);
    Route::get('standards-qualite/{standardQualite}/criteria', [StandardQualiteController::class, 'criteria'])
        ->name('standards-qualite.criteria');

    // Formations
    Route::apiResource('formations', FormationController::class);
    Route::post('formations/{formation}/register', [FormationController::class, 'register'])
        ->name('formations.register');
    Route::get('formations/{formation}/participants', [FormationController::class, 'participants'])
        ->name('formations.participants');
    Route::post('formations/{formation}/complete', [FormationController::class, 'complete'])
        ->name('formations.complete');

    // Pedagogical Notes
    Route::apiResource('pedagogical-notes', PedagogicalNoteController::class);
    Route::get('pedagogical-notes/by-teacher/{teacher}', [PedagogicalNoteController::class, 'byTeacher'])
        ->name('pedagogical-notes.by-teacher');
    Route::get('pedagogical-notes/by-school/{school}', [PedagogicalNoteController::class, 'bySchool'])
        ->name('pedagogical-notes.by-school');
});
