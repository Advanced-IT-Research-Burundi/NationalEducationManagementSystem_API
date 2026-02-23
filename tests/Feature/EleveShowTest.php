<?php

use App\Models\Eleve;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['admin_level' => 'PAYS']);
    $this->actingAs($this->user, 'sanctum');

    $now = now();
    DB::table('pays')->insert(['id' => 1, 'code' => 'BI', 'name' => 'Burundi', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('ministeres')->insert(['id' => 1, 'name' => 'Ministère Test', 'pays_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('provinces')->insert(['id' => 1, 'name' => 'Province Test', 'pays_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('communes')->insert(['id' => 1, 'name' => 'Commune Test', 'province_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('zones')->insert(['id' => 1, 'name' => 'Zone Test', 'commune_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('collines')->insert(['id' => 1, 'name' => 'Colline Test', 'zone_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
});

it('returns school and ecole_origine in the show response', function () {
    $currentSchool = School::withoutGlobalScopes()->create([
        'name' => 'École Primaire Kamenge',
        'colline_id' => 1,
    ]);

    $origineSchool = School::withoutGlobalScopes()->create([
        'name' => 'École Fondamentale Ngozi',
        'colline_id' => 1,
    ]);

    $eleve = Eleve::withoutGlobalScopes()->create([
        'nom' => 'Ndayisaba',
        'prenom' => 'Jean',
        'sexe' => 'M',
        'date_naissance' => '2010-05-15',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $currentSchool->id,
        'ecole_origine_id' => $origineSchool->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/academic/eleves/{$eleve->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.school.name', 'École Primaire Kamenge')
        ->assertJsonPath('data.school.id', $currentSchool->id)
        ->assertJsonPath('data.ecole_origine.name', 'École Fondamentale Ngozi')
        ->assertJsonPath('data.ecole_origine.id', $origineSchool->id);
});

it('returns null school when no school is assigned', function () {
    $eleve = Eleve::withoutGlobalScopes()->create([
        'nom' => 'Niyonzima',
        'prenom' => 'Marie',
        'sexe' => 'F',
        'date_naissance' => '2011-03-20',
        'lieu_naissance' => 'Gitega',
        'created_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/academic/eleves/{$eleve->id}");

    $response->assertSuccessful();
    expect($response->json('data.school'))->toBeNull();
});
