<?php

namespace App\Exceptions;

use App\Models\Product;

class StockException extends BaseException
{
    public function __construct(string $message, int $statusCode = 422, string $errorCode = 'ERROR_WITH_STOCK')
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
            message: "Insufficient stock for '{$product->name}'. Available: {$product->stock_quantity}, requested: {$requested}.",
            statusCode: 422,
            errorCode: 'INSUFFICIENT_STOCK'
        );
    }
}
