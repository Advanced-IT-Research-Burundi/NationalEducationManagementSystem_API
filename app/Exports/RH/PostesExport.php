<?php

namespace App\Exports\RH;

use App\Models\Poste;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PostesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(protected Collection $postes)
    {
    }

    private function relatedLabel(Poste $poste, string $relation): ?string
    {
        $related = $poste->getRelation($relation);

        if (! $related) {
            return null;
        }

        if (is_string($related)) {
            return $related;
        }

        return $related->name ?? $related->nom ?? null;
    }

    public function collection()
    {
        return $this->postes->map(function (Poste $poste) {
            return [
                $poste->code,
                $poste->nom,
                $poste->service_id,
                $this->relatedLabel($poste, 'service'),
                $poste->description,
                $poste->niveau_hierarchique,
                $poste->salaire_min,
                $poste->salaire_max,
                $poste->statut,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Nom',
            'Service ID',
            'Service',
            'Description',
            'Niveau hierarchique',
            'Salaire min',
            'Salaire max',
            'Statut',
        ];
    }
}
