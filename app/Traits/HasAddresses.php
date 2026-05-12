<?php

namespace App\Traits;

use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAddresses
{
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function defaultAddress(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable')
            ->where('is_default', true)
            ->limit(1);
    }

    public function addAddress(array $data): Address
    {
        return $this->addresses()->create($data);
    }

    public function setDefaultAddress(Address $address): void
    {
        $this->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }
}
