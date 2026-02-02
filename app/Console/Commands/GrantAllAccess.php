<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantAllAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:grant-all {id : The ID of the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant ALL roles and ALL permissions to a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        if (Role::count() === 0 && Permission::count() === 0) {
            $this->warn('No roles or permissions found. Please run the seeder first.');
            return 1;
        }

        // 1. Assign All Roles
        $roles = Role::all();
        if ($roles->isNotEmpty()) {
            $user->syncRoles($roles); // syncRoles replaces existing rules. use assignRole to add.
            // User requested "associe tout les roles", usually implies "have all of them". 
            // assignRole adds to existing. syncRoles forces the set.
            // Given the context of "Grant All", ensuring they have exactly ALL matches the intent.
            $this->info("Assigned " . $roles->count() . " roles to User ID {$userId}.");
        }

        // 2. Assign All Permissions
        // Note: Usually assigning roles is enough if roles have permissions, 
        // but the user explicitly asked for "tout les roles et les permission".
        // Direct permission assignment overrides role permissions.
        $permissions = Permission::all();
        if ($permissions->isNotEmpty()) {
            $user->syncPermissions($permissions);
            $this->info("Assigned " . $permissions->count() . " permissions to User ID {$userId}.");
        }

        $this->info("User ID {$userId} now has full access (All Roles & Permissions).");
        return 0;
    }
}
