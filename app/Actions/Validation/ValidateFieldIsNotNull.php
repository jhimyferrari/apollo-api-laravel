<?php

namespace App\Actions\Validation;

use App\Exceptions\InvalidFieldException;

class ValidateFieldIsNotNull
{
    /**
     * @throws InvalidFieldException
     */
    public function execute(mixed $value, string $fieldName): void
    {
        if ($value === null || $value === '' || (\is_array($value) && empty($value))) {
            throw new InvalidFieldException("The field `$fieldName` must have a value");
        }
    }
}
