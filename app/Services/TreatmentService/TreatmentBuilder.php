<?php

namespace App\Services\TreatmentService;

use App\Services\TreatmentService\Strategies\TreatmentStrategy;
use App\Services\TreatmentService\ValueObjects\TreatmentOptions;
use Illuminate\Database\Eloquent\Model;

class TreatmentBuilder
{
    private bool $mustBeNotNull = false;

    private bool $mustBeUnique = false;

    private ?string $ignoredId = null;

    private ?string $organizationId = null;

    public function __construct(
        private readonly TreatmentService $treat,
        private readonly TreatmentStrategy $strategy,
        private readonly mixed $value,
        private readonly string $field,
        private readonly Model $model,
    ) {}

    public function mustBeNotNull(bool $value = true)
    {
        $this->mustBeNotNull = $value;

        return $this;
    }

    public function mustBeUnique(bool $value = true)
    {
        $this->mustBeUnique = $value;

        return $this;
    }

    public function ignoredId(string $value)
    {
        $this->ignoredId = $value;

        return $this;
    }

    public function organizationId(string $value)
    {
        $this->organizationId = $value;

        return $this;
    }

    public function handle()
    {
        $options = new TreatmentOptions(
            mustBeNotNull: $this->mustBeNotNull,
            mustBeUnique: $this->mustBeUnique,
            ignoredId: $this->ignoredId,
            organizationId: $this->organizationId,
        );

        return $this->treat->handle($this->strategy, $this->value, $this->field, $this->model, $options);
    }
}
