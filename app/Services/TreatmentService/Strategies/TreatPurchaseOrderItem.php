<?php

namespace App\Services\TreatmentService\Strategies;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
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

    public function handle(mixed $value, string $field): PurchaseOrderItem
    {
        try {
            $item = $this->purchaseOrder->items()->findOrFail($value);

            return $item;
        } catch (ModelNotFoundException|QueryException $e) {
            report(new ModelNotFoundException(__('errors.not_found', ['value' => $value, 'attribute' => $field])));
            throw ValidationException::withMessages([$field => __('validation.order_item', ['attribute' => __("validation.attribute.$field")])]);
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException(__('errors.internal_error'));
        }

    }
}
