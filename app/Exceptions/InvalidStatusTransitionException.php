<?php

namespace App\Exceptions;

class InvalidStatusTransitionException extends BaseException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: 'INVALID_STATUS_TRANSATION'
        );
    }

    public static function make($from, $to): self
    {
        return new self(
            message: "Cannot transition from `{$from->value}` to `{$to->value}`."
        );
    }
}
