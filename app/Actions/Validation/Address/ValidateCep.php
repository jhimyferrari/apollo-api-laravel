<?php

namespace App\Actions\Validation\Address;

use Illuminate\Validation\ValidationException;

class ValidateCep
{
    private const PATTERN = '/^\d{8}$/';

    /**
     * @throws ValidationException
     */
    public function execute(string $cep, string $field)
    {
        $cep = preg_replace('/\D/', '', $cep);

        if ($cep === null || ! \preg_match(self::PATTERN, $cep)) {
            throw ValidationException::withMessages([$field => __('validation.cep', ['attribute' => 'cep'])]);
        }

        return $cep;
    }
}
