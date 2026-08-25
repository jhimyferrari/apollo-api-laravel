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
        $validator = Validator::make(
            ['document' => $document],
            ['document' => ['required', new CpfAndCnpj]]
        );

        if ($validator->fails()) {
            throw new InvalidFieldException($validator->errors()->first('document'));
        }
    }
}
