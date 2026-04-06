<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'eleve_id' => $this->eleve_id,
            'matiere_id' => $this->matiere_id,
            // 'enseignant_id' => $this->enseignant_id,
            'trimestre' => $this->trimestre,
            'categorie' => $this->categorie,
            'ponderation' => $this->ponderation,
            'note' => $this->note,
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,

            'eleve' => [
                'id' => $this->eleve_id,
                'nom' => $this->eleve->nom,
                'prenom' => $this->eleve->prenom,
                'matricule' => $this->eleve->matricule,
            ],
            'matiere' => [
                'id' => $this->matiere_id,
                'nom' => $this->matiere->nom,
            ],
            'enseignant' => [
                'id' => $this->enseignant_id,
                // 'nom' => $this->enseignant->nom,
                // 'prenom' => $this->enseignant->prenom,
            ],

            
            // 'eleve' => new EleveResource($this->whenLoaded("eleve", fn() => $this->eleve->nom)),
            // 'matiere' => new MatiereResource($this->whenLoaded("matiere", fn() => $this->matiere->id)),
            // 'enseignant' => new EnseignantResource($this->whenLoaded("enseignant", fn() => $this->enseignant->id)),
        ];
    }
}
