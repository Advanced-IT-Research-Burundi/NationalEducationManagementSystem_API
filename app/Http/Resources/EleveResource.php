<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EleveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Basic Info
            'id' => $this->id,
            'matricule' => $this->matricule,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'sexe' => $this->sexe,
            'date_naissance' => $this->date_naissance,
            'lieu_naissance' => $this->lieu_naissance,
            'nationalite' => $this->nationalite,
            'adresse' => $this->adresse,

            // Family Info
            'nom_pere' => $this->nom_pere,
            'nom_mere' => $this->nom_mere,
            'nom_tuteur' => $this->nom_tuteur,
            'contact_tuteur' => $this->contact_tuteur,
            'est_orphelin' => $this->est_orphelin,
            'a_handicap' => $this->a_handicap,
            'type_handicap' => $this->type_handicap,

            // School Info
            'school_id' => $this->school_id,
            'statut_global' => $this->statut_global,
            'ecole' => $this->whenLoaded('ecole')?->pluck('name'),
            'ecole_origine' => $this->whenLoaded('ecoleOrigine')?->pluck('name'),

            // Relations
            'classes' => $this->whenLoaded('classes')?->pluck('name'),
            'inscriptions' => $this->whenLoaded('inscriptions')?->pluck('name'),

            // Creator
            'created_by' => $this->whenLoaded('creator')?->pluck('name'),

            // Photo
            'photo_url' => $this->photo_path 
                ? asset('storage/' . $this->photo_path) 
                : null,

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
