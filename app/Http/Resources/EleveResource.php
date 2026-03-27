<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EleveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'matricule' => $this->matricule,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'nom_complet' => $this->nom_complet,
            'sexe' => $this->sexe,
            'date_naissance' => $this->date_naissance,
            'lieu_naissance' => $this->lieu_naissance,
            'nationalite' => $this->nationalite,
            'adresse' => $this->adresse,

            'province_origine_id' => $this->province_origine_id,
            'commune_origine_id' => $this->commune_origine_id,
            'zone_origine_id' => $this->zone_origine_id,
            'colline_origine_id' => $this->colline_origine_id,
            'niveau_id' => $this->niveau_id,

            'province_origine' => $this->whenLoaded('provinceOrigine', fn () => [
                'id' => $this->provinceOrigine->id,
                'name' => $this->provinceOrigine->name,
            ]),
            'commune_origine' => $this->whenLoaded('communeOrigine', fn () => [
                'id' => $this->communeOrigine->id,
                'name' => $this->communeOrigine->name,
            ]),
            'zone_origine' => $this->whenLoaded('zoneOrigine', fn () => [
                'id' => $this->zoneOrigine->id,
                'name' => $this->zoneOrigine->name,
            ]),
            'colline_origine' => $this->whenLoaded('collineOrigine', fn () => [
                'id' => $this->collineOrigine->id,
                'name' => $this->collineOrigine->name,
            ]),
            'niveau' => $this->whenLoaded('niveau', fn () => [
                'id' => $this->niveau->id,
                'nom' => $this->niveau->nom,
                'code' => $this->niveau->code,
            ]),

            'nom_pere' => $this->nom_pere,
            'nom_mere' => $this->nom_mere,
            'nom_tuteur' => $this->nom_tuteur,
            'contact_tuteur' => $this->contact_tuteur,
            'est_orphelin' => $this->est_orphelin,
            'a_handicap' => $this->a_handicap,
            'type_handicap' => $this->type_handicap,

            'school_id' => $this->school_id,
            'statut_global' => $this->statut_global,
            'school' => $this->whenLoaded('ecole', fn () => [
                'id' => $this->ecole->id,
                'name' => $this->ecole->name,
            ]),

            'classes' => $this->whenLoaded('classes', function () {
                return $this->classes->map(fn ($classe) => [
                    'id' => $classe->id,
                    'nom' => $classe->nom,
                ]);
            }),
            'inscriptions' => $this->whenLoaded('inscriptions'),

            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),

            'photo_url' => $this->photo_path
                ? asset('storage/'.$this->photo_path)
                : null,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
