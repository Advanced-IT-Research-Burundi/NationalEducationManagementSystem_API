<?php

use App\Models\Eleve;
use App\Models\EleveParent;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $now = now();
    DB::table('pays')->insert(['id' => 1, 'code' => 'BI', 'name' => 'Burundi', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('ministeres')->insert(['id' => 1, 'name' => 'Ministère Test', 'pays_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('provinces')->insert(['id' => 1, 'name' => 'Province Test', 'pays_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('communes')->insert(['id' => 1, 'name' => 'Commune Test', 'province_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('zones')->insert(['id' => 1, 'name' => 'Zone Test', 'commune_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('collines')->insert(['id' => 1, 'name' => 'Colline Test', 'zone_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
});

it('forbids parent from listing all eleves', function (): void {
    $schoolA = School::withoutGlobalScopes()->create([
        'name' => 'École A',
        'colline_id' => 1,
    ]);

    $eleve = Eleve::withoutGlobalScopes()->create([
        'nom' => 'Test',
        'prenom' => 'Enfant',
        'sexe' => 'M',
        'date_naissance' => '2012-01-01',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $schoolA->id,
    ]);

    $parent = User::factory()->create(['statut' => 'actif']);
    $parent->assignRole(Role::PARENT);

    EleveParent::create([
        'user_id' => $parent->id,
        'eleve_id' => $eleve->id,
        'nom_complet' => 'Parent Test',
        'relation' => 'Père',
    ]);

    $this->actingAs($parent, 'sanctum');

    $this->getJson('/api/academic/eleves')->assertForbidden();
});

it('returns linked children for parent role', function (): void {
    $schoolA = School::withoutGlobalScopes()->create([
        'name' => 'École A',
        'colline_id' => 1,
    ]);
    $schoolB = School::withoutGlobalScopes()->create([
        'name' => 'École B',
        'colline_id' => 1,
    ]);

    $eleveA = Eleve::withoutGlobalScopes()->create([
        'nom' => 'A',
        'prenom' => 'Enfant',
        'sexe' => 'M',
        'date_naissance' => '2012-01-01',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $schoolA->id,
    ]);
    $eleveB = Eleve::withoutGlobalScopes()->create([
        'nom' => 'B',
        'prenom' => 'Enfant',
        'sexe' => 'F',
        'date_naissance' => '2013-01-01',
        'lieu_naissance' => 'Gitega',
        'school_id' => $schoolB->id,
    ]);

    $parent = User::factory()->create(['statut' => 'actif']);
    $parent->assignRole(Role::PARENT);

    EleveParent::create([
        'user_id' => $parent->id,
        'eleve_id' => $eleveA->id,
        'nom_complet' => 'Parent Test',
        'relation' => 'Père',
    ]);
    EleveParent::create([
        'user_id' => $parent->id,
        'eleve_id' => $eleveB->id,
        'nom_complet' => 'Parent Test',
        'relation' => 'Mère',
    ]);

    $this->actingAs($parent, 'sanctum');

    $response = $this->getJson('/api/academic/parent/children');

    $response->assertSuccessful();
    $data = $response->json('data');
    expect(collect($data)->pluck('eleve.id')->sort()->values()->all())
        ->toBe([$eleveA->id, $eleveB->id]);
});

it('forbids parent children endpoint for users without parent role', function (): void {
    $staff = User::factory()->create([
        'statut' => 'actif',
        'admin_level' => 'PAYS',
        'pays_id' => 1,
    ]);
    $staff->assignRole(Role::ADMIN_NATIONAL);

    $this->actingAs($staff, 'sanctum');

    $this->getJson('/api/academic/parent/children')->assertForbidden();
});

it('allows parent to view linked eleve only', function (): void {
    $schoolA = School::withoutGlobalScopes()->create([
        'name' => 'École A',
        'colline_id' => 1,
    ]);

    $eleve = Eleve::withoutGlobalScopes()->create([
        'nom' => 'A',
        'prenom' => 'Enfant',
        'sexe' => 'M',
        'date_naissance' => '2012-01-01',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $schoolA->id,
    ]);

    $parent = User::factory()->create(['statut' => 'actif', 'school_id' => null]);
    $parent->assignRole(Role::PARENT);

    EleveParent::create([
        'user_id' => $parent->id,
        'eleve_id' => $eleve->id,
        'nom_complet' => 'Parent Test',
        'relation' => 'Père',
    ]);

    $this->actingAs($parent, 'sanctum');

    $this->getJson("/api/academic/eleves/{$eleve->id}")->assertSuccessful();
});

it('returns empty notes list for parent with no linked children', function (): void {
    $parent = User::factory()->create(['statut' => 'actif']);
    $parent->assignRole(Role::PARENT);

    $this->actingAs($parent, 'sanctum');

    $this->getJson('/api/academic/notes')->assertSuccessful();
});

it('requires authentication for parent children endpoint', function (): void {
    $this->getJson('/api/academic/parent/children')->assertUnauthorized();
});
