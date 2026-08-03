<?php

namespace App\Imports\RH;

use App\Models\Departement;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ServicesImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected int $created = 0;
    protected int $updated = 0;
    protected int $skipped = 0;
    protected array $errors = [];
    protected array $departementCache = [];
    protected array $userCache = [];

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

    private function resolveDepartementId(mixed $value): ?int
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

        if (! array_key_exists($key, $this->departementCache)) {
            $this->departementCache[$key] = Departement::query()
                ->whereRaw('LOWER(TRIM(code)) = ?', [$key])
                ->orWhereRaw('LOWER(TRIM(nom)) = ?', [$key])
                ->value('id');
        }

        return $this->departementCache[$key] ? (int) $this->departementCache[$key] : null;
    }

    private function resolveUserId(mixed $value): ?int
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

        if (! array_key_exists($key, $this->userCache)) {
            $this->userCache[$key] = User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$key])
                ->orWhereRaw('LOWER(TRIM(name)) = ?', [$key])
                ->value('id');
        }

        return $this->userCache[$key] ? (int) $this->userCache[$key] : null;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            try {
                $data = $this->normalizeRow($row->toArray());
                $nom = $this->text($this->get($data, 'nom'));
                $code = $this->text($this->get($data, 'code'));

                if (! $nom) {
                    $this->skipped++;
                    continue;
                }

                $payload = [
                    'code' => $code ?: 'SER-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'nom' => $nom,
                    'departement_id' => $this->resolveDepartementId($this->get($data, 'departement_id') ?? $this->get($data, 'departement')),
                    'responsable_id' => $this->resolveUserId($this->get($data, 'responsable_id') ?? $this->get($data, 'responsable')),
                    'description' => $this->text($this->get($data, 'description')),
                    'telephone' => $this->text($this->get($data, 'telephone')),
                    'email' => $this->text($this->get($data, 'email')),
                    'bureau' => $this->text($this->get($data, 'bureau')),
                    'statut' => strtoupper((string) ($this->text($this->get($data, 'statut')) ?? 'ACTIF')),
                    'is_active' => ! in_array(strtolower((string) $this->get($data, 'actif')), ['0', 'false', 'non'], true),
                ];

                $existing = null;
                if (! empty($payload['code'])) {
                    $existing = Service::query()->where('code', $payload['code'])->first();
                }
                if (! $existing) {
                    $existing = Service::query()->whereRaw('LOWER(TRIM(nom)) = ?', [mb_strtolower($payload['nom'])])->first();
                }

                if ($existing) {
                    $existing->update($payload);
                    $this->updated++;
                } else {
                    Service::create($payload);
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
