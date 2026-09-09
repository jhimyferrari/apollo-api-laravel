<?php

namespace App\Traits\Service;

use App\Interfaces\Models\Addressable;
use App\Models\Address;
use DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait HandlesAddress
{
    public function createAddress(Addressable $model, array $data): Address
    {

        $addressData = $this->treatAddress->execute($data, mustBeNotNull: true);

        return DB::transaction(function () use ($model, $addressData) {
            $address = $model->addAddress($addressData);

            if ($addressData['is_default']) {
                $model->setDefaultAddress($address);
            }

            return $address;

        });
    }

    public function setDefaultAddress(Addressable $model, Address $address): void
    {
        try {
            $address = $model->addresses()->findOrFail($address->id);
        } catch (Exception $e) {
            throw new ModelNotFoundException('Address not found');
        }
        DB::transaction(function () use ($model, $address) {
            $model->setDefaultAddress($address);
        });
    }
}
