<?php

namespace App\Exceptions;

class InvalidFieldException extends BaseException
{
    public function __construct(string $message)
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: 'INVALID_FIELD'
        );
    }
}
