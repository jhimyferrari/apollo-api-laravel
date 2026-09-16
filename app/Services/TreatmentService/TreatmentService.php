<?php

namespace App\Services\TreatmentService;

use App\Actions\Validation\ValidateDuplicateField;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Services\TreatmentService\Strategies\TreatmentStrategy;
use App\Services\TreatmentService\ValueObjects\TreatmentOptions;
use Illuminate\Database\Eloquent\Model;

class TreatmentService
{
    public function __construct(
        private readonly ValidateDuplicateField $validateDuplicateField,
        private readonly ValidateFieldIsNotNull $validateFieldIsNotNull,
    ) {}

    public function for(TreatmentStrategy $strategy, mixed $value, string $field, Model $model)
    {
        return new TreatmentBuilder(treat: $this, strategy: $strategy, value: $value, field: $field, model: $model);
    }

    public function handle(TreatmentStrategy $strategy, mixed $value, string $field, Model $model, TreatmentOptions $options = new TreatmentOptions): mixed
    {
        if ($options->mustBeNotNull) {
            $this->validateFieldIsNotNull->execute($value, $field);
        } elseif ((\is_array($value) && empty($value)) || (! \is_array($value) && (\is_null($value) || trim($value ?? '') === ''))) {
            return null;
        }

        $treated = $strategy->handle($value);

        if ($options->mustBeUnique) {
            $this->validateDuplicateField->execute(
                model: $model,
                value: $treated,
                field: $field,
                ignoredId: $options->ignoredId,
                organizationId: $options->organizationId
            );
        }

        return $treated;

    }
}
