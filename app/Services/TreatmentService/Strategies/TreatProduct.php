<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TreatProduct implements TreatmentStrategy
{
    /*
*
     * @throws ResourceNotFoundException
     */
    public function handle(mixed $value, string $field): Product
    {

        try {
            $product = Product::findOrFail($value);

            return $product;
        } catch (ModelNotFoundException|QueryException $e) {
            report(new ResourceNotFoundException("Product id $value not found"));
            throw ValidationException::withMessages([$field => __('validation.product', ['attribute' => $field])]);
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException(__('errors.internal_error'));
        }
    }
}
