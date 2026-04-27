<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'commune_id' => $this->commune_id,
            'province_id' => $this->province_id,
            'ministere_id' => $this->ministere_id,
            'pays_id' => $this->pays_id,
            'commune' => new CommuneResource($this->whenLoaded('commune')),
            'province' => new ProvinceResource($this->whenLoaded('province')),
            'ministere' => new MinistereResource($this->whenLoaded('ministere')),
            'pays' => new PaysResource($this->whenLoaded('pays')),
            'collines' => CollineResource::collection($this->whenLoaded('collines')),
        ];
    }
}
