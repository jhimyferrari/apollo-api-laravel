<?php

namespace App\Actions\Order;

use App\Enum\Status\OrderStatus;
use App\Enum\StockMovementType;
use App\Interfaces\Models\MovesStock;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Services\StockMovementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ConfirmOrder
{
    public function __construct(
        private readonly StockMovementService $stockMovement,
        private readonly ValidateOrderActiveRelations $validateOrderActiveRelations,
    ) {}

    public function execute(Model&MovesStock $order, StockMovementType $movementType): Model
    {
        $items = $order->stockMovementItems();
        if ($order instanceof SalesOrder || $order instanceof PurchaseOrder) {
            $this->validateOrderActiveRelations->execute($order, $items, requireItems: true);
        }

        return DB::transaction(function () use ($order, $movementType, $items) {
            foreach ($items as $item) {
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
