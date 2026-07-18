<?php

namespace App\Exports\RH;

use App\Models\PersonnelAdministratif;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PersonnelAdministratifExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(protected Collection $personnels)
    {
    }

    public function collection()
    {
        return $this->personnels->map(function (PersonnelAdministratif $personnel) {
            return [
                $personnel->matricule,
                $personnel->nom,
                $personnel->prenom,
                $personnel->service?->nom,
                $personnel->fonction?->nom,
                $personnel->type_personnel,
                $personnel->statut,
                $personnel->telephone,
                $personnel->email,
                optional($personnel->date_recrutement)->format('Y-m-d'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Prénom',
            'Service',
            'Fonction',
            'Type personnel',
            'Statut',
            'Téléphone',
            'Email',
            'Date recrutement',
        ];
    }
}
