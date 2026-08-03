<?php

namespace App\Exports\RH;

use App\Models\Departement;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DepartementsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(protected Collection $departements)
    {
    }

    private function relatedLabel(Departement $departement, string $relation): ?string
    {
        $related = $departement->getRelation($relation);

        if (! $related) {
            return null;
        }

        if (is_string($related)) {
            return $related;
        }

        return $related->name ?? $related->nom ?? $related->email ?? null;
    }

    public function collection()
    {
        return $this->departements->map(function (Departement $departement) {
            return [
                $departement->code,
                $departement->nom,
                $departement->description,
                $departement->departement_parent_id,
                $this->relatedLabel($departement, 'parent'),
                $departement->responsable_id,
                $this->relatedLabel($departement, 'responsable'),
                $departement->statut,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Nom',
            'Description',
            'Departement parent ID',
            'Departement parent',
            'Responsable ID',
            'Responsable',
            'Statut',
        ];
    }
}
