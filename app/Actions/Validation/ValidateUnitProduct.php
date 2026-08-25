<?php

namespace App\Actions\Validation;

use App\Enum\ProductUnit;
use App\Exceptions\InvalidFieldException;

class ValidateUnitProduct
{
    /**
     * @throws InvalidFieldException
     */
    public function execute(string $value)
    {
        if (ProductUnit::tryFrom($value) == null) {
            throw new InvalidFieldException("The $value unit for products does not exist ");
        }
    }
}
