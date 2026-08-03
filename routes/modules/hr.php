<?php

/**
 * Module HR Routes
 *
 * Teacher Profiles, Assignments & Transfers, Career Management, Attendance
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HR\TeacherController;
use App\Http\Controllers\Api\HR\TeacherQualificationController;
use App\Http\Controllers\Api\HR\TeacherAssignmentController;
use App\Http\Controllers\Api\HR\TransferRequestController;
use App\Http\Controllers\Api\HR\CareerController;
use App\Http\Controllers\Api\HR\AttendanceController;
use App\Http\Controllers\Api\HR\LeaveRequestController;
use App\Http\Controllers\Api\HR\ServiceController;
use App\Http\Controllers\Api\HR\FonctionController;
use App\Http\Controllers\Api\HR\PersonnelAdministratifController;
use App\Http\Controllers\Api\HR\PersonnelAdministratifMouvementController;
use App\Http\Controllers\Api\HR\RHDashboardController;
use App\Http\Controllers\Api\HR\DepartementController;
use App\Http\Controllers\Api\HR\PosteController;
use App\Http\Controllers\Api\HR\EmployeController;
use App\Http\Controllers\Api\HR\FormationController;

/*
|--------------------------------------------------------------------------
| Protected HR Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('hr')->name('hr.')->group(function () {

    Route::get('dashboard', [RHDashboardController::class, 'index'])
        ->middleware('permission:view_hr_dashboard|manage_hr')
        ->name('dashboard');

    // RH transversal
    Route::get('departements/hierarchy', [DepartementController::class, 'hierarchy'])
        ->middleware('permission:view_hr_dashboard|manage_hr')
        ->name('departements.hierarchy');
    Route::get('departements/export', [DepartementController::class, 'export'])
        ->middleware('permission:view_hr_dashboard|manage_hr_departments|manage_hr')
        ->name('departements.export');
    Route::post('departements/import', [DepartementController::class, 'import'])
        ->middleware('permission:manage_hr_departments|manage_hr')
        ->name('departements.import');
    Route::apiResource('departements', DepartementController::class)
        ->middleware('permission:view_hr_dashboard|manage_hr_departments|manage_hr');

    Route::get('postes/export', [PosteController::class, 'export'])
        ->middleware('permission:view_hr_dashboard|manage_hr_positions|manage_hr')
        ->name('postes.export');
    Route::post('postes/import', [PosteController::class, 'import'])
        ->middleware('permission:manage_hr_positions|manage_hr')
        ->name('postes.import');
    Route::apiResource('postes', PosteController::class)
        ->middleware('permission:view_hr_dashboard|manage_hr_positions|manage_hr');

    Route::get('services/export', [ServiceController::class, 'export'])
        ->middleware('permission:view_hr_dashboard|manage_hr_services|manage_hr')
        ->name('services.export');
    Route::post('services/import', [ServiceController::class, 'import'])
        ->middleware('permission:manage_hr_services|manage_hr')
        ->name('services.import');
    Route::apiResource('services', ServiceController::class)
        ->middleware('permission:view_hr_dashboard|manage_hr_services|manage_hr');
    Route::get('services/{service}/employes', [EmployeController::class, 'byService'])
        ->middleware('permission:view_hr_dashboard|manage_hr_services|manage_hr_employees|manage_hr')
        ->name('services.employes');

    Route::apiResource('fonctions', FonctionController::class);
    Route::get('services/{service}/fonctions', [FonctionController::class, 'byService'])
        ->name('services.fonctions');

    Route::get('employes/history', [EmployeController::class, 'dashboardHistory'])
        ->middleware('permission:view_hr_dashboard|manage_hr_employees|manage_hr')
        ->name('employes.history');
    Route::post('employes/import', [EmployeController::class, 'import'])
        ->middleware('permission:manage_hr_employees|manage_hr')
        ->name('employes.import');
    Route::get('employes/export', [EmployeController::class, 'export'])
        ->middleware('permission:export_hr_reports|manage_hr')
        ->name('employes.export');
    Route::post('employes/{employe}/archive', [EmployeController::class, 'archive'])
        ->middleware('permission:manage_hr_employees|manage_hr')
        ->name('employes.archive');
    Route::post('employes/{id}/restore', [EmployeController::class, 'restore'])
        ->middleware('permission:manage_hr_employees|manage_hr')
        ->name('employes.restore');
    Route::get('employes/{employe}/history', [EmployeController::class, 'history'])
        ->middleware('permission:view_hr_dashboard|manage_hr_employees|manage_hr')
        ->name('employes.history-item');
    Route::apiResource('employes', EmployeController::class)
        ->parameters(['employes' => 'employe'])
        ->middleware('permission:view_hr_dashboard|manage_hr_employees|manage_hr');

    Route::apiResource('formations', FormationController::class)
        ->middleware('permission:view_hr_dashboard|manage_hr_learning|manage_hr');
    Route::get('formations/export', [FormationController::class, 'export'])
        ->middleware('permission:view_hr_dashboard|manage_hr_learning|manage_hr')
        ->name('formations.export');
    Route::post('formations/import', [FormationController::class, 'import'])
        ->middleware('permission:manage_hr_learning|manage_hr')
        ->name('formations.import');

    // Legacy personnel routes, kept for compatibility
    Route::get('personnels/statistics', [PersonnelAdministratifController::class, 'statistics'])
        ->name('personnels.statistics');
    Route::get('personnels/export', [PersonnelAdministratifController::class, 'export'])
        ->name('personnels.export');
    Route::apiResource('personnels', PersonnelAdministratifController::class)
        ->parameters(['personnels' => 'personnelAdministratif']);
    Route::get('personnels/{personnelAdministratif}/mouvements', [PersonnelAdministratifController::class, 'mouvements'])
        ->name('personnels.mouvements');
    Route::apiResource('mouvements', PersonnelAdministratifMouvementController::class)
        ->only(['index', 'show'])
        ->parameters(['mouvements' => 'mouvement']);

    // Teachers
    Route::apiResource('teachers', TeacherController::class);
    Route::get('teachers/by-school/{school}', [TeacherController::class, 'bySchool'])
        ->name('teachers.by-school');
    Route::get('teachers/by-qualification/{qualification}', [TeacherController::class, 'byQualification'])
        ->name('teachers.by-qualification');
    Route::get('teachers/statistics', [TeacherController::class, 'statistics'])
        ->name('teachers.statistics');

    // Teacher Qualifications
    Route::apiResource('teachers.qualifications', TeacherQualificationController::class)
        ->shallow();
    Route::post('qualifications/{qualification}/verify', [TeacherQualificationController::class, 'verify'])
        ->name('qualifications.verify');

    // Teacher Assignments
    Route::apiResource('assignments', TeacherAssignmentController::class);
    Route::get('teachers/{teacher}/assignment-history', [TeacherAssignmentController::class, 'history'])
        ->name('assignments.history');
    Route::get('assignments/by-school/{school}', [TeacherAssignmentController::class, 'bySchool'])
        ->name('assignments.by-school');
    Route::get('assignments/current', [TeacherAssignmentController::class, 'current'])
        ->name('assignments.current');

    // Transfer Requests
    Route::apiResource('transfer-requests', TransferRequestController::class);
    Route::post('transfer-requests/{request}/approve', [TransferRequestController::class, 'approve'])
        ->name('transfer-requests.approve');
    Route::post('transfer-requests/{request}/reject', [TransferRequestController::class, 'reject'])
        ->name('transfer-requests.reject');
    Route::get('transfer-requests/pending', [TransferRequestController::class, 'pending'])
        ->name('transfer-requests.pending');

    // Career Management
    Route::get('careers', [CareerController::class, 'index'])
        ->name('careers.index');
    Route::get('careers/teacher/{teacher}', [CareerController::class, 'show'])
        ->name('careers.teacher');
    Route::get('careers/teacher/{teacher}/history', [CareerController::class, 'history'])
        ->name('careers.teacher.history');
    Route::post('careers/teacher/{teacher}/promote', [CareerController::class, 'promote'])
        ->name('careers.teacher.promote');
    Route::get('careers/grades', [CareerController::class, 'grades'])
        ->name('careers.grades');
    
    // Legacy career routes (keep for backward compatibility)
    Route::get('teachers/{teacher}/career', [CareerController::class, 'show'])
        ->name('career.show');
    Route::post('teachers/{teacher}/promotions', [CareerController::class, 'promote'])
        ->name('career.promote');
    Route::get('teachers/{teacher}/career-history', [CareerController::class, 'history'])
        ->name('career.history');
    Route::get('career/grades', [CareerController::class, 'grades'])
        ->name('career.grades');

    // Attendance
    Route::apiResource('attendance', AttendanceController::class)->only(['index', 'store', 'show']);
    Route::get('attendance/by-teacher/{teacher}', [AttendanceController::class, 'byTeacher'])
        ->name('attendance.by-teacher');
    Route::get('attendance/by-school/{school}', [AttendanceController::class, 'bySchool'])
        ->name('attendance.by-school');
    Route::get('attendance/summary', [AttendanceController::class, 'summary'])
        ->name('attendance.summary');
    Route::post('attendance/bulk', [AttendanceController::class, 'bulkStore'])
        ->name('attendance.bulk');

    // Leave Requests
    Route::apiResource('leave-requests', LeaveRequestController::class);
    Route::post('leave-requests/{request}/approve', [LeaveRequestController::class, 'approve'])
        ->name('leave-requests.approve');
    Route::post('leave-requests/{request}/reject', [LeaveRequestController::class, 'reject'])
        ->name('leave-requests.reject');
    Route::get('leave-requests/by-teacher/{teacher}', [LeaveRequestController::class, 'byTeacher'])
        ->name('leave-requests.by-teacher');
    Route::get('leave-requests/pending', [LeaveRequestController::class, 'pending'])
        ->name('leave-requests.pending');
});
