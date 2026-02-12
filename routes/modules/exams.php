<?php

/**
 * Module Exams Routes
 *
 * Exam Planning, Exam Centers, Results Management, Certification & Diplomas
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Exams\ExamenController;
use App\Http\Controllers\Api\Exams\SessionExamenController;
use App\Http\Controllers\Api\Exams\CentreExamenController;
use App\Http\Controllers\Api\Exams\InscriptionExamenController;
use App\Http\Controllers\Api\Exams\ResultatController;
use App\Http\Controllers\Api\Exams\CertificatController;

/*
|--------------------------------------------------------------------------
| Protected Exams Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('exams')->name('exams.')->group(function () {

    // Exam Planning (Examens)
    Route::apiResource('examens', ExamenController::class);
    Route::post('examens/{examen}/publish', [ExamenController::class, 'publish'])
        ->name('examens.publish');

    // Exam Sessions
    Route::get('sessions/list', [SessionExamenController::class, 'list'])->name('sessions.list');
    Route::apiResource('sessions', SessionExamenController::class);
    Route::post('sessions/{session}/open', [SessionExamenController::class, 'open'])
        ->name('sessions.open');
    Route::post('sessions/{session}/close', [SessionExamenController::class, 'close'])
        ->name('sessions.close');

    // Exam Centers
    Route::apiResource('centres', CentreExamenController::class);
    Route::post('centres/{centre}/assign-schools', [CentreExamenController::class, 'assignSchools'])
        ->name('centres.assign-schools');

    // Exam Inscriptions
    Route::apiResource('inscriptions', InscriptionExamenController::class);
    Route::post('inscriptions/{inscription}/validate', [InscriptionExamenController::class, 'validateInscription'])
        ->name('inscriptions.validate');
    Route::post('inscriptions/{inscription}/generate-anonymat', [InscriptionExamenController::class, 'generateAnonymat'])
        ->name('inscriptions.generate-anonymat');

    // Exam Results
    Route::apiResource('results', ResultatController::class);
    Route::post('results/calculate-averages', [ResultatController::class, 'calculateAverages'])
        ->name('results.calculate-averages');

    // Certificates
    Route::apiResource('certificates', CertificatController::class);
    Route::post('certificates/{certificate}/issue', [CertificatController::class, 'issue'])
        ->name('certificates.issue');
    Route::get('certificates/verify/{numero_unique}', [CertificatController::class, 'verify'])
        ->name('certificates.verify');
});
