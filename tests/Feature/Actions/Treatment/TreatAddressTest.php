<?php

use App\Actions\Treatment\TreatAddress;
use App\Actions\Validation\Address\ValidateCep;
use App\Actions\Validation\Address\ValidateCity;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
describe('TreatAddress', function () {
    it('returns null when addressRaw is empty and mustBeNotNull is false', function () {
        $result = app(TreatAddress::class)->execute([]);

        expect($result)->toBeNull();
    });

    it('calls validateFieldIsNotNull for the address itself when mustBeNotNull is true', function () {
        $addressRaw = [
            'street' => 'Rua A',
            'neighborhood' => 'Centro',
            'number' => '100',
            'cep' => '87500000',
            'city_ibge_code' => '4115200',
        ];

        $this->mock(ValidateFieldIsNotNull::class)
            ->shouldReceive('execute')
            ->with($addressRaw, 'address')
            ->once()
            ->withAnyArgs()
            ->zeroOrMoreTimes();

        $this->mock(ValidateCity::class)
            ->shouldReceive('execute')
            ->andReturn(new City(['name' => 'Umuarama', 'ibge_code' => '4115200']));

        $this->mock(ValidateCep::class)
            ->shouldReceive('execute')
            ->andReturn('87500000');

        app(TreatAddress::class)->execute($addressRaw, mustBeNotNull: true);
    });

    it('validates street, neighborhood, number and cep are not null', function () {
        $addressRaw = [
            'street' => 'Rua A',
            'neighborhood' => 'Centro',
            'number' => '100',
            'cep' => '87500000',
            'city_ibge_code' => '4115200',
        ];

        $this->mock(ValidateFieldIsNotNull::class)
            ->shouldReceive('execute')->with('Rua A', 'street')->once()
            ->shouldReceive('execute')->with('Centro', 'neighborhood')->once()
            ->shouldReceive('execute')->with('100', 'number')->once()
            ->shouldReceive('execute')->with('4115200', 'city_ibge_code')->once();

        $this->mock(ValidateCity::class)->shouldReceive('execute')->andReturn(new City(['name' => 'Umuarama', 'ibge_code' => '4115200']));
        $this->mock(ValidateCep::class)->shouldReceive('execute')->andReturn('87500000');

        app(TreatAddress::class)->execute($addressRaw);
    });

    it('trims street, neighborhood, number and complement', function () {
        $addressRaw = [
            'street' => '  Rua A  ',
            'neighborhood' => '  Centro  ',
            'number' => '  100  ',
            'complement' => '  Fundos  ',
            'cep' => '87500000',
            'city_ibge_code' => '4115200',
        ];

        $this->mock(ValidateFieldIsNotNull::class)->shouldReceive('execute');
        $this->mock(ValidateCity::class)->shouldReceive('execute')->andReturn(new City(['name' => 'Umuarama', 'ibge_code' => '4115200']));
        $this->mock(ValidateCep::class)->shouldReceive('execute')->andReturn('87500000');

        $result = app(TreatAddress::class)->execute($addressRaw);

        expect($result['street'])->toBe('Rua A')
            ->and($result['neighborhood'])->toBe('Centro')
            ->and($result['number'])->toBe('100')
            ->and($result['complement'])->toBe('Fundos');
    });

    it('returns complement as null when it is not sent', function () {
        $addressRaw = [
            'street' => 'Rua A',
            'neighborhood' => 'Centro',
            'number' => '100',
            'cep' => '87500000',
            'city_ibge_code' => '4115200',
        ];

        $this->mock(ValidateFieldIsNotNull::class)->shouldReceive('execute');
        $this->mock(ValidateCity::class)->shouldReceive('execute')->andReturn(new City(['name' => 'Umuarama', 'ibge_code' => '4115200']));
        $this->mock(ValidateCep::class)->shouldReceive('execute')->andReturn('87500000');

        $result = app(TreatAddress::class)->execute($addressRaw);

        expect($result['complement'])->toBeNull();
    });

    it('defaults is_default to false when it is not sent', function () {
        $addressRaw = [
            'street' => 'Rua A',
            'neighborhood' => 'Centro',
            'number' => '100',
            'cep' => '87500000',
            'city_ibge_code' => '4115200',
        ];

        $this->mock(ValidateFieldIsNotNull::class)->shouldReceive('execute');
        $this->mock(ValidateCity::class)->shouldReceive('execute')->andReturn(new City(['name' => 'Umuarama', 'ibge_code' => '4115200']));
        $this->mock(ValidateCep::class)->shouldReceive('execute')->andReturn('87500000');

        $result = app(TreatAddress::class)->execute($addressRaw);

        expect($result['is_default'])->toBeFalse();
    });

    it('casts is_default to boolean when it is sent', function () {
        $addressRaw = [
            'street' => 'Rua A',
            'neighborhood' => 'Centro',
            'number' => '100',
            'cep' => '87500000',
            'city_ibge_code' => '4115200',
            'is_default' => true,
        ];

        $this->mock(ValidateFieldIsNotNull::class)->shouldReceive('execute');
        $this->mock(ValidateCity::class)->shouldReceive('execute')->andReturn(new City(['name' => 'Umuarama', 'ibge_code' => '4115200']));
        $this->mock(ValidateCep::class)->shouldReceive('execute')->andReturn('87500000');

        $result = app(TreatAddress::class)->execute($addressRaw);

        expect($result['is_default'])->toBeTrue();
    });

    it('accepts number as an integer without throwing', function () {
        $addressRaw = [
            'street' => 'Rua A',
            'neighborhood' => 'Centro',
            'number' => 100,
            'cep' => '87500000',
            'city_ibge_code' => '4115200',
        ];

        $this->mock(ValidateFieldIsNotNull::class)->shouldReceive('execute');
        $this->mock(ValidateCity::class)->shouldReceive('execute')->andReturn(new City(['name' => 'Umuarama', 'ibge_code' => '4115200']));
        $this->mock(ValidateCep::class)->shouldReceive('execute')->andReturn('87500000');

        $result = app(TreatAddress::class)->execute($addressRaw);

        expect($result['number'])->toBe('100');
    });

    it('uses the value returned by ValidateCity for city', function () {
        $addressRaw = [
            'street' => 'Rua A',
            'neighborhood' => 'Centro',
            'number' => '100',
            'cep' => '87500000',
            'city_ibge_code' => '4115200',
        ];
        $city = new City(['name' => 'Umuarama', 'ibge_code' => '4115200']);

        $this->mock(ValidateFieldIsNotNull::class)->shouldReceive('execute');
        $this->mock(ValidateCity::class)
            ->shouldReceive('execute')
            ->with('4115200')
            ->once()
            ->andReturn($city);
        $this->mock(ValidateCep::class)->shouldReceive('execute')->andReturn('87500000');

        $result = app(TreatAddress::class)->execute($addressRaw);

        expect($result['city_ibge_code'])->toBe($city->ibge_code);
    });

    it('uses the value returned by ValidateCep for cep', function () {
        $addressRaw = [
            'street' => 'Rua A',
            'neighborhood' => 'Centro',
            'number' => '100',
            'cep' => '87500-000',
            'city_ibge_code' => '4115200',
        ];

        $this->mock(ValidateFieldIsNotNull::class)->shouldReceive('execute');
        $this->mock(ValidateCity::class)->shouldReceive('execute')->andReturn(new City(['name' => 'Umuarama', 'ibge_code' => '4115200']));
        $this->mock(ValidateCep::class)
            ->shouldReceive('execute')
            ->with('87500-000')
            ->once()
            ->andReturn('87500000');

        $result = app(TreatAddress::class)->execute($addressRaw);

        expect($result['cep'])->toBe('87500000');
    });
});
