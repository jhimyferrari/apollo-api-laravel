<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateClient;
use App\Actions\Validation\ValidateResourceStatus;
use App\Models\Client;

class TreatClient implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateClient $validateClient,
        private readonly ValidateResourceStatus $validateResourceStatus,
        private readonly bool $allowInactive = false,
    ) {}

    public function allowInactive(bool $allow = true): self
    {
        return new self($this->validateClient, $this->validateResourceStatus, $allow);
    }

    public function handle(mixed $value, string $field): Client
    {
        $client = $this->validateClient->execute($value, $field);

        if (! $this->allowInactive) {
            $this->validateResourceStatus->execute($client, $field);
        }

        return $client;
    }
}
