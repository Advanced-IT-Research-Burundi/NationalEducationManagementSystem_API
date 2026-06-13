<?php

use App\Models\AnneeScolaire;
use App\Models\CategorieCours;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Evaluation;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Note;
use App\Models\Pays;
use App\Models\Role;
use App\Models\Trimestre;
use App\Models\User;
use App\Support\BulletinCourseLayout;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function bulletinTestGeo(): array
{
    $paysId = Pays::query()->where('code', 'BI')->value('id')
        ?? Pays::create(['code' => 'BI', 'name' => 'Burundi'])->id;
    $provinceId = DB::table('provinces')->insertGetId(['name' => 'Bujumbura', 'pays_id' => $paysId, 'created_at' => now(), 'updated_at' => now()]);
    $communeId = DB::table('communes')->insertGetId(['name' => 'Mukaza', 'province_id' => $provinceId, 'created_at' => now(), 'updated_at' => now()]);
    $zoneId = DB::table('zones')->insertGetId(['name' => 'Zone A', 'commune_id' => $communeId, 'created_at' => now(), 'updated_at' => now()]);
    $collineId = DB::table('collines')->insertGetId(['name' => 'Colline Test', 'zone_id' => $zoneId, 'created_at' => now(), 'updated_at' => now()]);

    return compact('collineId');
}

function bulletinTestSchool(int $collineId): int
{
    return DB::table('schools')->insertGetId([
        'name' => 'Ecole Bulletin Test',
        'colline_id' => $collineId,
        'statut' => 'ACTIVE',
        'type_ecole' => 'PUBLIQUE',
        'niveau' => 'FONDAMENTAL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function bulletinTestNiveau(string $nom, string $code, int $ordre): Niveau
{
    $typeId = DB::table('types_scolaires')->where('nom', 'Fondamental')->value('id');
    if (! $typeId) {
        $typeId = DB::table('types_scolaires')->insertGetId([
            'nom' => 'Fondamental',
            'actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return Niveau::create([
        'nom' => $nom,
        'code' => $code,
        'ordre' => $ordre,
        'type_id' => $typeId,
        'actif' => true,
    ]);
}

function linkNiveauToSchool(int $niveauId, int $schoolId): void
{
    DB::table('niveau_school')->insert([
        'niveau_scolaire_id' => $niveauId,
        'school_id' => $schoolId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createBulletinFixture(): array
{
    $geo = bulletinTestGeo();
    $schoolId = bulletinTestSchool($geo['collineId']);
    $annee = AnneeScolaire::factory()->active()->create(['code' => '2025-2026']);
    $niveau7 = bulletinTestNiveau('7ème', '7F_BUL', 10);
    $niveau8 = bulletinTestNiveau('8ème', '8F_BUL', 11);
    linkNiveauToSchool($niveau7->id, $schoolId);
    linkNiveauToSchool($niveau8->id, $schoolId);

    $classe = Classe::withoutGlobalScopes()->create([
        'nom' => '7ème A',
        'code' => 'CL7_BUL',
        'niveau_id' => $niveau7->id,
        'school_id' => $schoolId,
        'annee_scolaire_id' => $annee->id,
        'statut' => 'ACTIVE',
    ]);

    $eleve = Eleve::withoutGlobalScopes()->create([
        'nom' => 'Test',
        'prenom' => 'Eleve',
        'sexe' => 'M',
        'date_naissance' => '2012-01-01',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $schoolId,
    ]);

    DB::table('eleve_class')->insert([
        'eleve_id' => $eleve->id,
        'classe_id' => $classe->id,
        'annee_scolaire' => $annee->code,
        'statut' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('schoolId', 'annee', 'niveau7', 'niveau8', 'classe', 'eleve');
}

function createMatiereForSchool(array $fixture, array $overrides = []): Matiere
{
    return Matiere::create(array_merge([
        'nom' => 'Cours Test',
        'code' => 'CRS' . random_int(1000, 9999),
        'niveau_id' => $fixture['niveau7']->id,
        'ponderation_tj' => 40,
        'ponderation_examen' => 40,
        'credit_heures' => 2,
        'actif' => true,
    ], $overrides));
}

function bulletinTestActor(int $schoolId): User
{
    $user = User::factory()->create([
        'statut' => 'actif',
        'school_id' => $schoolId,
    ]);
    $user->assignRole(Role::DIRECTEUR_ECOLE);

    return $user;
}

beforeEach(function (): void {
    Pays::query()->firstOrCreate(['code' => 'BI'], ['name' => 'Burundi']);
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('partitions bulletin courses without Autres pseudo-category', function (): void {
    $layout = BulletinCourseLayout::partitionForPdf([
        ['nom' => 'Français', 'categorie' => 'Langues', 'categorie_ordre' => 1],
        ['nom' => 'Entrepreneuriat', 'categorie' => null, 'categorie_ordre' => 99],
        ['nom' => 'Divers', 'categorie' => 'Autre', 'categorie_ordre' => 99],
    ]);

    expect($layout['groups'])->toHaveCount(1);
    expect($layout['groups'][0]['name'])->toBe('Langues');
    expect($layout['standalone'])->toHaveCount(2);
    expect(collect($layout['standalone'])->pluck('nom')->all())->toBe(['Entrepreneuriat', 'Divers']);
});

it('formats place as Non classé when incomplete and unranked', function (): void {
    expect(BulletinCourseLayout::formatPlace(null, false))->toBe('Non classé');
    expect(BulletinCourseLayout::formatPlace(2, true))->toBe('2 eme');
    expect(BulletinCourseLayout::formatPlace(null, true))->toBe('');
});

it('does not expose annual category totals before all trimesters are complete', function (): void {
    $totals = BulletinCourseLayout::computeGroupTotals([
        [
            'max_tj' => 40,
            'max_examen' => 0,
            'max_total' => 40,
            'annuel' => [
                'max_total' => 120,
                'note_total' => 65,
                'is_complete' => false,
            ],
            'trimestres' => [
                '1er Trimestre' => [
                    'max_tj' => 40,
                    'max_examen' => 0,
                    'max_total' => 40,
                    'note_tj' => 31,
                    'note_examen' => null,
                    'note_total' => 31,
                    'has_expected_notes' => true,
                    'is_complete' => true,
                ],
                '2e Trimestre' => [
                    'max_tj' => 40,
                    'max_examen' => 0,
                    'max_total' => 40,
                    'note_tj' => 34,
                    'note_examen' => null,
                    'note_total' => 34,
                    'has_expected_notes' => true,
                    'is_complete' => true,
                ],
            ],
        ],
    ]);

    expect($totals['annuel']['is_complete'])->toBeFalse();
    expect($totals['annuel']['max_tot'])->toBe(120);
    expect($totals['annuel']['has_tot'])->toBeFalse();
    expect($totals['annuel']['tot'])->toBe(0);
});

it('excludes courses from other levels and null niveau on bulletin', function (): void {
    $fixture = createBulletinFixture();

    createMatiereForSchool($fixture, ['nom' => 'Math 7ème', 'code' => 'MATH7']);
    createMatiereForSchool($fixture, ['nom' => 'Math 8ème', 'code' => 'MATH8', 'niveau_id' => $fixture['niveau8']->id]);
    createMatiereForSchool($fixture, ['nom' => 'Sans niveau', 'code' => 'NONIV', 'niveau_id' => null]);

    $response = $this->actingAs(bulletinTestActor($fixture['schoolId']), 'sanctum')
        ->getJson('/api/academic/bulletins/generate?' . http_build_query([
            'classe_id' => $fixture['classe']->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'mode' => 'annual',
        ]));

    $response->assertSuccessful();
    $courseNames = collect($response->json('data.bulletins.0.cours'))->pluck('nom')->all();

    expect($courseNames)->toContain('Math 7ème');
    expect($courseNames)->not->toContain('Math 8ème');
    expect($courseNames)->not->toContain('Sans niveau');
});

it('returns null notes instead of zero when evaluations are missing', function (): void {
    $fixture = createBulletinFixture();
    createMatiereForSchool($fixture, [
        'nom' => 'Français',
        'code' => 'FR_BUL',
        'ponderation_tj' => 40,
        'ponderation_examen' => 40,
    ]);

    $response = $this->actingAs(bulletinTestActor($fixture['schoolId']), 'sanctum')
        ->getJson('/api/academic/bulletins/generate?' . http_build_query([
            'classe_id' => $fixture['classe']->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'mode' => 'annual',
        ]));

    $response->assertSuccessful();
    $course = $response->json('data.bulletins.0.cours.0');

    expect($course['note_tj'])->toBeNull();
    expect($course['note_examen'])->toBeNull();
    expect($course['note_total'])->toBeNull();
});

it('marks incomplete students as non classé without rank or percentage', function (): void {
    $fixture = createBulletinFixture();
    $matiere = createMatiereForSchool($fixture, [
        'nom' => 'Kirundi',
        'code' => 'KIR_BUL',
        'ponderation_tj' => 40,
        'ponderation_examen' => 0,
    ]);

    $evaluation = Evaluation::withoutGlobalScopes()->create([
        'classe_id' => $fixture['classe']->id,
        'cours_id' => $matiere->id,
        'annee_scolaire_id' => $fixture['annee']->id,
        'trimestre' => '1er Trimestre',
        'type_evaluation' => 'TJ',
        'date_passation' => now(),
        'note_maximale' => 40,
    ]);

    $completeEleve = Eleve::withoutGlobalScopes()->create([
        'nom' => 'Complet',
        'prenom' => 'Eleve',
        'sexe' => 'F',
        'date_naissance' => '2012-02-02',
        'lieu_naissance' => 'Bujumbura',
        'school_id' => $fixture['schoolId'],
    ]);

    DB::table('eleve_class')->insert([
        'eleve_id' => $completeEleve->id,
        'classe_id' => $fixture['classe']->id,
        'annee_scolaire' => $fixture['annee']->code,
        'statut' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Note::create([
        'evaluation_id' => $evaluation->id,
        'eleve_id' => $completeEleve->id,
        'note' => 30,
    ]);

    foreach (['2e Trimestre', '3e Trimestre'] as $trimestreLabel) {
        $trimEval = Evaluation::withoutGlobalScopes()->create([
            'classe_id' => $fixture['classe']->id,
            'cours_id' => $matiere->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'trimestre' => $trimestreLabel,
            'type_evaluation' => 'TJ',
            'date_passation' => now(),
            'note_maximale' => 40,
        ]);

        Note::create([
            'evaluation_id' => $trimEval->id,
            'eleve_id' => $completeEleve->id,
            'note' => 28,
        ]);
    }

    $response = $this->actingAs(bulletinTestActor($fixture['schoolId']), 'sanctum')
        ->getJson('/api/academic/bulletins/generate?' . http_build_query([
            'classe_id' => $fixture['classe']->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'mode' => 'annual',
        ]));

    $response->assertSuccessful();

    $incomplete = collect($response->json('data.bulletins'))
        ->first(fn (array $bulletin) => $bulletin['eleve']['id'] === $fixture['eleve']->id);

    expect($incomplete['is_complete'])->toBeFalse();
    expect($incomplete['classement'])->toBe('non_classe');
    expect($incomplete['rang'])->toBeNull();
    expect($incomplete['pourcentage_global'])->toBeNull();

    $complete = collect($response->json('data.bulletins'))
        ->first(fn (array $bulletin) => $bulletin['eleve']['id'] === $completeEleve->id);

    expect($complete['is_complete'])->toBeTrue();
    expect($complete['classement'])->toBe('classé');
    expect($complete['rang'])->toBe(1);
});

it('keeps complete trimester students ranked in palmares', function (): void {
    $fixture = createBulletinFixture();
    $trimestre = Trimestre::create([
        'annee_scolaire_id' => $fixture['annee']->id,
        'nom' => '1er Trimestre',
        'date_debut' => now()->subMonth(),
        'date_fin' => now()->addMonth(),
        'actif' => true,
    ]);
    $matiere = createMatiereForSchool($fixture, [
        'nom' => 'Kirundi',
        'code' => 'KIR_PAL',
        'ponderation_tj' => 40,
        'ponderation_examen' => 0,
    ]);

    $evaluation = Evaluation::withoutGlobalScopes()->create([
        'classe_id' => $fixture['classe']->id,
        'cours_id' => $matiere->id,
        'annee_scolaire_id' => $fixture['annee']->id,
        'trimestre_id' => $trimestre->id,
        'trimestre' => $trimestre->nom,
        'type_evaluation' => 'TJ',
        'date_passation' => now(),
        'note_maximale' => 40,
    ]);

    Note::create([
        'evaluation_id' => $evaluation->id,
        'eleve_id' => $fixture['eleve']->id,
        'note' => 30,
    ]);

    $response = $this->actingAs(bulletinTestActor($fixture['schoolId']), 'sanctum')
        ->getJson('/api/academic/palmares?' . http_build_query([
            'classe_id' => $fixture['classe']->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'mode' => 'current',
            'trimestre' => '1er Trimestre',
        ]));

    $response->assertSuccessful();

    expect($response->json('data.classement'))->toHaveCount(1);
    expect($response->json('data.non_classes'))->toHaveCount(0);
    expect($response->json('data.classement.0.is_complete'))->toBeTrue();
    expect($response->json('data.classement.0.classement'))->toBe('classé');
    expect($response->json('data.classement.0.rang'))->toBe(1);
});

it('uses the three-trimester maximum in annual result columns when generating a trimester bulletin', function (): void {
    $fixture = createBulletinFixture();
    $matiere = createMatiereForSchool($fixture, [
        'nom' => 'Mathématiques',
        'code' => 'MATH_BUL',
        'ponderation_tj' => 40,
        'ponderation_examen' => 40,
    ]);

    foreach ([
        ['type' => 'TJ', 'max' => 40, 'note' => 32],
        ['type' => 'Examen', 'max' => 40, 'note' => 30],
    ] as $evaluationData) {
        $evaluation = Evaluation::withoutGlobalScopes()->create([
            'classe_id' => $fixture['classe']->id,
            'cours_id' => $matiere->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'trimestre' => '1er Trimestre',
            'type_evaluation' => $evaluationData['type'],
            'date_passation' => now(),
            'note_maximale' => $evaluationData['max'],
        ]);

        Note::create([
            'evaluation_id' => $evaluation->id,
            'eleve_id' => $fixture['eleve']->id,
            'note' => $evaluationData['note'],
        ]);
    }

    $response = $this->actingAs(bulletinTestActor($fixture['schoolId']), 'sanctum')
        ->getJson('/api/academic/bulletins/generate?' . http_build_query([
            'classe_id' => $fixture['classe']->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'mode' => 'current',
            'trimestre' => '1er Trimestre',
        ]));

    $response->assertSuccessful();
    $bulletin = $response->json('data.bulletins.0');
    $course = collect($bulletin['cours'])
        ->first(fn (array $item) => $item['nom'] === 'Mathématiques');

    expect($course['max_total'])->toBe(80);
    expect($course['annuel']['max_total'])->toBe(240);
    expect($bulletin['total_max'])->toBe(80);
    expect($bulletin['annuel']['total_max'])->toBe(240);
});

it('prints current trimester with previous locked trimesters only', function (): void {
    $fixture = createBulletinFixture();
    $matiere = createMatiereForSchool($fixture, [
        'nom' => 'Sciences',
        'code' => 'SCI_LOCKED_BUL',
        'ponderation_tj' => 40,
        'ponderation_examen' => 0,
    ]);

    $trimestre1 = Trimestre::create([
        'annee_scolaire_id' => $fixture['annee']->id,
        'nom' => '1er Trimestre',
        'date_debut' => '2025-09-01',
        'date_fin' => '2025-12-20',
        'actif' => false,
        'verrouille' => true,
    ]);
    $trimestre2 = Trimestre::create([
        'annee_scolaire_id' => $fixture['annee']->id,
        'nom' => '2e Trimestre',
        'date_debut' => '2026-01-05',
        'date_fin' => '2026-03-31',
        'actif' => true,
        'verrouille' => false,
    ]);
    $trimestre3 = Trimestre::create([
        'annee_scolaire_id' => $fixture['annee']->id,
        'nom' => '3e Trimestre',
        'date_debut' => '2026-04-01',
        'date_fin' => '2026-06-30',
        'actif' => false,
        'verrouille' => false,
    ]);

    foreach ([
        [$trimestre1, 31],
        [$trimestre2, 34],
        [$trimestre3, 36],
    ] as [$trimestre, $note]) {
        $evaluation = Evaluation::withoutGlobalScopes()->create([
            'classe_id' => $fixture['classe']->id,
            'cours_id' => $matiere->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'trimestre_id' => $trimestre->id,
            'trimestre' => $trimestre->nom,
            'type_evaluation' => 'TJ',
            'date_passation' => now(),
            'note_maximale' => 40,
        ]);

        Note::create([
            'evaluation_id' => $evaluation->id,
            'eleve_id' => $fixture['eleve']->id,
            'note' => $note,
        ]);
    }

    $response = $this->actingAs(bulletinTestActor($fixture['schoolId']), 'sanctum')
        ->getJson('/api/academic/bulletins/generate?' . http_build_query([
            'classe_id' => $fixture['classe']->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'mode' => 'current',
            'trimestre' => '2e Trimestre',
        ]));

    $response->assertSuccessful();
    $bulletin = $response->json('data.bulletins.0');
    $course = collect($bulletin['cours'])
        ->first(fn (array $item) => $item['nom'] === 'Sciences');

    expect(array_keys($bulletin['trimestres']))->toBe(['1er Trimestre', '2e Trimestre']);
    expect($course['trimestres']['1er Trimestre']['note_tj'])->toBe(31);
    expect($course['trimestres']['2e Trimestre']['note_tj'])->toBe(34);
    expect($course['trimestres'])->not->toHaveKey('3e Trimestre');
    expect($course['annuel']['is_complete'])->toBeFalse();
    expect($bulletin['annuel']['is_complete'])->toBeFalse();
    expect($bulletin['annuel']['conduite']['note'])->toBeNull();
});

it('keeps a real zero score distinct from missing notes', function (): void {
    $fixture = createBulletinFixture();
    $matiere = createMatiereForSchool($fixture, ['nom' => 'EPS', 'code' => 'EPS_BUL']);

    $evaluation = Evaluation::withoutGlobalScopes()->create([
        'classe_id' => $fixture['classe']->id,
        'cours_id' => $matiere->id,
        'annee_scolaire_id' => $fixture['annee']->id,
        'trimestre' => '1er Trimestre',
        'type_evaluation' => 'TJ',
        'date_passation' => now(),
        'note_maximale' => 40,
    ]);

    Note::create([
        'evaluation_id' => $evaluation->id,
        'eleve_id' => $fixture['eleve']->id,
        'note' => 0,
    ]);

    $response = $this->actingAs(bulletinTestActor($fixture['schoolId']), 'sanctum')
        ->getJson('/api/academic/bulletins/generate?' . http_build_query([
            'classe_id' => $fixture['classe']->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'mode' => 'annual',
        ]));

    $response->assertSuccessful();
    $course = collect($response->json('data.bulletins.0.cours'))
        ->first(fn (array $item) => $item['nom'] === 'EPS');

    expect($course['trimestres']['1er Trimestre']['note_tj'])->toBe(0);
});

it('excludes courses from hidden categories', function (): void {
    $fixture = createBulletinFixture();
    $hiddenCategory = CategorieCours::create([
        'nom' => 'Cache',
        'ordre' => 99,
        'afficher_bulletin' => false,
    ]);

    createMatiereForSchool($fixture, [
        'nom' => 'Cours visible',
        'code' => 'VIS_BUL',
    ]);
    createMatiereForSchool($fixture, [
        'nom' => 'Cours caché',
        'code' => 'HID_BUL',
        'categorie_cours_id' => $hiddenCategory->id,
    ]);

    $response = $this->actingAs(bulletinTestActor($fixture['schoolId']), 'sanctum')
        ->getJson('/api/academic/bulletins/generate?' . http_build_query([
            'classe_id' => $fixture['classe']->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'mode' => 'annual',
        ]));

    $response->assertSuccessful();
    $courseNames = collect($response->json('data.bulletins.0.cours'))->pluck('nom')->all();

    expect($courseNames)->toContain('Cours visible');
    expect($courseNames)->not->toContain('Cours caché');
});

it('tags standalone courses in bulletin payload', function (): void {
    $fixture = createBulletinFixture();
    $category = CategorieCours::create([
        'nom' => 'Langues',
        'ordre' => 1,
        'afficher_bulletin' => true,
    ]);

    createMatiereForSchool($fixture, [
        'nom' => 'Français',
        'code' => 'FR_ST',
        'categorie_cours_id' => $category->id,
    ]);
    createMatiereForSchool($fixture, [
        'nom' => 'Entrepreneuriat',
        'code' => 'ENT_ST',
        'categorie_cours_id' => null,
    ]);

    $response = $this->actingAs(bulletinTestActor($fixture['schoolId']), 'sanctum')
        ->getJson('/api/academic/bulletins/generate?' . http_build_query([
            'classe_id' => $fixture['classe']->id,
            'annee_scolaire_id' => $fixture['annee']->id,
            'mode' => 'annual',
        ]));

    $response->assertSuccessful();
    $courses = collect($response->json('data.bulletins.0.cours'));

    expect($courses->firstWhere('nom', 'Français')['display_group'])->toBe('grouped');
    expect($courses->firstWhere('nom', 'Entrepreneuriat')['display_group'])->toBe('standalone');
});
