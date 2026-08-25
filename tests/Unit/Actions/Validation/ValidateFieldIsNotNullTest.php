<?php

use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Exceptions\InvalidFieldException;

describe('ValidateFieldIsNotNull', function () {
    it('throw an error when pass a null value', function () {
        expect(fn () => app(ValidateFieldIsNotNull::class)->execute(null, 'some field'))->toThrow(InvalidFieldException::class, 'The field `some field` must have a value');
    });

    it('throw an error when pass a "" value', function () {
        expect(fn () => app(ValidateFieldIsNotNull::class)->execute('', 'some field'))->toThrow(InvalidFieldException::class, 'The field `some field` must have a value');
    });

    it('throw an error when pass an empty array value', function () {
        expect(fn () => app(ValidateFieldIsNotNull::class)->execute([], 'some field'))->toThrow(InvalidFieldException::class, 'The field `some field` must have a value');
    });
});
