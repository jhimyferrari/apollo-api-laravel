<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;

class TreatProduct implements TreatmentStrategy
{
    /*
*
     * @throws ResourceNotFoundException
     */
    public function handle(mixed $value): Product
    {

        try {
            $product = Product::findOrFail($value);

            return $product;
        } catch (ModelNotFoundException $e) {
            throw new ResourceNotFoundException("Product id $value not found");
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException('Something went wrong, please, contact support');
        }
    }
}
