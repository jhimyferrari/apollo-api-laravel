<?php

use App\Actions\Validation\Address\ValidateCity;
use App\Models\City;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ValidateCity', function () {
    it('returns the city when the ibge code exists', function () {
        $this->seed(UfSeeder::class);
        new CitiesSeeder()->run(1);
        $city = City::first();

        $result = app(ValidateCity::class)->execute($city->ibge_code);

        expect($result->ibge_code)->toBe($city->ibge_code);
    });

    it('throws ModelNotFoundException when the ibge code does not exist', function () {
        $invalidIbgeCode = '0000000';

        expect(fn () => app(ValidateCity::class)->execute($invalidIbgeCode))
            ->toThrow(ModelNotFoundException::class, "Does not find a city for IBGE code $invalidIbgeCode");
    });
});
