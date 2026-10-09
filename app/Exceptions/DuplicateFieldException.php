<?php

namespace App\Exceptions;

use App\Enum\ErrorType;

class DuplicateFieldException extends BaseException
{
    public function __construct(string $message = '', ErrorType $errorType = ErrorType::DuplicateField)
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: ErrorType::DuplicateField,
        );
    }
}
