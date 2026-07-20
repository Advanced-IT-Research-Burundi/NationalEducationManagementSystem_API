<?php

use App\Exports\EleveTemplateExport;
use App\Imports\EleveImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Validators\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

if (! function_exists('eleveImportRows')) {
    function eleveImportRows(array $dataRows, int $trailingBlankRows = 0): \Illuminate\Support\Collection
    {
        $rows = [
            ['matricule' => 'OBLIGATOIRE'],
            ['matricule' => 'EL-2026-001'],
            ['matricule' => 'Unique, max 20'],
            ...$dataRows,
            ...array_fill(0, $trailingBlankRows, [
                'matricule' => null,
                'nom' => null,
                'prenom' => null,
                'sexe' => null,
                'date_naissance' => null,
                'lieu_naissance' => null,
                'school_destination' => null,
                'niveau_scolaire' => null,
            ]),
        ];

        return collect(array_map(fn (array $row) => collect($row), $rows));
    }
}

beforeEach(function () {
    $now = now();

    DB::table('pays')->insert(['id' => 1, 'code' => 'BI', 'name' => 'Burundi', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('ministeres')->insert(['id' => 1, 'name' => 'Ministere Test', 'pays_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('provinces')->insert(['id' => 10, 'name' => 'Gitega', 'pays_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('communes')->insert(['id' => 20, 'name' => 'Gitega', 'province_id' => 10, 'pays_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('zones')->insert(['id' => 30, 'name' => 'Gitega Centre', 'commune_id' => 20, 'province_id' => 10, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('collines')->insert(['id' => 40, 'name' => 'Nyamugari', 'zone_id' => 30, 'commune_id' => 20, 'province_id' => 10, 'created_at' => $now, 'updated_at' => $now]);

    $this->schoolId = DB::table('schools')->insertGetId([
        'name' => 'Lycee Test',
        'code_ecole' => 'LYC-TEST',
        'type_ecole' => 'PUBLIQUE',
        'niveau' => 'FONDAMENTAL',
        'colline_id' => 40,
        'zone_id' => 30,
        'commune_id' => 20,
        'province_id' => 10,
        'ministere_id' => 1,
        'pays_id' => 1,
        'statut' => 'ACTIVE',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->niveauId = DB::table('niveaux_scolaires')->insertGetId([
        'nom' => '7eme',
        'code' => '7F',
        'ordre' => 7,
        'actif' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('niveau_school')->insert([
        'school_id' => $this->schoolId,
        'niveau_scolaire_id' => $this->niveauId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
});

it('imports students by resolving foreign table names', function () {
    (new EleveImport())->collection(eleveImportRows([
        [
            'matricule' => 'EL-IMP-001',
            'nom' => 'NDAYISHIMIYE',
            'prenom' => 'Jean',
            'sexe' => 'M',
            'date_naissance' => '2010-05-15',
            'lieu_naissance' => 'Gitega',
            'nationalite' => 'Burundaise',
            'school_destination' => 'Lycee Test',
            'niveau_scolaire' => '7eme',
            'province_origine' => 'Gitega',
            'commune_origine' => 'Gitega',
            'zone_origine' => 'Gitega Centre',
            'colline_origine' => 'Nyamugari',
            'est_orphelin' => 'Non',
            'a_handicap' => 'Oui',
            'type_handicap' => 'Auditif',
        ],
    ], 60));

    $this->assertDatabaseHas('eleves', [
        'matricule' => 'EL-IMP-001',
        'school_id' => $this->schoolId,
        'niveau_id' => $this->niveauId,
        'province_origine_id' => 10,
        'commune_origine_id' => 20,
        'zone_origine_id' => 30,
        'colline_origine_id' => 40,
        'a_handicap' => 1,
        'type_handicap' => 'Auditif',
    ]);
});

it('rejects invalid reference names without inserting students', function () {
    try {
        (new EleveImport())->collection(eleveImportRows([
            [
                'matricule' => 'EL-IMP-002',
                'nom' => 'NDUWIMANA',
                'prenom' => 'Aline',
                'sexe' => 'F',
                'date_naissance' => '2011-02-20',
                'lieu_naissance' => 'Gitega',
                'school_destination' => 'Lycee Test',
                'niveau_scolaire' => '7eme',
                'commune_origine' => 'Commune Inconnue',
            ],
        ]));

        $this->fail('The import should have failed.');
    } catch (ValidationException $exception) {
        expect($exception->failures()[0]->errors()[0])->toContain('introuvable');
    }

    $this->assertDatabaseMissing('eleves', [
        'matricule' => 'EL-IMP-002',
    ]);
});

it('ignores template rows before line seven and blank styled rows after data', function () {
    (new EleveImport())->collection(eleveImportRows([
        [
            'matricule' => 'EL-IMP-003',
            'nom' => 'BIZIMANA',
            'prenom' => 'Claude',
            'sexe' => 'M',
            'date_naissance' => '2012-01-10',
            'lieu_naissance' => 'Gitega',
            'school_destination' => 'Lycee Test',
            'niveau_scolaire' => '7eme',
        ],
    ], 200));

    $this->assertDatabaseHas('eleves', [
        'matricule' => 'EL-IMP-003',
        'nom' => 'BIZIMANA',
    ]);
});

it('updates existing matricules and inserts following rows before stopping after four rows without matricule', function () {
    $now = now();

    DB::table('eleves')->insert([
        'matricule' => 'EL-UPD-001',
        'nom' => 'ANCIEN',
        'prenom' => 'Nom',
        'sexe' => 'F',
        'date_naissance' => '2011-01-01',
        'lieu_naissance' => 'Ancien lieu',
        'school_id' => $this->schoolId,
        'niveau_id' => $this->niveauId,
        'statut_global' => 'actif',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    (new EleveImport())->collection(eleveImportRows([
        [
            'matricule' => 'EL-UPD-001',
            'nom' => 'NOUVEAU',
            'prenom' => 'Nom',
            'sexe' => 'M',
            'date_naissance' => '2011-01-01',
            'lieu_naissance' => 'Gitega',
            'school_destination' => 'Lycee Test',
            'niveau_scolaire' => '7eme',
        ],
        [
            'matricule' => 'EL-NEW-001',
            'nom' => 'KANEZA',
            'prenom' => 'Alice',
            'sexe' => 'F',
            'date_naissance' => '2012-03-11',
            'lieu_naissance' => 'Gitega',
            'school_destination' => 'Lycee Test',
            'niveau_scolaire' => '7eme',
        ],
        ['matricule' => '', 'nom' => 'Sans matricule 1'],
        ['matricule' => '', 'nom' => 'Sans matricule 2'],
        ['matricule' => '', 'nom' => 'Sans matricule 3'],
        ['matricule' => '', 'nom' => 'Sans matricule 4'],
        [
            'matricule' => 'EL-AFTER-STOP',
            'nom' => 'APRES',
            'prenom' => 'Arret',
            'sexe' => 'M',
            'date_naissance' => '2012-04-12',
            'lieu_naissance' => 'Gitega',
            'school_destination' => 'Lycee Test',
            'niveau_scolaire' => '7eme',
        ],
    ]));

    $this->assertDatabaseHas('eleves', [
        'matricule' => 'EL-UPD-001',
        'nom' => 'NOUVEAU',
        'sexe' => 'M',
    ]);
    $this->assertDatabaseHas('eleves', [
        'matricule' => 'EL-NEW-001',
        'nom' => 'KANEZA',
    ]);
    $this->assertDatabaseMissing('eleves', [
        'matricule' => 'EL-AFTER-STOP',
    ]);
});

it('resolves a unique colline even when the provided hierarchy does not match', function () {
    $now = now();

    DB::table('zones')->insert([
        'id' => 31,
        'name' => 'Makebuko',
        'commune_id' => 20,
        'province_id' => 10,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    DB::table('collines')->insert([
        'id' => 41,
        'name' => 'Taba',
        'zone_id' => 31,
        'commune_id' => 20,
        'province_id' => 10,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    (new EleveImport())->collection(eleveImportRows([
        [
            'matricule' => 'EL-TABA-001',
            'nom' => 'TABA',
            'prenom' => 'Test',
            'sexe' => 'F',
            'date_naissance' => '2012-04-12',
            'lieu_naissance' => 'Gitega',
            'school_destination' => 'Lycee Test',
            'niveau_scolaire' => '7eme',
            'zone_origine' => 'Gitega Centre',
            'colline_origine' => 'Taba',
        ],
    ]));

    $this->assertDatabaseHas('eleves', [
        'matricule' => 'EL-TABA-001',
        'zone_origine_id' => 31,
        'colline_origine_id' => 41,
    ]);
});

it('generates an import template with the references sheet', function () {
    $contents = Excel::raw(new EleveTemplateExport(), ExcelFormat::XLSX);
    $path = tempnam(sys_get_temp_dir(), 'eleves-template-').'.xlsx';
    file_put_contents($path, $contents);

    $spreadsheet = IOFactory::load($path);

    expect($spreadsheet->getSheetNames())->toContain('Import_Eleves', 'REFERENCES', 'INSTRUCTIONS');
    expect($spreadsheet->getSheetByName('Import_Eleves')->getCell('H3')->getValue())->toBe('school_destination *');
    expect($spreadsheet->getSheetByName('REFERENCES')->getCell('D1')->getValue())->toBe('schools.name');

    @unlink($path);
});
