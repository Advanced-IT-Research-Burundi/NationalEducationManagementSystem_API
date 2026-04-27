<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'zone_id' => $this->zone_id,
            'commune_id' => $this->commune_id,
            'province_id' => $this->province_id,
            'ministere_id' => $this->ministere_id,
            'pays_id' => $this->pays_id,
            'zone' => new ZoneResource($this->whenLoaded('zone')),
            'commune' => new CommuneResource($this->whenLoaded('commune')),
            'province' => new ProvinceResource($this->whenLoaded('province')),
            'ministere' => new MinistereResource($this->whenLoaded('ministere')),
            'pays' => new PaysResource($this->whenLoaded('pays')),
            'schools' => SchoolResource::collection($this->whenLoaded('schools')),
        ];
    }
}
