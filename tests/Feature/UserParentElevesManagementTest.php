<?php

use App\Models\Eleve;
use App\Models\EleveParent;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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

it('creates a parent user and syncs parent_eleves', function (): void {
    Mail::fake();

    $school = School::withoutGlobalScopes()->create([
        'name' => 'Ecole Test',
        'colline_id' => 1,
    ]);

    $eleve = Eleve::withoutGlobalScopes()->create([
        'nom' => 'Uwitonze',
        'prenom' => 'Jean',
        'sexe' => 'M',
        'date_naissance' => '2012-01-01',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $school->id,
    ]);

    $admin = User::factory()->create([
        'statut' => 'actif',
        'admin_level' => 'PAYS',
        'pays_id' => 1,
    ]);
    $admin->assignRole(Role::ADMIN_NATIONAL);

    $payload = [
        'nom' => 'Parent',
        'prenom' => 'API',
        'email' => 'parent-api-'.uniqid('', true).'@example.test',
        'role' => Role::PARENT,
        'admin_level' => 'PAYS',
        'pays_id' => 1,
        'parent_eleves' => [
            ['eleve_id' => $eleve->id, 'relation' => 'Père'],
        ],
    ];

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/users', $payload);

    $response->assertCreated();

    $parent = User::query()->where('email', $payload['email'])->first();
    expect($parent)->not->toBeNull()
        ->and($parent->hasRole(Role::PARENT))->toBeTrue();

    $link = EleveParent::query()->where('user_id', $parent->id)->where('eleve_id', $eleve->id)->first();
    expect($link)->not->toBeNull()
        ->and($link->relation)->toBe('Père');
});

it('updates parent_eleves for an existing parent user', function (): void {
    Mail::fake();

    $school = School::withoutGlobalScopes()->create([
        'name' => 'Ecole Test',
        'colline_id' => 1,
    ]);

    $eleve1 = Eleve::withoutGlobalScopes()->create([
        'nom' => 'A',
        'prenom' => 'Un',
        'sexe' => 'M',
        'date_naissance' => '2012-01-01',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $school->id,
    ]);
    $eleve2 = Eleve::withoutGlobalScopes()->create([
        'nom' => 'B',
        'prenom' => 'Deux',
        'sexe' => 'F',
        'date_naissance' => '2013-01-01',
        'lieu_naissance' => 'Gitega',
        'school_id' => $school->id,
    ]);

    $admin = User::factory()->create([
        'statut' => 'actif',
        'admin_level' => 'PAYS',
        'pays_id' => 1,
    ]);
    $admin->assignRole(Role::ADMIN_NATIONAL);

    $parent = User::factory()->create([
        'statut' => 'actif',
        'admin_level' => 'PAYS',
        'pays_id' => 1,
    ]);
    $parent->assignRole(Role::PARENT);

    EleveParent::create([
        'user_id' => $parent->id,
        'eleve_id' => $eleve1->id,
        'nom_complet' => $parent->name,
        'relation' => 'Père',
    ]);

    $this->actingAs($admin, 'sanctum')->putJson("/api/users/{$parent->id}", [
        'parent_eleves' => [
            ['eleve_id' => $eleve2->id, 'relation' => 'Tuteur'],
        ],
    ])->assertSuccessful();

    expect(EleveParent::query()->where('user_id', $parent->id)->count())->toBe(1);
    $row = EleveParent::query()->where('user_id', $parent->id)->first();
    expect($row->eleve_id)->toBe($eleve2->id)
        ->and($row->relation)->toBe('Tuteur');
});

it('removes parent links when role is no longer parent', function (): void {
    Mail::fake();

    $school = School::withoutGlobalScopes()->create([
        'name' => 'Ecole Test',
        'colline_id' => 1,
    ]);

    $eleve = Eleve::withoutGlobalScopes()->create([
        'nom' => 'C',
        'prenom' => 'Trois',
        'sexe' => 'M',
        'date_naissance' => '2012-01-01',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $school->id,
    ]);

    $admin = User::factory()->create([
        'statut' => 'actif',
        'admin_level' => 'PAYS',
        'pays_id' => 1,
    ]);
    $admin->assignRole(Role::ADMIN_NATIONAL);

    $parent = User::factory()->create([
        'statut' => 'actif',
        'admin_level' => 'PAYS',
        'pays_id' => 1,
    ]);
    $parent->assignRole(Role::PARENT);

    EleveParent::create([
        'user_id' => $parent->id,
        'eleve_id' => $eleve->id,
        'nom_complet' => $parent->name,
        'relation' => 'Père',
    ]);

    $this->actingAs($admin, 'sanctum')->putJson("/api/users/{$parent->id}", [
        'role' => Role::PERSONNEL_ADMINISTRATIF,
    ])->assertSuccessful();

    expect(EleveParent::query()->where('user_id', $parent->id)->count())->toBe(0);
});

it('forbids assigning an eleve the admin cannot view', function (): void {
    Mail::fake();

    $schoolA = School::withoutGlobalScopes()->create([
        'name' => 'Ecole A',
        'colline_id' => 1,
    ]);
    $schoolB = School::withoutGlobalScopes()->create([
        'name' => 'Ecole B',
        'colline_id' => 1,
    ]);

    $eleveB = Eleve::withoutGlobalScopes()->create([
        'nom' => 'Hors',
        'prenom' => 'Scope',
        'sexe' => 'M',
        'date_naissance' => '2012-01-01',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $schoolB->id,
    ]);

    $director = User::factory()->create([
        'statut' => 'actif',
        'admin_level' => 'ECOLE',
        'admin_entity_id' => $schoolA->id,
        'school_id' => $schoolA->id,
        'pays_id' => 1,
        'province_id' => 1,
        'commune_id' => 1,
        'zone_id' => 1,
        'colline_id' => 1,
    ]);
    $director->assignRole(Role::DIRECTEUR_ECOLE);
    $director->givePermissionTo(['manage_users', 'create_user', 'update_user', 'view_user', 'view_any_user']);

    $payload = [
        'nom' => 'Parent',
        'prenom' => 'Hors scope',
        'email' => 'parent-scope-'.uniqid('', true).'@example.test',
        'role' => Role::PARENT,
        'admin_level' => 'ECOLE',
        'pays_id' => 1,
        'province_id' => 1,
        'commune_id' => 1,
        'zone_id' => 1,
        'colline_id' => 1,
        'school_id' => $schoolA->id,
        'parent_eleves' => [
            ['eleve_id' => $eleveB->id, 'relation' => 'Père'],
        ],
    ];

    $this->actingAs($director, 'sanctum')->postJson('/api/users', $payload)->assertForbidden();
});
