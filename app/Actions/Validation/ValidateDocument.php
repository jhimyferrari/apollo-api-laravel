<?php

namespace App\Actions\Validation;

use App\Exceptions\InvalidFieldException;
use App\Rules\CpfAndCnpj;
use Validator;

class ValidateDocument
{
    /**
     * @throws InvalidFieldException
     */
    public function execute(string $document)
    {
        Validator::make(
            ['document' => $document],
            ['document' => ['required', new CpfAndCnpj]]
        )->validated();
    }
}
