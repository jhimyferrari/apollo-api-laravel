<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateDocument;
use App\Helpers\DocumentHelper;

/**
 * Action for validate and formate documents
 */
class TreatDocument implements TreatmentStrategy
{
    public function __construct(
        private ValidateDocument $validateDocument,
    ) {}

    public function handle(mixed $value): string
    {

        $value = DocumentHelper::remove_pontuation($value);
        $this->validateDocument->execute($value);

        return $value;
    }
}
