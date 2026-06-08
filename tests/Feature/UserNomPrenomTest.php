<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $now = now();
    DB::table('pays')->insert(['id' => 1, 'code' => 'BI', 'name' => 'Burundi', 'created_at' => $now, 'updated_at' => $now]);

    $this->admin = User::factory()->create([
        'statut' => 'actif',
        'admin_level' => 'PAYS',
        'pays_id' => 1,
    ]);
    $this->admin->assignRole(Role::ADMIN_NATIONAL);
    $this->actingAs($this->admin, 'sanctum');
});

it('requires nom and prenom when creating a user', function () {
    Mail::fake();

    $response = $this->postJson('/api/users', [
        'email' => 'missing-names@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => Role::PERSONNEL_ADMINISTRATIF,
        'admin_level' => 'PAYS',
        'pays_id' => 1,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['nom', 'prenom']);
});

it('stores nom and prenom and syncs the legacy name column', function () {
    Mail::fake();

    $response = $this->postJson('/api/users', [
        'nom' => 'Ndayisaba',
        'prenom' => 'Jean',
        'email' => 'jean.ndayisaba@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => Role::PERSONNEL_ADMINISTRATIF,
        'admin_level' => 'PAYS',
        'pays_id' => 1,
    ]);

    $response->assertCreated();

    $user = User::query()->where('email', 'jean.ndayisaba@example.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->nom)->toBe('Ndayisaba')
        ->and($user->prenom)->toBe('Jean')
        ->and($user->name)->toBe('Ndayisaba Jean');
});

it('updates nom and prenom on an existing user', function () {
    $user = User::factory()->create([
        'nom' => 'Ancien',
        'prenom' => 'Nom',
        'name' => 'Ancien Nom',
        'statut' => 'actif',
        'admin_level' => 'PAYS',
        'pays_id' => 1,
    ]);
    $user->assignRole(Role::PERSONNEL_ADMINISTRATIF);

    $response = $this->putJson("/api/users/{$user->id}", [
        'nom' => 'Niyonzima',
        'prenom' => 'Marie',
    ]);

    $response->assertSuccessful();

    $user->refresh();

    expect($user->nom)->toBe('Niyonzima')
        ->and($user->prenom)->toBe('Marie')
        ->and($user->name)->toBe('Niyonzima Marie');
});
