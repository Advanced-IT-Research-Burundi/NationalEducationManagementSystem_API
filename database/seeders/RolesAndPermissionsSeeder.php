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
            'setting'
        ];

        // 2. Define Actions
        $actions = [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'restore',
            'force_delete'
        ];

        // 3. Create Permissions
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}_{$entity}"]);
            }
        }
        
        // Special permissions
         Permission::firstOrCreate(['name' => 'access_dashboard']);
         Permission::firstOrCreate(['name' => 'access_settings']);

        // 4. Create Roles
        // Admin National (Super Admin)
        $adminNational = Role::firstOrCreate(['name' => 'Admin National']);
        $adminNational->givePermissionTo(Permission::all());

        // ... other roles can be defined here if needed, keeping existing logic for reference
        // but ensuring strict hierarchy as per Spatie best practices
        
        // 5. Assign to User ID 1
        $user = \App\Models\User::find(1);
        if ($user) {
            $user->assignRole($adminNational);
        }
    }
}
