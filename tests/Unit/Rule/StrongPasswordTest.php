<?php

use App\Rules\StrongPassword;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);
describe('StrongPassword', function () {
    it('should accept only valid passwords', function () {
        $validator = Validator::make(
            ['field' => 'Abcdef1!'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeTrue();
        $validator = Validator::make(
            ['field' => 'Abcdefghijkl1234!@#$'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeTrue();

    });

    it('should accept accented letters', function () {
        $validator = Validator::make(
            ['field' => 'Çáéíó1!x'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeTrue();

    });

    it('should accept punctuation and symbols', function () {
        $validator = Validator::make(
            ['field' => 'Abcdefg1_'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeTrue();
        $validator = Validator::make(
            ['field' => 'Abcdefg1$'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeTrue();
        $validator = Validator::make(
            ['field' => 'Abcdefg1+'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeTrue();
        $validator = Validator::make(
            ['field' => 'Abcdefg1@'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeTrue();

    });

    it('should count characters, not bytes', function () {
        $validator = Validator::make(
            ['field' => 'Aç1!çççç'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeTrue();
        $validator = Validator::make(
            ['field' => 'Aç1!ççç'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();

    });

    it('should not accept passwords shorter than the minimum', function () {
        $validator = Validator::make(
            ['field' => 'Ab1!cde'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();

    });

    it('should not accept passwords without lowercase or uppercase', function () {
        $validator = Validator::make(
            ['field' => 'ABCDEFG1!'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();
        $validator = Validator::make(
            ['field' => 'abcdefg1!'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();

    });

    it('should not accept passwords without a number', function () {
        $validator = Validator::make(
            ['field' => 'Abcdefgh!'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();

    });

    it('should not accept passwords without a symbol', function () {
        $validator = Validator::make(
            ['field' => 'Abcdefg1'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();
        $validator = Validator::make(
            ['field' => 'Abcdef1 x'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();

    });

    it('should not accept passwords without letters', function () {
        $validator = Validator::make(
            ['field' => '12345678!'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();

    });

    it('should not accept passwords that fail several rules at once', function () {
        $validator = Validator::make(
            ['field' => 'abc'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();
        $validator = Validator::make(
            ['field' => 'abcdefgh'],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();

    });

    it('should not accept values that are not strings', function () {
        $validator = Validator::make(
            ['field' => 12345678],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();
        $validator = Validator::make(
            ['field' => 12.5],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();
        $validator = Validator::make(
            ['field' => ['Abcdef1!']],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();
        $validator = Validator::make(
            ['field' => true],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeFalse();

    });

    it('should not accept empty values when required', function () {
        $validator = Validator::make(
            ['field' => ''],
            ['field' => ['required', new StrongPassword]]
        );
        expect($validator->passes())->toBeFalse();
        $validator = Validator::make(
            ['field' => null],
            ['field' => ['required', new StrongPassword]]
        );
        expect($validator->passes())->toBeFalse();

    });

    it('should skip the rule for empty values when not required', function () {
        $validator = Validator::make(
            ['field' => ''],
            ['field' => new StrongPassword]
        );
        expect($validator->passes())->toBeTrue();

    });

    it('should respect a custom minimum length', function () {
        $validator = Validator::make(
            ['field' => 'Abcdef1!gh'],
            ['field' => new StrongPassword(12)]
        );
        expect($validator->passes())->toBeFalse();
        $validator = Validator::make(
            ['field' => 'Abcdefgh1!ij'],
            ['field' => new StrongPassword(12)]
        );
        expect($validator->passes())->toBeTrue();

    });
});
