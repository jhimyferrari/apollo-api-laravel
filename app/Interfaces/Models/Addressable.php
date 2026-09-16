<?php

namespace App\Interfaces\Models;

use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Addressable
{
    public function addresses(): MorphMany;

    public function defaultAddress(): MorphOne;

    public function addAddress(array $data): Address;

    public function deleteAddress(Address $address): void;

    public function setDefaultAddress(Address $address): void;
}
