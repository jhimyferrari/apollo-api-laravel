<?php

namespace App\Actions\Validation;

use App\Exceptions\PasswordValidationException;

class ValidatePasswordComplexity
{
    public function execute(string $password): void
    {
        if (! preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
            throw new PasswordValidationException('Password must contain at least 8 characters, one uppercase letter, one lowercase letter, one number, and one special character');
        }

    }
}
