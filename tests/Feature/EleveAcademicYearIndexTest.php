<?php

use App\Models\AnneeScolaire;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $now = now();

    DB::table('pays')->insert(['id' => 1, 'code' => 'BI', 'name' => 'Burundi', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('ministeres')->insert(['id' => 1, 'name' => 'Ministère Test', 'pays_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('provinces')->insert(['id' => 1, 'name' => 'Province Test', 'pays_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('communes')->insert(['id' => 1, 'name' => 'Commune Test', 'province_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('zones')->insert(['id' => 1, 'name' => 'Zone Test', 'commune_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('collines')->insert(['id' => 1, 'name' => 'Colline Test', 'zone_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

    $this->year2024 = AnneeScolaire::factory()->create([
        'code' => '2023-2024',
        'libelle' => 'Année scolaire 2023-2024',
        'est_active' => false,
    ]);

    $this->year2025 = AnneeScolaire::factory()->active()->create([
        'code' => '2024-2025',
        'libelle' => 'Année scolaire 2024-2025',
    ]);

    $this->niveau7 = DB::table('niveaux_scolaires')->insertGetId([
        'nom' => '7ème',
        'code' => '7F',
        'ordre' => 7,
        'actif' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->niveau8 = DB::table('niveaux_scolaires')->insertGetId([
        'nom' => '8ème',
        'code' => '8F',
        'ordre' => 8,
        'actif' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->schoolA = DB::table('schools')->insertGetId([
        'name' => 'École A',
        'code_ecole' => 'ECOLE-A',
        'type_ecole' => 'PUBLIQUE',
        'niveau' => 'FONDAMENTAL',
        'colline_id' => 1,
        'zone_id' => 1,
        'commune_id' => 1,
        'province_id' => 1,
        'ministere_id' => 1,
        'pays_id' => 1,
        'statut' => 'ACTIVE',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->schoolB = DB::table('schools')->insertGetId([
        'name' => 'École B',
        'code_ecole' => 'ECOLE-B',
        'type_ecole' => 'PUBLIQUE',
        'niveau' => 'FONDAMENTAL',
        'colline_id' => 1,
        'zone_id' => 1,
        'commune_id' => 1,
        'province_id' => 1,
        'ministere_id' => 1,
        'pays_id' => 1,
        'statut' => 'ACTIVE',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->campagne2024 = DB::table('campagnes_inscription')->insertGetId([
        'annee_scolaire_id' => $this->year2024->id,
        'school_id' => $this->schoolA,
        'type' => 'reinscription',
        'date_ouverture' => '2023-08-01',
        'date_cloture' => '2023-10-31',
        'statut' => 'ouverte',
        'created_by' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->campagne2025 = DB::table('campagnes_inscription')->insertGetId([
        'annee_scolaire_id' => $this->year2025->id,
        'school_id' => $this->schoolB,
        'type' => 'reinscription',
        'date_ouverture' => '2024-08-01',
        'date_cloture' => '2024-10-31',
        'statut' => 'ouverte',
        'created_by' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $permission = Permission::create([
        'name' => 'view_any_eleve',
        'guard_name' => 'api',
    ]);

    $this->user = User::factory()->create([
        'is_super_admin' => true,
        'statut' => 'actif',
    ]);
    $this->user->givePermissionTo($permission);

    $this->actingAs($this->user, 'sanctum');
});

it('returns students and contextual school and level for the consulted academic year', function () {
    $now = now();

    $studentHistory = DB::table('eleves')->insertGetId([
        'matricule' => 'ELV-HISTORY-001',
        'nom' => 'Ndayisaba',
        'prenom' => 'Jean',
        'sexe' => 'M',
        'date_naissance' => '2010-05-15',
        'lieu_naissance' => 'Bujumbura',
        // Current snapshot points to the most recent year on purpose.
        'school_id' => $this->schoolB,
        'niveau_id' => $this->niveau8,
        'statut_global' => 'actif',
        'created_by' => $this->user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $studentOnly2025 = DB::table('eleves')->insertGetId([
        'matricule' => 'ELV-2025-ONLY',
        'nom' => 'Niyonzima',
        'prenom' => 'Marie',
        'sexe' => 'F',
        'date_naissance' => '2011-03-20',
        'lieu_naissance' => 'Gitega',
        'school_id' => $this->schoolB,
        'niveau_id' => $this->niveau8,
        'statut_global' => 'actif',
        'created_by' => $this->user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $classe2024 = DB::table('classes')->insertGetId([
        'nom' => '7A',
        'code' => 'CL-7A-2024',
        'niveau_id' => $this->niveau7,
        'school_id' => $this->schoolA,
        'annee_scolaire_id' => $this->year2024->id,
        'capacite' => 40,
        'effectif' => 1,
        'statut' => 'ACTIVE',
        'created_by' => $this->user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $classe2025 = DB::table('classes')->insertGetId([
        'nom' => '8A',
        'code' => 'CL-8A-2025',
        'niveau_id' => $this->niveau8,
        'school_id' => $this->schoolB,
        'annee_scolaire_id' => $this->year2025->id,
        'capacite' => 40,
        'effectif' => 2,
        'statut' => 'ACTIVE',
        'created_by' => $this->user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $inscription2024 = DB::table('inscriptions')->insertGetId([
        'numero_inscription' => 'INS-2024-000001',
        'eleve_id' => $studentHistory,
        'campagne_id' => $this->campagne2024,
        'annee_scolaire_id' => $this->year2024->id,
        'school_id' => $this->schoolA,
        'niveau_demande_id' => $this->niveau7,
        'type_inscription' => 'reinscription',
        'statut' => 'valide',
        'statut_academique' => 'en_cours',
        'date_inscription' => '2023-09-05',
        'date_soumission' => $now,
        'date_validation' => $now,
        'created_by' => $this->user->id,
        'valide_par' => $this->user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $inscription2025 = DB::table('inscriptions')->insertGetId([
        'numero_inscription' => 'INS-2025-000001',
        'eleve_id' => $studentHistory,
        'campagne_id' => $this->campagne2025,
        'annee_scolaire_id' => $this->year2025->id,
        'school_id' => $this->schoolB,
        'niveau_demande_id' => $this->niveau8,
        'type_inscription' => 'reinscription',
        'statut' => 'valide',
        'statut_academique' => 'en_cours',
        'date_inscription' => '2024-09-05',
        'date_soumission' => $now,
        'date_validation' => $now,
        'created_by' => $this->user->id,
        'valide_par' => $this->user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $inscriptionOnly2025 = DB::table('inscriptions')->insertGetId([
        'numero_inscription' => 'INS-2025-000002',
        'eleve_id' => $studentOnly2025,
        'campagne_id' => $this->campagne2025,
        'annee_scolaire_id' => $this->year2025->id,
        'school_id' => $this->schoolB,
        'niveau_demande_id' => $this->niveau8,
        'type_inscription' => 'nouvelle',
        'statut' => 'valide',
        'statut_academique' => 'en_cours',
        'date_inscription' => '2024-09-07',
        'date_soumission' => $now,
        'date_validation' => $now,
        'created_by' => $this->user->id,
        'valide_par' => $this->user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('affectations_classe')->insert([
        [
            'inscription_id' => $inscription2024,
            'classe_id' => $classe2024,
            'date_affectation' => '2023-09-10',
            'est_active' => true,
            'numero_ordre' => 1,
            'affecte_par' => $this->user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'inscription_id' => $inscription2025,
            'classe_id' => $classe2025,
            'date_affectation' => '2024-09-10',
            'est_active' => true,
            'numero_ordre' => 1,
            'affecte_par' => $this->user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'inscription_id' => $inscriptionOnly2025,
            'classe_id' => $classe2025,
            'date_affectation' => '2024-09-12',
            'est_active' => true,
            'numero_ordre' => 2,
            'affecte_par' => $this->user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    $response2024 = $this->getJson('/api/academic/eleves?annee_scolaire_id='.$this->year2024->id);

    $response2024->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $studentHistory)
        ->assertJsonPath('data.0.school.id', $this->schoolA)
        ->assertJsonPath('data.0.school.name', 'École A')
        ->assertJsonPath('data.0.niveau.id', $this->niveau7)
        ->assertJsonPath('data.0.niveau.nom', '7ème')
        ->assertJsonPath('data.0.inscription_courante.annee_scolaire_id', $this->year2024->id);

    $response2025 = $this->getJson('/api/academic/eleves?annee_scolaire_id='.$this->year2025->id);

    $response2025->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $year2025Rows = collect($response2025->json('data'));
    $historyRow2025 = $year2025Rows->firstWhere('id', $studentHistory);

    expect($year2025Rows->pluck('id')->all())
        ->toContain($studentHistory, $studentOnly2025)
        ->and($historyRow2025['school']['id'] ?? null)->toBe($this->schoolB)
        ->and($historyRow2025['niveau']['id'] ?? null)->toBe($this->niveau8)
        ->and($historyRow2025['inscription_courante']['annee_scolaire_id'] ?? null)->toBe($this->year2025->id);
});
