<?php

namespace App\Services;

use App\Actions\Validation\Address\ValidateCep;
use App\Actions\Validation\Address\ValidateCity;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Interfaces\Models\Addressable;
use App\Models\Address;
use App\Services\TreatmentService\Strategies\TreatCEP;
use App\Services\TreatmentService\Strategies\TreatCityByIbgeCode;
use App\Services\TreatmentService\Strategies\TreatRegularString;
use App\Services\TreatmentService\TreatmentService;
use DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddressService
{
    public function __construct(
        private readonly TreatmentService $treatment,
        private readonly TreatRegularString $treatString,
        private readonly TreatCityByIbgeCode $treatCity,
        private readonly TreatCEP $treatCep,
        private readonly Address $model,
        private ValidateFieldIsNotNull $validateFieldIsNotNull,
        private ValidateCity $validateCity,
        private ValidateCep $validateCep
    ) {}

    public function create(Addressable $addressable, array $data)
    {

        $data['street'] = $this->treatment->for($this->treatString, $data['street'], 'street', $this->model)->mustBeNotNull()->handle();
        $data['neighborhood'] = $this->treatment->for($this->treatString, $data['neighborhood'], 'neighborhood', $this->model)->mustBeNotNull()->handle();
        $data['number'] = $this->treatment->for($this->treatString, $data['number'], 'number', $this->model)->mustBeNotNull()->handle();
        $data['street'] = $this->treatment->for($this->treatString, $data['street'], 'street', $this->model)->mustBeNotNull()->handle();

        $data['cep'] = $this->treatment->for($this->treatCep, $data['cep'], 'cep', $this->model)->mustBeNotNull()->handle();
        if (isset($data['complement'])) {
            $data['complement'] = $this->treatment->for($this->treatString, $data['complement'], 'complement', $this->model)->handle();
        }

        $data['city_ibge_code'] = $this->treatment->for($this->treatCity, $data['city_ibge_code'], 'city_ibge_code', $this->model)->mustBeNotNull()->handle()->ibge_code;

        $data['is_default'] = (isset($data['is_default'])) ? (bool) $data['is_default'] : false;

        return DB::transaction(function () use ($addressable, $data) {
            $address = $addressable->addAddress($data);
            if ($data['is_default']) {
                $addressable->setDefaultAddress($address);
            }

            return $address;
        });
    }

    public function update(Address $address, array $data)
    {

        if (\array_key_exists('street', $data)) {
            $address->street = $this->treatment->for($this->treatString, $data['street'], 'street', $this->model)->mustBeNotNull()->handle();
        }

        if (\array_key_exists('neighborhood', $data)) {
            $address->neighborhood = $this->treatment->for($this->treatString, $data['neighborhood'], 'neighborhood', $this->model)->mustBeNotNull()->handle();
        }

        if (\array_key_exists('number', $data)) {
            $address->number = $this->treatment->for($this->treatString, $data['number'], 'number', $this->model)->mustBeNotNull()->handle();
        }

        if (\array_key_exists('cep', $data)) {
            $address->cep = $this->treatment->for($this->treatCep, $data['cep'], 'cep', $this->model)->mustBeNotNull()->handle();
        }
        if (\array_key_exists('complement', $data)) {
            $address->complement = $this->treatment->for($this->treatString, $data['complement'], 'complement', $this->model)->handle();
        }

        if (\array_key_exists('city_ibge_code', $data)) {
            $address->city_ibge_code = $this->treatment->for($this->treatCity, $data['city_ibge_code'], 'city_ibge_code', $this->model)->mustBeNotNull()->handle()->ibge_code;
        }

        return DB::transaction(function () use ($address, $data) {
            $address->save();
            if (\array_key_exists('is_default', $data)) {
                $address->addressable->setDefaultAddress($address);
            }

            return $address;
        });
    }

    public function delete(Address $address)
    {
        DB::transaction(function () use ($address) {
            $address->delete();
        });
    }

    public function setDefault(Addressable $addressable, Address $address): void
    {
        try {
            $address = $addressable->addresses()->findOrFail($address->id);
        } catch (Exception $e) {
            throw new ModelNotFoundException('Address not found');
        }
        DB::transaction(function () use ($addressable, $address) {
            $addressable->setDefaultAddress($address);
        });
    }
}
