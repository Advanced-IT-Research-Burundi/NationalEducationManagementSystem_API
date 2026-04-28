<?php

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\Note;
use App\Models\User;
use App\Scopes\AcademicYearScope;
use App\Services\AcademicYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    AcademicYearService::clearCache();
    Context::flush();

    $this->year2024 = AnneeScolaire::factory()->create([
        'code' => '2023-2024',
        'est_active' => false,
    ]);
    $this->year2025 = AnneeScolaire::factory()->active()->create([
        'code' => '2024-2025',
    ]);
});

/* ------------------------------------------------------------------
 * AcademicYearService
 * ----------------------------------------------------------------*/

it('resolves the active year from the database', function () {
    expect(AcademicYearService::currentId())->toBe($this->year2025->id);
});

it('prefers Context over DB', function () {
    AcademicYearService::setCurrent($this->year2024->id);

    expect(AcademicYearService::currentId())->toBe($this->year2024->id);
});

it('returns null when no year is active and no Context is set', function () {
    $this->year2025->update(['est_active' => false]);
    AcademicYearService::clearCache();

    expect(AcademicYearService::currentId())->toBeNull();
});

it('clears cache when academic year activation changes', function () {
    expect(AcademicYearService::currentId())->toBe($this->year2025->id);

    $this->year2024->activate();

    expect(AcademicYearService::currentId())->toBe($this->year2024->id);
});

/* ------------------------------------------------------------------
 * Direct scope – Classe (has annee_scolaire_id column)
 * ----------------------------------------------------------------*/

it('filters classes by active academic year', function () {
    $niveauId = DB::table('niveaux_scolaires')->insertGetId([
        'nom' => 'Niveau Test',
        'code' => 'NT-'.fake()->unique()->randomNumber(4),
        'ordre' => 1,
        'actif' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('classes')->insert([
        ['nom' => 'Classe A', 'niveau_id' => $niveauId, 'annee_scolaire_id' => $this->year2025->id, 'statut' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now()],
        ['nom' => 'Classe B', 'niveau_id' => $niveauId, 'annee_scolaire_id' => $this->year2025->id, 'statut' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now()],
        ['nom' => 'Classe C', 'niveau_id' => $niveauId, 'annee_scolaire_id' => $this->year2024->id, 'statut' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now()],
    ]);

    AcademicYearService::setCurrent($this->year2025->id);

    expect(Classe::count())->toBe(2);
});

it('bypasses scope with withoutGlobalScope', function () {
    $niveauId = DB::table('niveaux_scolaires')->insertGetId([
        'nom' => 'Niveau Bypass',
        'code' => 'NB-'.fake()->unique()->randomNumber(4),
        'ordre' => 1,
        'actif' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('classes')->insert([
        ['nom' => 'X1', 'niveau_id' => $niveauId, 'annee_scolaire_id' => $this->year2025->id, 'statut' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now()],
        ['nom' => 'X2', 'niveau_id' => $niveauId, 'annee_scolaire_id' => $this->year2024->id, 'statut' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now()],
    ]);

    AcademicYearService::setCurrent($this->year2025->id);

    expect(Classe::withoutGlobalScope(AcademicYearScope::class)->count())->toBe(2);
});

it('returns all records when no year is active and no Context', function () {
    $this->year2025->update(['est_active' => false]);
    AcademicYearService::clearCache();
    Context::flush();

    $niveauId = DB::table('niveaux_scolaires')->insertGetId([
        'nom' => 'Niveau All',
        'code' => 'NA-'.fake()->unique()->randomNumber(4),
        'ordre' => 1,
        'actif' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('classes')->insert([
        ['nom' => 'Y1', 'niveau_id' => $niveauId, 'annee_scolaire_id' => $this->year2025->id, 'statut' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now()],
        ['nom' => 'Y2', 'niveau_id' => $niveauId, 'annee_scolaire_id' => $this->year2024->id, 'statut' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now()],
    ]);

    expect(Classe::count())->toBe(2);
});

/* ------------------------------------------------------------------
 * Indirect scope – Note (via evaluation.annee_scolaire_id)
 * ----------------------------------------------------------------*/

it('filters notes indirectly through evaluation year', function () {
    $niveauId = DB::table('niveaux_scolaires')->insertGetId([
        'nom' => 'Niveau Notes',
        'code' => 'NN-'.fake()->unique()->randomNumber(4),
        'ordre' => 1,
        'actif' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $classeId = DB::table('classes')->insertGetId([
        'nom' => 'Classe Notes',
        'niveau_id' => $niveauId,
        'annee_scolaire_id' => $this->year2025->id,
        'statut' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $matiereId = DB::table('matieres')->insertGetId([
        'nom' => 'Maths',
        'code' => 'MAT-'.fake()->unique()->randomNumber(4),
        'actif' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $eleveId = DB::table('eleves')->insertGetId([
        'nom' => 'Doe',
        'prenom' => 'John',
        'sexe' => 'M',
        'date_naissance' => '2010-01-01',
        'lieu_naissance' => 'Bujumbura',
        'matricule' => 'ELV-'.fake()->unique()->randomNumber(6),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $evalActive = DB::table('evaluations')->insertGetId([
        'classe_id' => $classeId,
        'cours_id' => $matiereId,
        'annee_scolaire_id' => $this->year2025->id,
        'trimestre' => '1er Trimestre',
        'type_evaluation' => 'TJ',
        'date_passation' => now()->toDateString(),
        'note_maximale' => 20,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $evalOther = DB::table('evaluations')->insertGetId([
        'classe_id' => $classeId,
        'cours_id' => $matiereId,
        'annee_scolaire_id' => $this->year2024->id,
        'trimestre' => '1er Trimestre',
        'type_evaluation' => 'TJ',
        'date_passation' => now()->toDateString(),
        'note_maximale' => 20,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('notes')->insert([
        ['evaluation_id' => $evalActive, 'eleve_id' => $eleveId, 'note' => 15, 'created_at' => now(), 'updated_at' => now()],
        ['evaluation_id' => $evalOther, 'eleve_id' => $eleveId, 'note' => 12, 'created_at' => now(), 'updated_at' => now()],
    ]);

    AcademicYearService::setCurrent($this->year2025->id);

    expect(Note::count())->toBe(1);
    expect(Note::first()->note)->toBe('15.00');
});

/* ------------------------------------------------------------------
 * Middleware
 * ----------------------------------------------------------------*/

it('sets academic year from X-Annee-Scolaire-Id header', function () {
    $user = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($user, 'sanctum')
        ->withHeaders(['X-Annee-Scolaire-Id' => (string) $this->year2024->id])
        ->getJson('/api/health');

    expect(Context::get('annee_scolaire_id'))->toBe($this->year2024->id);
});

it('ignores invalid header values', function () {
    $user = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($user, 'sanctum')
        ->withHeaders(['X-Annee-Scolaire-Id' => '99999'])
        ->getJson('/api/health');

    expect(Context::get('annee_scolaire_id'))->toBeNull();
});
