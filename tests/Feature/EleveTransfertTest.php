<?php

use App\Enums\StatutAcademique;
use App\Models\AnneeScolaire;
use App\Models\Eleve;
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

    $this->year = AnneeScolaire::factory()->active()->create([
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

    $this->schoolB = DB::table('schools')->insertGetId([
        'name' => 'Ecole B',
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

    $permission = Permission::create([
        'name' => 'update_eleve',
        'guard_name' => 'api',
    ]);

    $this->user = User::factory()->create([
        'is_super_admin' => true,
        'statut' => 'actif',
    ]);
    $this->user->givePermissionTo($permission);

    $this->actingAs($this->user, 'sanctum');
});

function createEleveWithActiveInscription(object $test, int $niveauId, int $schoolId): Eleve
{
    $now = now();

    $eleve = Eleve::withoutGlobalScopes()->create([
        'nom' => 'Test',
        'prenom' => 'Eleve',
        'sexe' => 'M',
        'date_naissance' => '2010-01-01',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $schoolId,
        'niveau_id' => $niveauId,
        'est_redoublant' => true,
        'created_by' => $test->user->id,
    ]);

    DB::table('inscriptions')->insert([
        'numero_inscription' => 'INS-TR-'.uniqid(),
        'eleve_id' => $eleve->id,
        'annee_scolaire_id' => $test->year->id,
        'school_id' => $schoolId,
        'niveau_demande_id' => $niveauId,
        'type_inscription' => 'reinscription',
        'statut' => 'valide',
        'statut_academique' => StatutAcademique::EnCours->value,
        'date_inscription' => $now->toDateString(),
        'date_soumission' => $now,
        'date_validation' => $now,
        'created_by' => $test->user->id,
        'valide_par' => $test->user->id,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return $eleve;
}

it('transfers student to upper level when niveau_cible is superieur', function () {
    $eleve = createEleveWithActiveInscription($this, $this->niveau7, $this->schoolA);

    $response = $this->postJson("/api/academic/eleves/{$eleve->id}/transfert", [
        'ecole_destination_id' => $this->schoolB,
        'niveau_cible' => 'superieur',
        'motif' => 'Déménagement familial',
        'validation_avancement' => false,
    ]);

    $response->assertSuccessful();

    $eleve->refresh();

    expect($eleve->school_id)->toBe($this->schoolB)
        ->and($eleve->niveau_id)->toBe($this->niveau8)
        ->and($eleve->est_redoublant)->toBeFalse();

    $newInscription = DB::table('inscriptions')
        ->where('eleve_id', $eleve->id)
        ->where('school_id', $this->schoolB)
        ->where('statut_academique', StatutAcademique::EnCours->value)
        ->first();

    expect($newInscription)->not->toBeNull()
        ->and((int) $newInscription->niveau_demande_id)->toBe($this->niveau8);

    $mouvement = DB::table('mouvements_eleve')
        ->where('eleve_id', $eleve->id)
        ->latest('id')
        ->first();

    expect($mouvement->motif)->toContain('Passage au niveau supérieur')
        ->and($mouvement->motif)->toContain('Déménagement familial')
        ->and((int) $mouvement->niveau_destination_id)->toBe($this->niveau8);
});

it('keeps same level when niveau_cible is meme without validation', function () {
    $eleve = createEleveWithActiveInscription($this, $this->niveau7, $this->schoolA);

    $response = $this->postJson("/api/academic/eleves/{$eleve->id}/transfert", [
        'ecole_destination_id' => $this->schoolB,
        'niveau_cible' => 'meme',
        'validation_avancement' => false,
    ]);

    $response->assertSuccessful();

    $eleve->refresh();

    expect($eleve->niveau_id)->toBe($this->niveau7);

    $newInscription = DB::table('inscriptions')
        ->where('eleve_id', $eleve->id)
        ->where('school_id', $this->schoolB)
        ->where('statut_academique', StatutAcademique::EnCours->value)
        ->first();

    expect((int) $newInscription->niveau_demande_id)->toBe($this->niveau7);
});

it('promotes to upper level when validation_avancement is true', function () {
    $eleve = createEleveWithActiveInscription($this, $this->niveau7, $this->schoolA);

    $response = $this->postJson("/api/academic/eleves/{$eleve->id}/transfert", [
        'ecole_destination_id' => $this->schoolB,
        'niveau_cible' => 'meme',
        'validation_avancement' => true,
    ]);

    $response->assertSuccessful();

    $eleve->refresh();

    expect($eleve->niveau_id)->toBe($this->niveau8)
        ->and($eleve->est_redoublant)->toBeFalse();

    $mouvement = DB::table('mouvements_eleve')
        ->where('eleve_id', $eleve->id)
        ->latest('id')
        ->first();

    expect($mouvement->motif)->toContain('Avancement académique validé');
});
