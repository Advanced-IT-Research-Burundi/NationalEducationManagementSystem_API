<?php

namespace App\Imports\RH;

use App\Models\Colline;
use App\Models\Commune;
use App\Models\Departement;
use App\Models\Employe;
use App\Models\Pays;
use App\Models\Poste;
use App\Models\Province;
use App\Models\Service;
use App\Models\Zone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployesImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected int $created = 0;
    protected int $updated = 0;
    protected int $skipped = 0;
    protected array $errors = [];
    protected array $departementCache = [];
    protected array $posteCache = [];
    protected array $serviceCache = [];
    protected array $paysCache = [];
    protected array $provinceCache = [];
    protected array $communeCache = [];
    protected array $zoneCache = [];
    protected array $collineCache = [];

    private function normalizeKey(string $key): string
    {
        $k = preg_replace('/[^\w]/u', '_', $key);
        $k = preg_replace('/_+/', '_', $k);

        return mb_strtolower(trim($k, '_'));
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

    private function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function normalizeDate(mixed $value): ?string
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

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $text, $matches)) {
            return $matches[1];
        }

        try {
            return \Carbon\Carbon::parse($text)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveByIdOrName(array &$cache, string $modelClass, mixed $value, array $columns): ?int
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

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = $modelClass::query()
                ->where(function ($query) use ($columns, $key) {
                    foreach ($columns as $index => $column) {
                        if ($index === 0) {
                            $query->whereRaw("LOWER(TRIM({$column})) = ?", [$key]);
                        } else {
                            $query->orWhereRaw("LOWER(TRIM({$column})) = ?", [$key]);
                        }
                    }
                })
                ->value('id');
        }

        return $cache[$key] ? (int) $cache[$key] : null;
    }

    private function resolveDepartementId(mixed $value): ?int
    {
        return $this->resolveByIdOrName($this->departementCache, Departement::class, $value, ['nom', 'code']);
    }

    private function resolvePosteId(mixed $value): ?int
    {
        return $this->resolveByIdOrName($this->posteCache, Poste::class, $value, ['nom', 'code']);
    }

    private function resolveServiceId(mixed $value): ?int
    {
        return $this->resolveByIdOrName($this->serviceCache, Service::class, $value, ['nom', 'code']);
    }

    private function resolvePaysId(mixed $value): ?int
    {
        return $this->resolveByIdOrName($this->paysCache, Pays::class, $value, ['name', 'code']);
    }

    private function resolveProvinceId(mixed $value, ?int $paysId = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $key = mb_strtolower(trim((string) $value)) . '|' . ($paysId ?? 0);
        if (! array_key_exists($key, $this->provinceCache)) {
            $query = Province::query()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $value))]);
            if ($paysId) {
                $query->where('pays_id', $paysId);
            }
            $this->provinceCache[$key] = $query->value('id');
        }

        return $this->provinceCache[$key] ? (int) $this->provinceCache[$key] : null;
    }

    private function resolveCommuneId(mixed $value, ?int $provinceId = null, ?int $paysId = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $key = mb_strtolower(trim((string) $value)) . '|' . ($provinceId ?? 0) . '|' . ($paysId ?? 0);
        if (! array_key_exists($key, $this->communeCache)) {
            $query = Commune::query()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $value))]);
            if ($provinceId) {
                $query->where('province_id', $provinceId);
            }
            if ($paysId) {
                $query->where('pays_id', $paysId);
            }
            $this->communeCache[$key] = $query->value('id');
        }

        return $this->communeCache[$key] ? (int) $this->communeCache[$key] : null;
    }

    private function resolveZoneId(mixed $value, ?int $communeId = null, ?int $provinceId = null, ?int $paysId = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $key = mb_strtolower(trim((string) $value)) . '|' . ($communeId ?? 0) . '|' . ($provinceId ?? 0) . '|' . ($paysId ?? 0);
        if (! array_key_exists($key, $this->zoneCache)) {
            $query = Zone::query()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $value))]);
            if ($communeId) {
                $query->where('commune_id', $communeId);
            }
            if ($provinceId) {
                $query->where('province_id', $provinceId);
            }
            if ($paysId) {
                $query->where('pays_id', $paysId);
            }
            $this->zoneCache[$key] = $query->value('id');
        }

        return $this->zoneCache[$key] ? (int) $this->zoneCache[$key] : null;
    }

    private function resolveCollineId(mixed $value, ?int $zoneId = null, ?int $communeId = null, ?int $provinceId = null, ?int $paysId = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $key = mb_strtolower(trim((string) $value)) . '|' . ($zoneId ?? 0) . '|' . ($communeId ?? 0) . '|' . ($provinceId ?? 0) . '|' . ($paysId ?? 0);
        if (! array_key_exists($key, $this->collineCache)) {
            $query = Colline::query()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $value))]);
            if ($zoneId) {
                $query->where('zone_id', $zoneId);
            }
            if ($communeId) {
                $query->where('commune_id', $communeId);
            }
            if ($provinceId) {
                $query->where('province_id', $provinceId);
            }
            if ($paysId) {
                $query->where('pays_id', $paysId);
            }
            $this->collineCache[$key] = $query->value('id');
        }

        return $this->collineCache[$key] ? (int) $this->collineCache[$key] : null;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            try {
                $data = $this->normalizeRow($row->toArray());

                $nom = $this->normalizeText($this->get($data, 'nom'));
                $prenom = $this->normalizeText($this->get($data, 'prenom'));
                $matricule = $this->normalizeText($this->get($data, 'matricule'));
                $email = $this->normalizeText($this->get($data, 'email'));
                $telephone = $this->normalizeText($this->get($data, 'telephone'));
                $sexe = strtoupper((string) ($this->normalizeText($this->get($data, 'sexe')) ?? ''));
                $departementId = $this->resolveDepartementId($this->get($data, 'departement_id') ?? $this->get($data, 'departement'));
                $posteId = $this->resolvePosteId($this->get($data, 'poste_id') ?? $this->get($data, 'poste'));
                $serviceId = $this->resolveServiceId($this->get($data, 'service_id') ?? $this->get($data, 'service'));
                $paysId = $this->resolvePaysId($this->get($data, 'pays_id') ?? $this->get($data, 'pays'));
                $provinceId = $this->resolveProvinceId($this->get($data, 'province_id') ?? $this->get($data, 'province'), $paysId);
                $communeId = $this->resolveCommuneId($this->get($data, 'commune_id') ?? $this->get($data, 'commune'), $provinceId, $paysId);
                $zoneId = $this->resolveZoneId($this->get($data, 'zone_id') ?? $this->get($data, 'zone'), $communeId, $provinceId, $paysId);
                $collineId = $this->resolveCollineId($this->get($data, 'colline_id') ?? $this->get($data, 'colline'), $zoneId, $communeId, $provinceId, $paysId);

                $payload = [
                    'matricule' => $matricule,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'sexe' => in_array($sexe, ['M', 'F'], true) ? $sexe : null,
                    'date_naissance' => $this->normalizeDate($this->get($data, 'date_naissance')),
                    'email' => $email,
                    'telephone' => $telephone,
                    'statut' => strtoupper((string) ($this->normalizeText($this->get($data, 'statut')) ?? Employe::STATUT_ACTIF)),
                    'departement_id' => $departementId,
                    'poste_id' => $posteId,
                    'service_id' => $serviceId,
                    'pays_id' => $paysId,
                    'province_id' => $provinceId,
                    'commune_id' => $communeId,
                    'zone_id' => $zoneId,
                    'colline_id' => $collineId,
                    'salaire_base' => $this->get($data, 'salaire_base'),
                    'date_embauche' => $this->normalizeDate($this->get($data, 'date_embauche')),
                    'temps_travail' => $this->normalizeText($this->get($data, 'temps_travail')),
                    'type_contrat' => $this->normalizeText($this->get($data, 'type_contrat')),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ];

                if (! ($payload['nom'] && $payload['prenom'])) {
                    $this->skipped++;
                    continue;
                }

                $employe = null;
                if (! empty($payload['matricule'])) {
                    $employe = Employe::query()->where('matricule', $payload['matricule'])->first();
                }
                if (! $employe && ! empty($payload['email'])) {
                    $employe = Employe::query()->where('email', $payload['email'])->first();
                }

                if ($employe) {
                    $employe->update($payload);
                    $this->updated++;
                } else {
                    if (empty($payload['matricule'])) {
                        $payload['matricule'] = 'EMP-' . str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT);
                    }
                    Employe::create($payload);
                    $this->created++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = [
                    'row' => $index + 2,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
