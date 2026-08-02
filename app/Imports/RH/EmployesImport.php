<?php

namespace App\Imports\RH;

use App\Models\Employe;
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

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            try {
                $payload = [
                    'matricule' => $row['matricule'] ?? null,
                    'nom' => $row['nom'] ?? null,
                    'prenom' => $row['prenom'] ?? null,
                    'email' => $row['email'] ?? null,
                    'telephone' => $row['telephone'] ?? null,
                    'statut' => $row['statut'] ?? Employe::STATUT_ACTIF,
                    'departement_id' => $row['departement_id'] ?? null,
                    'poste_id' => $row['poste_id'] ?? null,
                    'service_id' => $row['service_id'] ?? null,
                    'salaire_base' => $row['salaire_base'] ?? null,
                    'date_embauche' => $row['date_embauche'] ?? null,
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
