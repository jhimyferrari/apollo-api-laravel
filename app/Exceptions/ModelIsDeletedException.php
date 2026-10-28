<?php

namespace App\Exceptions;

use App\Enum\ErrorType;

class ModelIsDeletedException extends BaseException
{
    public function __construct(string $message = '', int $statusCode = 409, ErrorType $errorCode = ErrorType::ModelIsDeleted, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: $message,
            statusCode : $statusCode,
            errorCode: $errorCode,
            previous: $previous
        );
    }

    public static function forProduct(string $productId): self
    {
        return new self(
            message: __('validation.product.wasDelete', ['product' => $productId])
        );

    }
}
