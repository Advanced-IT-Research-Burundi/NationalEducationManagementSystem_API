<?php

/**
 * Module System Routes
 *
 * System Administration, Helpdesk & Support, Training Materials
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\System\SystemConfigController;
use App\Http\Controllers\Api\System\BackupController;
use App\Http\Controllers\Api\System\ApiKeyController;
use App\Http\Controllers\Api\System\AuditLogController;
use App\Http\Controllers\Api\System\SupportTicketController;
use App\Http\Controllers\Api\System\TrainingMaterialController;
use App\Http\Controllers\Api\System\SystemHealthController;

/*
|--------------------------------------------------------------------------
| Protected System Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('system')->name('system.')->group(function () {

    // System Health
    Route::get('health', [SystemHealthController::class, 'index'])
        ->name('health');
    Route::get('health/database', [SystemHealthController::class, 'database'])
        ->name('health.database');
    Route::get('health/storage', [SystemHealthController::class, 'storage'])
        ->name('health.storage');
    Route::get('health/queue', [SystemHealthController::class, 'queue'])
        ->name('health.queue');

    // System Configuration
    Route::get('config', [SystemConfigController::class, 'index'])
        ->name('config.index');
    Route::put('config', [SystemConfigController::class, 'update'])
        ->name('config.update');
    Route::get('config/{key}', [SystemConfigController::class, 'show'])
        ->name('config.show');

    // Backups
    Route::apiResource('backups', BackupController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])
        ->name('backups.restore');
    Route::get('backups/{backup}/download', [BackupController::class, 'download'])
        ->name('backups.download');

    // API Keys
    Route::apiResource('api-keys', ApiKeyController::class);
    Route::post('api-keys/{key}/regenerate', [ApiKeyController::class, 'regenerate'])
        ->name('api-keys.regenerate');
    Route::post('api-keys/{key}/revoke', [ApiKeyController::class, 'revoke'])
        ->name('api-keys.revoke');

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->name('audit-logs.index');
    Route::get('audit-logs/{log}', [AuditLogController::class, 'show'])
        ->name('audit-logs.show');
    Route::get('audit-logs/by-user/{user}', [AuditLogController::class, 'byUser'])
        ->name('audit-logs.by-user');
    Route::get('audit-logs/by-action/{action}', [AuditLogController::class, 'byAction'])
        ->name('audit-logs.by-action');
    Route::get('audit-logs/export', [AuditLogController::class, 'export'])
        ->name('audit-logs.export');

    // Support Tickets
    Route::apiResource('tickets', SupportTicketController::class);
    Route::post('tickets/{ticket}/respond', [SupportTicketController::class, 'respond'])
        ->name('tickets.respond');
    Route::post('tickets/{ticket}/assign', [SupportTicketController::class, 'assign'])
        ->name('tickets.assign');
    Route::post('tickets/{ticket}/close', [SupportTicketController::class, 'close'])
        ->name('tickets.close');
    Route::post('tickets/{ticket}/reopen', [SupportTicketController::class, 'reopen'])
        ->name('tickets.reopen');
    Route::get('tickets/open', [SupportTicketController::class, 'open'])
        ->name('tickets.open');
    Route::get('tickets/by-status/{status}', [SupportTicketController::class, 'byStatus'])
        ->name('tickets.by-status');

    // Training Materials
    Route::apiResource('training-materials', TrainingMaterialController::class);
    Route::get('training-materials/by-category/{category}', [TrainingMaterialController::class, 'byCategory'])
        ->name('training-materials.by-category');
    Route::get('training-materials/{material}/download', [TrainingMaterialController::class, 'download'])
        ->name('training-materials.download');

    // User Management (System Admin specific)
    Route::post('users/{user}/reset-password', [SystemConfigController::class, 'resetUserPassword'])
        ->name('users.reset-password');
    Route::get('users/{user}/activity-logs', [AuditLogController::class, 'userActivity'])
        ->name('users.activity-logs');
});
