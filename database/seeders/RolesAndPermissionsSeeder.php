<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Entity Groups
        $entities = [
            'user',
            'role',
            'permission',
            'pays',
            'ministere',
            'province',
            'commune',
            'zone',
            'colline',
            'school',
            'student',
            'teacher',
            'report',
            'setting',
            'classe',
            'eleve',
            'enseignant',
            'annee_scolaire',
            'trimestre',
            'matiere',
            'note',
            'bulletin',
            'inscription',
            'paiement',
            'audit',
        ];

        // 2. Define Actions
        $actions = [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'restore',
            'force_delete',
        ];

        // 3. Create Permissions
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}_{$entity}", 'guard_name' => 'api']);
            }
        }

        // Special permissions
        $specialPermissions = [
            'access_dashboard',
            'access_settings',
            'validate_data',
            'export_data',
            'import_data',
            'manage_users',
            'manage_schools',
            'manage_roles',
            'manage_permissions',
            'submit_school',
            'validate_school',
            'deactivate_school',
            'view_statistics',
            'view_reports',
            'generate_reports',
            'manage_academic_year',
            'manage_classes',
            'manage_students',
            'manage_teachers',
            'manage_grades',
            'print_bulletins',
            'view_audit_logs',
        ];

        foreach ($specialPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // 4. Create Roles with appropriate permissions

        // Admin National (Super Admin) - Full access to everything
        $adminNational = Role::firstOrCreate(['name' => 'Admin National', 'guard_name' => 'api']);
        $adminNational->syncPermissions(Permission::all());

        // Admin Ministère - Ministry level administration
        $adminMinistry = Role::firstOrCreate(['name' => 'Admin Ministère', 'guard_name' => 'api']);
        $adminMinistry->syncPermissions([
            // User management
            'view_user', 'view_any_user', 'create_user', 'update_user',
            // Geographic hierarchy (view only for higher levels)
            'view_pays', 'view_any_pays',
            'view_ministere', 'view_any_ministere', 'update_ministere',
            'view_province', 'view_any_province', 'create_province', 'update_province', 'delete_province',
            'view_commune', 'view_any_commune', 'create_commune', 'update_commune', 'delete_commune',
            'view_zone', 'view_any_zone', 'create_zone', 'update_zone', 'delete_zone',
            'view_colline', 'view_any_colline', 'create_colline', 'update_colline', 'delete_colline',
            // Schools
            'view_school', 'view_any_school', 'create_school', 'update_school', 'delete_school',
            'validate_school', 'deactivate_school',
            // Students & Teachers
            'view_student', 'view_any_student', 'view_teacher', 'view_any_teacher',
            // Reports
            'view_report', 'view_any_report', 'create_report', 'view_statistics', 'view_reports', 'generate_reports',
            // Special
            'access_dashboard', 'access_settings', 'validate_data', 'export_data', 'manage_schools',
        ]);

        // Directeur Provincial - Provincial level
        $directeurProvincial = Role::firstOrCreate(['name' => 'Directeur Provincial', 'guard_name' => 'api']);
        $directeurProvincial->syncPermissions([
            'view_user', 'view_any_user', 'create_user', 'update_user',
            'view_province', 'view_any_province',
            'view_commune', 'view_any_commune', 'create_commune', 'update_commune',
            'view_zone', 'view_any_zone', 'create_zone', 'update_zone',
            'view_colline', 'view_any_colline', 'create_colline', 'update_colline',
            'view_school', 'view_any_school', 'create_school', 'update_school',
            'validate_school', 'submit_school',
            'view_student', 'view_any_student', 'view_teacher', 'view_any_teacher',
            'view_report', 'view_any_report', 'create_report',
            'access_dashboard', 'validate_data', 'export_data', 'view_statistics', 'view_reports',
        ]);

        // Agent Communal - Commune level
        $agentCommunal = Role::firstOrCreate(['name' => 'Agent Communal', 'guard_name' => 'api']);
        $agentCommunal->syncPermissions([
            'view_user', 'view_any_user',
            'view_commune', 'view_any_commune',
            'view_zone', 'view_any_zone', 'create_zone', 'update_zone',
            'view_colline', 'view_any_colline', 'create_colline', 'update_colline',
            'view_school', 'view_any_school', 'create_school', 'update_school',
            'submit_school',
            'view_student', 'view_any_student', 'view_teacher', 'view_any_teacher',
            'view_report', 'view_any_report',
            'access_dashboard', 'view_statistics', 'view_reports',
        ]);

        // Superviseur Zone - Zone level
        $superviseurZone = Role::firstOrCreate(['name' => 'Superviseur Zone', 'guard_name' => 'api']);
        $superviseurZone->syncPermissions([
            'view_user', 'view_any_user',
            'view_zone', 'view_any_zone',
            'view_colline', 'view_any_colline', 'create_colline', 'update_colline',
            'view_school', 'view_any_school', 'update_school',
            'submit_school',
            'view_student', 'view_any_student', 'view_teacher', 'view_any_teacher',
            'view_report', 'view_any_report',
            'access_dashboard', 'view_statistics',
        ]);

        // Directeur École - School Director
        $directeurEcole = Role::firstOrCreate(['name' => 'Directeur École', 'guard_name' => 'api']);
        $directeurEcole->syncPermissions([
            'view_user', 'view_any_user', 'create_user', 'update_user',
            'view_school', 'update_school',
            'view_student', 'view_any_student', 'create_student', 'update_student', 'delete_student',
            'view_teacher', 'view_any_teacher', 'create_teacher', 'update_teacher',
            'view_classe', 'view_any_classe', 'create_classe', 'update_classe', 'delete_classe',
            'view_eleve', 'view_any_eleve', 'create_eleve', 'update_eleve', 'delete_eleve',
            'view_enseignant', 'view_any_enseignant', 'create_enseignant', 'update_enseignant',
            'view_inscription', 'view_any_inscription', 'create_inscription', 'update_inscription',
            'view_note', 'view_any_note', 'create_note', 'update_note',
            'view_bulletin', 'view_any_bulletin', 'create_bulletin', 'print_bulletins',
            'view_paiement', 'view_any_paiement', 'create_paiement', 'update_paiement',
            'view_report', 'view_any_report', 'create_report',
            'access_dashboard', 'manage_classes', 'manage_students', 'manage_teachers',
            'manage_grades', 'view_statistics', 'view_reports',
        ]);

        // Enseignant - Teacher
        $enseignant = Role::firstOrCreate(['name' => 'Enseignant', 'guard_name' => 'api']);
        $enseignant->syncPermissions([
            'view_school',
            'view_student', 'view_any_student',
            'view_classe', 'view_any_classe',
            'view_eleve', 'view_any_eleve',
            'view_matiere', 'view_any_matiere',
            'view_note', 'view_any_note', 'create_note', 'update_note',
            'view_bulletin', 'view_any_bulletin',
            'access_dashboard', 'manage_grades',
        ]);

        // Personnel Administratif - Administrative Staff
        $personnelAdmin = Role::firstOrCreate(['name' => 'Personnel Administratif', 'guard_name' => 'api']);
        $personnelAdmin->syncPermissions([
            'view_school',
            'view_student', 'view_any_student', 'create_student', 'update_student',
            'view_eleve', 'view_any_eleve', 'create_eleve', 'update_eleve',
            'view_classe', 'view_any_classe',
            'view_inscription', 'view_any_inscription', 'create_inscription', 'update_inscription',
            'view_paiement', 'view_any_paiement', 'create_paiement', 'update_paiement',
            'view_bulletin', 'view_any_bulletin', 'print_bulletins',
            'access_dashboard', 'manage_students',
        ]);

        // 5. Assign Admin National to User ID 1
        $user = \App\Models\User::find(1);
        if ($user) {
            $user->assignRole($adminNational);
        }
    }
}
