<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'number' => $this->number,
            'status' => $this->status,
            'document' => $this->document,
            'legal_name' => $this->legal_name,
            'trade_name' => $this->trade_name,
            'state_registration' => $this->state_registration,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => AddressResource::collection($this->whenLoaded('address')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}
