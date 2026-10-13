<?php

namespace App\Actions\Validation;

use App\Enum\ProductUnit;
use App\Exceptions\InvalidFieldException;
use Illuminate\Validation\ValidationException;

class ValidateUnitProduct
{
    /**
     * @throws InvalidFieldException
     */
    public function execute(string $value, string $field)
    {
        if (ProductUnit::tryFrom($value) == null) {
            throw ValidationException::withMessages([$field => __('validation.product_unit', ['attribute' => __("validation.attribute.$field")])]);
        }
    }
}
