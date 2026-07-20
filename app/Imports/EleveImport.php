<?php

namespace App\Imports;

use App\Models\Colline;
use App\Models\Commune;
use App\Models\Niveau;
use App\Models\Province;
use App\Models\School;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Validators\ValidationException;

class EleveImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private const FIRST_DATA_ROW = 7;
    private const MAX_CONSECUTIVE_ROWS_WITHOUT_MATRICULE = 4;

    public function headingRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows): void
    {
        $failures = [];
        $preparedRows = [];
        $seenMatricules = [];
        $rawRows = [];
        $rowsWithoutMatriculeInARow = 0;

        foreach ($rows as $index => $row) {
            $rawData = $row->toArray();
            $excelRow = $index + 4;
            $data = $this->normalizeRow($rawData);

            if ($excelRow < self::FIRST_DATA_ROW || $this->isTemplateMetaRow($data)) {
                continue;
            }

            $flat = $this->extractFlatRow($data);

            if ($flat['matricule'] === '') {
                $rowsWithoutMatriculeInARow++;

                if ($rowsWithoutMatriculeInARow >= self::MAX_CONSECUTIVE_ROWS_WITHOUT_MATRICULE) {
                    break;
                }

                continue;
            }

            $rowsWithoutMatriculeInARow = 0;
            $rawRows[$excelRow] = $rawData;
            $rowFailureCount = count($failures);

            $key = $this->lookupKey($flat['matricule']);
            if (isset($seenMatricules[$key])) {
                $this->addFailure(
                    $failures,
                    $excelRow,
                    'matricule',
                    "Le matricule '{$flat['matricule']}' est duplique dans ce fichier (deja present a la ligne {$seenMatricules[$key]}).",
                    $rawData
                );
            } else {
                $seenMatricules[$key] = $excelRow;
            }

            $validator = Validator::make($flat, [
                'matricule' => ['required', 'string', 'max:20'],
                'nom' => ['required', 'string', 'max:100'],
                'prenom' => ['required', 'string', 'max:100'],
                'sexe' => ['required', 'in:M,F'],
                'date_naissance' => ['required', 'date_format:Y-m-d', 'before:today'],
                'lieu_naissance' => ['required', 'string', 'max:150'],
                'nationalite' => ['nullable', 'string', 'max:50'],
                'adresse' => ['nullable', 'string', 'max:500'],
                'nom_pere' => ['nullable', 'string', 'max:200'],
                'nom_mere' => ['nullable', 'string', 'max:200'],
                'nom_tuteur' => ['nullable', 'string', 'max:200'],
                'contact_tuteur' => ['nullable', 'string', 'max:255'],
                'type_handicap' => ['nullable', 'string', 'max:100'],
            ], [
                'matricule.required' => 'Le matricule est obligatoire.',
                'matricule.max' => 'Le matricule ne peut pas depasser 20 caracteres.',
                'nom.required' => 'Le nom est obligatoire.',
                'prenom.required' => 'Le prenom est obligatoire.',
                'sexe.required' => 'Le sexe est obligatoire.',
                'sexe.in' => 'Le sexe doit etre M ou F.',
                'date_naissance.required' => 'La date de naissance est obligatoire.',
                'date_naissance.date_format' => 'La date de naissance doit etre au format YYYY-MM-DD.',
                'date_naissance.before' => 'La date de naissance doit etre anterieure a aujourd hui.',
                'lieu_naissance.required' => 'Le lieu de naissance est obligatoire.',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->addFailure($failures, $excelRow, 'validation', $error, $rawData);
                }
                continue;
            }

            $references = $this->resolveReferences($flat, $excelRow, $failures, $rawData);
            $estOrphelin = $this->normalizeBoolean($flat['est_orphelin'], 'est_orphelin', $excelRow, $failures, $rawData);
            $aHandicap = $this->normalizeBoolean($flat['a_handicap'], 'a_handicap', $excelRow, $failures, $rawData);

            if ($aHandicap && $flat['type_handicap'] === '') {
                $this->addFailure(
                    $failures,
                    $excelRow,
                    'type_handicap',
                    'Le type de handicap est obligatoire quand a_handicap vaut 1/Oui.',
                    $rawData
                );
            }

            if (count($failures) > $rowFailureCount) {
                continue;
            }

            $preparedRows[] = [
                'matricule' => $flat['matricule'],
                'nom' => $flat['nom'],
                'prenom' => $flat['prenom'],
                'sexe' => $flat['sexe'],
                'date_naissance' => $flat['date_naissance'],
                'lieu_naissance' => $flat['lieu_naissance'],
                'nationalite' => $flat['nationalite'] ?: 'Burundaise',
                'province_origine_id' => $references['province_id'],
                'commune_origine_id' => $references['commune_id'],
                'zone_origine_id' => $references['zone_id'],
                'colline_origine_id' => $references['colline_id'],
                'niveau_id' => $references['niveau_id'],
                'school_id' => $references['school_id'],
                'adresse' => $flat['adresse'] ?: null,
                'nom_pere' => $flat['nom_pere'] ?: null,
                'nom_mere' => $flat['nom_mere'] ?: null,
                'nom_tuteur' => $flat['nom_tuteur'] ?: null,
                'contact_tuteur' => $flat['contact_tuteur'] ?: null,
                'est_orphelin' => (int) $estOrphelin,
                'a_handicap' => (int) $aHandicap,
                'type_handicap' => $aHandicap ? ($flat['type_handicap'] ?: null) : null,
                'statut_global' => 'actif',
                'created_by' => Auth::id(),
            ];
        }

        if (! empty($failures)) {
            $this->throwValidationException($failures);
        }

        $now = now()->format('Y-m-d H:i:s');

        DB::transaction(function () use ($preparedRows, $now) {
            foreach ($preparedRows as $payload) {
                $matricule = $payload['matricule'];
                unset($payload['matricule']);

                $existing = DB::table('eleves')->where('matricule', $matricule)->first();

                if ($existing) {
                    DB::table('eleves')
                        ->where('matricule', $matricule)
                        ->update(array_merge($payload, [
                            'deleted_at' => null,
                            'updated_at' => $now,
                        ]));

                    continue;
                }

                DB::table('eleves')->insert(array_merge($payload, [
                    'matricule' => $matricule,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        });
    }

    private function extractFlatRow(array $data): array
    {
        return [
            'matricule' => $this->stringValue($this->getAny($data, 'matricule')),
            'nom' => $this->stringValue($this->getAny($data, 'nom')),
            'prenom' => $this->stringValue($this->getAny($data, 'prenom')),
            'sexe' => strtoupper($this->stringValue($this->getAny($data, 'sexe'))),
            'date_naissance' => $this->normalizeDate($this->getAny($data, 'date_naissance')),
            'lieu_naissance' => $this->stringValue($this->getAny($data, 'lieu_naissance')),
            'nationalite' => $this->stringValue($this->getAny($data, 'nationalite')),
            'school_destination' => $this->stringValue($this->getAny($data, 'school_destination', 'ecole_destination', 'ecole')),
            'niveau_scolaire' => $this->stringValue($this->getAny($data, 'niveau_scolaire', 'niveau')),
            'province_origine' => $this->stringValue($this->getAny($data, 'province_origine', 'province')),
            'commune_origine' => $this->stringValue($this->getAny($data, 'commune_origine', 'commune')),
            'zone_origine' => $this->stringValue($this->getAny($data, 'zone_origine', 'zone')),
            'colline_origine' => $this->stringValue($this->getAny($data, 'colline_origine', 'colline')),
            'adresse' => $this->stringValue($this->getAny($data, 'adresse')),
            'nom_pere' => $this->stringValue($this->getAny($data, 'nom_pere')),
            'nom_mere' => $this->stringValue($this->getAny($data, 'nom_mere')),
            'nom_tuteur' => $this->stringValue($this->getAny($data, 'nom_tuteur')),
            'contact_tuteur' => $this->stringValue($this->getAny($data, 'contact_tuteur')),
            'est_orphelin' => $this->getAny($data, 'est_orphelin'),
            'a_handicap' => $this->getAny($data, 'a_handicap'),
            'type_handicap' => $this->stringValue($this->getAny($data, 'type_handicap')),
        ];
    }

    private function resolveReferences(array $flat, int $excelRow, array &$failures, array $rawData): array
    {
        $province = $this->resolveReference(
            Province::class,
            'name',
            $flat['province_origine'],
            'province_origine',
            'Province',
            $excelRow,
            $failures,
            $rawData
        );

        $commune = $this->resolveReference(
            Commune::class,
            'name',
            $flat['commune_origine'],
            'commune_origine',
            'Commune',
            $excelRow,
            $failures,
            $rawData,
            function (Builder $query) use ($province) {
                if ($province) {
                    $query->where('province_id', $province->id);
                }
            },
            $province ? '' : 'Indiquez aussi province_origine si plusieurs communes portent ce nom.'
        );

        $zone = $this->resolveReference(
            Zone::class,
            'name',
            $flat['zone_origine'],
            'zone_origine',
            'Zone',
            $excelRow,
            $failures,
            $rawData,
            function (Builder $query) use ($province, $commune) {
                if ($commune) {
                    $query->where('commune_id', $commune->id);
                } elseif ($province) {
                    $query->where('province_id', $province->id);
                }
            },
            $commune ? '' : 'Indiquez aussi commune_origine si plusieurs zones portent ce nom.'
        );

        $colline = $this->resolveCollineReference($flat, $province, $commune, $zone, $excelRow, $failures, $rawData);

        if ($colline) {
            $zone = Zone::find($colline->zone_id) ?: $zone;
            $commune = Commune::find($colline->commune_id ?: $zone?->commune_id) ?: $commune;
            $province = Province::find($colline->province_id ?: $zone?->province_id ?: $commune?->province_id) ?: $province;
        } elseif ($zone) {
            $commune = $commune ?: Commune::find($zone->commune_id);
            $province = $province ?: Province::find($zone->province_id ?: $commune?->province_id);
        } elseif ($commune) {
            $province = $province ?: Province::find($commune->province_id);
        }

        $school = null;
        if ($flat['school_destination'] === '' && Auth::user()?->school_id) {
            $school = School::withoutGlobalScopes()->find(Auth::user()->school_id);
        } elseif ($flat['school_destination'] !== '') {
            $school = $this->resolveReference(
                School::class,
                'name',
                $flat['school_destination'],
                'school_destination',
                'Ecole destination',
                $excelRow,
                $failures,
                $rawData,
                fn (Builder $query) => $query->withoutGlobalScopes(),
                'Utilisez le nom exact dans la colonne school_destination.',
                ['code_ecole']
            );
        }

        if ($flat['school_destination'] === '' && ! $school) {
            $this->addFailure($failures, $excelRow, 'school_destination', 'L ecole destination est obligatoire.', $rawData);
        }

        $niveau = null;
        if ($flat['niveau_scolaire'] !== '') {
            $niveau = $this->resolveReference(
                Niveau::class,
                'nom',
                $flat['niveau_scolaire'],
                'niveau_scolaire',
                'Niveau scolaire',
                $excelRow,
                $failures,
                $rawData,
                null,
                'Utilisez le nom ou le code exact du niveau scolaire.',
                ['code']
            );
        }

        if ($flat['niveau_scolaire'] === '') {
            $this->addFailure($failures, $excelRow, 'niveau_scolaire', 'Le niveau scolaire est obligatoire.', $rawData);
        }

        if ($school && $niveau) {
            $attachedNiveaux = DB::table('niveau_school')->where('school_id', $school->id)->count();
            $niveauAllowed = DB::table('niveau_school')
                ->where('school_id', $school->id)
                ->where('niveau_scolaire_id', $niveau->id)
                ->exists();

            if ($attachedNiveaux > 0 && ! $niveauAllowed) {
                $this->addFailure(
                    $failures,
                    $excelRow,
                    'niveau_scolaire',
                    "Le niveau '{$flat['niveau_scolaire']}' n est pas associe a l ecole '{$school->name}'.",
                    $rawData
                );
            }
        }

        return [
            'province_id' => $province?->id,
            'commune_id' => $commune?->id,
            'zone_id' => $zone?->id,
            'colline_id' => $colline?->id,
            'school_id' => $school?->id,
            'niveau_id' => $niveau?->id,
        ];
    }

    private function resolveCollineReference(
        array $flat,
        ?Model $province,
        ?Model $commune,
        ?Model $zone,
        int $excelRow,
        array &$failures,
        array $rawData
    ): ?Colline {
        if ($flat['colline_origine'] === '') {
            return null;
        }

        $scopedQuery = $this->referenceQuery(Colline::class, 'name', $flat['colline_origine']);
        if ($zone) {
            $scopedQuery->where('zone_id', $zone->id);
        } elseif ($commune) {
            $scopedQuery->where('commune_id', $commune->id);
        } elseif ($province) {
            $scopedQuery->where('province_id', $province->id);
        }

        $scopedMatches = $scopedQuery->limit(2)->get();
        if ($scopedMatches->count() === 1) {
            return $scopedMatches->first();
        }

        if ($scopedMatches->count() > 1) {
            $this->addFailure(
                $failures,
                $excelRow,
                'colline_origine',
                "Colline '{$flat['colline_origine']}' est ambigue. Indiquez une zone_origine plus precise.",
                $rawData
            );

            return null;
        }

        $globalMatches = $this->referenceQuery(Colline::class, 'name', $flat['colline_origine'])->limit(2)->get();

        if ($globalMatches->isEmpty()) {
            $this->addFailure(
                $failures,
                $excelRow,
                'colline_origine',
                "Colline '{$flat['colline_origine']}' introuvable.",
                $rawData
            );

            return null;
        }

        if ($globalMatches->count() > 1) {
            $this->addFailure(
                $failures,
                $excelRow,
                'colline_origine',
                "Colline '{$flat['colline_origine']}' est ambigue. Indiquez zone_origine, commune_origine et province_origine exactes.",
                $rawData
            );

            return null;
        }

        return $globalMatches->first();
    }

    private function resolveReference(
        string $modelClass,
        string $column,
        string $value,
        string $field,
        string $label,
        int $excelRow,
        array &$failures,
        array $rawData,
        ?callable $scope = null,
        string $ambiguityHint = '',
        array $alternativeColumns = []
    ): ?Model {
        if ($value === '') {
            return null;
        }

        $query = $this->referenceQuery($modelClass, $column, $value, $alternativeColumns);

        if ($scope) {
            $scope($query);
        }

        $matches = $query->limit(2)->get();

        if ($matches->isEmpty()) {
            $this->addFailure($failures, $excelRow, $field, "{$label} '{$value}' introuvable.", $rawData);
            return null;
        }

        if ($matches->count() > 1) {
            $hint = $ambiguityHint ? " {$ambiguityHint}" : '';
            $this->addFailure($failures, $excelRow, $field, "{$label} '{$value}' est ambigu.{$hint}", $rawData);
            return null;
        }

        return $matches->first();
    }

    private function referenceQuery(string $modelClass, string $column, string $value, array $alternativeColumns = []): Builder
    {
        /** @var Builder $query */
        $query = $modelClass::query();
        $lookupValue = $this->lookupKey($value);

        return $query->where(function (Builder $query) use ($column, $lookupValue, $alternativeColumns) {
            $query->whereRaw("LOWER(TRIM({$column})) = ?", [$lookupValue]);

            foreach ($alternativeColumns as $alternativeColumn) {
                $query->orWhereRaw("LOWER(TRIM({$alternativeColumn})) = ?", [$lookupValue]);
            }
        });
    }

    private function normalizeRow(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $out[$this->normalizeKey((string) $key)] = $value;
        }

        return $out;
    }

    private function normalizeKey(string $key): string
    {
        $key = preg_replace('/[^\w]/u', '_', $key);
        $key = preg_replace('/_+/', '_', $key);

        return mb_strtolower(trim((string) $key, '_'));
    }

    private function getAny(array $data, string ...$fields): mixed
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                return $data[$field];
            }
        }

        return null;
    }

    private function stringValue(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function lookupKey(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private function isTemplateMetaRow(array $data): bool
    {
        $matricule = $this->lookupKey($this->stringValue($this->getAny($data, 'matricule')));
        $sexe = $this->lookupKey($this->stringValue($this->getAny($data, 'sexe')));

        foreach (['obligatoire', 'optionnel', 'exemple', 'unique', 'el-2026-001', 'nom exact'] as $word) {
            if (str_contains($matricule, $word)) {
                return true;
            }
        }

        return $sexe !== '' && ! in_array($sexe, ['m', 'f'], true);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_null($value) || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    private function normalizeBoolean(mixed $value, string $field, int $excelRow, array &$failures, array $rawData): bool
    {
        if (is_null($value) || trim((string) $value) === '') {
            return false;
        }

        $normalized = $this->lookupKey((string) $value);

        if (in_array($normalized, ['1', 'oui', 'o', 'yes', 'true', 'vrai'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'non', 'n', 'no', 'false', 'faux'], true)) {
            return false;
        }

        $this->addFailure($failures, $excelRow, $field, "{$field} doit valoir 0/1 ou Oui/Non.", $rawData);

        return false;
    }

    private function addFailure(array &$failures, int $row, string $attribute, string $message, array $values): void
    {
        $failures[] = new Failure($row, $attribute, [$message], $values);
    }

    private function throwValidationException(array $failures): never
    {
        throw new ValidationException(
            \Illuminate\Validation\ValidationException::withMessages(['import' => ['Erreurs de validation']]),
            $failures
        );
    }
}
