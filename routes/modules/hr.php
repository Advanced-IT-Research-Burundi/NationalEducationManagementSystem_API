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

/*
|--------------------------------------------------------------------------
| Protected HR Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('hr')->name('hr.')->group(function () {

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
