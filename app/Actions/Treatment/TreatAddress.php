<?php

namespace App\Actions\Treatment;

use App\Actions\Validation\Address\ValidateCep;
use App\Actions\Validation\Address\ValidateCity;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Exceptions\InvalidFieldException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TreatAddress
{
    public function __construct(
        private ValidateFieldIsNotNull $validateFieldIsNotNull,
        private ValidateCity $validateCity,
        private ValidateCep $validateCep
    ) {}

    public function execute(array $addressRaw, bool $mustBeNotNull = false): ?array
    {

        if ($mustBeNotNull) {
            $this->validateFieldIsNotNull->execute($addressRaw, 'address');
        }
        if (empty($addressRaw)) {
            return null;
        }
        $addressTreated = [];

        $this->validateFieldIsNotNull->execute($addressRaw['street'], 'street');
        $this->validateFieldIsNotNull->execute($addressRaw['neighborhood'], 'neighborhood');
        $this->validateFieldIsNotNull->execute($addressRaw['number'], 'number');
        $this->validateFieldIsNotNull->execute($addressRaw['city_ibge_code'], 'city_ibge_code');

        try {
            $addressTreated['city_ibge_code'] = $this->validateCity->execute($addressRaw['city_ibge_code'])->ibge_code;
        } catch (ModelNotFoundException $e) {
            throw new InvalidFieldException($e->getMessage());
        } catch (Exception $e) {
            report($e);
            throw new InvalidFieldException($e->getMessage());
        }
        $addressTreated['cep'] = $this->validateCep->execute($addressRaw['cep']);

        $addressTreated['street'] = trim($addressRaw['street']);
        $addressTreated['neighborhood'] = trim($addressRaw['neighborhood']);
        $addressTreated['number'] = trim($addressRaw['number']);
        $addressTreated['complement'] = (isset($addressRaw['complement'])) ? trim($addressRaw['complement']) : null;

        $addressTreated['is_default'] = (isset($addressRaw['is_default'])) ? (bool) $addressRaw['is_default'] : false;

        return $addressTreated;
    }
}
