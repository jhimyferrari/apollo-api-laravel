<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'street' => $this->street,
            'neighborhood' => $this->neighborhood,
            'number' => $this->number,
            'complement' => $this->complement,
            'cep' => $this->cep,
            'city_ibge_code' => $this->city_ibge_code,
        ];
    }
}
