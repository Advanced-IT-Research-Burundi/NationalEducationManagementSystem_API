<?php

/**
 * Module Academic Routes
 *
 * Années Scolaires, Niveaux, Classes, Enseignants, Élèves, Inscriptions, Affectations, Mouvements
 */

use App\Http\Controllers\Api\Academic\AnneeScolaireController;
use App\Http\Controllers\Api\Academic\ClasseController;
use App\Http\Controllers\Api\Academic\EleveController;
use App\Http\Controllers\Api\Academic\EnseignantController;
use App\Http\Controllers\Api\Academic\MouvementEleveController;
use App\Http\Controllers\Api\Academic\NiveauController;
use App\Http\Controllers\Api\Academic\MatiereController;
use App\Http\Controllers\Api\Academic\SectionController;
use App\Http\Controllers\Api\Exams\ResultatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Protected Academic Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->prefix('academic')->name('academic.')->group(function () {

    // Années Scolaires (School Years)
    Route::get('annees-scolaires/list', [AnneeScolaireController::class, 'list'])->name('annees-scolaires.list');
    Route::get('annees-scolaires/current', [AnneeScolaireController::class, 'current'])->name('annees-scolaires.current');
    Route::post('annees-scolaires/{annee_scolaire}/toggle-active', [AnneeScolaireController::class, 'toggleActive'])->name('annees-scolaires.toggle-active');
    Route::apiResource('annees-scolaires', AnneeScolaireController::class);

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

    // Mouvements Élèves (Student Movements: transfers, dropouts, etc.)
    Route::get('mouvements-eleve/statistics', [MouvementEleveController::class, 'statistics'])->name('mouvements-eleve.statistics');
    Route::get('mouvements-eleve/types', [MouvementEleveController::class, 'types'])->name('mouvements-eleve.types');
    Route::get('mouvements-eleve/by-eleve/{eleve}', [MouvementEleveController::class, 'byEleve'])->name('mouvements-eleve.by-eleve');
    Route::get('mouvements-eleve/by-school/{school}', [MouvementEleveController::class, 'bySchool'])->name('mouvements-eleve.by-school');
    Route::get('mouvements-eleve/by-type/{type}', [MouvementEleveController::class, 'byType'])->name('mouvements-eleve.by-type');
    Route::post('mouvements-eleve/{mouvement_eleve}/validate', [MouvementEleveController::class, 'validate'])->name('mouvements-eleve.validate');
    Route::post('mouvements-eleve/{mouvement_eleve}/reject', [MouvementEleveController::class, 'reject'])->name('mouvements-eleve.reject');
    Route::apiResource('mouvements-eleve', MouvementEleveController::class)->parameters([
        'mouvements-eleve' => 'mouvement_eleve',
    ]);

    // Sections
    Route::get('sections/list', [SectionController::class, 'list'])->name('sections.list');
    Route::apiResource('sections', SectionController::class);

    // Matières
    Route::get('matieres/list', [MatiereController::class, 'list'])->name('matieres.list');
    Route::apiResource('matieres', MatiereController::class);

    // Bulletins (Averaged results)
    Route::get('bulletins', [ResultatController::class, 'bulletins'])->name('bulletins.index');
});
