<?php

use App\Actions\Validation\Address\ValidateCep;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);
describe('ValidateCep', function () {
    it('returns the cep without mask when it has 8 digits', function () {
        $result = app(ValidateCep::class)->execute('87500-000', 'cep');

        expect($result)->toBe('87500000');
    });

    it('returns the cep as is when it already has no mask', function () {
        $result = app(ValidateCep::class)->execute('87500000', 'cep');

        expect($result)->toBe('87500000');
    });

    it('throws InvalidFieldException when the cep has fewer than 8 digits', function () {
        expect(fn () => app(ValidateCep::class)->execute('1234', 'cep'))
            ->toThrow(ValidationException::class, __('validation.cep', ['attribute' => 'cep']));
    });

    it('throws InvalidFieldException when the cep has more than 8 digits', function () {
        expect(fn () => app(ValidateCep::class)->execute('875000001', 'cep'))
            ->toThrow(ValidationException::class, __('validation.cep', ['attribute' => 'cep']));
    });

    it('throws InvalidFieldException when the cep contains only letters', function () {
        expect(fn () => app(ValidateCep::class)->execute('abcdefgh', 'cep'))
            ->toThrow(ValidationException::class, __('validation.cep', ['attribute' => 'cep']));
    });
});
