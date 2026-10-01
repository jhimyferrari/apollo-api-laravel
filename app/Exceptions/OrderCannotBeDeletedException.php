<?php

namespace App\Exceptions;

use App\Enum\Status\OrderStatus;

class OrderCannotBeDeletedException extends BaseException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: 'ORDER_CANNOT_BE_DELETED'
        );
    }

    public static function notDraft(OrderStatus $currentStatus): self
    {
        return new self(
            message: "Order cannot be deleted because it is `{$currentStatus->value}`. Only draft orders can be deleted.",
        );
    }
}
