<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EleveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
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

            'province_origine' => new ProvinceResource($this->whenLoaded('provinceOrigine')),
            'commune_origine' => new CommuneResource($this->whenLoaded('communeOrigine')),
            'zone_origine' => new ZoneResource($this->whenLoaded('zoneOrigine')),
            'colline_origine' => new CollineResource($this->whenLoaded('collineOrigine')),
            'niveau' => new NiveauResource($this->whenLoaded('niveau')),

            'nom_pere' => $this->nom_pere,
            'nom_mere' => $this->nom_mere,
            'nom_tuteur' => $this->nom_tuteur,
            'contact_tuteur' => $this->contact_tuteur,
            'est_orphelin' => $this->est_orphelin,
            'a_handicap' => $this->a_handicap,
            'type_handicap' => $this->type_handicap,

            'school_id' => $this->school_id,
            'statut_global' => $this->statut_global,
            'school' => new SchoolResource($this->whenLoaded('ecole')),

            'classes' => $this->whenLoaded('classes', function () {
                return $this->classes->map(fn ($classe) => [
                    'id' => $classe->id,
                    'nom' => $classe->nom,
                ]);
            }),
            'parents' => $this->whenLoaded('parents', function () {
                return $this->parents->map(fn ($parent) => [
                    'id' => $parent->id,
                    'user_id' => $parent->user_id,
                    'name' => $parent->nom_complet,
                    'email' => $parent->email,
                    'telephone' => $parent->telephone,
                    'adresse' => $parent->adresse,
                    'relation_type' => $parent->relation,
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

        // When inscriptions are loaded and a target year is set, extract the
        // contextual inscription data so the frontend knows the student's state
        // for the consulted school year (niveau, classe, statut_academique, etc.)
        $inscriptionCourante = $this->getInscriptionCourante();
        if ($inscriptionCourante) {
            $data['inscription_courante'] = [
                'id' => $inscriptionCourante->id,
                'annee_scolaire_id' => $inscriptionCourante->annee_scolaire_id,
                'school_id' => $inscriptionCourante->school_id,
                'statut_academique' => $inscriptionCourante->statut_academique,
                'est_redoublant' => $inscriptionCourante->est_redoublant,
                'niveau' => $inscriptionCourante->relationLoaded('niveauDemande') && $inscriptionCourante->niveauDemande
                    ? ['id' => $inscriptionCourante->niveauDemande->id, 'nom' => $inscriptionCourante->niveauDemande->nom]
                    : null,
                'ecole' => $inscriptionCourante->relationLoaded('ecole') && $inscriptionCourante->ecole
                    ? ['id' => $inscriptionCourante->ecole->id, 'name' => $inscriptionCourante->ecole->name]
                    : null,
                'classe' => $inscriptionCourante->relationLoaded('affectation') && $inscriptionCourante->affectation?->classe
                    ? ['id' => $inscriptionCourante->affectation->classe->id, 'nom' => $inscriptionCourante->affectation->classe->nom]
                    : null,
            ];
        }

        return $data;
    }

    /**
     * Extract the inscription for the consulted school year from the loaded inscriptions.
     */
    private function getInscriptionCourante()
    {
        if (! $this->relationLoaded('inscriptions') || $this->inscriptions->isEmpty()) {
            return null;
        }

        $targetYearId = $this->getAttribute('_annee_scolaire_consultee_id');

        if ($targetYearId) {
            return $this->inscriptions
                ->where('annee_scolaire_id', $targetYearId)
                ->first();
        }

        return $this->inscriptions->first();
    }
}
