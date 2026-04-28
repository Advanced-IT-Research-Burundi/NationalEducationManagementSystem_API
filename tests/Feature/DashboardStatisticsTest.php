<?php

use App\Models\AnneeScolaire;
use App\Models\Commune;
use App\Models\Pays;
use App\Models\Province;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Models\Zone;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Build the full geographic hierarchy needed by the schools table.
 *
 * @return array{pays: Pays, province: Province, commune: Commune, zone: Zone}
 */
function createGeoHierarchy(): array
{
    $pays = Pays::create(['code' => 'BI', 'name' => 'Burundi']);
    $ministere = DB::table('ministeres')->insertGetId([
        'name' => 'Min. Éducation',
        'pays_id' => $pays->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $province = Province::create(['name' => 'Bujumbura Mairie', 'pays_id' => $pays->id, 'ministere_id' => $ministere]);
    $commune = Commune::create(['name' => 'Mukaza', 'province_id' => $province->id, 'pays_id' => $pays->id]);
    $zone = Zone::create(['name' => 'Zone Centre', 'commune_id' => $commune->id, 'province_id' => $province->id, 'pays_id' => $pays->id]);
    $collineId = DB::table('collines')->insertGetId([
        'name' => 'Colline A',
        'zone_id' => $zone->id,
        'commune_id' => $commune->id,
        'province_id' => $province->id,
        'pays_id' => $pays->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('pays', 'province', 'commune', 'zone') + ['colline_id' => $collineId, 'ministere_id' => $ministere];
}

function createSchool(array $geo, array $overrides = []): School
{
    return School::create(array_merge([
        'name' => fake()->company(),
        'statut' => 'ACTIVE',
        'type_ecole' => 'PUBLIQUE',
        'niveau' => 'FONDAMENTAL',
        'colline_id' => $geo['colline_id'],
        'zone_id' => $geo['zone']->id,
        'commune_id' => $geo['commune']->id,
        'province_id' => $geo['province']->id,
        'pays_id' => $geo['pays']->id,
    ], $overrides));
}

// ─── National endpoint responds without SQL errors ──────────────────────────

it('returns national dashboard data without SQL errors', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $geo = createGeoHierarchy();
    $school = createSchool($geo);
    $annee = AnneeScolaire::factory()->active()->create();

    $superAdmin = User::query()->where('is_super_admin', true)->firstOrFail();

    $response = $this
        ->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/statistics/dashboard/national');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'niveau',
            'data' => ['global', 'kpis'],
        ]);
});

// ─── Evolution effectifs uses correct table name ────────────────────────────

it('computes evolution effectifs from the inscriptions table', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $geo = createGeoHierarchy();
    $school = createSchool($geo);
    $annee = AnneeScolaire::factory()->active()->create();

    $eleveId = DB::table('eleves')->insertGetId([
        'matricule' => 'ELV'.fake()->unique()->numerify('######'),
        'nom' => 'Test',
        'prenom' => 'Eleve',
        'sexe' => 'M',
        'date_naissance' => '2012-01-01',
        'lieu_naissance' => 'Bujumbura',
        'statut_global' => 'actif',
        'school_id' => $school->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $campagneId = DB::table('campagnes_inscription')->insertGetId([
        'annee_scolaire_id' => $annee->id,
        'school_id' => $school->id,
        'type' => 'nouvelle',
        'date_ouverture' => now()->subMonth(),
        'date_cloture' => now()->addMonth(),
        'statut' => 'ouverte',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $niveauId = DB::table('niveaux_scolaires')->insertGetId([
        'code' => 'CP1',
        'nom' => 'Cours Préparatoire 1',
        'ordre' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('inscriptions')->insert([
        'numero_inscription' => 'INSCR20260001',
        'eleve_id' => $eleveId,
        'campagne_id' => $campagneId,
        'annee_scolaire_id' => $annee->id,
        'school_id' => $school->id,
        'niveau_demande_id' => $niveauId,
        'type_inscription' => 'nouvelle',
        'statut' => 'valide',
        'date_inscription' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $superAdmin = User::query()->where('is_super_admin', true)->firstOrFail();

    $response = $this
        ->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/statistics/dashboard/national');

    $response->assertSuccessful();

    $evolution = $response->json('data.evolution_effectifs');
    expect($evolution)->toBeArray();

    $anneeEntry = collect($evolution)->firstWhere('annee_id', $annee->id);
    expect($anneeEntry)->not->toBeNull();
    expect($anneeEntry['total_eleves'])->toBe(1);
});

// ─── Role-based scoping ─────────────────────────────────────────────────────

it('scopes dashboard data for a provincial user to their province', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $geo = createGeoHierarchy();
    $schoolInProvince = createSchool($geo);

    $otherProvince = Province::create(['name' => 'Gitega', 'pays_id' => $geo['pays']->id]);
    $otherCommune = Commune::create(['name' => 'Gitega Centre', 'province_id' => $otherProvince->id, 'pays_id' => $geo['pays']->id]);
    $otherZone = Zone::create(['name' => 'Zone Gitega', 'commune_id' => $otherCommune->id, 'province_id' => $otherProvince->id, 'pays_id' => $geo['pays']->id]);
    $otherCollineId = DB::table('collines')->insertGetId([
        'name' => 'Colline B',
        'zone_id' => $otherZone->id,
        'commune_id' => $otherCommune->id,
        'province_id' => $otherProvince->id,
        'pays_id' => $geo['pays']->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $schoolOutside = School::create([
        'name' => 'École Gitega',
        'statut' => 'ACTIVE',
        'type_ecole' => 'PUBLIQUE',
        'niveau' => 'FONDAMENTAL',
        'colline_id' => $otherCollineId,
        'zone_id' => $otherZone->id,
        'commune_id' => $otherCommune->id,
        'province_id' => $otherProvince->id,
        'pays_id' => $geo['pays']->id,
    ]);

    AnneeScolaire::factory()->active()->create();

    $provincialUser = User::factory()->create([
        'statut' => 'actif',
        'admin_level' => 'PROVINCE',
        'admin_entity_id' => $geo['province']->id,
    ]);
    $provincialUser->assignRole(Role::DIRECTEUR_PROVINCIAL);

    $response = $this
        ->actingAs($provincialUser, 'sanctum')
        ->getJson('/api/statistics/dashboard/national');

    $response->assertSuccessful();

    $totalSchools = $response->json('data.global.total_schools');
    expect($totalSchools)->toBe(1);
});

it('scopes dashboard data for a school user to their school', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $geo = createGeoHierarchy();
    $mySchool = createSchool($geo, ['name' => 'Mon École']);
    $otherSchool = createSchool($geo, ['name' => 'Autre École']);
    AnneeScolaire::factory()->active()->create();

    $schoolUser = User::factory()->create([
        'statut' => 'actif',
        'admin_level' => 'ECOLE',
        'admin_entity_id' => $mySchool->id,
        'school_id' => $mySchool->id,
    ]);
    $schoolUser->assignRole(Role::DIRECTEUR_ECOLE);

    $response = $this
        ->actingAs($schoolUser, 'sanctum')
        ->getJson('/api/statistics/dashboard/national');

    $response->assertSuccessful();

    $totalSchools = $response->json('data.global.total_schools');
    expect($totalSchools)->toBe(1);
});

it('returns all schools for super admin regardless of geography', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $geo = createGeoHierarchy();
    createSchool($geo, ['name' => 'École A']);
    createSchool($geo, ['name' => 'École B']);
    createSchool($geo, ['name' => 'École C']);
    AnneeScolaire::factory()->active()->create();

    $superAdmin = User::query()->where('is_super_admin', true)->firstOrFail();

    $response = $this
        ->actingAs($superAdmin, 'sanctum')
        ->getJson('/api/statistics/dashboard/national');

    $response->assertSuccessful();

    $totalSchools = $response->json('data.global.total_schools');
    expect($totalSchools)->toBe(3);
});

it('requires authentication to access dashboard', function () {
    $response = $this->getJson('/api/statistics/dashboard/national');
    $response->assertUnauthorized();
});
