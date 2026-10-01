<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\ResourceNotFoundException;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use RuntimeException;

class TreatPurchaseOrderItem implements TreatmentStrategy
{
    public function __construct(
        private readonly PurchaseOrder $purchaseOrder,
    ) {}

    public function order(PurchaseOrder $order): self
    {
        return new self(purchaseOrder: $order);
    }

    public function handle(mixed $value): PurchaseOrderItem
    {
        try {
            $item = $this->purchaseOrder->items()->findOrFail($value);

            return $item;

        } catch (ModelNotFoundException|QueryException $e) {
            throw new ResourceNotFoundException("Item id $value not found");
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException('Something went wrong, please, contact support');
        }

    }
}
