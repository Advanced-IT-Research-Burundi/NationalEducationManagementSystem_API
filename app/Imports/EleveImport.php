<?php

namespace App\Imports;

use App\Models\Colline;
use App\Models\School;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Validators\ValidationException;
use Maatwebsite\Excel\Validators\Failure;

class EleveImport implements ToCollection, WithHeadingRow, WithStartRow, SkipsEmptyRows, WithChunkReading
{
    /**
     * Ligne 3 = headers (matricule, nom, prenom...)
     */
    public function headingRow(): int
    {
        return 3;
    }

    /**
     * Ligne 7 = première ligne de vraies données.
     * Lignes 4-6 (type/exemple/notes) sont ignorées.
     */
    public function startRow(): int
    {
        return 7;
    }

    private array $collineCache = [];
    private array $ecoleCache   = [];

    // ── FK resolution ────────────────────────────────────────────────────────

    private function resolveCollineId(?string $nom): ?int
    {
        if (empty($nom)) return null;
        $key = mb_strtolower(trim($nom));
        if (! array_key_exists($key, $this->collineCache)) {
            $this->collineCache[$key] = Colline::whereRaw('LOWER(TRIM(name)) = ?', [$key])->value('id');
        }
        return $this->collineCache[$key];
    }

    private function resolveSchoolId(?string $nom): ?int
    {
        if (empty($nom)) return null;
        $key = mb_strtolower(trim($nom));
        if (! array_key_exists($key, $this->ecoleCache)) {
            $this->ecoleCache[$key] = School::whereRaw('LOWER(TRIM(name)) = ?', [$key])->value('id');
        }
        return $this->ecoleCache[$key];
    }

    // ── Key normalization ────────────────────────────────────────────────────

    private function normalizeKey(string $key): string
    {
        $k = preg_replace('/[^\w]/u', '_', $key);
        $k = preg_replace('/_+/', '_', $k);
        return mb_strtolower(trim($k, '_'));
    }

    private function normalizeRow(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $out[$this->normalizeKey((string) $k)] = $v;
        }
        return $out;
    }

    private function get(array $data, string $field): mixed
    {
        return $data[$field] ?? $data[$field . '_'] ?? null;
    }

    // ── Date normalization ───────────────────────────────────────────────────

    private function normalizeDate(mixed $value): ?string
    {
        if (empty($value)) return null;
        if ($value instanceof \DateTime) return $value->format('Y-m-d');
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {}
        }
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', (string) $value, $m)) return $m[1];
        return null;
    }

    // ── Main collection ──────────────────────────────────────────────────────

    public function collection(Collection $rows): void
    {
        $failures = [];
        $now      = now()->format('Y-m-d H:i:s');
        $userId   = Auth::id();

        foreach ($rows as $index => $row) {
            $rawData  = $row->toArray();
            // startRow=7, index 0-based → ligne Excel réelle
            $excelRow = $index + 7;

            $data = $this->normalizeRow($rawData);

            // Ignorer lignes vraiment vides
            $nonEmpty = array_filter(array_values($data), fn($v) => ! is_null($v) && $v !== '');
            if (empty($nonEmpty)) continue;

            $flat = [
                'matricule'          => trim((string) ($this->get($data, 'matricule') ?? '')),
                'nom'                => trim((string) ($this->get($data, 'nom') ?? '')),
                'prenom'             => trim((string) ($this->get($data, 'prenom') ?? '')),
                'sexe'               => strtoupper(trim((string) ($this->get($data, 'sexe') ?? ''))),
                'date_naissance'     => $this->normalizeDate($this->get($data, 'date_naissance')),
                'lieu_naissance'     => trim((string) ($this->get($data, 'lieu_naissance') ?? '')),
                'nationalite'        => trim((string) ($this->get($data, 'nationalite') ?? '')),
                'colline_origine'    => $this->get($data, 'colline_origine'),
                'adresse'            => $this->get($data, 'adresse'),
                'nom_pere'           => $this->get($data, 'nom_pere'),
                'nom_mere'           => $this->get($data, 'nom_mere'),
                'nom_tuteur'         => $this->get($data, 'nom_tuteur'),
                'contact_tuteur'     => $this->get($data, 'contact_tuteur'),
                'est_orphelin'       => $this->get($data, 'est_orphelin'),
                'a_handicap'         => $this->get($data, 'a_handicap'),
                'type_handicap'      => $this->get($data, 'type_handicap'),
                'school_destination' => $this->get($data, 'school_destination'),
            ];

            $validator = Validator::make($flat, [
                'matricule'      => ['required', 'string', 'max:20'],
                'nom'            => ['required', 'string', 'max:100'],
                'prenom'         => ['required', 'string', 'max:100'],
                'sexe'           => ['required', 'in:M,F'],
                'date_naissance' => ['required', 'date_format:Y-m-d'],
                'lieu_naissance' => ['required', 'string', 'max:150'],
            ], [
                'matricule.required'         => "Le matricule est obligatoire",
                'nom.required'               => "Le nom est obligatoire",
                'prenom.required'            => "Le prénom est obligatoire",
                'sexe.required'              => "Le sexe est obligatoire (M ou F)",
                'sexe.in'                    => "Le sexe doit être M ou F",
                'date_naissance.required'    => "La date de naissance est obligatoire",
                'date_naissance.date_format' => "La date doit être au format YYYY-MM-DD",
                'lieu_naissance.required'    => "Le lieu de naissance est obligatoire",
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $failures[] = new Failure($excelRow, 'validation', [$error], $rawData);
                }
                continue;
            }

            $aHandicap = (bool) ($flat['a_handicap'] ?? false);

            $payload = [
                'nom'                => $flat['nom'],
                'prenom'             => $flat['prenom'],
                'sexe'               => $flat['sexe'],
                'date_naissance'     => $flat['date_naissance'],
                'lieu_naissance'     => $flat['lieu_naissance'],
                'nationalite'        => $flat['nationalite'] ?: 'Burundaise',
                'colline_origine_id' => $this->resolveCollineId($flat['colline_origine']),
                'adresse'            => $flat['adresse'],
                'nom_pere'           => $flat['nom_pere'],
                'nom_mere'           => $flat['nom_mere'],
                'nom_tuteur'         => $flat['nom_tuteur'],
                'contact_tuteur'     => $flat['contact_tuteur'],
                'est_orphelin'       => (int) (bool) ($flat['est_orphelin'] ?? false),
                'a_handicap'         => (int) $aHandicap,
                'type_handicap'      => $aHandicap ? $flat['type_handicap'] : null,
                'school_id'          => $this->resolveSchoolId($flat['school_destination']),
                'statut_global'      => 'actif',
                'created_by'         => $userId,
                'updated_at'         => $now,
            ];

            $existing = DB::table('eleves')->where('matricule', $flat['matricule'])->first();

            if ($existing) {
                DB::table('eleves')
                    ->where('matricule', $flat['matricule'])
                    ->update(array_merge($payload, ['deleted_at' => null]));
            } else {
                DB::table('eleves')->insert(array_merge($payload, [
                    'matricule'  => $flat['matricule'],
                    'created_at' => $now,
                ]));
            }
        }

        if (! empty($failures)) {
            throw new ValidationException(
                \Illuminate\Validation\ValidationException::withMessages(['import' => ['Erreurs de validation']]),
                $failures
            );
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
