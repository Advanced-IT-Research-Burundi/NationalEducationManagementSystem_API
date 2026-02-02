<?php

/**
 * NEMS API Routes
 *
 * Main API routing file that includes all modular routes.
 *
 * Modules:
 * 1. Core       - Authentication, Users, Roles, Permissions, Geographic Hierarchy
 * 2. Schools    - School Management, Workflow, Directory
 * 3. Pedagogy   - Inspections, Quality Standards, Training
 * 4. Exams      - Exam Planning, Centers, Results, Certification
 * 5. Statistics - Data Collection, KPIs, Dashboards, M&E
 * 6. Infrastructure - Buildings, Equipment, Maintenance
 * 7. HR         - Teachers, Assignments, Careers, Attendance
 * 8. System     - Administration, Helpdesk, Training Materials
 * 9. Partners   - PTF, NGO, Research, External Audit
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Version & Health Check
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return response()->json([
        'name' => 'NEMS API',
        'version' => '1.0.0',
        'status' => 'operational',
        'modules' => [
            'core',
            'schools',
            'academic',
            'pedagogy',
            'exams',
            'statistics',
            'infrastructure',
            'hr',
            'system',
            'partners',
        ],
    ]);
});

Route::get('/health', function () {
    return response()->json(['status' => 'healthy', 'timestamp' => now()]);
});

/*
|--------------------------------------------------------------------------
| Current User (Quick Access)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user()->load(['roles', 'permissions']);
});

/*
|--------------------------------------------------------------------------
| Module Routes
|--------------------------------------------------------------------------
|
| Each module has its own route file in routes/modules/
| Routes are organized by functional domain.
|
*/

// Module 1: Core (Auth, Users, Roles, Permissions, Geographic Hierarchy)
require __DIR__.'/modules/core.php';

// Module 2: Schools (School Management, Workflow)
require __DIR__.'/modules/schools.php';

// Module 2b: Academic (Niveaux, Classes, Enseignants, Élèves)
require __DIR__.'/modules/academic.php';

// Module 3: Pedagogy (Inspections, Quality Standards, Training)
require __DIR__.'/modules/pedagogy.php';

// Module 4: Exams (Planning, Centers, Results, Certification)
require __DIR__.'/modules/exams.php';

// Module 5: Statistics (Data Collection, KPIs, Dashboards, M&E)
require __DIR__.'/modules/statistics.php';

// Module 6: Infrastructure (Buildings, Equipment, Maintenance)
require __DIR__.'/modules/infrastructure.php';

// Module 7: HR (Teachers, Assignments, Careers, Attendance)
require __DIR__.'/modules/hr.php';

// Module 8: System (Administration, Helpdesk, Training Materials)
require __DIR__.'/modules/system.php';

// Module 9: Partners (PTF, NGO, Research, External Audit)
require __DIR__.'/modules/partners.php';

/*
|--------------------------------------------------------------------------
| Dashboard Routes (Role-Based Examples)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    // Admin National Dashboard
    Route::get('/admin/dashboard', function () {
        return response()->json([
            'message' => 'Welcome National Admin',
            'scope' => 'All Data',
            'modules' => ['core', 'schools', 'pedagogy', 'exams', 'statistics', 'infrastructure', 'hr', 'system', 'partners'],
        ]);
    })->middleware('role:admin_national')->name('dashboard.admin');

    // Provincial Director Dashboard
    Route::get('/province/dashboard', function () {
        return response()->json([
            'message' => 'Welcome Provincial Director',
            'scope' => 'Province Data',
            'modules' => ['core', 'schools', 'pedagogy', 'statistics', 'infrastructure', 'hr'],
        ]);
    })->middleware('role:directeur_provincial')->name('dashboard.province');

    // Communal Officer Dashboard
    Route::get('/commune/dashboard', function () {
        return response()->json([
            'message' => 'Welcome Communal Officer',
            'scope' => 'Commune Data',
            'modules' => ['core', 'schools', 'statistics'],
        ]);
    })->middleware('role:officier_communal')->name('dashboard.commune');

    // School Director Dashboard
    Route::get('/school/dashboard', function () {
        return response()->json([
            'message' => 'Welcome School Director',
            'scope' => 'School Data',
            'modules' => ['schools', 'hr'],
        ]);
    })->middleware('role:directeur_ecole')->name('dashboard.school');
});

/*
|--------------------------------------------------------------------------
| Data Export (Permission-Based)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/data/export', function () {
        return response()->json(['message' => 'Data Exported Successfully']);
    })->middleware('permission:export_data')->name('data.export');
});
