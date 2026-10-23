<?php

namespace App\Exceptions;

use App\Enum\ErrorType;
use App\Enum\Status\OrderStatus;

class OrderCannotBeDeletedException extends BaseException
{
    public function __construct(string $message = '')
    {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errorCode: ErrorType::OrderNotDeletable
        );
    }

    public static function notDraft(OrderStatus $currentStatus): self
    {
        return new self(
            message: __('messages.order_cannot_be_delete', ['status' => $currentStatus->value]),
        );
    }
}
