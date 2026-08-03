<?php

namespace App\Imports\RH;

use App\Models\Employe;
use App\Models\Formation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FormationsImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected int $created = 0;
    protected int $updated = 0;
    protected int $skipped = 0;
    protected array $errors = [];
    protected array $employeCache = [];
    protected array $formationCache = [];

    private function normalizeKey(string $key): string
    {
        $key = preg_replace('/[^\w]/u', '_', $key);
        $key = preg_replace('/_+/', '_', $key);

        return mb_strtolower(trim($key, '_'));
    }

    private function normalizeRow(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $out[$this->normalizeKey((string) $key)] = $value;
        }

        return $out;
    }

    private function get(array $data, string $field): mixed
    {
        return $data[$field] ?? $data[$field . '_'] ?? null;
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveEmployeId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $key = mb_strtolower(trim((string) $value));
        if ($key === '') {
            return null;
        }

        if (! array_key_exists($key, $this->employeCache)) {
            $this->employeCache[$key] = Employe::query()
                ->whereRaw('LOWER(TRIM(matricule)) = ?', [$key])
                ->orWhereRaw('LOWER(TRIM(email)) = ?', [$key])
                ->orWhereRaw('LOWER(TRIM(CONCAT(nom, " ", prenom))) = ?', [$key])
                ->orWhereRaw('LOWER(TRIM(CONCAT(prenom, " ", nom))) = ?', [$key])
                ->value('id');
        }

        return $this->employeCache[$key] ? (int) $this->employeCache[$key] : null;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            try {
                $data = $this->normalizeRow($row->toArray());
                $intitule = $this->text($this->get($data, 'intitule'));

                if (! $intitule) {
                    $this->skipped++;
                    continue;
                }

                $payload = [
                    'employe_id' => $this->resolveEmployeId($this->get($data, 'employe_id') ?? $this->get($data, 'employe') ?? $this->get($data, 'matricule')),
                    'intitule' => $intitule,
                    'organisme' => $this->text($this->get($data, 'organisme')),
                    'type' => strtoupper((string) ($this->text($this->get($data, 'type')) ?? 'FORMATION')),
                    'domaine' => $this->text($this->get($data, 'domaine')),
                    'date_debut' => $this->date($this->get($data, 'date_debut')),
                    'date_fin' => $this->date($this->get($data, 'date_fin')),
                    'duree' => $this->text($this->get($data, 'duree')),
                    'lieu' => $this->text($this->get($data, 'lieu')),
                    'cout' => $this->get($data, 'cout'),
                    'description' => $this->text($this->get($data, 'description')),
                    'resultat' => $this->text($this->get($data, 'resultat')),
                    'est_certifie' => in_array(strtolower((string) $this->get($data, 'est_certifie')), ['1', 'true', 'yes', 'oui'], true),
                    'numero_certificat' => $this->text($this->get($data, 'numero_certificat')),
                    'date_obtention' => $this->date($this->get($data, 'date_obtention')),
                    'date_expiration' => $this->date($this->get($data, 'date_expiration')),
                    'piece_jointe' => $this->text($this->get($data, 'piece_jointe')),
                    'presence' => $this->text($this->get($data, 'presence')),
                    'taux_presence' => $this->get($data, 'taux_presence'),
                    'commentaire' => $this->text($this->get($data, 'commentaire')),
                ];

                $existing = null;
                if (! empty($payload['numero_certificat'])) {
                    $existing = Formation::query()->where('numero_certificat', $payload['numero_certificat'])->first();
                }
                if (! $existing && $payload['employe_id']) {
                    $existing = Formation::query()
                        ->where('employe_id', $payload['employe_id'])
                        ->where('intitule', $payload['intitule'])
                        ->whereDate('date_debut', $payload['date_debut'])
                        ->first();
                }

                if ($existing) {
                    $existing->update($payload);
                    $this->updated++;
                } else {
                    Formation::create(array_merge($payload, ['created_by' => Auth::id()]));
                    $this->created++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = ['row' => $index + 2, 'message' => $e->getMessage()];
            }
        }
    }

    public function getCreatedCount(): int { return $this->created; }
    public function getUpdatedCount(): int { return $this->updated; }
    public function getSkippedCount(): int { return $this->skipped; }
    public function getErrors(): array { return $this->errors; }
}
