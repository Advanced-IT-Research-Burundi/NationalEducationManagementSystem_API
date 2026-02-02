<?php

/**
 * Module Exams Routes
 *
 * Exam Planning, Exam Centers, Results Management, Certification & Diplomas
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Exams\ExamSessionController;
use App\Http\Controllers\Api\Exams\ExamCalendarController;
use App\Http\Controllers\Api\Exams\ExamCenterController;
use App\Http\Controllers\Api\Exams\ExamResultController;
use App\Http\Controllers\Api\Exams\CertificateController;
use App\Http\Controllers\Api\Exams\DiplomaRegistryController;

/*
|--------------------------------------------------------------------------
| Protected Exams Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('exams')->name('exams.')->group(function () {

    // Exam Calendar
    Route::apiResource('calendar', ExamCalendarController::class);
    Route::get('calendar/upcoming', [ExamCalendarController::class, 'upcoming'])
        ->name('calendar.upcoming');
    Route::get('calendar/by-year/{year}', [ExamCalendarController::class, 'byYear'])
        ->name('calendar.by-year');

    // Exam Sessions
    Route::apiResource('sessions', ExamSessionController::class);
    Route::post('sessions/{session}/open', [ExamSessionController::class, 'open'])
        ->name('sessions.open');
    Route::post('sessions/{session}/close', [ExamSessionController::class, 'close'])
        ->name('sessions.close');
    Route::get('sessions/{session}/statistics', [ExamSessionController::class, 'statistics'])
        ->name('sessions.statistics');

    // Exam Centers
    Route::apiResource('centers', ExamCenterController::class);
    Route::post('centers/{center}/assign-schools', [ExamCenterController::class, 'assignSchools'])
        ->name('centers.assign-schools');
    Route::get('centers/{center}/candidates', [ExamCenterController::class, 'candidates'])
        ->name('centers.candidates');
    Route::post('centers/{center}/activate', [ExamCenterController::class, 'activate'])
        ->name('centers.activate');
    Route::post('centers/{center}/deactivate', [ExamCenterController::class, 'deactivate'])
        ->name('centers.deactivate');

    // Exam Results
    Route::apiResource('results', ExamResultController::class);
    Route::post('results/import', [ExamResultController::class, 'import'])
        ->name('results.import');
    Route::post('results/{result}/validate', [ExamResultController::class, 'validateResult'])
        ->name('results.validate');
    Route::get('results/by-session/{session}', [ExamResultController::class, 'bySession'])
        ->name('results.by-session');
    Route::get('results/by-center/{center}', [ExamResultController::class, 'byCenter'])
        ->name('results.by-center');
    Route::get('results/statistics/{session}', [ExamResultController::class, 'statistics'])
        ->name('results.statistics');

    // Certificates
    Route::apiResource('certificates', CertificateController::class);
    Route::post('certificates/{certificate}/issue', [CertificateController::class, 'issue'])
        ->name('certificates.issue');
    Route::get('certificates/{certificate}/verify', [CertificateController::class, 'verify'])
        ->name('certificates.verify');
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])
        ->name('certificates.download');

    // Diploma Registry
    Route::get('diploma-registry', [DiplomaRegistryController::class, 'index'])
        ->name('diploma-registry.index');
    Route::get('diploma-registry/{diploma}', [DiplomaRegistryController::class, 'show'])
        ->name('diploma-registry.show');
    Route::get('diploma-registry/search', [DiplomaRegistryController::class, 'search'])
        ->name('diploma-registry.search');
    Route::get('diploma-registry/{diploma}/verify', [DiplomaRegistryController::class, 'verify'])
        ->name('diploma-registry.verify');
});
