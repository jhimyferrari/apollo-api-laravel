<?php

namespace App\Services\TreatmentService\Strategies;

use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use RuntimeException;

class TreatPurchaseOrder implements TreatmentStrategy
{
    public function handle(mixed $value): SalesOrder
    {
        try {
            $saleOrder = PurchaseOrder::findOrFail($value);

            return $saleOrder;

        } catch (ModelNotFoundException|QueryException $e) {
            throw new ModelNotFoundException("PurchaseOrder id $value not found");
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException('Something went wrong, please, contact support');
        }

    }
}
