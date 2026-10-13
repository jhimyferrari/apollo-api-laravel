<?php

namespace App\Actions\Validation;

use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Validator;

class ValidatePasswordComplexity
{
    public function execute(string $password): void
    {
        Validator::make(
            ['password' => $password],
            ['password' => new StrongPassword]
        )->validate();

    }
}
