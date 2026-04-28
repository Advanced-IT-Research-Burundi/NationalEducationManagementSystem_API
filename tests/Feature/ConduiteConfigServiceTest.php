<?php

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Niveau;
use App\Services\AcademicYearService;
use App\Services\ConduiteConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    AcademicYearService::clearCache();
    Context::flush();

    $this->anneeScolaire = AnneeScolaire::factory()->active()->create([
        'code' => '2025-2026',
    ]);

    $typeId = DB::table('types_scolaires')->insertGetId([
        'nom' => 'Fondamental',
        'actif' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $typePostId = DB::table('types_scolaires')->insertGetId([
        'nom' => 'Post Fondamental',
        'actif' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->niveauPrimary = Niveau::create([
        'nom' => '3ème Année Fondamentale',
        'code' => '3F_TEST',
        'ordre' => 6,
        'type_id' => $typeId,
        'actif' => true,
    ]);

    $this->niveauSecondary = Niveau::create([
        'nom' => '7ème Année Fondamentale',
        'code' => '7F_TEST',
        'ordre' => 10,
        'type_id' => $typeId,
        'actif' => true,
    ]);

    $this->niveauPostFond = Niveau::create([
        'nom' => '2ème Année Post-Fondamentale',
        'code' => '2PF_TEST',
        'ordre' => 14,
        'type_id' => $typePostId,
        'actif' => true,
    ]);

    $paysId = DB::table('pays')->insertGetId(['code' => 'BI', 'name' => 'Burundi', 'created_at' => now(), 'updated_at' => now()]);
    $provinceId = DB::table('provinces')->insertGetId(['name' => 'Bujumbura', 'pays_id' => $paysId, 'created_at' => now(), 'updated_at' => now()]);
    $communeId = DB::table('communes')->insertGetId(['name' => 'Mukaza', 'province_id' => $provinceId, 'created_at' => now(), 'updated_at' => now()]);
    $zoneId = DB::table('zones')->insertGetId(['name' => 'Zone A', 'commune_id' => $communeId, 'created_at' => now(), 'updated_at' => now()]);
    $collineId = DB::table('collines')->insertGetId(['name' => 'Colline Test', 'zone_id' => $zoneId, 'created_at' => now(), 'updated_at' => now()]);

    $schoolId = DB::table('schools')->insertGetId([
        'name' => 'École Test Conduite',
        'colline_id' => $collineId,
        'statut' => 'ACTIVE',
        'type_ecole' => 'PUBLIQUE',
        'niveau' => 'FONDAMENTAL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->classePrimary = Classe::withoutGlobalScopes()->create([
        'nom' => 'Classe 3F',
        'code' => 'CL3F_COND',
        'niveau_id' => $this->niveauPrimary->id,
        'school_id' => $schoolId,
        'annee_scolaire_id' => $this->anneeScolaire->id,
        'statut' => 'ACTIVE',
    ]);

    $this->classeSecondary = Classe::withoutGlobalScopes()->create([
        'nom' => 'Classe 7F',
        'code' => 'CL7F_COND',
        'niveau_id' => $this->niveauSecondary->id,
        'school_id' => $schoolId,
        'annee_scolaire_id' => $this->anneeScolaire->id,
        'statut' => 'ACTIVE',
    ]);

    $this->classePostFond = Classe::withoutGlobalScopes()->create([
        'nom' => 'Classe 2PF',
        'code' => 'CL2PF_COND',
        'niveau_id' => $this->niveauPostFond->id,
        'school_id' => $schoolId,
        'annee_scolaire_id' => $this->anneeScolaire->id,
        'statut' => 'ACTIVE',
    ]);
});

it('returns max 10 for primary level (ordre < 10)', function () {
    $config = ConduiteConfigService::resolveForClasse($this->classePrimary);

    expect($config['max_note'])->toBe(10);
});

it('returns max 60 for secondary level (ordre = 10)', function () {
    $config = ConduiteConfigService::resolveForClasse($this->classeSecondary);

    expect($config['max_note'])->toBe(60);
});

it('returns max 60 for post-fondamental (ordre > 10)', function () {
    $config = ConduiteConfigService::resolveForClasse($this->classePostFond);

    expect($config['max_note'])->toBe(60);
});

it('detects primary class correctly via isSecondary', function () {
    expect(ConduiteConfigService::isSecondary($this->classePrimary))->toBeFalse();
    expect(ConduiteConfigService::isSecondary($this->classeSecondary))->toBeTrue();
    expect(ConduiteConfigService::isSecondary($this->classePostFond))->toBeTrue();
});

it('resolves by classe ID (integer)', function () {
    $config = ConduiteConfigService::resolveForClasse($this->classePrimary->id);

    expect($config['max_note'])->toBe(10);
});

it('returns correct primary thresholds', function () {
    $config = ConduiteConfigService::resolveForClasse($this->classePrimary);
    $thresholds = $config['thresholds'];

    expect($thresholds)->toBe([
        'Excellent' => 8,
        'Bon' => 7,
        'Passable' => 5,
        'Mauvais' => 3,
    ]);
});

it('returns correct secondary thresholds', function () {
    $config = ConduiteConfigService::resolveForClasse($this->classeSecondary);
    $thresholds = $config['thresholds'];

    expect($thresholds)->toBe([
        'Excellent' => 50,
        'Bon' => 40,
        'Passable' => 30,
        'Mauvais' => 20,
    ]);
});

it('builds correct appreciation for primary (max=10)', function () {
    expect(ConduiteConfigService::buildAppreciation(10, 10))->toBe('Excellent');
    expect(ConduiteConfigService::buildAppreciation(8, 10))->toBe('Excellent');
    expect(ConduiteConfigService::buildAppreciation(7, 10))->toBe('Bon');
    expect(ConduiteConfigService::buildAppreciation(5, 10))->toBe('Passable');
    expect(ConduiteConfigService::buildAppreciation(3, 10))->toBe('Mauvais');
    expect(ConduiteConfigService::buildAppreciation(1, 10))->toBe('Très mauvais');
});

it('builds correct appreciation for secondary (max=60)', function () {
    expect(ConduiteConfigService::buildAppreciation(60, 60))->toBe('Excellent');
    expect(ConduiteConfigService::buildAppreciation(50, 60))->toBe('Excellent');
    expect(ConduiteConfigService::buildAppreciation(40, 60))->toBe('Bon');
    expect(ConduiteConfigService::buildAppreciation(30, 60))->toBe('Passable');
    expect(ConduiteConfigService::buildAppreciation(20, 60))->toBe('Mauvais');
    expect(ConduiteConfigService::buildAppreciation(10, 60))->toBe('Très mauvais');
});

it('uses max=10 as default conduite for primary classes via getMaxNote', function () {
    expect(ConduiteConfigService::getMaxNote($this->classePrimary))->toBe(10);
    expect(ConduiteConfigService::getMaxNote($this->classeSecondary))->toBe(60);
});
