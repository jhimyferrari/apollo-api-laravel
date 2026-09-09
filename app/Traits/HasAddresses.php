<?php

namespace App\Traits;

use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasAddresses
{
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function defaultAddress(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable')
            ->where('is_default', true);
    }

    public function addAddress(array $data): Address
    {
        return $this->addresses()->create([...$data, 'organization_id' => $this->organization_id]);

    }

    public function deleteAddress(Address $address): void
    {
        $address = $this->addresses()->findOrFail($address->id);
        $address->delete();
    }

    public function setDefaultAddress(Address $address): void
    {
        $this->addresses()->whereNot('id', $address->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }
}
