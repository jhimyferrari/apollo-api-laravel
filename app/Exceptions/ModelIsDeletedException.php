<?php

namespace App\Exceptions;

class ModelIsDeletedException extends BaseException
{
    public function __construct(string $message = '', int $statusCode = 409, string $errorCode = 'MODEL_IS_DELETED', ?\Throwable $previous = null)
    {
        parent::__construct(
            message: $message,
            statusCode : $statusCode,
            errorCode: $errorCode,
            previous: $previous
        );
    }

    public static function forProduct(string $operation, string $productId): self
    {
        return new self(
            message: "Product `{$productId}` was not found for use in `{$operation}`. It may have been deleted."
        );

    }
}
