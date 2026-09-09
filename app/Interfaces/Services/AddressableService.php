<?php

namespace App\Interfaces\Services;

use App\Interfaces\Models\Addressable;
use App\Models\Address;

interface AddressableService
{
    public function setDefaultAddress(Addressable $model, Address $address): void;

    public function createAddress(Addressable $model, array $data): Address;
}
