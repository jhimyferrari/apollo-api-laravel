<?php

use App\Actions\Validation\ValidateDuplicateField;
use App\Actions\Validation\ValidateFieldIsNotNull;
// ajuste para a exception real, se necessário
use App\Services\TreatmentService\Strategies\TreatmentStrategy;
use App\Services\TreatmentService\TreatmentService;
use App\Services\TreatmentService\ValueObjects\TreatmentOptions;
use Illuminate\Database\Eloquent\Model;

describe('TreatmentService', function () {

    beforeEach(function () {
        $this->validateDuplicateField = $this->mock(ValidateDuplicateField::class);
        $this->validateFieldIsNotNull = $this->mock(ValidateFieldIsNotNull::class);

        $this->service = app(TreatmentService::class);

        $this->model = $this->mock(Model::class);
        $this->strategy = $this->mock(TreatmentStrategy::class);
    });

    it('returns the value treated by the strategy', function () {
        $this->strategy->shouldReceive('handle')
            ->once()
            ->with('raw value')
            ->andReturn('treated value');

        $result = $this->service->handle(
            strategy: $this->strategy,
            value: 'raw value',
            field: 'name',
            model: $this->model,
        );

        expect($result)->toBe('treated value');
    });

    it('returns null early when value is an empty string, without calling the strategy', function () {
        $this->strategy->shouldNotReceive('handle');

        $result = $this->service->handle(
            strategy: $this->strategy,
            value: '   ',
            field: 'name',
            model: $this->model,
        );

        expect($result)->toBeNull();
    });

    it('returns null early when value is an empty array, without calling the strategy', function () {
        $this->strategy->shouldNotReceive('handle');

        $result = $this->service->handle(
            strategy: $this->strategy,
            value: [],
            field: 'tags',
            model: $this->model,
        );

        expect($result)->toBeNull();
    });

    it('calls ValidateFieldIsNotNull when mustBeNotNull is true, even for an empty value', function () {
        $this->validateFieldIsNotNull->shouldReceive('execute')
            ->once()
            ->with('', 'name');

        $this->strategy->shouldReceive('handle')
            ->once()
            ->with('')
            ->andReturn('');

        $this->service->handle(
            strategy: $this->strategy,
            value: '',
            field: 'name',
            model: $this->model,
            options: new TreatmentOptions(mustBeNotNull: true),
        );
    });

    it('calls ValidateDuplicateField with the treated value when mustBeUnique is true', function () {
        $this->strategy->shouldReceive('handle')
            ->once()
            ->with('raw-doc')
            ->andReturn('treated-doc');

        $this->validateDuplicateField->shouldReceive('execute')
            ->once()
            ->with(
                model: $this->model,
                value: 'treated-doc',
                field: 'document',
                ignoredId: 'client-ulid',
                organizationId: 'org-ulid',
            );

        $result = $this->service->handle(
            strategy: $this->strategy,
            value: 'raw-doc',
            field: 'document',
            model: $this->model,
            options: new TreatmentOptions(
                mustBeUnique: true,
                ignoredId: 'client-ulid',
                organizationId: 'org-ulid',
            ),
        );

        expect($result)->toBe('treated-doc');
    });

    it('does not call ValidateDuplicateField when mustBeUnique is false', function () {
        $this->strategy->shouldReceive('handle')->once()->andReturn('treated');
        $this->validateDuplicateField->shouldNotReceive('execute');

        $this->service->handle(
            strategy: $this->strategy,
            value: 'raw',
            field: 'document',
            model: $this->model,
        );
    });

    it('builds and executes the treatment through the fluent builder', function () {
        $this->strategy->shouldReceive('handle')
            ->once()
            ->with('raw-doc')
            ->andReturn('treated-doc');

        $this->validateFieldIsNotNull->shouldReceive('execute')->once();
        $this->validateDuplicateField->shouldReceive('execute')->once();

        $result = $this->service
            ->for($this->strategy, 'raw-doc', 'document', $this->model)
            ->mustBeNotNull()
            ->mustBeUnique()
            ->ignoredId('client-ulid')
            ->handle();

        expect($result)->toBe('treated-doc');
    });
});
