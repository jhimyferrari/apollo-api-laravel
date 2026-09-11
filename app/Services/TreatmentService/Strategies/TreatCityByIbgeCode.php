<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\Address\ValidateCity;
use App\Exceptions\InvalidFieldException;
use App\Models\City;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TreatCityByIbgeCode implements TreatmentStrategy
{
    public function __construct(
        private ValidateCity $validateCity,
    ) {}

    public function handle(mixed $value): City
    {

        try {
            $value = $this->validateCity->execute($value);
        } catch (ModelNotFoundException $e) {
            throw new InvalidFieldException($e->getMessage());
        } catch (Exception $e) {
            report($e);

            throw new InvalidFieldException($e->getMessage());
        }

        return $value;

    }
}
