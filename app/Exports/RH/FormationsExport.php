<?php

namespace App\Exports\RH;

use App\Models\Formation;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FormationsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(protected Collection $formations)
    {
    }

    private function relatedLabel(Formation $formation, string $relation): ?string
    {
        $related = $formation->getRelation($relation);

        if (! $related) {
            return null;
        }

        if (is_string($related)) {
            return $related;
        }

        return $related->nom_complet ?? $related->nom ?? $related->matricule ?? null;
    }

    public function collection()
    {
        return $this->formations->map(function (Formation $formation) {
            return [
                $formation->employe_id,
                $this->relatedLabel($formation, 'employe'),
                $formation->intitule,
                $formation->organisme,
                $formation->type,
                $formation->domaine,
                optional($formation->date_debut)->format('Y-m-d'),
                optional($formation->date_fin)->format('Y-m-d'),
                $formation->duree,
                $formation->lieu,
                $formation->cout,
                $formation->description,
                $formation->resultat,
                $formation->est_certifie ? '1' : '0',
                $formation->numero_certificat,
                optional($formation->date_obtention)->format('Y-m-d'),
                optional($formation->date_expiration)->format('Y-m-d'),
                $formation->presence,
                $formation->taux_presence,
                $formation->commentaire,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employe ID',
            'Employe',
            'Intitule',
            'Organisme',
            'Type',
            'Domaine',
            'Date debut',
            'Date fin',
            'Duree',
            'Lieu',
            'Cout',
            'Description',
            'Resultat',
            'Est certifie',
            'Numero certificat',
            'Date obtention',
            'Date expiration',
            'Presence',
            'Taux presence',
            'Commentaire',
        ];
    }
}
