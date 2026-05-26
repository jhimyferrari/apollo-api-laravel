<?php

namespace App\Exceptions;

class ResourceNotFoundException extends BaseException
{
    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: 'RESOURCE_NOT_FOUND'
        );
    }
}
