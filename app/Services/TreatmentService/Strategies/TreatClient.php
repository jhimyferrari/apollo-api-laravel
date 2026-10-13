<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateClient;
use App\Models\Client;

class TreatClient implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateClient $validateClient
    ) {}

    public function handle(mixed $value, string $field): Client
    {
        return $this->validateClient->execute($value, $field);

    }
}
