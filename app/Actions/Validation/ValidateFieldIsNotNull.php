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
        if (\is_array($value)) {
            if (empty($value)) {
                throw new InvalidFieldException("The field `$fieldName` must have a value");
            }
        } else {
            if ($value === null || $value === '' || preg_match('/^[\p{Z}\s]*$/u', $value)) {
                throw new InvalidFieldException("The field `$fieldName` must have a value");
            }
        }
    }
}
