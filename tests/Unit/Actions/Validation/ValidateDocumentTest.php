<?php

use App\Actions\Validation\ValidateDocument;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);
describe('ValidateDocument', function () {

    it('throw an error when pass a invalid cnpj', function () {
        expect(fn () => app(ValidateDocument::class)->execute('11.111.111/0001-11'))->toThrow(ValidationException::class);
    });
    it('not throw an error when pass a valid cnpj', function () {
        expect(fn () => app(ValidateDocument::class)->execute(fake()->cnpj()))->not->toThrow(ValidationException::class);
    });

    it('not throw an error when pass a valid cnpj without pontuation', function () {
        expect(fn () => app(ValidateDocument::class)->execute(fake()->cnpj(false)))->not->toThrow(ValidationException::class);
    });
    it('throw an error when pass a invalid cpf', function () {
        expect(fn () => app(ValidateDocument::class)->execute('222.222.222-22'))->toThrow(ValidationException::class);
    });
    it('not throw an error when pass a valid cpf', function () {
        expect(fn () => app(ValidateDocument::class)->execute(fake()->cpf()))->not->toThrow(ValidationException::class);
    });

    it('not throw an error when pass a valid cpf without pontuation', function () {
        expect(fn () => app(ValidateDocument::class)->execute(fake()->cpf(false)))->not->toThrow(ValidationException::class);
    });
});
