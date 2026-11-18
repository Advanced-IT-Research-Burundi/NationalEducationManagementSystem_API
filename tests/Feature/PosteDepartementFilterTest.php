<?php

use App\Models\Poste;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $timestamps = ['created_at' => now(), 'updated_at' => now()];

    $this->departementA = DB::table('departements')->insertGetId(['code' => 'DEP-A', 'nom' => 'Pédagogie', ...$timestamps]);
    $this->departementB = DB::table('departements')->insertGetId(['code' => 'DEP-B', 'nom' => 'Finances', ...$timestamps]);

    $serviceA = DB::table('services')->insertGetId(['code' => 'SRV-A', 'nom' => 'Inspection', 'departement_id' => $this->departementA, ...$timestamps]);
    $serviceB = DB::table('services')->insertGetId(['code' => 'SRV-B', 'nom' => 'Comptabilité', 'departement_id' => $this->departementB, ...$timestamps]);

    DB::table('postes')->insert([
        ['code' => 'P1', 'nom' => 'Inspecteur', 'service_id' => $serviceA, 'departement_id' => null, ...$timestamps],
        ['code' => 'P2', 'nom' => 'Conseiller pédagogique', 'service_id' => null, 'departement_id' => $this->departementA, ...$timestamps],
        ['code' => 'P3', 'nom' => 'Comptable', 'service_id' => $serviceB, 'departement_id' => null, ...$timestamps],
        ['code' => 'P4', 'nom' => 'Chauffeur', 'service_id' => null, 'departement_id' => null, ...$timestamps],
    ]);
});

it('keeps postes linked to the departement directly or through their service', function () {
    $noms = Poste::query()->byDepartement($this->departementA)->orderBy('code')->pluck('nom')->all();

    expect($noms)->toBe(['Inspecteur', 'Conseiller pédagogique']);
});

it('excludes postes of other departements and postes without departement', function () {
    $noms = Poste::query()->byDepartement($this->departementB)->pluck('nom')->all();

    expect($noms)->toBe(['Comptable']);
});
