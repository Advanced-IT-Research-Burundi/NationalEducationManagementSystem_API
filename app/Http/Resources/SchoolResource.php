<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code_ecole' => $this->code_ecole,
            'name' => $this->name,
            'type_ecole' => $this->type_ecole,
            'niveau' => $this->niveau,
            'statut' => $this->statut,
            'statut_label' => $this->statut_label,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'site_web' => $this->site_web,
            'adresse_physique' => $this->adresse_physique,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'capacite_accueil' => $this->capacite_accueil,
            'annee_creation' => $this->annee_creation,
            'directeur_name' => $this->directeur_name,
            'directeur_id' => $this->directeur_id,

            'colline_id' => $this->colline_id,
            'zone_id' => $this->zone_id,
            'commune_id' => $this->commune_id,
            'province_id' => $this->province_id,
            'ministere_id' => $this->ministere_id,
            'pays_id' => $this->pays_id,

            'colline' => new CollineResource($this->whenLoaded('colline')),
            'zone' => new ZoneResource($this->whenLoaded('zone')),
            'commune' => new CommuneResource($this->whenLoaded('commune')),
            'province' => new ProvinceResource($this->whenLoaded('province')),
            'ministere' => new MinistereResource($this->whenLoaded('ministere')),
            'pays' => new PaysResource($this->whenLoaded('pays')),

            'directeur' => $this->whenLoaded('directeur', fn () => [
                'id' => $this->directeur->id,
                'name' => $this->directeur->name,
            ]),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'validator' => $this->whenLoaded('validator', fn () => [
                'id' => $this->validator->id,
                'name' => $this->validator->name,
            ]),

            'niveaux_scolaires' => NiveauResource::collection($this->whenLoaded('niveauxScolaires')),
            'enseignants' => EnseignantResource::collection($this->whenLoaded('enseignants')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
