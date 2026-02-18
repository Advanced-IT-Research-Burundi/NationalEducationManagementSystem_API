<?php

namespace App\Exports;

use App\Models\Eleve;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ElevesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $query = Eleve::with(['school']);

        if (isset($this->filters['school_id'])) {
            $query->where('school_id', $this->filters['school_id']);
        }

        if (isset($this->filters['statut'])) {
            $query->where('statut', $this->filters['statut']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Matricule',
            'Nom',
            'Prénom',
            'Date de naissance',
            'Sexe',
            'École',
            'Statut',
            'Date création',
        ];
    }

    public function map($eleve): array
    {
        return [
            $eleve->id,
            $eleve->matricule,
            $eleve->nom,
            $eleve->prenom,
            $eleve->date_naissance?->format('Y-m-d'),
            $eleve->sexe,
            $eleve->school?->name ?? '',
            $eleve->statut,
            $eleve->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
