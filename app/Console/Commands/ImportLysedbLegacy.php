<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ImportLysedbLegacy extends Command
{
    protected $signature = 'legacy:import-lysedb
        {--school-id= : Existing schools.id that owns all migrated records}
        {--legacy-connection=legacy_mysql : Database connection containing/importing the old lysedb tables}
        {--dump= : Optional path to lysedb SQL dump}
        {--import-dump : Import the dump into the legacy connection before migrating}
        {--chunk=500 : Number of legacy rows to process per batch}
        {--dry-run : Validate and count without writing into the new database}
        {--force : Run without confirmation prompts}';

    protected $description = 'Import the old lysedb academic data into the current NEMS schema with crosswalks and rejection logs';

    private const SOURCE = 'lysedb';

    private ?int $runId = null;

    private int $schoolId;

    private int $chunkSize = 500;

    private bool $dryRun = false;

    /** @var array<string, int> */
    private array $stats = [];

    /** @var array<string, array<string, int>> */
    private array $memoryMaps = [];

    private int $fakeId = -1;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $this->chunkSize = max(50, (int) $this->option('chunk'));
        $legacyConnection = (string) $this->option('legacy-connection');

        $schoolId = (int) $this->option('school-id');
        if ($schoolId <= 0 || ! DB::table('schools')->whereid($schoolId)->exists()) {
            $this->error('Provide a valid --school-id for the default destination school.');

            return self::FAILURE;
        }
        $this->schoolId = $schoolId;

        try {
            $legacy = DB::connection($legacyConnection);
            $this->assertLegacyConnectionIsSeparate($legacyConnection);

            if ($this->option('import-dump')) {
                $this->importDump($legacy);
            }

            $this->assertLegacyTables($legacy);

            if (! $this->dryRun) {
                $this->runId = DB::table('legacy_import_runs')->insertGetId([
                    'source' => self::SOURCE,
                    'status' => 'running',
                    'options' => json_encode([
                        'school_id' => $this->schoolId,
                        'legacy_connection' => $legacyConnection,
                        'dump' => $this->option('dump'),
                        'import_dump' => (bool) $this->option('import-dump'),
                    ]),
                    'started_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->info($this->dryRun ? 'DRY RUN - no destination writes will be made.' : 'Starting legacy import.');

            $this->seedAcademicReferenceData($legacy);
            $this->seedReglementScolaire($legacy);
            $this->seedTeachers($legacy);
            $this->seedStudents($legacy);
            $this->seedTeacherAssignments($legacy);
            $this->seedAggregateNotes($legacy);
            $this->seedTravauxNotes($legacy);

            $this->finishRun('completed');
            $this->printStats();

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->finishRun('failed', $exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function assertLegacyConnectionIsSeparate(string $legacyConnection): void
    {
        $default = DB::connection()->getDatabaseName();
        $legacy = DB::connection($legacyConnection)->getDatabaseName();

        if ($default !== null && $legacy !== null && $default === $legacy) {
            throw new \RuntimeException('Legacy connection points to the same database as the application DB.');
        }
    }

    private function importDump(ConnectionInterface $legacy): void
    {
        if ($this->dryRun) {
            $this->warn('Skipping dump import during dry-run.');

            return;
        }

        $dump = (string) $this->option('dump');
        if ($dump === '' || ! File::exists($dump)) {
            throw new \InvalidArgumentException('Provide an existing --dump path when using --import-dump.');
        }

        if (! $this->option('force') && ! $this->confirm("Import {$dump} into {$legacy->getDatabaseName()}? Existing legacy tables may be replaced.")) {
            throw new \RuntimeException('Dump import cancelled.');
        }

        $this->info('Importing SQL dump into legacy connection...');
        $legacy->unprepared('SET FOREIGN_KEY_CHECKS=0');

        $statement = '';
        $handle = fopen($dump, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open dump file.');
        }

        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                continue;
            }

            $statement .= $line;

            if (str_ends_with(rtrim($line), ';')) {
                $legacy->unprepared($statement);
                $statement = '';
            }
        }

        fclose($handle);
        $legacy->unprepared('SET FOREIGN_KEY_CHECKS=1');
        $this->bump('dump_statements_imported');
    }

    private function assertLegacyTables(ConnectionInterface $legacy): void
    {
        foreach (['anneescolaire', 'niveau', 'classe', 'cours', 'enseignant', 'eleve', 'notes'] as $table) {
            if (! Schema::connection($legacy->getName())->hasTable($table)) {
                throw new \RuntimeException("Legacy table `{$table}` was not found on connection `{$legacy->getName()}`.");
            }
        }
    }

    private function seedAcademicReferenceData(ConnectionInterface $legacy): void
    {
        $this->info('Migrating academic years, trimestres, geography, levels, classes, and courses...');

        $this->migrateAcademicYears($legacy);
        $this->migrateGeography($legacy);
        $this->migrateLevels($legacy);
        $this->migrateClasses($legacy);
        $this->migrateCourses($legacy);
    }

    private function migrateAcademicYears(ConnectionInterface $legacy): void
    {
        $latest = $legacy->table('anneescolaire')->max('libelleas');

        $legacy->table('anneescolaire')->orderBy('idas')->chunk($this->chunkSize, function ($years) use ($latest) {
            foreach ($years as $year) {
                $label = trim((string) $year->libelleas);
                if (! preg_match('/^(\d{4})-(\d{4})$/', $label, $matches)) {
                    $this->reject('anneescolaire', $year->idas, 'Invalid school year label', (array) $year);

                    continue;
                }

                $payload = [
                    'code' => $label,
                    'libelle' => $label,
                    'date_debut' => "{$matches[1]}-09-01",
                    'date_fin' => "{$matches[2]}-08-31",
                    'est_active' => $label === $latest,
                    'updated_at' => now(),
                ];

                $id = $this->dryRun
                    ? 0
                    : DB::table('annee_scolaires')->updateOrInsert(['code' => $label], $payload + ['created_at' => now()]);

                $targetId = $this->dryRun ? $this->fakeId() : (int) DB::table('annee_scolaires')->where('code', $label)->value('id');
                $this->rememberMap('anneescolaire', $year->idas, 'annee_scolaires', $targetId);
                $this->bump($id ? 'years_upserted' : 'years_seen');
            }
        });

        foreach ($this->allMaps('anneescolaire', 'annee_scolaires') as $oldYearId => $newYearId) {
            foreach (['1er Trimestre', '2e Trimestre', '3e Trimestre'] as $name) {
                $this->upsertTrimestre((int) $oldYearId, $newYearId, $name);
            }
        }
    }

    private function migrateGeography(ConnectionInterface $legacy): void
    {
        if (Schema::connection($legacy->getName())->hasTable('dpe')) {
            $legacy->table('dpe')->orderBy('iddpe')->chunk($this->chunkSize, function ($rows) {
                foreach ($rows as $row) {
                    $name = $this->normalizeName($row->libelledpe ?? '');
                    if ($name === '') {
                        $this->reject('dpe', $row->iddpe, 'Blank province name', (array) $row);

                        continue;
                    }

                    $id = $this->dryRun ? 0 : DB::table('provinces')->updateOrInsert(
                        ['name' => $name],
                        ['pays_id' => 1, 'updated_at' => now(), 'created_at' => now()]
                    );
                    $targetId = $this->dryRun ? $this->fakeId() : (int) DB::table('provinces')->where('name', $name)->value('id');
                    $this->rememberMap('dpe', $row->iddpe, 'provinces', $targetId);
                    $this->bump($id ? 'provinces_upserted' : 'provinces_seen');
                }
            });
        }

        if (Schema::connection($legacy->getName())->hasTable('dce')) {
            $legacy->table('dce')->orderBy('iddce')->chunk($this->chunkSize, function ($rows) {
                foreach ($rows as $row) {
                    $provinceId = $this->map('dpe', $row->iddpe, 'provinces');
                    $name = $this->normalizeName($row->libelledce ?? '');
                    if (! $provinceId || $name === '') {
                        $this->reject('dce', $row->iddce, 'Missing province map or commune name', (array) $row);

                        continue;
                    }

                    $id = $this->dryRun ? 0 : DB::table('communes')->updateOrInsert(
                        ['name' => $name, 'province_id' => $provinceId, 'pays_id' => 1],
                        ['updated_at' => now(), 'created_at' => now()]
                    );
                    $targetId = $this->dryRun ? $this->fakeId() : (int) DB::table('communes')
                        ->where('name', $name)
                        ->where('province_id', $provinceId)
                        ->value('id');
                    $this->rememberMap('dce', $row->iddce, 'communes', $targetId);
                    $this->bump($id ? 'communes_upserted' : 'communes_seen');
                }
            });
        }
    }

    private function migrateLevels(ConnectionInterface $legacy): void
    {
        $legacy->table('niveau')->orderBy('idniveau')->chunk($this->chunkSize, function ($levels) {
            foreach ($levels as $level) {
                $name = $this->normalizeName($level->libelleniv ?? '');
                if ($name === '') {
                    $this->reject('niveau', $level->idniveau, 'Blank level name', (array) $level);

                    continue;
                }

                $typeId = $this->ensureType($level->catniveau ?: $level->cat_niveau ?: 'Legacy');
                $cycleId = $this->ensureCycle($level->cat_niveau ?: $level->catniveau ?: 'Legacy', $typeId);
                $code = 'LYS-N' . $level->idniveau;

                $this->upsert('niveaux_scolaires', ['code' => $code], [
                    'nom' => $name,
                    'ordre' => (int) $level->idniveau,
                    'type_id' => $typeId,
                    'cycle_id' => $cycleId,
                    'description' => 'Imported from lysedb niveau',
                    'actif' => true,
                ]);

                $targetId = $this->dryRun ? $this->fakeId() : (int) DB::table('niveaux_scolaires')->where('code', $code)->value('id');
                $this->rememberMap('niveau', $level->idniveau, 'niveaux_scolaires', $targetId);
                $this->attachSchoolLevel($targetId);
                $this->bump('levels_upserted');
            }
        });
    }

    private function migrateClasses(ConnectionInterface $legacy): void
    {
        $years = $this->allMaps('anneescolaire', 'annee_scolaires');

        $legacy->table('classe')->orderBy('idclasse')->chunk($this->chunkSize, function ($classes) use ($years) {
            foreach ($classes as $class) {
                $levelId = $this->map('niveau', $class->idniveau, 'niveaux_scolaires');
                if (! $levelId) {
                    $this->reject('classe', $class->idclasse, 'Missing level map', (array) $class);

                    continue;
                }

                foreach ($years as $oldYearId => $yearId) {
                    $name = $this->normalizeName($class->libellecl ?: $class->siglecl ?: ('Classe ' . $class->idclasse));
                    $code = 'LYS-CL' . $class->idclasse . '-' . $oldYearId;

                    $this->upsert('classes', [
                        'school_id' => $this->schoolId,
                        'nom' => $name,
                        'annee_scolaire_id' => $yearId,
                    ], [
                        'code' => $code,
                        'niveau_id' => $levelId,
                        'niveau_scolaire_id' => $levelId,
                        'statut' => 'ACTIVE',
                        'created_by' => null,
                    ]);

                    $targetId = $this->dryRun ? $this->fakeId() : (int) DB::table('classes')
                        ->where('school_id', $this->schoolId)
                        ->where('nom', $name)
                        ->where('annee_scolaire_id', $yearId)
                        ->value('id');
                    $this->rememberMap('classe', $class->idclasse, 'classes', $targetId, (string) $oldYearId);
                    $this->bump('classes_upserted');
                }
            }
        });
    }

    private function migrateCourses(ConnectionInterface $legacy): void
    {
        $legacy->table('cours')->orderBy('idcours')->chunk($this->chunkSize, function ($courses) {
            foreach ($courses as $course) {
                $levelId = $this->map('niveau', $course->idniveau, 'niveaux_scolaires');
                if (! $levelId) {
                    $this->reject('cours', $course->idcours, 'Missing level map', (array) $course);

                    continue;
                }

                $name = $this->normalizeName($course->libellec ?? '');
                if ($name === '') {
                    $this->reject('cours', $course->idcours, 'Blank course name', (array) $course);

                    continue;
                }

                $categoryId = $this->ensureCourseCategory($course->cat_cours ?: $course->catcours ?: 'Legacy');
                $code = 'LYS-C' . $course->idcours;
                $weight = max(1, (int) $course->ponderation);

                $this->upsert('matieres', ['code' => $code], [
                    'nom' => $name,
                    'niveau_id' => $levelId,
                    'categorie_cours_id' => $categoryId,
                    'coefficient' => $weight,
                    'ponderation_tj' => 0,
                    'ponderation_competence' => 0,
                    'ponderation_examen' => $weight,
                    'credit_heures' => 0,
                    'heures_par_semaine' => 0,
                    'actif' => true,
                ]);

                $targetId = $this->dryRun ? $this->fakeId() : (int) DB::table('matieres')->where('code', $code)->value('id');
                $this->rememberMap('cours', $course->idcours, 'matieres', $targetId);
                $this->attachMatiereLevel($targetId, $levelId);
                $this->bump('courses_upserted');
            }
        });
    }

    private function seedReglementScolaire(ConnectionInterface $legacy): void
    {
        if (! Schema::connection($legacy->getName())->hasTable('reglement_scolaire')) {
            return;
        }

        $this->info('Migrating school rules...');
        $legacy->table('reglement_scolaire')->orderBy('idreglement_scolaire')->chunk($this->chunkSize, function ($rows) {
            foreach ($rows as $row) {
                $title = $this->normalizeName($row->libelle_reglement_scolaire ?? $row->intitule ?? '');
                if ($title === '') {
                    $this->reject('reglement_scolaire', $row->idreglement_scolaire, 'Blank rule title', (array) $row);

                    continue;
                }

                $this->upsert('reglement_scolaires', [
                    'school_id' => $this->schoolId,
                    'article_number' => 'LYS-' . $row->idreglement_scolaire,
                ], [
                    'intitule' => $title,
                    'points_retires' => (int) ($row->points_retires ?? $row->note ?? 0),
                    'sanction' => $row->sanction ?? null,
                ]);
                $targetId = $this->dryRun ? $this->fakeId() : (int) DB::table('reglement_scolaires')
                    ->where('school_id', $this->schoolId)
                    ->where('article_number', 'LYS-' . $row->idreglement_scolaire)
                    ->value('id');
                $this->rememberMap('reglement_scolaire', $row->idreglement_scolaire, 'reglement_scolaires', $targetId);
                $this->bump('rules_upserted');
            }
        });
    }

    private function seedTeachers(ConnectionInterface $legacy): void
    {
        $this->info('Migrating teachers and reset-required users...');

        $legacy->table('enseignant')->orderBy('idens')->chunk($this->chunkSize, function ($teachers) {
            foreach ($teachers as $teacher) {
                $name = trim($this->normalizeName(($teacher->prenomens ?? '') . ' ' . ($teacher->nomens ?? '')));
                if ($name === '') {
                    $this->reject('enseignant', $teacher->idens, 'Blank teacher name', (array) $teacher);

                    continue;
                }

                $email = 'legacy.teacher.' . $teacher->idens . '@invalid.local';
                $userId = $this->dryRun ? 0 : $this->upsertUser($email, $name);
                $matricule = 'LYS-ENS-' . $teacher->idens;

                $this->upsert('enseignants', ['matricule' => $matricule], [
                    'user_id' => $userId,
                    'school_id' => $this->schoolId,
                    'qualification' => $this->normalizeQualification($teacher->qualification ?? ''),
                    'qualification_precision' => $teacher->qualification ?: null,
                    'domaines' => json_encode(array_values(array_filter([(string) ($teacher->domaine ?? '')]))),
                    'annees_experience' => 0,
                    'statut' => ((int) ($teacher->etat ?? 0)) === 1 ? 'SUSPENDU' : 'ACTIF',
                ]);

                $targetId = $this->dryRun ? $this->fakeId() : (int) DB::table('enseignants')->where('matricule', $matricule)->value('id');
                $this->rememberMap('enseignant', $teacher->idens, 'enseignants', $targetId);
                $this->attachTeacherSchool($targetId);
                $this->bump('teachers_upserted');
            }
        });
    }

    private function seedStudents(ConnectionInterface $legacy): void
    {
        $this->info('Migrating students, parents, inscriptions, and class assignments...');

        foreach (['eleve', 'eleve1', 'eleve2'] as $table) {
            if (! Schema::connection($legacy->getName())->hasTable($table)) {
                continue;
            }

            $legacy->table($table)->orderBy('numel')->chunk($this->chunkSize, function ($students) use ($table) {
                foreach ($students as $student) {
                    $this->seedOneStudent($table, $student);
                }
            });
        }
    }

    private function seedOneStudent(string $table, object $student): void
    {
        $sex = strtoupper(trim((string) ($student->sexe ?? '')));
        if (! in_array($sex, ['M', 'F'], true)) {
            $this->reject($table, $student->numel, 'Invalid sex', (array) $student);

            return;
        }

        $yearId = $this->map('anneescolaire', $student->idas, 'annee_scolaires');
        $classId = $this->map('classe', $student->idclasse, 'classes', (string) $student->idas);
        if (! $yearId || ! $classId) {
            $this->reject($table, $student->numel, 'Missing year or class map', (array) $student);

            return;
        }

        $class = $this->dryRun ? null : DB::table('classes')->whereid($classId)->first(['niveau_id']);
        if (! $this->dryRun && ! $class?->niveau_id) {
            $this->reject($table, $student->numel, 'Mapped class is missing a level', (array) $student);

            return;
        }
        $levelId = $class?->niveau_id ?: 1;
        $matricule = 'LYS-' . strtoupper($table) . '-' . $student->numel;

        $this->upsert('eleves', ['matricule' => $matricule], [
            'nom' => $this->normalizeName($student->nomel ?? '') ?: 'INCONNU',
            'prenom' => $this->normalizeName($student->prenomel ?? '') ?: '-',
            'sexe' => $sex,
            'date_naissance' => $this->validDate($student->datenaissance ?? null) ?: '2024-01-01',
            'lieu_naissance' => $this->normalizeName($student->lieunaissance ?? '') ?: '-',
            'nationalite' => $this->normalizeName($student->nationalite ?? '') ?: 'Burundaise',
            'commune_origine_id' => $this->map('dce', $student->iddce ?? null, 'communes'),
            'niveau_id' => $levelId,
            'adresse' => trim(implode(', ', array_filter([
                $student->communeresidance ?? null,
                $student->zoneresidance ?? null,
                $student->quartierresidance ?? null,
            ]))) ?: null,
            'nom_pere' => $this->fullName($student->nompere ?? null, $student->prenompere ?? null),
            'nom_mere' => $this->fullName($student->nommere ?? null, $student->prenommere ?? null),
            'nom_tuteur' => $this->fullName($student->nomtuteur ?? null, $student->prenomtuteur ?? null),
            'contact_tuteur' => $student->telpere ?: $student->telmere ?: null,
            'est_orphelin' => $this->truthy($student->orpherin ?? null),
            'a_handicap' => false,
            'school_id' => $this->schoolId,
            'statut_global' => 'actif',
            'est_redoublant' => ((int) ($student->red ?? 0)) > 0,
        ]);

        $eleveId = $this->dryRun ? $this->fakeId() : (int) DB::table('eleves')->where('matricule', $matricule)->value('id');
        $this->rememberMap($table, $student->numel, 'eleves', $eleveId);
        $this->seedParents($eleveId, $student);

        $inscriptionId = $this->seedInscription($table, $student, $eleveId, $yearId, $levelId ?: (int) DB::table('classes')->whereid($classId)->value('niveau_id'));
        $this->seedClassAssignment($table, $student, $inscriptionId, $classId);
        $this->bump('students_upserted');
    }

    private function seedParents(int $eleveId, object $student): void
    {
        if ($this->dryRun || ! $eleveId) {
            return;
        }

        foreach (
            [
                ['relation' => 'pere', 'name' => $this->fullName($student->nompere ?? null, $student->prenompere ?? null), 'phone' => $student->telpere ?? null],
                ['relation' => 'mere', 'name' => $this->fullName($student->nommere ?? null, $student->prenommere ?? null), 'phone' => $student->telmere ?? null],
                ['relation' => 'tuteur', 'name' => $this->fullName($student->nomtuteur ?? null, $student->prenomtuteur ?? null), 'phone' => $student->telpere ?: ($student->telmere ?? null)],
            ] as $parent
        ) {
            if (! $parent['name']) {
                continue;
            }

            DB::table('parents')->updateOrInsert(
                ['eleve_id' => $eleveId, 'relation' => $parent['relation']],
                [
                    'nom_complet' => $parent['name'],
                    'telephone' => $parent['phone'] ?: null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $this->bump('parents_upserted');
        }
    }

    private function seedInscription(string $table, object $student, int $eleveId, int $yearId, int $levelId): int
    {
        $numero = 'LYS-INS-' . strtoupper($table) . '-' . $student->numel . '-' . $student->idas;

        $dateInscription = $this->validDate($student->created_at ?? null);

        // fallback if invalid date
        if (
            empty($dateInscription) ||
            strtotime($dateInscription) === false ||
            str_starts_with($dateInscription, '-')
        ) {
            $dateInscription = DB::table('annee_scolaires')
                ->where('id', $yearId)
                ->value('date_debut');
        }

        $this->upsert('inscriptions', [
            'eleve_id' => $eleveId,
            'annee_scolaire_id' => $yearId,
            'school_id' => $this->schoolId,
        ], [
            'numero_inscription' => $numero,
            'eleve_id' => $eleveId,
            'annee_scolaire_id' => $yearId,
            'school_id' => $this->schoolId,
            'niveau_demande_id' => $levelId,
            'type_inscription' => 'reinscription',
            'statut' => 'valide',
            'statut_academique' => 'en_cours',
            'date_inscription' => $dateInscription,
            'date_soumission' => now(),
            'date_validation' => now(),
            'est_redoublant' => ((int) ($student->red ?? 0)) > 0,
        ]);

        $id = $this->dryRun
            ? $this->fakeId()
            : (int) DB::table('inscriptions')
                ->where('eleve_id', $eleveId)
                ->where('annee_scolaire_id', $yearId)
                ->where('school_id', $this->schoolId)
                ->value('id');

        $this->rememberMap($table, $student->numel, 'inscriptions', $id, (str $student->idas);

        $this->bump('inscriptions_upserted');

        return $id;
    }

    private function seedClassAssignment(string $table, object $student, int $inscriptionId, int $classId): void
    {
        if ($this->dryRun || ! $inscriptionId || ! $classId) {
            return;
        }

        DB::table('affectations_classe')
            ->where('inscription_id', $inscriptionId)
            ->where('est_active', true)
            ->where('classe_id', '!=', $classId)
            ->update([
                'est_active' => false,
                'date_fin' => now(),
                'updated_at' => now(),
            ]);

        $dateInscription = DB::table('inscriptions')->whereid($inscriptionId)->value('date_inscription');

        DB::table('affectations_classe')->updateOrInsert(
            ['inscription_id' => $inscriptionId, 'classe_id' => $classId],
            [
                'date_affectation' => $dateInscription ?: now()->toDateString(),
                'est_active' => true,
                'numero_ordre' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $eleveId = $this->map($table, $student->numel, 'eleves');
        $classe = DB::table('classes')->whereid($classId)->first(['school_id', 'niveau_id']);

        DB::table('eleve_class')->updateOrInsert(
            ['eleve_id' => $eleveId, 'classe_id' => $classId],
            [
                'annee_scolaire' => (string) $student->idas,
                'date_inscription' => $dateInscription,
                'statut' => 'ACTIVE',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('eleves')->whereid($eleveId)->update([
            'school_id' => $classe?->school_id ?? $this->schoolId,
            'niveau_id' => $classe?->niveau_id,
            'statut_global' => 'actif',
            'updated_at' => now(),
        ]);

        DB::table('classes')->whereid($classId)->update([
            'effectif' => DB::table('eleve_class')
                ->where('classe_id', $classId)
                ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                ->count(),
            'updated_at' => now(),
        ]);

        $this->bump('class_assignments_upserted');
    }

    private function seedTeacherAssignments(ConnectionInterface $legacy): void
    {
        if (! Schema::connection($legacy->getName())->hasTable('enseigner')) {
            return;
        }

        $this->info('Migrating teacher/course assignments...');
        $legacy->table('enseigner')->orderBy('idenseigner')->chunk($this->chunkSize, function ($rows) {
            foreach ($rows as $row) {
                $teacherId = $this->map('enseignant', $row->idens, 'enseignants');
                $matiereId = $this->map('cours', $row->idcours, 'matieres');
                $yearId = $this->map('anneescolaire', $row->idas, 'annee_scolaires');
                $classId = $this->map('classe', $row->idclasse, 'classes', (string) $row->idas);

                if (! $teacherId || ! $matiereId || ! $yearId || ! $classId) {
                    $this->reject('enseigner', $row->idenseigner, 'Missing teacher, course, year, or class map', (array) $row);

                    continue;
                }

                $this->upsert('affectations_matieres', [
                    'enseignant_id' => $teacherId,
                    'matiere_id' => $matiereId,
                    'annee_scolaire_id' => $yearId,
                ], [
                    'school_id' => $this->schoolId,
                    'statut' => 'ACTIVE',
                ]);

                $courseName = $this->dryRun ? null : DB::table('matieres')->whereid($matiereId)->value('nom');
                $this->upsert('affectations_enseignants', [
                    'enseignant_id' => $teacherId,
                    'classe_id' => $classId,
                    'matiere' => $courseName,
                ], [
                    'date_debut' => DB::table('annee_scolaires')->whereid($yearId)->value('date_debut') ?: now()->toDateString(),
                    'statut' => 'ACTIVE',
                ]);

                $targetId = $this->dryRun ? $this->fakeId() : (int) DB::table('affectations_matieres')
                    ->where('enseignant_id', $teacherId)
                    ->where('matiere_id', $matiereId)
                    ->where('annee_scolaire_id', $yearId)
                    ->value('id');
                $this->rememberMap('enseigner', $row->idenseigner, 'affectations_matieres', $targetId);
                $this->bump('teacher_assignments_upserted');
            }
        });
    }

    private function seedAggregateNotes(ConnectionInterface $legacy): void
    {
        $this->info('Migrating aggregate notes...');

        $legacy->table('notes')->orderBy('idnoter')->chunk($this->chunkSize, function ($rows) {
            foreach ($rows as $row) {
                foreach ([1, 2, 3] as $trimester) {
                    foreach (
                        [
                            'TJ' => ['field' => 'tj' . $trimester, 'max' => 'maxtj'],
                            'Examen' => ['field' => 'exam' . $trimester, 'max' => 'maxexam'],
                            'Compétence' => ['field' => 'comp' . $trimester, 'max' => 'maxcomp'],
                        ] as $type => $fields
                    ) {
                        $value = (float) ($row->{$fields['field']} ?? 0);
                        if ($value <= 0) {
                            continue;
                        }

                        $this->seedOneNote('notes', $row->idnoter, $row, $type, $trimester, $value, (float) ($row->{$fields['max']} ?? 0));
                    }
                }
            }
        });
    }

    private function seedTravauxNotes(ConnectionInterface $legacy): void
    {
        if (! Schema::connection($legacy->getName())->hasTable('notestravaux')) {
            return;
        }

        $this->info('Migrating individual work notes...');

        $legacy->table('notestravaux')->orderBy('idnotertravaux')->chunk($this->chunkSize, function ($rows) {
            foreach ($rows as $row) {
                $trimester = $this->trimesterNumber($row->libelletravaux ?? '') ?: 1;
                $type = str_contains(strtoupper((string) $row->libelletravaux), 'EX') ? 'Examen' : 'TJ';
                $this->seedOneNote('notestravaux', $row->idnotertravaux, $row, $type, $trimester, (float) $row->notetravaux, (float) $row->maxtravaux, 'trav' . (int) $row->numtrav);
            }
        });
    }

    private function seedOneNote(string $sourceTable, int $sourceKey, object $row, string $type, int $trimester, float $value, float $max, string $slot = 'aggregate'): void
    {
        $yearId = $this->map('anneescolaire', $row->idas, 'annee_scolaires');
        $classId = $this->map('classe', $row->idclasse, 'classes', (string) $row->idas);
        $courseId = $this->map('cours', $row->idcours, 'matieres');
        $studentMap = $this->findStudentMap((int) $row->numel);

        if (! $yearId || ! $classId || ! $courseId || ! $studentMap) {
            $this->reject($sourceTable, $sourceKey, 'Missing note dependency map', (array) $row);

            return;
        }

        $trimestreName = $this->trimestreName($trimester);
        $trimestreId = $this->findTrimestreId($yearId, $trimestreName);
        $evaluationId = $this->ensureEvaluation($classId, $courseId, $yearId, $trimestreId, $trimestreName, $type, $max, $slot);
        $inscriptionId = $this->map($studentMap['source_table'], $row->numel, 'inscriptions', (string) $row->idas);

        if ($this->dryRun || ! $evaluationId) {
            $this->bump('notes_seen');

            return;
        }

        DB::table('notes')->updateOrInsert(
            ['evaluation_id' => $evaluationId, 'eleve_id' => $studentMap['target_id']],
            [
                'inscription_id' => $inscriptionId,
                'note' => $value,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->bump('notes_upserted');
    }

    private function ensureEvaluation(int $classId, int $courseId, int $yearId, ?int $trimestreId, string $trimestreName, string $type, float $max, string $slot): int
    {
        $date = DB::table('annee_scolaires')->whereid($yearId)->value('date_debut') ?: now()->toDateString();
        $date = Carbon::parse($date)->addDays($slot === 'aggregate' ? 0 : max(1, (int) preg_replace('/\D+/', '', $slot)))->toDateString();
        $max = $max > 0 ? $max : 100;

        $this->upsert('evaluations', [
            'classe_id' => $classId,
            'cours_id' => $courseId,
            'annee_scolaire_id' => $yearId,
            'trimestre' => $trimestreName,
            'type_evaluation' => $type,
            'date_passation' => $date,
        ], [
            'trimestre_id' => $trimestreId,
            'note_maximale' => $max,
        ]);

        return $this->dryRun ? $this->fakeId() : (int) DB::table('evaluations')
            ->where('classe_id', $classId)
            ->where('cours_id', $courseId)
            ->where('annee_scolaire_id', $yearId)
            ->where('trimestre', $trimestreName)
            ->where('type_evaluation', $type)
            ->where('date_passation', $date)
            ->value('id');
    }

    private function upsert(string $table, array $keys, array $values): void
    {
        if ($this->dryRun) {
            return;
        }

        DB::table($table)->updateOrInsert($keys, $values + [
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function upsertUser(string $email, string $name): int
    {
        DB::table('users')->updateOrInsert(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(Str::random(32)),
                'statut' => 'inactif',
                'school_id' => $this->schoolId,
                'admin_level' => 'ECOLE',
                'admin_entity_id' => $this->schoolId,
                'must_change_password' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return (int) DB::table('users')->where('email', $email)->value('id');
    }

    private function ensureType(?string $name): int
    {
        $name = $this->normalizeName($name ?: 'Legacy') ?: 'Legacy';
        $this->upsert('types_scolaires', ['nom' => $name], ['actif' => true]);

        return $this->dryRun ? $this->fakeId() : (int) DB::table('types_scolaires')->where('nom', $name)->value('id');
    }

    private function ensureCycle(?string $name, int $typeId): int
    {
        $name = $this->normalizeName($name ?: 'Legacy') ?: 'Legacy';
        $this->upsert('cycles_scolaires', ['nom' => $name, 'type_id' => $typeId], ['actif' => true]);

        return $this->dryRun ? $this->fakeId() : (int) DB::table('cycles_scolaires')->where('nom', $name)->where('type_id', $typeId)->value('id');
    }

    private function ensureCourseCategory(?string $name): int
    {
        $name = $this->normalizeName($name ?: 'Legacy') ?: 'Legacy';
        $this->upsert('categories_cours', ['nom' => $name], ['ordre' => 0, 'afficher_bulletin' => true]);

        return $this->dryRun ? $this->fakeId() : (int) DB::table('categories_cours')->where('nom', $name)->value('id');
    }

    private function upsertTrimestre(int $oldYearId, int $yearId, string $name): void
    {
        $this->upsert('trimestres', ['annee_scolaire_id' => $yearId, 'nom' => $name], [
            'actif' => false,
            'verrouille' => false,
        ]);

        $targetId = $this->dryRun ? $this->fakeId() : (int) DB::table('trimestres')->where('annee_scolaire_id', $yearId)->where('nom', $name)->value('id');
        $this->rememberMap('trimestre', $this->oldTrimestreId($name), 'trimestres', $targetId, (string) $oldYearId);
    }

    private function attachSchoolLevel(int $levelId): void
    {
        if ($this->dryRun || ! $levelId) {
            return;
        }

        DB::table('niveau_school')->updateOrInsert(
            ['school_id' => $this->schoolId, 'niveau_scolaire_id' => $levelId],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }

    private function attachMatiereLevel(int $matiereId, int $levelId): void
    {
        if ($this->dryRun || ! $matiereId || ! $levelId) {
            return;
        }

        DB::table('matiere_niveaux')->updateOrInsert(
            ['matiere_id' => $matiereId, 'niveau_id' => $levelId],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }

    private function attachTeacherSchool(int $teacherId): void
    {
        if ($this->dryRun || ! $teacherId) {
            return;
        }

        DB::table('enseignant_school')->updateOrInsert(
            ['enseignant_id' => $teacherId, 'school_id' => $this->schoolId],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }

    private function rememberMap(string $sourceTable, mixed $sourceKey, string $targetTable, int $targetId, string $context = ''): void
    {
        $key = $this->memoryKey($sourceTable, $sourceKey, $targetTable, $context);
        $this->memoryMaps[$key] = ['target_id' => $targetId];

        if ($this->dryRun || ! $targetId) {
            return;
        }

        DB::table('legacy_import_maps')->updateOrInsert(
            [
                'source' => self::SOURCE,
                'source_table' => $sourceTable,
                'source_key' => (string) $sourceKey,
                'source_context' => $context,
                'target_table' => $targetTable,
            ],
            [
                'legacy_import_run_id' => $this->runId,
                'target_id' => $targetId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function map(string $sourceTable, mixed $sourceKey, string $targetTable, string $context = ''): ?int
    {
        if ($sourceKey === null || $sourceKey === '') {
            return null;
        }

        $key = $this->memoryKey($sourceTable, $sourceKey, $targetTable, $context);
        if (isset($this->memoryMaps[$key])) {
            return $this->memoryMaps[$key]['target_id'] ?: null;
        }

        if ($this->dryRun) {
            return null;
        }

        $targetId = DB::table('legacy_import_maps')
            ->where('source', self::SOURCE)
            ->where('source_table', $sourceTable)
            ->where('source_key', (string) $sourceKey)
            ->where('source_context', $context)
            ->where('target_table', $targetTable)
            ->value('target_id');

        return $targetId ? (int) $targetId : null;
    }

    /** @return array<string, int> */
    private function allMaps(string $sourceTable, string $targetTable): array
    {
        if (! $this->dryRun) {
            return DB::table('legacy_import_maps')
                ->where('source', self::SOURCE)
                ->where('source_table', $sourceTable)
                ->where('target_table', $targetTable)
                ->pluck('target_id', 'source_key')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        $maps = [];
        foreach ($this->memoryMaps as $key => $value) {
            [$srcTable, $srcKey, $target] = explode('|', $key);
            if ($srcTable === $sourceTable && $target === $targetTable) {
                $maps[$srcKey] = $value['target_id'];
            }
        }

        return $maps;
    }

    /** @return array{source_table:string,target_id:int}|null */
    private function findStudentMap(int $legacyStudentId): ?array
    {
        foreach (['eleve', 'eleve1', 'eleve2'] as $table) {
            $id = $this->map($table, $legacyStudentId, 'eleves');
            if ($id) {
                return ['source_table' => $table, 'target_id' => $id];
            }
        }

        return null;
    }

    private function reject(string $sourceTable, mixed $sourceKey, string $reason, array $payload = []): void
    {
        $this->bump('rejections');

        if ($this->dryRun) {
            $this->warn("Rejected {$sourceTable}:{$sourceKey} - {$reason}");

            return;
        }

        DB::table('legacy_import_rejections')->insert([
            'legacy_import_run_id' => $this->runId,
            'source' => self::SOURCE,
            'source_table' => $sourceTable,
            'source_key' => $sourceKey === null ? null : (string) $sourceKey,
            'reason' => $reason,
            'payload' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function finishRun(string $status, ?Throwable $exception = null): void
    {
        if ($this->dryRun || ! $this->runId) {
            return;
        }

        DB::table('legacy_import_runs')->whereid($this->runId)->update([
            'status' => $status,
            'stats' => json_encode($this->stats),
            'error' => $exception?->getMessage(),
            'finished_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function printStats(): void
    {
        $this->newLine();
        $this->info('Import stats:');
        foreach ($this->stats as $key => $value) {
            $this->line("  {$key}: {$value}");
        }
    }

    private function bump(string $key): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + 1;
    }

    private function fakeId(): int
    {
        return $this->fakeId--;
    }

    private function memoryKey(string $sourceTable, mixed $sourceKey, string $targetTable, string $context = ''): string
    {
        return $sourceTable . '|' . (string) $sourceKey . '|' . $targetTable . '|' . $context;
    }

    private function normalizeName(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
    }

    private function fullName(?string $lastName, ?string $firstName): ?string
    {
        $name = $this->normalizeName(trim(($firstName ?? '') . ' ' . ($lastName ?? '')));

        return $name !== '' ? $name : null;
    }

    private function validDate(mixed $value): ?string
    {
        try {
            if (! $value || $value === '0000-00-00') {
                return null;
            }

            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function truthy(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'y', 'yes', 'oui', 'o', 'true'], true);
    }

    private function normalizeQualification(?string $value): string
    {
        $value = strtoupper($this->normalizeName($value));

        return match (true) {
            str_contains($value, 'MASTER') => 'MASTER',
            str_contains($value, 'DOCTOR') => 'DOCTORAT',
            str_contains($value, 'LICENCE') => 'LICENCE',
            str_contains($value, 'DIPLO') => 'DIPLOME_PEDAGOGIQUE',
            default => 'AUTRE',
        };
    }

    private function trimestreName(int $number): string
    {
        return match ($number) {
            2 => '2e Trimestre',
            3 => '3e Trimestre',
            default => '1er Trimestre',
        };
    }

    private function oldTrimestreId(string $name): int
    {
        return match ($name) {
            '2e Trimestre' => 2,
            '3e Trimestre' => 3,
            default => 1,
        };
    }

    private function trimesterNumber(string $label): ?int
    {
        return match (true) {
            str_contains(strtoupper($label), '3') || str_contains(strtoupper($label), 'T3') => 3,
            str_contains(strtoupper($label), '2') || str_contains(strtoupper($label), 'T2') => 2,
            str_contains(strtoupper($label), '1') || str_contains(strtoupper($label), 'T1') => 1,
            default => null,
        };
    }

    private function findTrimestreId(int $yearId, string $name): ?int
    {
        if ($this->dryRun) {
            return null;
        }

        $id = DB::table('trimestres')->where('annee_scolaire_id', $yearId)->where('nom', $name)->value('id');

        return $id ? (int) $id : null;
    }
}
