<?php

namespace App\Exceptions;

use App\Enum\ErrorType;

class InvalidStatusTransitionException extends BaseException
{
    public function __construct(string $message = '', ErrorType $errorCode = ErrorType::InvalidStatusTransition)
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: $errorCode
        );
    }

    public static function make($from, $to): self
    {
        return new self(
            message: "Cannot transition from `{$from->value}` to `{$to->value}`."
        );
    }
}
