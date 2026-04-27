<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'ministere_id' => $this->ministere_id,
            'pays_id' => $this->pays_id,
            'ministere' => new MinistereResource($this->whenLoaded('ministere')),
            'pays' => new PaysResource($this->whenLoaded('pays')),
            'communes' => CommuneResource::collection($this->whenLoaded('communes')),
        ];
    }
}
