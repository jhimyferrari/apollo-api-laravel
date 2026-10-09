<?php

namespace App\Exceptions;

use App\Enum\ErrorType;

class InvalidStatusException extends BaseException
{
    public function __construct(string $message = '', ErrorType $errorCode = ErrorType::InvalidStatus)
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: ErrorType::InvalidStatus

        );

    }
}
