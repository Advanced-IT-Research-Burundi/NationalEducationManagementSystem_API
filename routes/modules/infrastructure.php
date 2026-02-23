<?php

/**
 * Module Infrastructure Routes
 *
 * Building Management, Equipment Inventory, Maintenance
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Infrastructure\BuildingController;
use App\Http\Controllers\Api\Infrastructure\BuildingAssessmentController;
use App\Http\Controllers\Api\Infrastructure\EquipmentController;
use App\Http\Controllers\Api\Infrastructure\EquipmentMovementController;
use App\Http\Controllers\Api\Infrastructure\MaintenanceRequestController;
use App\Http\Controllers\Api\Infrastructure\MaintenanceReportController;
use App\Http\Controllers\Api\Infrastructure\RoomController;

/*
|--------------------------------------------------------------------------
| Protected Infrastructure Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('infrastructure')->name('infrastructure.')->group(function () {

    // Buildings
    Route::apiResource('buildings', BuildingController::class);
    Route::get('buildings/by-school/{school}', [BuildingController::class, 'bySchool'])
        ->name('buildings.by-school');
    Route::get('buildings/by-condition/{condition}', [BuildingController::class, 'byCondition'])
        ->name('buildings.by-condition');
    Route::get('buildings/statistics', [BuildingController::class, 'statistics'])
        ->name('buildings.statistics');

    // Rooms (Salles)
    Route::get('rooms/statistics', [RoomController::class, 'statistics'])
        ->name('rooms.statistics');
    Route::apiResource('rooms', RoomController::class)->parameters(['rooms' => 'salle']);

    // Building Assessments
    Route::apiResource('assessments', BuildingAssessmentController::class);
    Route::get('assessments/by-building/{building}', [BuildingAssessmentController::class, 'byBuilding'])
        ->name('assessments.by-building');
    Route::post('assessments/{assessment}/submit', [BuildingAssessmentController::class, 'submit'])
        ->name('assessments.submit');

    // Equipment
    Route::apiResource('equipment', EquipmentController::class);
    Route::get('equipment/inventory', [EquipmentController::class, 'inventory'])
        ->name('equipment.inventory');
    Route::get('equipment/by-school/{school}', [EquipmentController::class, 'bySchool'])
        ->name('equipment.by-school');
    Route::get('equipment/by-category/{category}', [EquipmentController::class, 'byCategory'])
        ->name('equipment.by-category');
    Route::post('equipment/{equipment}/transfer', [EquipmentController::class, 'transfer'])
        ->name('equipment.transfer');

    // Equipment Movements
    Route::apiResource('equipment-movements', EquipmentMovementController::class)->only(['index', 'show', 'store']);
    Route::get('equipment-movements/by-equipment/{equipment}', [EquipmentMovementController::class, 'byEquipment'])
        ->name('equipment-movements.by-equipment');

    // Maintenance Requests
    Route::apiResource('maintenance-requests', MaintenanceRequestController::class);
    Route::post('maintenance-requests/{request}/assign', [MaintenanceRequestController::class, 'assign'])
        ->name('maintenance-requests.assign');
    Route::post('maintenance-requests/{request}/start', [MaintenanceRequestController::class, 'start'])
        ->name('maintenance-requests.start');
    Route::post('maintenance-requests/{request}/complete', [MaintenanceRequestController::class, 'complete'])
        ->name('maintenance-requests.complete');
    Route::get('maintenance-requests/pending', [MaintenanceRequestController::class, 'pending'])
        ->name('maintenance-requests.pending');

    // Maintenance Reports
    Route::apiResource('maintenance-reports', MaintenanceReportController::class);
    Route::get('maintenance-reports/by-school/{school}', [MaintenanceReportController::class, 'bySchool'])
        ->name('maintenance-reports.by-school');
    Route::get('maintenance-reports/summary', [MaintenanceReportController::class, 'summary'])
        ->name('maintenance-reports.summary');
});
