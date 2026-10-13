<?php

namespace App\Actions\Validation\Address;

use App\Models\City;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ValidateCity
{
    /**
     * @throws ModelNotFoundException
     */
    public function execute(string $cityIbgeCode, string $field): City
    {
        try {
            $city = City::where('ibge_code', $cityIbgeCode)->firstOrFail();
        } catch (ModelNotFoundException) {
            report(new ModelNotFoundException(__('errors.not_found', ['value' => $cityIbgeCode, 'attribute' => 'city_ibge_code'])));
            throw ValidationException::withMessages([$field => __('validation.city', ['attribute' => __('validation.attributes.city_ibge_code')])]);
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException(__('errors.internal_error'));
        }

        return $city;
    }
}
