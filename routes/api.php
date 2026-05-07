<?php

/**
 * NEMS API Routes
 *
 * Main API routing file that includes all modular routes.
 *
 * Modules:
 * 1. Core       - Authentication, Users, Roles, Permissions, Geographic Hierarchy
 * 2. Schools    - School Management, Workflow, Directory
 * 4. Statistics - KPIs, Dashboards, M&E
 * 5. Infrastructure - Buildings, Equipment, Maintenance
 * 6. HR         - Teachers, Assignments, Careers, Attendance
 * 7. System     - Administration, Helpdesk, Training Materials
 * 8. Partners   - PTF, NGO, Research, External Audit
 */

use App\Http\Controllers\Api\Academic\AnneeScolaireController;
use App\Http\Controllers\Api\Academic\CycleScolaireController;
use App\Http\Controllers\Api\Academic\MatiereController;
use App\Http\Controllers\Api\Academic\NiveauController;
use App\Http\Controllers\Api\Academic\SectionController;
use App\Http\Controllers\Api\Academic\TypeScolaireController;
use App\Http\Controllers\Api\Academic\EvaluationController;
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

// Module 4: Statistics (KPIs, Dashboards, M&E)
require __DIR__.'/modules/statistics.php';

// Module 5: Infrastructure (Buildings, Equipment, Maintenance)
require __DIR__.'/modules/infrastructure.php';

// Module 6: HR (Teachers, Assignments, Careers, Attendance)
require __DIR__.'/modules/hr.php';

// Module 7: System (Administration, Helpdesk, Training Materials)
require __DIR__.'/modules/system.php';

// Module 8: Partners (PTF, NGO, Research, External Audit)
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
            'modules' => ['core', 'schools', 'statistics', 'infrastructure', 'hr', 'system', 'partners'],
        ]);
    })->middleware('role:admin_national')->name('dashboard.admin');

    // Provincial Director Dashboard
    Route::get('/province/dashboard', function () {
        return response()->json([
            'message' => 'Welcome Provincial Director',
            'scope' => 'Province Data',
            'modules' => ['core', 'schools', 'statistics', 'infrastructure', 'hr'],
        ]);
    })->middleware('role:directeur_provincial')->name('dashboard.province');

    // Communal Officer Dashboard
    Route::get('/commune/dashboard', function () {
        return response()->json([
            'message' => 'Welcome Communal Officer',
            'scope' => 'Commune Data',
            'modules' => ['core', 'schools', 'statistics'],
        ]);
    })->middleware('role:agent_communal')->name('dashboard.commune');

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

/*
|--------------------------------------------------------------------------
| Settings / Configuration Routes (Alias for UI compatibility)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    // Années Scolaires (alias without /academic prefix for UI)
    Route::get('annees-scolaires/list', [AnneeScolaireController::class, 'list']);
    Route::get('annees-scolaires/current', [AnneeScolaireController::class, 'current']);
    Route::post('annees-scolaires/{anneeScolaire}/toggle-active', [AnneeScolaireController::class, 'toggleActive']);
    // Paramètre nommé `anneeScolaire` (camelCase) pour aligner avec le binding implicite
    // de `AnneeScolaire $anneeScolaire` dans le contrôleur, et avec routes/modules/academic.php.
    Route::apiResource('annees-scolaires', AnneeScolaireController::class)->parameters([
        'annees-scolaires' => 'anneeScolaire',
    ]);

    // Niveaux Scolaires (alias without /academic prefix for UI)
    Route::get('niveaux-scolaires/list', [NiveauController::class, 'list']);
    Route::apiResource('niveaux-scolaires', NiveauController::class)->parameters([
        'niveaux-scolaires' => 'niveau',
    ]);

    // Types scolaires (alias without /academic prefix for UI)
    Route::get('types-scolaires/list', [TypeScolaireController::class, 'list']);
    Route::apiResource('types-scolaires', TypeScolaireController::class)->parameters([
        'types-scolaires' => 'typeScolaire',
    ]);

    // Cycles scolaires (alias without /academic prefix for UI)
    Route::get('cycles-scolaires/list', [CycleScolaireController::class, 'list']);
    Route::apiResource('cycles-scolaires', CycleScolaireController::class)->parameters([
        'cycles-scolaires' => 'cycleScolaire',
    ]);

    // Sections scolaires (alias without /academic prefix for UI)
    Route::get('sections-scolaires/list', [SectionController::class, 'list']);
    Route::apiResource('sections-scolaires', SectionController::class)->parameters([
        'sections-scolaires' => 'section',
    ]);

    // Matières (alias without /academic prefix for UI)
    Route::get('matieres/list', [MatiereController::class, 'list']);
    Route::apiResource('matieres', MatiereController::class);

    // Évaluations (alias without /academic prefix for UI)
    Route::get('evaluations/by-classe/{classe}', [EvaluationController::class, 'byClasse']);
    Route::get('evaluations/by-eleve/{eleve}', [EvaluationController::class, 'byEleve']);
    Route::apiResource('evaluations', EvaluationController::class);

    // Route::get('eleves/export', [EleveController::class, 'export']);
});
