<?php

/**
 * Module Academic Routes
 *
 * Niveaux, Classes, Enseignants, Élèves, Inscriptions, Affectations
 */

use App\Http\Controllers\Api\Academic\ClasseController;
use App\Http\Controllers\Api\Academic\EleveController;
use App\Http\Controllers\Api\Academic\EnseignantController;
use App\Http\Controllers\Api\Academic\NiveauController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Protected Academic Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('academic')->name('academic.')->group(function () {

    // Niveaux (Grade Levels)
    Route::get('niveaux/list', [NiveauController::class, 'list'])->name('niveaux.list');
    Route::apiResource('niveaux', NiveauController::class);

    // Classes
    Route::get('classes/statistics', [ClasseController::class, 'statistics'])->name('classes.statistics');
    Route::get('classes/by-school/{school}', [ClasseController::class, 'bySchool'])->name('classes.by-school');
    Route::get('classes/{classe}/enseignants', [ClasseController::class, 'enseignants'])->name('classes.enseignants');
    Route::get('classes/{classe}/eleves', [ClasseController::class, 'eleves'])->name('classes.eleves');
    Route::apiResource('classes', ClasseController::class);

    // Enseignants (Teachers)
    Route::get('enseignants/statistics', [EnseignantController::class, 'statistics'])->name('enseignants.statistics');
    Route::get('enseignants/by-school/{school}', [EnseignantController::class, 'bySchool'])->name('enseignants.by-school');
    Route::get('enseignants/{enseignant}/classes', [EnseignantController::class, 'classes'])->name('enseignants.classes');
    Route::post('enseignants/assign-to-classe', [EnseignantController::class, 'assignToClasse'])->name('enseignants.assign');
    Route::delete('affectations/{affectation}', [EnseignantController::class, 'removeFromClasse'])->name('affectations.remove');
    Route::apiResource('enseignants', EnseignantController::class);

    // Élèves (Students)
    Route::get('eleves/statistics', [EleveController::class, 'statistics'])->name('eleves.statistics');
    Route::get('eleves/by-school/{school}', [EleveController::class, 'bySchool'])->name('eleves.by-school');
    Route::get('eleves/by-classe/{classe}', [EleveController::class, 'byClasse'])->name('eleves.by-classe');
    Route::post('eleves/enroll', [EleveController::class, 'enroll'])->name('eleves.enroll');
    Route::delete('inscriptions/{inscription}', [EleveController::class, 'unenroll'])->name('inscriptions.unenroll');
    Route::post('inscriptions/{inscription}/transfer', [EleveController::class, 'transfer'])->name('inscriptions.transfer');
    Route::apiResource('eleves', EleveController::class);
});
