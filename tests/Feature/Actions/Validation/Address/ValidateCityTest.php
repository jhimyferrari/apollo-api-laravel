<?php

use App\Actions\Validation\Address\ValidateCity;
use App\Models\City;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('ValidateCity', function () {
    it('returns the city when the ibge code exists', function () {
        $this->seed(UfSeeder::class);
        new CitiesSeeder()->run(1);
        $city = City::first();

        $result = app(ValidateCity::class)->execute($city->ibge_code, 'ibge_city_code');

        expect($result->ibge_code)->toBe($city->ibge_code);
    });

    it('throws ValidationException when the ibge code does not exist', function () {
        $invalidIbgeCode = '0000000';

        expect(fn () => app(ValidateCity::class)->execute($invalidIbgeCode, 'city_ibge_code'))
            ->toThrow(ValidationException::class, __('validation.city', ['attribute' => __('validation.attributes.city_ibge_code')]));
    });
});
