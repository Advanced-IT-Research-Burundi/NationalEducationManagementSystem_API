<?php

namespace App\Imports;

use App\Models\AnneeScolaire;
use App\Models\Colline;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\School;
use App\Services\AcademicYearService;
use App\Services\MatriculeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Validators\ValidationException;

class EleveImport implements ToCollection, SkipsEmptyRows
{
    private array $collineCache = [];

    private array $ecoleCache = [];

    private array $niveauCache = [];

    public function __construct(
        private readonly ?int $defaultSchoolId = null,
        private readonly ?int $defaultNiveauId = null,
        private readonly ?int $forcedSchoolId = null,
    ) {}

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw new \RuntimeException('Le fichier est vide.');
        }

        $headerRowIndex = $this->detectHeaderRowIndex($rows);

        if ($headerRowIndex === null) {
            throw new \RuntimeException(
                'En-têtes introuvables. Une colonne doit s\'appeler « matricule » (ligne 3 du modèle ou ligne 1 pour un CSV).'
            );
        }

        $headerKeys = $this->extractHeaderKeys($rows[$headerRowIndex]->toArray());
        $anneeScolaire = $this->resolveAnneeScolaire();

        if (! $anneeScolaire) {
            throw new \RuntimeException('Aucune année scolaire active. Impossible d\'importer.');
        }

        $failures = [];
        $prepared = [];
        $matriculesInFile = [];
        $skippedMeta = 0;
        $skippedEmpty = 0;

        foreach ($rows as $index => $row) {
            if ($index <= $headerRowIndex) {
                continue;
            }

            $excelRow = $index + 1;
            $rawData = $row->toArray();
            $data = $this->mapRow($headerKeys, $rawData);

            if ($this->isTemplateMetaRow($data)) {
                $skippedMeta++;
                continue;
            }

            $nonEmpty = array_filter(array_values($data), fn ($v) => ! is_null($v) && $v !== '');
            if (empty($nonEmpty)) {
                $skippedEmpty++;
                continue;
            }

            $flat = $this->flattenRow($data);
            $rowFailures = $this->validateRow($flat, $excelRow, $rawData, $matriculesInFile);

            if (! empty($rowFailures)) {
                $failures = array_merge($failures, $rowFailures);
                continue;
            }

            $schoolId = $this->forcedSchoolId ?? $this->resolveSchoolId($flat['school_destination']) ?? $this->defaultSchoolId;
            $niveauId = $this->resolveNiveauId($flat['niveau']) ?? $this->defaultNiveauId;
            $collineOrigine = trim((string) ($flat['colline_origine'] ?? ''));

            if ($collineOrigine !== '' && ! $this->resolveCollineId($collineOrigine)) {
                $failures[] = new Failure($excelRow, 'colline_origine', [
                    "Colline « {$collineOrigine} » introuvable. Voir l'onglet LISTES.",
                ], $rawData);
                continue;
            }

            if (! $schoolId) {
                $failures[] = new Failure($excelRow, 'school_destination', [
                    'École introuvable ou non renseignée. Indiquez le nom exact ou utilisez le contexte d\'import.',
                ], $rawData);
                continue;
            }

            if ($this->forcedSchoolId && $flat['school_destination']) {
                $resolved = $this->resolveSchoolId($flat['school_destination']);
                if ($resolved && $resolved !== $this->forcedSchoolId) {
                    $failures[] = new Failure($excelRow, 'school_destination', [
                        'Vous ne pouvez importer des élèves que pour votre établissement.',
                    ], $rawData);
                    continue;
                }
            }

            if (! $niveauId) {
                $failures[] = new Failure($excelRow, 'niveau', [
                    'Niveau introuvable ou non renseigné. Indiquez le nom exact ou utilisez le contexte d\'import.',
                ], $rawData);
                continue;
            }

            $matricule = trim((string) ($flat['matricule'] ?? ''));
            if ($matricule === '') {
                $matricule = $this->generateMatricule($schoolId, $matriculesInFile);
            }

            $matriculesInFile[] = mb_strtolower($matricule);

            $aHandicap = $this->parseBool($flat['a_handicap'] ?? false);

            $prepared[] = [
                'excel_row' => $excelRow,
                'matricule' => $matricule,
                'payload' => [
                    'nom' => $flat['nom'],
                    'prenom' => $flat['prenom'],
                    'sexe' => $flat['sexe'],
                    'date_naissance' => $flat['date_naissance'],
                    'lieu_naissance' => $flat['lieu_naissance'],
                    'nationalite' => $flat['nationalite'] ?: 'Burundaise',
                    'colline_origine_id' => $this->resolveCollineId($collineOrigine ?: null),
                    'adresse' => $flat['adresse'],
                    'nom_pere' => $flat['nom_pere'],
                    'nom_mere' => $flat['nom_mere'],
                    'nom_tuteur' => $flat['nom_tuteur'],
                    'contact_tuteur' => $flat['contact_tuteur'],
                    'est_orphelin' => (int) $this->parseBool($flat['est_orphelin'] ?? false),
                    'a_handicap' => (int) $aHandicap,
                    'type_handicap' => $aHandicap ? $flat['type_handicap'] : null,
                    'school_id' => $schoolId,
                    'niveau_id' => $niveauId,
                    'statut_global' => 'actif',
                    'created_by' => Auth::id(),
                ],
                'school_id' => $schoolId,
                'niveau_id' => $niveauId,
            ];
        }

        if (! empty($failures)) {
            throw new ValidationException(
                \Illuminate\Validation\ValidationException::withMessages(['import' => ['Erreurs de validation']]),
                $failures
            );
        }

        if (empty($prepared)) {
            throw new ValidationException(
                \Illuminate\Validation\ValidationException::withMessages(['import' => ['Aucune ligne de données valide']]),
                [new Failure(0, 'file', [$this->buildEmptyFileMessage($skippedMeta, $skippedEmpty, $headerRowIndex)], [])]
            );
        }

        DB::transaction(function () use ($prepared, $anneeScolaire) {
            $now = now()->format('Y-m-d H:i:s');
            $userId = Auth::id();

            foreach ($prepared as $row) {
                $payload = array_merge($row['payload'], ['updated_at' => $now]);
                $matricule = $row['matricule'];

                $existing = DB::table('eleves')->where('matricule', $matricule)->first();

                if ($existing) {
                    DB::table('eleves')
                        ->where('matricule', $matricule)
                        ->update(array_merge($payload, ['deleted_at' => null]));

                    $eleveId = (int) $existing->id;
                } else {
                    $eleveId = DB::table('eleves')->insertGetId(array_merge($payload, [
                        'matricule' => $matricule,
                        'created_at' => $now,
                    ]));
                }

                $this->ensureInscription(
                    eleveId: $eleveId,
                    schoolId: $row['school_id'],
                    niveauId: $row['niveau_id'],
                    anneeScolaire: $anneeScolaire,
                    userId: $userId,
                    now: $now,
                );
            }
        });
    }

    private function detectHeaderRowIndex(Collection $rows): ?int
    {
        foreach ($rows as $index => $row) {
            if ($index > 15) {
                break;
            }

            foreach ($row->toArray() as $cell) {
                $key = $this->normalizeKey((string) $cell);
                if (in_array($key, ['matricule', 'matricule_'], true)) {
                    return $index;
                }
            }
        }

        return null;
    }

    private function buildEmptyFileMessage(int $skippedMeta, int $skippedEmpty, int $headerRowIndex): string
    {
        $firstDataRow = $headerRowIndex + 2;
        $parts = [
            'Aucune ligne de données valide trouvée dans le fichier.',
            "Saisissez vos élèves à partir de la ligne {$firstDataRow} (après les lignes d'exemple du modèle).",
            'Chaque ligne doit contenir au minimum : nom, prénom, sexe (M/F), date de naissance, lieu de naissance, école et niveau.',
        ];

        if ($skippedMeta > 0) {
            $parts[] = "{$skippedMeta} ligne(s) d'exemple/instruction ignorée(s) — ne modifiez pas les lignes 4 à 6, ajoutez vos données à partir de la ligne 7.";
        }

        if ($skippedEmpty > 0) {
            $parts[] = "{$skippedEmpty} ligne(s) vide(s) ignorée(s).";
        }

        return implode(' ', $parts);
    }

    private function extractHeaderKeys(array $headerRow): array
    {
        $keys = [];
        foreach ($headerRow as $value) {
            $keys[] = $this->normalizeKey((string) $value);
        }

        return $keys;
    }

    private function mapRow(array $headerKeys, array $rawRow): array
    {
        $data = [];
        foreach ($headerKeys as $i => $key) {
            if ($key === '') {
                continue;
            }
            $data[$key] = $rawRow[$i] ?? null;
        }

        return $data;
    }

    private function flattenRow(array $data): array
    {
        return [
            'matricule' => trim((string) ($this->get($data, 'matricule') ?? '')),
            'nom' => trim((string) ($this->get($data, 'nom') ?? '')),
            'prenom' => trim((string) ($this->get($data, 'prenom') ?? '')),
            'sexe' => strtoupper(trim((string) ($this->get($data, 'sexe') ?? ''))),
            'date_naissance' => $this->normalizeDate($this->get($data, 'date_naissance')),
            'lieu_naissance' => trim((string) ($this->get($data, 'lieu_naissance') ?? '')),
            'nationalite' => trim((string) ($this->get($data, 'nationalite') ?? '')),
            'colline_origine' => $this->get($data, 'colline_origine'),
            'adresse' => $this->get($data, 'adresse'),
            'nom_pere' => $this->get($data, 'nom_pere'),
            'nom_mere' => $this->get($data, 'nom_mere'),
            'nom_tuteur' => $this->get($data, 'nom_tuteur'),
            'contact_tuteur' => $this->get($data, 'contact_tuteur'),
            'est_orphelin' => $this->get($data, 'est_orphelin'),
            'a_handicap' => $this->get($data, 'a_handicap'),
            'type_handicap' => $this->get($data, 'type_handicap'),
            'school_destination' => $this->get($data, 'school_destination'),
            'niveau' => $this->get($data, 'niveau'),
        ];
    }

    private function validateRow(array $flat, int $excelRow, array $rawData, array $matriculesInFile): array
    {
        $validator = Validator::make($flat, [
            'matricule' => ['nullable', 'string', 'max:20'],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'sexe' => ['required', 'in:M,F'],
            'date_naissance' => ['required', 'date_format:Y-m-d'],
            'lieu_naissance' => ['required', 'string', 'max:150'],
        ], [
            'nom.required' => 'Le nom est obligatoire',
            'prenom.required' => 'Le prénom est obligatoire',
            'sexe.required' => 'Le sexe est obligatoire (M ou F)',
            'sexe.in' => 'Le sexe doit être M ou F',
            'date_naissance.required' => 'La date de naissance est obligatoire',
            'date_naissance.date_format' => 'La date doit être au format YYYY-MM-DD ou DD/MM/YYYY',
            'lieu_naissance.required' => 'Le lieu de naissance est obligatoire',
        ]);

        $failures = [];

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $failures[] = new Failure($excelRow, 'validation', [$error], $rawData);
            }
        }

        $matricule = trim((string) ($flat['matricule'] ?? ''));
        if ($matricule !== '') {
            $key = mb_strtolower($matricule);
            if (in_array($key, $matriculesInFile, true)) {
                $failures[] = new Failure($excelRow, 'matricule', [
                    "Matricule en doublon dans le fichier : {$matricule}",
                ], $rawData);
            }
        }

        return $failures;
    }

    private function ensureInscription(
        int $eleveId,
        int $schoolId,
        int $niveauId,
        AnneeScolaire $anneeScolaire,
        ?int $userId,
        string $now,
    ): void {
        $existing = Inscription::withoutGlobalScopes()
            ->where('eleve_id', $eleveId)
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->first();

        if ($existing) {
            Inscription::withoutGlobalScopes()
                ->where('id', $existing->id)
                ->update([
                    'school_id' => $schoolId,
                    'niveau_demande_id' => $niveauId,
                    'statut' => 'valide',
                    'statut_academique' => 'en_cours',
                    'updated_at' => $now,
                ]);

            return;
        }

        $prefix = 'INS';
        $year = $anneeScolaire->date_debut?->format('Y') ?? date('Y');
        $sequence = Inscription::withoutGlobalScopes()->count() + 1;

        Inscription::withoutGlobalScopes()->create([
            'numero_inscription' => sprintf('%s%s%06d', $prefix, $year, $sequence),
            'eleve_id' => $eleveId,
            'annee_scolaire_id' => $anneeScolaire->id,
            'school_id' => $schoolId,
            'niveau_demande_id' => $niveauId,
            'type_inscription' => 'nouvelle',
            'statut' => 'valide',
            'statut_academique' => 'en_cours',
            'date_inscription' => now()->toDateString(),
            'date_soumission' => $now,
            'date_validation' => $now,
            'est_redoublant' => false,
            'created_by' => $userId,
            'valide_par' => $userId,
        ]);
    }

    private function resolveAnneeScolaire(): ?AnneeScolaire
    {
        $id = AcademicYearService::currentId();

        if ($id) {
            return AnneeScolaire::withoutGlobalScopes()->find($id);
        }

        return AnneeScolaire::withoutGlobalScopes()->active()->first();
    }

    private function generateMatricule(int $schoolId, array $matriculesInFile): string
    {
        $service = app(MatriculeService::class);
        $stub = new Eleve(['school_id' => $schoolId]);

        do {
            $matricule = $service->generate($stub);
            $key = mb_strtolower($matricule);
        } while (
            in_array($key, $matriculesInFile, true)
            || DB::table('eleves')->where('matricule', $matricule)->exists()
        );

        return $matricule;
    }

    private function resolveCollineId(?string $nom): ?int
    {
        if (empty($nom)) {
            return null;
        }
        $key = mb_strtolower(trim($nom));
        if (! array_key_exists($key, $this->collineCache)) {
            $this->collineCache[$key] = Colline::whereRaw('LOWER(TRIM(name)) = ?', [$key])->value('id');
        }

        return $this->collineCache[$key];
    }

    private function resolveSchoolId(?string $nom): ?int
    {
        if (empty($nom)) {
            return null;
        }
        $key = mb_strtolower(trim($nom));
        if (! array_key_exists($key, $this->ecoleCache)) {
            $this->ecoleCache[$key] = School::whereRaw('LOWER(TRIM(name)) = ?', [$key])->value('id');
        }

        return $this->ecoleCache[$key];
    }

    private function resolveNiveauId(?string $nom): ?int
    {
        if (empty($nom)) {
            return null;
        }
        $key = mb_strtolower(trim($nom));
        if (! array_key_exists($key, $this->niveauCache)) {
            $this->niveauCache[$key] = Niveau::whereRaw('LOWER(TRIM(nom)) = ?', [$key])
                ->orWhereRaw('LOWER(TRIM(code)) = ?', [$key])
                ->value('id');
        }

        return $this->niveauCache[$key];
    }

    private function normalizeKey(string $key): string
    {
        $k = preg_replace('/[^\w]/u', '_', $key);
        $k = preg_replace('/_+/', '_', $k);

        return mb_strtolower(trim($k, '_'));
    }

    private function get(array $data, string $field): mixed
    {
        return $data[$field] ?? $data[$field.'_'] ?? null;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        $string = trim((string) $value);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $string)) {
            return $string;
        }

        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $string, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return null;
    }

    private function parseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        $normalized = mb_strtolower(trim((string) $value));

        if (in_array($normalized, ['1', 'oui', 'yes', 'true', 'vrai', 'o', 'y'], true)) {
            return true;
        }

        return false;
    }

    private function isTemplateMetaRow(array $data): bool
    {
        $matricule = mb_strtolower(trim((string) ($this->get($data, 'matricule') ?? '')));
        $nom = mb_strtoupper(trim((string) ($this->get($data, 'nom') ?? '')));
        $prenom = mb_strtolower(trim((string) ($this->get($data, 'prenom') ?? '')));
        $sexe = mb_strtolower(trim((string) ($this->get($data, 'sexe') ?? '')));

        if (str_contains($matricule, 'obligatoire') || str_contains($matricule, 'optionnel')) {
            return true;
        }

        if (str_contains($matricule, 'unique, libre') || str_contains($matricule, 'vide = généré auto')) {
            return true;
        }

        if ($nom === 'NOM FAMILLE' || $sexe === 'm ou f') {
            return true;
        }

        // Ligne d'exemple du modèle (ligne 5) : ignorer seulement si inchangée
        if ($matricule === 'el-2024-001' && $nom === 'NDAYISHIMIYE' && $prenom === 'jean pierre') {
            return true;
        }

        return false;
    }
}