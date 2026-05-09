<?php

use App\Rules\CpfAndCnpj;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);
describe('CpfAndCnpj', function () {
    it('should accept only valids CPF ', function () {
        $validator = Validator::make(
            ['field' => '703.523.740-20'],
            ['field' => new CpfAndCnpj]
        );
        expect($validator->passes())->toBeTrue();
        $validator = Validator::make(
            ['field' => '62624551085'],
            ['field' => new CpfAndCnpj]
        );
        expect($validator->passes())->toBeTrue();

    });

    it('should accept only valids CNPJ ', function () {
        $validator = Validator::make(
            ['field' => '63.428.463/0001-79'],
            ['field' => new CpfAndCnpj]
        );
        expect($validator->passes())->toBeTrue();
        $validator = Validator::make(
            ['field' => '59513452000110'],
            ['field' => new CpfAndCnpj]
        );
        expect($validator->passes())->toBeTrue();

    });

    it('should not accept only valids CNPJ ', function () {
        $validator = Validator::make(
            ['field' => '63.428.463/0001-71'],
            ['field' => new CpfAndCnpj]
        );
        expect($validator->passes())->toBeFalse();
        $validator = Validator::make(
            ['field' => '59513452000111'],
            ['field' => new CpfAndCnpj]
        );
        expect($validator->passes())->toBeFalse();

    });

    it('should not accept only valids CPF ', function () {
        $validator = Validator::make(
            ['field' => '149.126.390-60'],
            ['field' => new CpfAndCnpj]
        );
        expect($validator->passes())->toBeFalse();
        $validator = Validator::make(
            ['field' => '306271793045'],
            ['field' => new CpfAndCnpj]
        );
        expect($validator->passes())->toBeFalse();

    });
});
