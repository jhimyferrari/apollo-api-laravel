<?php

use App\Actions\Validation\Address\ValidateCep;
use App\Exceptions\InvalidFieldException;

describe('ValidateCep', function () {
    it('returns the cep without mask when it has 8 digits', function () {
        $result = app(ValidateCep::class)->execute('87500-000');

        expect($result)->toBe('87500000');
    });

    it('returns the cep as is when it already has no mask', function () {
        $result = app(ValidateCep::class)->execute('87500000');

        expect($result)->toBe('87500000');
    });

    it('throws InvalidFieldException when the cep has fewer than 8 digits', function () {
        expect(fn () => app(ValidateCep::class)->execute('1234'))
            ->toThrow(InvalidFieldException::class, 'CEP 1234 is invalid');
    });

    it('throws InvalidFieldException when the cep has more than 8 digits', function () {
        expect(fn () => app(ValidateCep::class)->execute('875000001'))
            ->toThrow(InvalidFieldException::class, 'CEP 875000001 is invalid');
    });

    it('throws InvalidFieldException when the cep contains only letters', function () {
        expect(fn () => app(ValidateCep::class)->execute('abcdefgh'))
            ->toThrow(InvalidFieldException::class, 'CEP  is invalid');
    });
});
