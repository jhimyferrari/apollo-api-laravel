<?php

namespace App\Services\TreatmentService\ValueObjects;

class TreatmentOptions
{
    public function __construct(
        public readonly bool $mustBeNotNull = false,
        public readonly bool $mustBeUnique = false,
        public readonly ?string $ignoredId = null,
        public readonly ?string $organizationId = null
    ) {}
}
