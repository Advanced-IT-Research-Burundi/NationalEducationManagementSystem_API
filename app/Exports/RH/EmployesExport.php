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
                $employe->departement?->nom,
                $employe->poste?->nom,
                $employe->service?->nom,
                $employe->statut,
                optional($employe->date_embauche)->format('Y-m-d'),
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
            'Departement',
            'Poste',
            'Service',
            'Statut',
            'Date embauche',
        ];
    }
}
