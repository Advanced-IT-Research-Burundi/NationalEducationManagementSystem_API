<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnseignantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'matricule' => $this->matricule,
            'statut' => $this->statut,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'school' => new SchoolResource($this->whenLoaded('school')),
            'ecoles' => SchoolResource::collection($this->whenLoaded('ecoles')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
