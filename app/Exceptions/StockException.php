<?php

namespace App\Exceptions;

use App\Enum\ErrorType;
use App\Models\Product;

class StockException extends BaseException
{
    public function __construct(string $message, int $statusCode = 422, ErrorType $errorCode = ErrorType::InsufficientStock)
    {
        parent::__construct(
            message: $message,
            statusCode: $statusCode,
            errorCode: $errorCode

        );
    }

    public static function insufficientStock(Product $product, float $requested): self
    {
        return new self(
            message: __('messages.insufficient_stock', ['product' => $product->name, 'available' => $product->stock_quantity, 'requested' => $requested]),
            statusCode: 422,
            errorCode: ErrorType::InsufficientStock
        );
    }
}
