<?php

namespace App\Exceptions;

class CannotDeleteAdminException extends BaseException
{
    public function __construct(string $message = 'Admin user cannot be deleted.')
    {
        parent::__construct(
            message: $message,
            statusCode: 403,
            errorCode: 'CANNOT_DELETE_ADMIN',

        );
    }
}
