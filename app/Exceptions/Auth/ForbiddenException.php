<?php

namespace App\Exceptions\Auth;

use App\Exceptions\BaseException;

class ForbiddenException extends BaseException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message,
            statusCode: 403,
            errorCode: 'FORBIDDEN'
        );
    }
}
