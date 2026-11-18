<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('returns labelled recent activities to a super admin', function (): void {
    $superAdmin = User::query()->where('is_super_admin', true)->firstOrFail();
    $target = User::factory()->create()->refresh();

    activity('users')
        ->performedOn($target)
        ->causedBy($superAdmin)
        ->event('updated')
        ->log('updated');

    activity('default')
        ->performedOn($target)
        ->event('deleted')
        ->log('deleted');

    $response = $this->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/system/audit-logs/recent?limit=2');

    $response->assertSuccessful()->assertJsonCount(2, 'data');

    expect($response->json('data.0'))->toMatchArray([
        'event' => 'deleted',
        'event_label' => 'Suppression',
        'subject_type_label' => 'Utilisateur',
        'subject_label' => $target->name,
        'causer_name' => 'Système',
    ])->and($response->json('data.1.event_label'))->toBe('Modification')
        ->and($response->json('data.1.causer_name'))->toBe($superAdmin->name);
});

it('allows a national admin to read recent activities', function (): void {
    $nationalAdmin = User::factory()->create(['statut' => 'actif']);
    $nationalAdmin->assignRole(Role::ADMIN_NATIONAL);

    $this->actingAs($nationalAdmin, 'sanctum')
        ->getJson('/api/system/audit-logs/recent')
        ->assertSuccessful();
});

it('forbids recent activities to non national users', function (string $role): void {
    $user = User::factory()->create(['statut' => 'actif']);
    $user->assignRole($role);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/system/audit-logs/recent')
        ->assertForbidden();
})->with([
    'directeur école' => Role::DIRECTEUR_ECOLE,
    'directeur provincial' => Role::DIRECTEUR_PROVINCIAL,
    'admin ministère' => Role::ADMIN_MINISTERE,
]);

it('requires authentication for recent activities', function (): void {
    $this->getJson('/api/system/audit-logs/recent')->assertUnauthorized();
});
