<?php

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Services\AcademicYearService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    AcademicYearService::clearCache();
    Context::flush();

    $annee = AnneeScolaire::factory()->active()->create();
    AcademicYearService::setCurrent($annee->id);

    $niveauId = DB::table('niveaux_scolaires')->insertGetId([
        'nom' => 'NS3',
        'code' => 'NS3-'.fake()->unique()->randomNumber(4),
        'ordre' => 1,
        'actif' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->sectionA = DB::table('sections')->insertGetId(['nom' => 'SVT', 'code' => 'SVT', 'created_at' => now(), 'updated_at' => now()]);
    $this->sectionB = DB::table('sections')->insertGetId(['nom' => 'SMP', 'code' => 'SMP', 'created_at' => now(), 'updated_at' => now()]);

    $row = fn (string $nom, ?int $sectionId) => [
        'nom' => $nom,
        'niveau_id' => $niveauId,
        'section_id' => $sectionId,
        'annee_scolaire_id' => $annee->id,
        'statut' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('classes')->insert([
        $row('NS3 SVT 1', $this->sectionA),
        $row('NS3 SVT 2', $this->sectionA),
        $row('NS3 SMP', $this->sectionB),
        $row('NS3 sans section', null),
    ]);
});

it('keeps only the classes of the filtered section', function () {
    $noms = Classe::query()->bySection($this->sectionA)->pluck('nom')->sort()->values()->all();

    expect($noms)->toBe(['NS3 SVT 1', 'NS3 SVT 2']);
});

it('excludes classes without a section when a section is filtered', function () {
    $noms = Classe::query()->bySection($this->sectionB)->pluck('nom')->all();

    expect($noms)->toBe(['NS3 SMP']);
});
