<?php

namespace App\Actions\Order;

use App\Enum\Status\OrderStatus;
use App\Enum\StockMovementType;
use App\Interfaces\Models\MovesStock;
use App\Services\StockMovementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ConfirmOrder
{
    public function __construct(
        private readonly StockMovementService $stockMovement,
    ) {}

    public function execute(Model&MovesStock $order, StockMovementType $movementType): Model
    {
        return DB::transaction(function () use ($order, $movementType) {
            foreach ($order->stockMovementItems() as $item) {
                $this->stockMovement->move(
                    product: $item->product,
                    quantity: $item->quantity,
                    type: $movementType,
                    reference: $order,
                );
            }

            $order->status = OrderStatus::Confirmed;

            $order->confirmed_at = now();
            $order->save();

            return $order->fresh('items');
        });
    }
}
