<?php

use App\Actions\Validation\ValidatePasswordComplexity;
use App\Exceptions\PasswordValidationException;

describe('ValidatePasswordComplexity', function () {
    it('should throw an exception when numbers of characters its below 8', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('Ab$1233'))->toThrow(PasswordValidationException::class);
    });

    it('should throw an exception when characters its just lowercase', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('qweretyasdfsadf'))->toThrow(PasswordValidationException::class);
    });

    it('should throw an exception when characters its just uppercase', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('JKSDFJNSDFOIJ'))->toThrow(PasswordValidationException::class);
    });

    it('should throw an exception when characters not has numerical characters', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('qDJFOJsadf'))->toThrow(PasswordValidationException::class);
    });

    it('should throw an exception when characters not has special characters', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('qDJ93FOJsadf'))->toThrow(PasswordValidationException::class);
    });

    it('should not throw an exception when characters its complexity ', function () {
        expect(fn () => app(ValidatePasswordComplexity::class)->execute('PasswordW1ThC0m%plex9T'))->not->toThrow(PasswordValidationException::class);
    });
});
