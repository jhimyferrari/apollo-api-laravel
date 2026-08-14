<?php

use App\Actions\Fetch\Ncm\FetchNcmFromReceita;
use App\Actions\Validation\ValidateNCM;
use App\Exceptions\InvalidFieldException;
use App\Models\NcmCode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
describe('ValidateNCM', function () {

    it('throws when format is invalid', function () {
        app(ValidateNCM::class)->execute('123');
    })->throws(InvalidFieldException::class);

    it('throws when format has letters', function () {
        app(ValidateNCM::class)->execute('8471301a');
    })->throws(InvalidFieldException::class);

    it('returns the ncm when it exists locally and is active', function () {
        $ncm = NcmCode::factory()->create([
            'code' => '84713012',
        ]);

        $result = app(ValidateNCM::class)->execute('84713012');

        expect($result->id)->toBe($ncm->id);
    });

    it('throws when the local ncm was inactivated', function () {
        NcmCode::factory()->inactive()->create([
            'code' => '84713012',
        ]);

        app(ValidateNCM::class)->execute('84713012');
    })->throws(InvalidFieldException::class);

    it('falls back to receita when ncm is not found locally', function () {
        $fetched = NcmCode::factory()->make(['code' => '99999999']);

        $this->mock(FetchNcmFromReceita::class, function ($mock) use ($fetched) {
            $mock->shouldReceive('execute')
                ->once()
                ->with('99999999')
                ->andReturn($fetched);
        });

        $result = app(ValidateNCM::class)->execute('99999999');

        expect($result->code)->toBe('99999999');
    });

    it('throws when ncm is not found locally nor in receita', function () {
        $this->mock(FetchNcmFromReceita::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->with('00000000')
                ->andReturn(null);
        });

        app(ValidateNCM::class)->execute('00000000');
    })->throws(InvalidFieldException::class);

    it('does not call the fetch fallback when ncm already exists locally', function () {
        NcmCode::factory()->create(['code' => '84713012']);

        $this->mock(FetchNcmFromReceita::class, function ($mock) {
            $mock->shouldNotReceive('execute');
        });

        app(ValidateNCM::class)->execute('84713012');
    });
});
