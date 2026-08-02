<?php

namespace App\Exports\RH;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ServicesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(protected Collection $services)
    {
    }

    private function relatedLabel(Service $service, string $relation): ?string
    {
        $related = $service->getRelation($relation);

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
        return $this->services->map(function (Service $service) {
            return [
                $service->code,
                $service->nom,
                $service->departement_id,
                $this->relatedLabel($service, 'departement'),
                $service->responsable_id,
                $this->relatedLabel($service, 'responsable'),
                $service->description,
                $service->telephone,
                $service->email,
                $service->bureau,
                $service->statut,
                $service->is_active ? '1' : '0',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Nom',
            'Departement ID',
            'Departement',
            'Responsable ID',
            'Responsable',
            'Description',
            'Telephone',
            'Email',
            'Bureau',
            'Statut',
            'Actif',
        ];
    }
}
