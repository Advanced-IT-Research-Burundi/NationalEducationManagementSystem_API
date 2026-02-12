<?php

/**
 * Module Statistics Routes
 *
 * Data Collection, KPIs & Dashboards, Strategic Planning, M&E (Monitoring & Evaluation)
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Statistics\DataCollectionController;
use App\Http\Controllers\Api\Statistics\CollectionCampaignController;
use App\Http\Controllers\Api\Statistics\KpiController;
use App\Http\Controllers\Api\Statistics\DashboardController;
use App\Http\Controllers\Api\Statistics\StrategicPlanController;
use App\Http\Controllers\Api\Statistics\StatisticalReportController;
use App\Http\Controllers\Api\Statistics\AlertController;

/*
|--------------------------------------------------------------------------
| Protected Statistics Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('statistics')->name('statistics.')->group(function () {

    // Data Collections
    Route::apiResource('data-collections', DataCollectionController::class);
    Route::post('data-collections/{collection}/validate', [DataCollectionController::class, 'validateData'])
        ->name('data-collections.validate');
    Route::post('data-collections/{collection}/flag', [DataCollectionController::class, 'flagIssue'])
        ->name('data-collections.flag');
    Route::get('data-collections/by-school/{school}', [DataCollectionController::class, 'bySchool'])
        ->name('data-collections.by-school');

    // Collection Campaigns
    Route::apiResource('campaigns', CollectionCampaignController::class);
    Route::post('campaigns/{campaign}/start', [CollectionCampaignController::class, 'start'])
        ->name('campaigns.start');
    Route::post('campaigns/{campaign}/close', [CollectionCampaignController::class, 'close'])
        ->name('campaigns.close');
    Route::get('campaigns/{campaign}/progress', [CollectionCampaignController::class, 'progress'])
        ->name('campaigns.progress');

    // KPIs
    Route::apiResource('kpis', KpiController::class);
    Route::get('kpis/{kpi}/values', [KpiController::class, 'values'])
        ->name('kpis.values');
    Route::post('kpis/{kpi}/calculate', [KpiController::class, 'calculate'])
        ->name('kpis.calculate');
    Route::get('kpis/by-category/{category}', [KpiController::class, 'byCategory'])
        ->name('kpis.by-category');

    // Dashboards
    Route::apiResource('dashboards', DashboardController::class);
    Route::get('dashboards/{dashboard}/data', [DashboardController::class, 'data'])
        ->name('dashboards.data');
    Route::get('dashboards/{dashboard}/export', [DashboardController::class, 'export'])
        ->name('dashboards.export');

    // Quick Dashboard Endpoints
    Route::get('dashboard/national', [DashboardController::class, 'national'])
        ->name('dashboard.national');
    Route::get('dashboard/provincial/{province}', [DashboardController::class, 'provincial'])
        ->name('dashboard.provincial');
    Route::get('dashboard/communal/{commune}', [DashboardController::class, 'communal'])
        ->name('dashboard.communal');
    Route::get('dashboard/ecole/{ecole}', [DashboardController::class, 'ecole'])
        ->name('dashboard.ecole');
    Route::post('dashboard/clear-cache', [DashboardController::class, 'clearCache'])
        ->name('dashboard.clear-cache');

    // Strategic Plans
    Route::apiResource('strategic-plans', StrategicPlanController::class);
    Route::get('strategic-plans/{plan}/objectives', [StrategicPlanController::class, 'objectives'])
        ->name('strategic-plans.objectives');
    Route::get('strategic-plans/{plan}/progress', [StrategicPlanController::class, 'progress'])
        ->name('strategic-plans.progress');

    // Statistical Reports
    Route::apiResource('reports', StatisticalReportController::class);
    Route::post('reports/generate', [StatisticalReportController::class, 'generate'])
        ->name('reports.generate');
    Route::get('reports/{report}/export', [StatisticalReportController::class, 'export'])
        ->name('reports.export');
    Route::get('reports/annual/{year}', [StatisticalReportController::class, 'annual'])
        ->name('reports.annual');

    // Alerts
    Route::apiResource('alerts', AlertController::class);
    Route::post('alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge'])
        ->name('alerts.acknowledge');
    Route::post('alerts/{alert}/resolve', [AlertController::class, 'resolve'])
        ->name('alerts.resolve');
    Route::get('alerts/pending', [AlertController::class, 'pending'])
        ->name('alerts.pending');
});
