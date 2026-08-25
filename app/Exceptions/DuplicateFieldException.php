<?php

namespace App\Exceptions;

class DuplicateFieldException extends BaseException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: 'DUPLICATE_FIELD',
        );
    }
}
