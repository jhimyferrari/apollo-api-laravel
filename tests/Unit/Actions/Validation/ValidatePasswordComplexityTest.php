<?php

use App\Actions\Validation\ValidatePasswordComplexity;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);
describe('ValidatePasswordComplexity', function () {
    it('should throw an exception when numbers of characters its below 8', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('Ab$1233'))->toThrow(ValidationException::class);
    });

    it('should throw an exception when characters its just lowercase', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('qweretyasdfsadf'))->toThrow(ValidationException::class);
    });

    it('should throw an exception when characters its just uppercase', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('JKSDFJNSDFOIJ'))->toThrow(ValidationException::class);
    });

    it('should throw an exception when characters not has numerical characters', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('qDJFOJsadf'))->toThrow(ValidationException::class);
    });

    it('should throw an exception when characters not has special characters', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('qDJ93FOJsadf'))->toThrow(ValidationException::class);
    });

    it('should not throw an exception when characters its complexity ', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('PasswordW1ThC0m%plex9T'))->not->toThrow(ValidationException::class);
    });
});
