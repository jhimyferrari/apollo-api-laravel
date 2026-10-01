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

    public static function mustBeNumeric(string $value): self
    {
        return new self(message: "The value `$value` must be numeric");
    }

    public static function mustBeInteger(string $value): self
    {
        return new self(message: "The value `$value` must be an integer");
    }

    public static function mustBePositive(string $value): self
    {
        return new self(message: "The value `$value` must be positive");
    }

    public static function mustBeNegative(string $value): self
    {
        return new self(message: "The value `$value` must be negative");
    }
}
