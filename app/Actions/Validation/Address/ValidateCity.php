<?php

namespace App\Actions\Validation\Address;

use App\Models\City;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ValidateCity
{
    /**
     * @throws ModelNotFoundException
     */
    public function execute(string $cityIbgeCode): City
    {
        $city = City::where('ibge_code', $cityIbgeCode)->first();

        if ($city === null) {
            throw new ModelNotFoundException("Does not find a city for IBGE code $cityIbgeCode");
        }

        return $city;
    }
}
