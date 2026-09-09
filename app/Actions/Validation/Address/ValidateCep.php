<?php

namespace App\Actions\Validation\Address;

use App\Exceptions\InvalidFieldException;

class ValidateCep
{
    private const PATTERN = '/^\d{8}$/';

    /**
     * @throws InvalidFieldException
     */
    public function execute(string $cep)
    {
        $cep = preg_replace('/\D/', '', $cep);

        if ($cep === null || ! \preg_match(self::PATTERN, $cep)) {
            throw new InvalidFieldException("CEP $cep is invalid");
        }

        return $cep;
    }
}
