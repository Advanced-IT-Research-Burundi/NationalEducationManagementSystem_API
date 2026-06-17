<?php

use App\Models\AnneeScolaire;
use App\Models\Inscription;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    $this->year = AnneeScolaire::factory()->active()->create([
        'code' => '2024-2025',
        'libelle' => 'Année scolaire 2024-2025',
    ]);

    $this->niveauId = DB::table('niveaux_scolaires')->insertGetId([
        'nom' => '7ème',
        'code' => '7F',
        'ordre' => 7,
        'actif' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->schoolId = DB::table('schools')->insertGetId([
        'name' => 'Ecole A',
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

    $permission = Permission::create([
        'name' => 'create_eleve',
        'guard_name' => 'api',
    ]);

    $this->user = User::factory()->create([
        'is_super_admin' => true,
        'statut' => 'actif',
    ]);
    $this->user->givePermissionTo($permission);

    $this->actingAs($this->user, 'sanctum');
});

function makeEleveImportCsv(array $rows): UploadedFile
{
    $headers = [
        'matricule', 'nom', 'prenom', 'sexe', 'date_naissance', 'lieu_naissance',
        'nationalite', 'colline_origine', 'adresse', 'nom_pere', 'nom_mere',
        'nom_tuteur', 'contact_tuteur', 'est_orphelin', 'a_handicap', 'type_handicap',
        'school_destination', 'niveau',
    ];

    $lines = [implode(',', $headers)];
    foreach ($rows as $row) {
        $lines[] = implode(',', $row);
    }

    $content = implode("\n", $lines);

    return UploadedFile::fake()->createWithContent('eleves.csv', $content);
}

it('imports students and creates inscriptions for the active year', function () {
    $file = makeEleveImportCsv([
        [
            'IMP-001', 'Ndayisaba', 'Jean', 'M', '2010-05-15', 'Bujumbura',
            'Burundaise', 'Colline Test', '', '', '', '', '', '0', '0', '',
            'Ecole A', '7ème',
        ],
    ]);

    $response = $this->postJson('/api/academic/eleves/import', [
        'file' => $file,
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'Importation réussie']);

    $this->assertDatabaseHas('eleves', [
        'matricule' => 'IMP-001',
        'nom' => 'Ndayisaba',
        'school_id' => $this->schoolId,
        'niveau_id' => $this->niveauId,
    ]);

    $eleveId = DB::table('eleves')->where('matricule', 'IMP-001')->value('id');

    $this->assertDatabaseHas('inscriptions', [
        'eleve_id' => $eleveId,
        'annee_scolaire_id' => $this->year->id,
        'school_id' => $this->schoolId,
        'niveau_demande_id' => $this->niveauId,
        'statut_academique' => 'en_cours',
    ]);
});

it('rolls back the whole import when a row is invalid', function () {
    $file = makeEleveImportCsv([
        [
            'IMP-OK', 'Valid', 'Student', 'M', '2010-05-15', 'Bujumbura',
            '', '', '', '', '', '', '', '0', '0', '',
            'Ecole A', '7ème',
        ],
        [
            'IMP-BAD', 'Invalid', 'Student', 'X', '2010-05-15', 'Bujumbura',
            '', '', '', '', '', '', '', '0', '0', '',
            'Ecole A', '7ème',
        ],
    ]);

    $response = $this->postJson('/api/academic/eleves/import', [
        'file' => $file,
    ]);

    $response->assertStatus(422);

    expect(DB::table('eleves')->where('matricule', 'IMP-OK')->exists())->toBeFalse();
    expect(Inscription::withoutGlobalScopes()->count())->toBe(0);
});

it('returns a helpful error when the file only contains headers', function () {
    $headers = implode(',', [
        'matricule', 'nom', 'prenom', 'sexe', 'date_naissance', 'lieu_naissance',
        'nationalite', 'colline_origine', 'adresse', 'nom_pere', 'nom_mere',
        'nom_tuteur', 'contact_tuteur', 'est_orphelin', 'a_handicap', 'type_handicap',
        'school_destination', 'niveau',
    ]);

    $file = UploadedFile::fake()->createWithContent('headers-only.csv', $headers);

    $response = $this->postJson('/api/academic/eleves/import', [
        'file' => $file,
        'school_id' => $this->schoolId,
        'niveau_id' => $this->niveauId,
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'Erreur de validation lors de l\'importation']);

    expect($response->json('errors.0'))->toContain('Aucune ligne de données valide');
});

it('rejects import for users without create permission', function () {
    $unauthorized = User::factory()->create(['is_super_admin' => false, 'statut' => 'actif']);
    $this->actingAs($unauthorized, 'sanctum');

    $file = makeEleveImportCsv([
        [
            'IMP-403', 'Test', 'User', 'M', '2010-05-15', 'Bujumbura',
            '', '', '', '', '', '', '', '0', '0', '',
            'Ecole A', '7ème',
        ],
    ]);

    $this->postJson('/api/academic/eleves/import', ['file' => $file])
        ->assertForbidden();
});