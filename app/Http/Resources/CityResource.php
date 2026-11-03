<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ibge_code' => $this->ibge_code,
            'name' => $this->name,
            'uf_ibge_code' => $this->uf_ibge_code,
            'uf' => new UfResource($this->whenLoaded('uf')),
        ];
    }
}
