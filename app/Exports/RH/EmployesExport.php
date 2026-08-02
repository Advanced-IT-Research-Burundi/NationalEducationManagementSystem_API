<?php

namespace App\Exports\RH;

use App\Models\Employe;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(protected Collection $employes)
    {
    }

    private function relatedName(Employe $employe, string $relation): ?string
    {
        $related = $employe->getRelation($relation);

        if (! $related) {
            return null;
        }

        if (is_string($related)) {
            return $related;
        }

        return $related->name ?? $related->nom ?? $related->label ?? null;
    }

    public function collection()
    {
        return $this->employes->map(function (Employe $employe) {
            return [
                $employe->matricule,
                $employe->nom,
                $employe->prenom,
                $employe->sexe,
                optional($employe->date_naissance)->format('Y-m-d'),
                $employe->telephone,
                $employe->email,
                $employe->departement_id,
                $employe->departement?->nom,
                $employe->poste_id,
                $employe->poste?->nom,
                $employe->service_id,
                $employe->service?->nom,
                $employe->statut,
                optional($employe->date_embauche)->format('Y-m-d'),
                $employe->temps_travail,
                $employe->type_contrat,
                $employe->salaire_base,
                $employe->pays_id,
                $this->relatedName($employe, 'pays'),
                $employe->province_id,
                $this->relatedName($employe, 'province'),
                $employe->commune_id,
                $this->relatedName($employe, 'commune'),
                $employe->zone_id,
                $this->relatedName($employe, 'zone'),
                $employe->colline_id,
                $this->relatedName($employe, 'colline'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Prenom',
            'Sexe',
            'Date naissance',
            'Telephone',
            'Email',
            'Departement ID',
            'Departement',
            'Poste ID',
            'Poste',
            'Service ID',
            'Service',
            'Statut',
            'Date embauche',
            'Temps travail',
            'Type contrat',
            'Salaire base',
            'Pays ID',
            'Pays',
            'Province ID',
            'Province',
            'Commune ID',
            'Commune',
            'Zone ID',
            'Zone',
            'Colline ID',
            'Colline',
        ];
    }
}
