<?php

namespace App\Exceptions;

use App\Enum\ErrorType;

class PasswordValidationException extends BaseException
{
    public function __construct(string $message)
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: ErrorType::InvalidPassword

        );
    }
}
