<?php

/**
 * Module Partners Routes
 *
 * Donor Access (PTF), NGO Observer Access, Research Access, External Audit
 *
 * Note: All partner routes are READ-ONLY
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Partners\PartenaireController;
use App\Http\Controllers\Api\Partners\ProjetPartenaireController;
use App\Http\Controllers\Api\Partners\FinancementController;
use App\Http\Controllers\Api\Partners\PartnerProjectController;
use App\Http\Controllers\Api\Partners\ProjectIndicatorController;
use App\Http\Controllers\Api\Partners\PublicStatisticsController;
use App\Http\Controllers\Api\Partners\ResearchDataController;
use App\Http\Controllers\Api\Partners\ExternalAuditController;

/*
|--------------------------------------------------------------------------
| Protected Partners Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('partners')->name('partners.')->group(function () {

    // Partenaires (CRUD)
    Route::apiResource('partenaires', PartenaireController::class);
    
    // Projets de Partenariat (CRUD)
    Route::apiResource('projets', ProjetPartenaireController::class);
    Route::get('projets/{projet}/financements', [FinancementController::class, 'byProject'])
        ->name('projets.financements');
    
    // Financements (CRUD)
    Route::apiResource('financements', FinancementController::class);
    
    // Statistics
    Route::get('statistics', [PartenaireController::class, 'statistics'])
        ->name('statistics');

    // Partner Projects (PTF - Partenaires Techniques et Financiers)
    Route::get('projects', [PartnerProjectController::class, 'index'])
        ->name('projects.index');
    Route::get('projects/{project}', [PartnerProjectController::class, 'show'])
        ->name('projects.show');
    Route::get('projects/{project}/indicators', [ProjectIndicatorController::class, 'index'])
        ->name('projects.indicators');
    Route::get('projects/{project}/reports', [PartnerProjectController::class, 'reports'])
        ->name('projects.reports');
    Route::get('projects/{project}/progress', [PartnerProjectController::class, 'progress'])
        ->name('projects.progress');

    // Project Indicators
    Route::get('indicators', [ProjectIndicatorController::class, 'all'])
        ->name('indicators.all');
    Route::get('indicators/{indicator}', [ProjectIndicatorController::class, 'show'])
        ->name('indicators.show');
    Route::get('indicators/{indicator}/values', [ProjectIndicatorController::class, 'values'])
        ->name('indicators.values');
});

/*
|--------------------------------------------------------------------------
| Public Statistics Routes (NGO, Public Access)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('public')->name('public.')->group(function () {

    // Public Statistics
    Route::get('statistics', [PublicStatisticsController::class, 'index'])
        ->name('statistics.index');
    Route::get('statistics/education-indicators', [PublicStatisticsController::class, 'educationIndicators'])
        ->name('statistics.education-indicators');
    Route::get('statistics/school-coverage', [PublicStatisticsController::class, 'schoolCoverage'])
        ->name('statistics.school-coverage');
    Route::get('statistics/enrollment', [PublicStatisticsController::class, 'enrollment'])
        ->name('statistics.enrollment');
    Route::get('statistics/by-province/{province}', [PublicStatisticsController::class, 'byProvince'])
        ->name('statistics.by-province');
    Route::get('statistics/trends', [PublicStatisticsController::class, 'trends'])
        ->name('statistics.trends');
});

/*
|--------------------------------------------------------------------------
| Research Access Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('research')->name('research.')->group(function () {

    // Research Requests
    Route::post('requests', [ResearchDataController::class, 'createRequest'])
        ->name('requests.create');
    Route::get('requests/{request}', [ResearchDataController::class, 'showRequest'])
        ->name('requests.show');
    Route::get('requests/{request}/status', [ResearchDataController::class, 'requestStatus'])
        ->name('requests.status');

    // Anonymized Data Access
    Route::get('anonymized-data', [ResearchDataController::class, 'anonymizedData'])
        ->name('anonymized-data');
    Route::get('anonymized-data/schools', [ResearchDataController::class, 'anonymizedSchools'])
        ->name('anonymized-data.schools');
    Route::get('anonymized-data/students', [ResearchDataController::class, 'anonymizedStudents'])
        ->name('anonymized-data.students');
    Route::get('anonymized-data/results', [ResearchDataController::class, 'anonymizedResults'])
        ->name('anonymized-data.results');

    // Export
    Route::post('export', [ResearchDataController::class, 'export'])
        ->name('export');
    Route::get('export/{export}/download', [ResearchDataController::class, 'downloadExport'])
        ->name('export.download');
});

/*
|--------------------------------------------------------------------------
| External Audit Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('audit')->name('audit.')->group(function () {

    // Audit Data Access
    Route::get('data', [ExternalAuditController::class, 'data'])
        ->name('data');
    Route::get('data/schools', [ExternalAuditController::class, 'schools'])
        ->name('data.schools');
    Route::get('data/users', [ExternalAuditController::class, 'users'])
        ->name('data.users');
    Route::get('data/transactions', [ExternalAuditController::class, 'transactions'])
        ->name('data.transactions');

    // Audit Logs
    Route::get('logs', [ExternalAuditController::class, 'logs'])
        ->name('logs');
    Route::get('logs/by-date-range', [ExternalAuditController::class, 'logsByDateRange'])
        ->name('logs.by-date-range');

    // System Configuration (read-only)
    Route::get('system-config', [ExternalAuditController::class, 'systemConfig'])
        ->name('system-config');

    // Audit Reports
    Route::post('reports', [ExternalAuditController::class, 'createReport'])
        ->name('reports.create');
    Route::get('reports/{report}', [ExternalAuditController::class, 'showReport'])
        ->name('reports.show');
});
