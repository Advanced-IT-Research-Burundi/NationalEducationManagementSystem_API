<?php

namespace App\Imports\RH;

use App\Models\Poste;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PostesImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected int $created = 0;
    protected int $updated = 0;
    protected int $skipped = 0;
    protected array $errors = [];
    protected array $serviceCache = [];

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

    private function resolveServiceId(mixed $value): ?int
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

        if (! array_key_exists($key, $this->serviceCache)) {
            $this->serviceCache[$key] = Service::query()
                ->whereRaw('LOWER(TRIM(code)) = ?', [$key])
                ->orWhereRaw('LOWER(TRIM(nom)) = ?', [$key])
                ->value('id');
        }

        return $this->serviceCache[$key] ? (int) $this->serviceCache[$key] : null;
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
                    'code' => $code ?: 'POSTE-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'nom' => $nom,
                    'service_id' => $this->resolveServiceId($this->get($data, 'service_id') ?? $this->get($data, 'service')),
                    'description' => $this->text($this->get($data, 'description')),
                    'niveau_hierarchique' => (int) ($this->get($data, 'niveau_hierarchique') ?: 1),
                    'salaire_min' => $this->get($data, 'salaire_min'),
                    'salaire_max' => $this->get($data, 'salaire_max'),
                    'statut' => strtoupper((string) ($this->text($this->get($data, 'statut')) ?? 'ACTIF')),
                ];

                $existing = null;
                if (! empty($payload['code'])) {
                    $existing = Poste::query()->where('code', $payload['code'])->first();
                }
                if (! $existing) {
                    $existing = Poste::query()->whereRaw('LOWER(TRIM(nom)) = ?', [mb_strtolower($payload['nom'])])->first();
                }

                if ($existing) {
                    $existing->update($payload);
                    $this->updated++;
                } else {
                    Poste::create($payload);
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
