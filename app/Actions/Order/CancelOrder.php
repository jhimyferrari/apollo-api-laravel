<?php

namespace App\Actions\Order;

use App\Enum\Status\OrderStatus;
use App\Enum\StockMovementType;
use App\Interfaces\Models\MovesStock;
use App\Services\StockMovementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CancelOrder
{
    public function __construct(
        private readonly StockMovementService $stockMovement,
    ) {}

    /**
     * @param  StockMovementType  $reversalType  must be the inverse of confirm
     */
    public function execute(Model&MovesStock $order, StockMovementType $reversalType, ?string $reason = null): Model
    {

        return DB::transaction(function () use ($order, $reversalType, $reason) {
            if ($order->status === OrderStatus::Confirmed) {
                foreach ($order->stockMovementItems() as $item) {
                    $this->stockMovement->move(
                        product: $item->product,
                        quantity: $item->quantity,
                        type: $reversalType,
                        reference: $order,
                    );
                }
            }
            $order->status = OrderStatus::Cancelled;
            $order->cancelled_at = now();
            $order->cancellation_reason = $reason;
            $order->save();

            return $order->fresh();
        });
    }
}
