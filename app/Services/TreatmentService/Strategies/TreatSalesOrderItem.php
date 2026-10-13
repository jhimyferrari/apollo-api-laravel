<?php

namespace App\Services\TreatmentService\Strategies;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TreatSalesOrderItem implements TreatmentStrategy
{
    public function __construct(
        private readonly SalesOrder $salesOrder,
    ) {}

    public function order(SalesOrder $order): self
    {
        return new self(salesOrder: $order);
    }

    public function handle(mixed $value, string $field): SalesOrderItem
    {
        try {
            $item = $this->salesOrder->items()->findOrFail($value);

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
