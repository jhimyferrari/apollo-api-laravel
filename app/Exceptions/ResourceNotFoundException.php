<?php

namespace App\Exceptions;

use App\Enum\ErrorType;

class ResourceNotFoundException extends BaseException
{
    public function __construct(string $message = '', ErrorType $errorCode = ErrorType::ResourceNotFound, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: ErrorType::ResourceNotFound
        );
    }
}
