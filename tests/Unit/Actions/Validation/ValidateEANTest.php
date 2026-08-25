<?php

use App\Actions\Validation\ValidateEAN;
use App\Exceptions\InvalidFieldException;
use Tests\TestCase;

uses(TestCase::class);
describe('ValidateEan', function () {
    it('throw an error when pass an empty value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute('')
        )->toThrow(InvalidFieldException::class, 'The EAN code `` it`s not valid');
    });

    it('throw an error when pass a string with just 8 spaces value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute('        ')
        )->toThrow(InvalidFieldException::class, 'The EAN code `` it`s not valid');
    });

    it('throw an error when pass a string with just 13 spaces value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute('             ')
        )->toThrow(InvalidFieldException::class, 'The EAN code `` it`s not valid');
    });

    it('not throw an error when pass a 13 length valid value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute(fake()->ean13())
        )->not->toThrow(InvalidFieldException::class);
    });

    it('not throw an error when pass a 8 length valid value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute(fake()->ean8())
        )->not->toThrow(InvalidFieldException::class);
    });
    it('throw an error when pass a invalid value', function () {
        expect(
            fn () => app(ValidateEAN::class)->execute('1234566799909')
        )->toThrow(InvalidFieldException::class, 'The EAN code `1234566799909` it`s not valid');
    });
});
