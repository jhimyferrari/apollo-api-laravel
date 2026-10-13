<?php

namespace App\Exceptions;

use App\Enum\ErrorType;

class CannotDeleteAdminException extends BaseException
{
    public function __construct(string $message = 'Administrador não pode ser deletado.')
    {
        parent::__construct(
            message: $message,
            statusCode: 403,
            errorCode: ErrorType::AdminCannotBeDeleted,

        );
    }
}
