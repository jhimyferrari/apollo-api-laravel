<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Client;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use RuntimeException;

class TreatClient implements TreatmentStrategy
{
    public function handle(mixed $value): Client
    {
        try {
            $client = Client::findOrFail($value);

            return $client;

        } catch (ModelNotFoundException|QueryException $e) {
            throw new ResourceNotFoundException("Client id $value not found");
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException('Something went wrong, please, contact support');
        }

    }
}
