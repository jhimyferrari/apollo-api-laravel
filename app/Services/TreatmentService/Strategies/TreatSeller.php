<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Seller;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use RuntimeException;

class TreatSeller implements TreatmentStrategy
{
    public function handle(mixed $value): Seller
    {
        try {
            $seller = Seller::findOrFail($value);

            return $seller;

        } catch (ModelNotFoundException|QueryException $e) {
            throw new ResourceNotFoundException("Seller id $value not found");
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException('Something went wrong, please, contact support');
        }

    }
}
