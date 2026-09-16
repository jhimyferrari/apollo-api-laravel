<?php

namespace App\Services\TreatmentService\Strategies;

use App\Models\Brand;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use RuntimeException;

class TreatBrand implements TreatmentStrategy
{
    public function handle(mixed $value): Brand
    {
        try {
            $brand = Brand::findOrFail($value);

            return $brand;

        } catch (ModelNotFoundException|QueryException $e) {
            throw new ModelNotFoundException("Brand id $value not found");
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException('Something went wrong, please, contact support');
        }

    }
}
