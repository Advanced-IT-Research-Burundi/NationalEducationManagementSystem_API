<?php

/**
 * Module Academic Routes
 *
 * Années Scolaires, Niveaux, Classes, Enseignants, Élèves, Inscriptions, Affectations, Mouvements
 * + Module Cours (Cours, Évaluations, Notes, Bulletins, Palmarès, Catégories)
 */

use App\Http\Controllers\Api\Academic\AnneeScolaireController;
use App\Http\Controllers\Api\Academic\ClasseController;
use App\Http\Controllers\Api\Academic\CycleScolaireController;
use App\Http\Controllers\Api\Academic\EleveController;
use App\Http\Controllers\Api\Academic\EnseignantController;
use App\Http\Controllers\Api\Academic\MouvementEleveController;
use App\Http\Controllers\Api\Academic\NiveauController;
use App\Http\Controllers\Api\Academic\MatiereController;
use App\Http\Controllers\Api\Academic\SectionController;
use App\Http\Controllers\Api\Academic\TypeScolaireController;
use App\Http\Controllers\Api\Academic\AffectationMatiereController;
use App\Http\Controllers\Api\Cours\CoursController;
use App\Http\Controllers\Api\Cours\CategoriCoursController;
use App\Http\Controllers\Api\Cours\EvaluationController;
use App\Http\Controllers\Api\Cours\NoteController;
use App\Http\Controllers\Api\Cours\BulletinController;
use App\Http\Controllers\Api\Cours\PalmaresController;
use App\Http\Controllers\Api\Cours\ReglementScolaireController;
use App\Http\Controllers\Api\Cours\ConduiteController;
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
    Route::apiResource('annees-scolaires', AnneeScolaireController::class)->parameters([
        'annees-scolaires' => 'anneeScolaire',
    ]);

    // Niveaux (Grade Levels)
    Route::get('niveaux/list', [NiveauController::class, 'list'])->name('niveaux.list');
    Route::apiResource('niveaux', NiveauController::class)->parameters([
        'niveaux' => 'niveau',
    ]);

    // Types scolaires
    Route::get('types-scolaires/list', [TypeScolaireController::class, 'list'])->name('types-scolaires.list');
    Route::apiResource('types-scolaires', TypeScolaireController::class)->parameters([
        'types-scolaires' => 'typeScolaire',
    ]);

    // Cycles scolaires
    Route::get('cycles-scolaires/list', [CycleScolaireController::class, 'list'])->name('cycles-scolaires.list');
    Route::apiResource('cycles-scolaires', CycleScolaireController::class)->parameters([
        'cycles-scolaires' => 'cycleScolaire',
    ]);

    // Classes
    Route::get('classes/statistics', [ClasseController::class, 'statistics'])->name('classes.statistics');
    Route::get('classes/by-school/{school}', [ClasseController::class, 'bySchool'])->name('classes.by-school');
    Route::get('classes/{classe}/enseignants', [ClasseController::class, 'enseignants'])->name('classes.enseignants');
    Route::get('classes/{classe}/eleves', [ClasseController::class, 'eleves'])->name('classes.eleves');
    Route::post('classes/{classe}/eleves', [ClasseController::class, 'addEleve'])->name('classes.eleves.add');
    Route::apiResource('classes', ClasseController::class)->parameters([
        'classes' => 'classe',
    ]);

    // Enseignants (Teachers)
    Route::get('enseignants/statistics', [EnseignantController::class, 'statistics'])->name('enseignants.statistics');
    Route::get('enseignants/by-school/{school}', [EnseignantController::class, 'bySchool'])->name('enseignants.by-school');
    Route::get('enseignants/{enseignant}/classes', [EnseignantController::class, 'classes'])->name('enseignants.classes');
    Route::post('enseignants/assign-to-classe', [EnseignantController::class, 'assignToClasse'])->name('enseignants.assign');
    Route::delete('affectations/{affectation}', [EnseignantController::class, 'removeFromClasse'])->name('affectations.remove');
    Route::patch('enseignants/{enseignant}/ecoles/sync', [EnseignantController::class, 'syncEcoles'])->name('enseignants.ecoles.sync');
    Route::apiResource('enseignants', EnseignantController::class);

    // Élèves (Students)
    Route::get('eleves/statistics', [EleveController::class, 'statistics'])->name('eleves.statistics');
    Route::get('eleves/by-school/{school}', [EleveController::class, 'bySchool'])->name('eleves.by-school');
    Route::get('eleves/by-classe/{classe}', [EleveController::class, 'byClasse'])->name('eleves.by-classe');
    Route::post('eleves/enroll', [EleveController::class, 'enroll'])->name('eleves.enroll');
    Route::patch('eleves/{eleve}/niveau', [EleveController::class, 'updateNiveau'])->name('eleves.update-niveau');
    Route::delete('inscriptions/{inscription}', [EleveController::class, 'unenroll'])->name('inscriptions.unenroll');
    Route::post('inscriptions/{inscription}/transfer', [EleveController::class, 'transfer'])->name('inscriptions.transfer');
    Route::post('eleves/{eleve}/transfert', [EleveController::class, 'transfertEtablissement'])->name('eleves.transfert');
    Route::apiResource('eleves', EleveController::class)->parameters([
        'eleves' => 'eleve',
    ]);

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

    // Matières (legacy, kept for backward compatibility)
    Route::get('matieres/list', [MatiereController::class, 'list'])->name('matieres.list');
    Route::apiResource('matieres', MatiereController::class);

    // Affectations Matières
    Route::patch('affectations-matieres/{affectation}/statut', [AffectationMatiereController::class, 'updateStatut'])->name('affectations-matieres.statut');
    Route::apiResource('affectations-matieres', AffectationMatiereController::class);

    /*
    |--------------------------------------------------------------------------
    | Module Cours
    |--------------------------------------------------------------------------
    */

    // Catégories de cours
    Route::get('categories-cours/list', [CategoriCoursController::class, 'list'])->name('categories-cours.list');
    Route::apiResource('categories-cours', CategoriCoursController::class);

    // Cours (enriched matieres)
    Route::get('cours/list', [CoursController::class, 'list'])->name('cours.list');
    Route::apiResource('cours', CoursController::class);

    // Évaluations (new architecture)
    Route::post('evaluations/{evaluation}/notes', [EvaluationController::class, 'storeNotes'])->name('evaluations.store-notes');
    Route::get('evaluations/{evaluation}/notes', [EvaluationController::class, 'showNotes'])->name('evaluations.show-notes');
    Route::post('evaluations/{evaluation}/import-notes', [EvaluationController::class, 'importNotes'])->name('evaluations.import-notes');
    Route::get('evaluations/{evaluation}/export-template', [EvaluationController::class, 'exportTemplate'])->name('evaluations.export-template');
    Route::apiResource('evaluations', EvaluationController::class);

    // Notes consultation
    Route::get('notes', [NoteController::class, 'index'])->name('notes.index');

    // --- MODULE: CONDUITE ---
    Route::apiResource('reglements-scolaires', ReglementScolaireController::class);
    Route::post('conduite/sanctions', [ConduiteController::class, 'modifierConduite'])->name('conduite.sanctionner');
    Route::post('conduite/sanctions/bulk', [ConduiteController::class, 'bulkSanction'])->name('conduite.sanctionner-bulk');
    Route::get('conduite/notes', [ConduiteController::class, 'getNotesByClasse'])->name('conduite.notes');
    Route::get('conduite/historique/{eleve}', [ConduiteController::class, 'historiqueSanctions'])->name('conduite.historique');

    // Bulletins
    Route::get('bulletins/generate', [BulletinController::class, 'generate'])->name('bulletins.generate');
    Route::get('bulletins/pdf', [BulletinController::class, 'pdf'])->name('bulletins.pdf');

    // Palmarès
    Route::get('palmares', [PalmaresController::class, 'index'])->name('palmares.index');
    Route::get('palmares/pdf', [PalmaresController::class, 'pdf'])->name('palmares.pdf');
});
