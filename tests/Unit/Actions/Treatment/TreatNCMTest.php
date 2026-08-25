<?php

use App\Actions\Treatment\TreatNCM;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Actions\Validation\ValidateNCM;
use App\Exceptions\InvalidFieldException;
use App\Models\NcmCode;
use Tests\TestCase;

uses(TestCase::class);
describe('TreatNCM', function () {

    it('validates not-null and returns the ncm when mustBeNotNull is true and value is valid', function () {
        $ncm = NcmCode::factory()->make(['code' => '84713012']);

        $this->mock(ValidateFieldIsNotNull::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->with('ncm', '84713012');
        });

        $this->mock(ValidateNCM::class, function ($mock) use ($ncm) {
            $mock->shouldReceive('execute')
                ->once()
                ->with('84713012')
                ->andReturn($ncm);
        });

        $result = app(TreatNCM::class)->execute('84713012', mustBeNotNull: true);

        expect($result)->toBe($ncm);
    });

    it('throws when mustBeNotNull is true and value is empty', function () {
        $this->mock(ValidateFieldIsNotNull::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->with('ncm', '')
                ->andThrow(new InvalidFieldException('The field `ncm` must not be null'));
        });

        $this->mock(ValidateNCM::class, function ($mock) {
            $mock->shouldNotReceive('execute');
        });

        app(TreatNCM::class)->execute('', mustBeNotNull: true);
    })->throws(InvalidFieldException::class);

    it('returns null when mustBeNotNull is false and value is empty', function () {
        $this->mock(ValidateFieldIsNotNull::class, function ($mock) {
            $mock->shouldNotReceive('execute');
        });

        $this->mock(ValidateNCM::class, function ($mock) {
            $mock->shouldNotReceive('execute');
        });

        $result = app(TreatNCM::class)->execute('', mustBeNotNull: false);

        expect($result)->toBeNull();
    });

    it('validates and returns the ncm when mustBeNotNull is false and value is provided', function () {
        $ncm = NcmCode::factory()->make(['code' => '84713012']);

        $this->mock(ValidateFieldIsNotNull::class, function ($mock) {
            $mock->shouldNotReceive('execute');
        });

        $this->mock(ValidateNCM::class, function ($mock) use ($ncm) {
            $mock->shouldReceive('execute')
                ->once()
                ->with('84713012')
                ->andReturn($ncm);
        });

        $result = app(TreatNCM::class)->execute('84713012', mustBeNotNull: false);

        expect($result)->toBe($ncm);
    });
});
