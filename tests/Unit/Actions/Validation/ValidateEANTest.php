<?php

use App\Actions\Validation\ValidateEAN;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);
describe('ValidateEan', function () {
    it('throw an error when pass an empty value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute('', 'ean')
        )->toThrow(ValidationException::class, __('validation.ean', ['attribute' => 'ean']));
    });

    it('throw an error when pass a string with just 8 spaces value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute('        ', 'ean')
        )->toThrow(ValidationException::class);
    });

    it('throw an error when pass a string with just 13 spaces value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute('             ', 'ean')
        )->toThrow(ValidationException::class);
    });

    it('not throw an error when pass a 13 length valid value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute(fake()->ean13(), 'ean')
        )->not->toThrow(ValidationException::class);
    });

    it('not throw an error when pass a 8 length valid value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute(fake()->ean8(), 'ean')
        )->not->toThrow(ValidationException::class);
    });
    it('throw an error when pass a invalid value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute('1234566799909', 'ean')
        )->toThrow(ValidationException::class);
    });
});
