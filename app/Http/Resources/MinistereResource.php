<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MinistereResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'pays_id' => $this->pays_id,
            'pays' => new PaysResource($this->whenLoaded('pays')),
            'provinces' => ProvinceResource::collection($this->whenLoaded('provinces')),
        ];
    }
}
