<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_update_a_system_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $superAdmin = User::query()->where('is_super_admin', true)->firstOrFail();
        $role = Role::query()->where('name', Role::ADMIN_NATIONAL)->firstOrFail();

        $response = $this
            ->actingAs($superAdmin, 'sanctum')
            ->putJson("/api/roles/{$role->id}", [
                'description' => 'Description modifiee par superadmin',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('description', 'Description modifiee par superadmin');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'description' => 'Description modifiee par superadmin',
        ]);
    }

    public function test_superadmin_can_create_another_superadmin_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $superAdmin = User::query()->where('is_super_admin', true)->firstOrFail();

        $response = $this
            ->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Second Super Admin',
                'email' => 'second-superadmin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => Role::SUPER_ADMIN,
                'admin_level' => 'PAYS',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'second-superadmin@example.com')
            ->assertJsonPath('user.is_super_admin', true);

        $createdUser = User::query()->where('email', 'second-superadmin@example.com')->firstOrFail();

        $this->assertTrue($createdUser->isSuperAdmin());
        $this->assertTrue($createdUser->fresh()->is_super_admin);
    }

    public function test_non_superadmin_cannot_create_a_superadmin_user(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $adminNational = User::factory()->create([
            'statut' => 'actif',
            'admin_level' => 'PAYS',
        ]);
        $adminNational->assignRole(Role::ADMIN_NATIONAL);

        $response = $this
            ->actingAs($adminNational, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Blocked Super Admin',
                'email' => 'blocked-superadmin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => Role::SUPER_ADMIN,
                'admin_level' => 'PAYS',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }
}
