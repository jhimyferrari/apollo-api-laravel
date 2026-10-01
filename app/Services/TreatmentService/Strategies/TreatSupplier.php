<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Supplier;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use RuntimeException;

class TreatSupplier implements TreatmentStrategy
{
    public function handle(mixed $value): Supplier
    {
        try {
            $supplier = Supplier::findOrFail($value);

            return $supplier;

        } catch (ModelNotFoundException|QueryException $e) {
            throw new ResourceNotFoundException("Supplier id $value not found");
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException('Something went wrong, please, contact support');
        }

    }
}
