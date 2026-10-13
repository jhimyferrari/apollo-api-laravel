<?php

use App\Actions\Validation\ValidateFieldIsNotNull;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

describe('ValidateFieldIsNotNull', function () {
    it('throw an error when pass a null value', function () {
        expect(fn () => app(ValidateFieldIsNotNull::class)->execute(null, 'name'))
            ->toThrow(ValidationException::class, __('validation.required', ['attribute' => 'nome']));
    });

    it('throw an error when pass a "" value', function () {
        expect(fn () => app(ValidateFieldIsNotNull::class)->execute('', 'name'))
            ->toThrow(ValidationException::class, __('validation.required', ['attribute' => 'nome']));
    });

    it('throw an error when pass an empty array value', function () {
        expect(fn () => app(ValidateFieldIsNotNull::class)->execute([], 'name'))
            ->toThrow(ValidationException::class, __('validation.required', ['attribute' => 'nome']));
    });
});
