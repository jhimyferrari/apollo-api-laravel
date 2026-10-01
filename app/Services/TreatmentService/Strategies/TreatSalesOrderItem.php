<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\ResourceNotFoundException;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
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

    public function handle(mixed $value): SalesOrderItem
    {
        try {
            $item = $this->salesOrder->items()->findOrFail($value);

            return $item;

        } catch (ModelNotFoundException|QueryException $e) {
            throw new ResourceNotFoundException("Item id $value not found");
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException('Something went wrong, please, contact support');
        }

    }
}
