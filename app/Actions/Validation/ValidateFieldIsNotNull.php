<?php

namespace App\Actions\Validation;

use App\Exceptions\InvalidFieldException;

use function PHPUnit\Framework\isEmpty;

class ValidateFieldIsNotNull
{
    /**
     * @throws InvalidFieldException
     */
    public function execute(mixed $value, string $fieldName): void
    {
        if ($value == null || $value == '' || (\is_array($value) && isEmpty($value))) {
            throw new InvalidFieldException("The field `$fieldName` must have a value");
        }
    }
}
