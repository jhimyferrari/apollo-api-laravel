<?php

namespace App\Services\TreatmentService\Strategies;

use App\Models\SalesOrder;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use RuntimeException;

class TreatSalesOrder implements TreatmentStrategy
{
    public function handle(mixed $value): SalesOrder
    {
        try {
            $saleOrder = SalesOrder::findOrFail($value);

            return $saleOrder;

        } catch (ModelNotFoundException|QueryException $e) {
            throw new ModelNotFoundException("SaleOrder id $value not found");
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException('Something went wrong, please, contact support');
        }

    }
}
