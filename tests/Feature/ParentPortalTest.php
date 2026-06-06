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
        'name' => 'Ecole A',
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
        'name' => 'Ecole A',
        'colline_id' => 1,
    ]);
    $schoolB = School::withoutGlobalScopes()->create([
        'name' => 'Ecole B',
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
        'name' => 'Ecole A',
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

it('allows parent to view notes for linked children', function (): void {
    $school = School::withoutGlobalScopes()->create([
        'name' => 'Ecole Test',
        'colline_id' => 1,
    ]);

    $eleve = Eleve::withoutGlobalScopes()->create([
        'nom' => 'A',
        'prenom' => 'Enfant',
        'sexe' => 'M',
        'date_naissance' => '2012-01-01',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $school->id,
    ]);

    $parent = User::factory()->create(['statut' => 'actif']);
    $parent->assignRole(Role::PARENT);

    EleveParent::create([
        'user_id' => $parent->id,
        'eleve_id' => $eleve->id,
        'nom_complet' => 'Parent Test',
        'relation' => 'Père',
    ]);

    $anneeId = DB::table('annee_scolaires')->insertGetId([
        'code' => '2025-2026',
        'libelle' => '2025/2026',
        'date_debut' => now()->subMonths(6)->toDateString(),
        'date_fin' => now()->addMonths(6)->toDateString(),
        'est_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $niveauId = DB::table('niveaux_scolaires')->insertGetId([
        'nom' => 'Niveau Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $trimestreId = DB::table('trimestres')->insertGetId([
        'annee_scolaire_id' => $anneeId,
        'nom' => '1er Trimestre',
        'date_debut' => now()->subMonth()->toDateString(),
        'date_fin' => now()->addMonth()->toDateString(),
        'actif' => true,
        'verrouille' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $classeId = DB::table('classes')->insertGetId([
        'nom' => 'Classe Test',
        'school_id' => $school->id,
        'niveau_id' => $niveauId,
        'annee_scolaire_id' => $anneeId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $matiereId = DB::table('matieres')->insertGetId([
        'nom' => 'Mathématiques',
        'code' => 'MAT',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $evaluationId = DB::table('evaluations')->insertGetId([
        'classe_id' => $classeId,
        'cours_id' => $matiereId,
        'annee_scolaire_id' => 1,
        'trimestre' => '1er Trimestre',
        'trimestre_id' => $trimestreId,
        'type_evaluation' => 'TJ',
        'date_passation' => now()->toDateString(),
        'note_maximale' => 20,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('notes')->insert([
        'evaluation_id' => $evaluationId,
        'eleve_id' => $eleve->id,
        'note' => 18,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($parent, 'sanctum');

    $response = $this->getJson('/api/academic/notes');

    $response->assertSuccessful();
    expect($response->json('data'))->not->toBeEmpty();
});

it('requires authentication for parent children endpoint', function (): void {
    $this->getJson('/api/academic/parent/children')->assertUnauthorized();
});

it('forbids parent from downloading bulletin pdf', function (): void {
    $parent = User::factory()->create(['statut' => 'actif']);
    $parent->assignRole(Role::PARENT);

    $this->actingAs($parent, 'sanctum');

    $this->getJson('/api/academic/bulletins/pdf?classe_id=1&eleve_id=1')
        ->assertForbidden();
});
