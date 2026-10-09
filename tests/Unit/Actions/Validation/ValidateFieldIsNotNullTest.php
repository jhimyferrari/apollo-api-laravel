<?php

use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Exceptions\InvalidFieldException;
use Tests\TestCase;

uses(TestCase::class);

describe('ValidateFieldIsNotNull', function () {
    it('throw an error when pass a null value', function () {
        expect(fn () => app(ValidateFieldIsNotNull::class)->execute(null, 'some field'))
            ->toThrow(InvalidFieldException::class, __('errors.field_required', ['field' => __('validation.attributes.some field')]));
    });

    it('throw an error when pass a "" value', function () {
        expect(fn () => app(ValidateFieldIsNotNull::class)->execute('', 'some field'))
            ->toThrow(InvalidFieldException::class, __('errors.field_required', ['field' => __('validation.attributes.some field')]));
    });

    it('throw an error when pass an empty array value', function () {
        expect(fn () => app(ValidateFieldIsNotNull::class)->execute([], 'some field'))
            ->toThrow(InvalidFieldException::class, __('errors.field_required', ['field' => __('validation.attributes.some field')]));
    });
});
