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

        // create permissions
        $permissions = [
            'view_data',
            'create_data',
            'update_data',
            'delete_data',
            'validate_data',
            'export_data',
            'manage_users',
            'manage_schools',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign existing permissions
        
        // 1. Admin National
        $role1 = Role::firstOrCreate(['name' => 'Admin National']);
        $role1->givePermissionTo(Permission::all());

        // 2. Admin Ministère
        $role2 = Role::firstOrCreate(['name' => 'Admin Ministère']);
        $role2->givePermissionTo([
            'view_data', 'create_data', 'update_data', 'validate_data', 'export_data', 'manage_users', 'manage_schools'
        ]);

        // 3. Directeur Provincial
        $role3 = Role::firstOrCreate(['name' => 'Directeur Provincial']);
        $role3->givePermissionTo([
            'view_data', 'create_data', 'update_data', 'validate_data', 'export_data', 'manage_users', 'manage_schools'
        ]);

        // 4. Responsable Communal
        $role4 = Role::firstOrCreate(['name' => 'Responsable Communal']);
        $role4->givePermissionTo([
            'view_data', 'create_data', 'update_data', 'validate_data', 'export_data', 'manage_schools'
        ]);

        // 5. Superviseur Zone
        $role5 = Role::firstOrCreate(['name' => 'Superviseur Zone']);
        $role5->givePermissionTo([
            'view_data', 'create_data', 'update_data', 'validate_data', 'manage_schools'
        ]);

        // 6. Directeur École
        $role6 = Role::firstOrCreate(['name' => 'Directeur École']);
        $role6->givePermissionTo([
            'view_data', 'create_data', 'update_data', 'validate_data'
        ]);

        // 7. Enseignant
        $role7 = Role::firstOrCreate(['name' => 'Enseignant']);
        $role7->givePermissionTo([
            'view_data'
        ]);

        // 8. Agent Administratif
        $role8 = Role::firstOrCreate(['name' => 'Agent Administratif']);
        $role8->givePermissionTo([
            'view_data', 'create_data'
        ]);
    }
}
