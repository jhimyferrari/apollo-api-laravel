<?php

use App\Actions\Fetch\Ncm\FetchNcmFromReceita;
use App\Actions\Validation\ValidateNCM;
use App\Models\NcmCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);
describe('ValidateNCM', function () {

    it('throws when format is invalid', function () {
        expect(fn () => app(ValidateNCM::class)->execute('123', 'ncm'))
            ->toThrow(
                ValidationException::class, __('validation.regex', ['attribute' => 'ncm'])
            );
    });

    it('throws when format has letters', function () {
        expect(fn () => app(ValidateNCM::class)->execute('8471301a', 'ncm'))
            ->toThrow(
                ValidationException::class, __('validation.regex', ['attribute' => 'ncm'])
            );
    });

    it('returns the ncm when it exists locally and is active', function () {
        $ncm = NcmCode::factory()->create([
            'code' => '84713012',
        ]);

        $result = app(ValidateNCM::class)->execute('84713012', 'ncm');

        expect($result->id)->toBe($ncm->id);
    });

    it('throws when the local ncm was inactivated', function () {
        NcmCode::factory()->inactive()->create([
            'code' => '84713012',
        ]);

        expect(fn () => app(ValidateNCM::class)->execute('84713012', 'ncm'))
            ->toThrow(
                ValidationException::class, __('validation.ncm.inactive', ['value' => '84713012'])
            );
    });

    it('falls back to receita when ncm is not found locally', function () {
        $fetched = NcmCode::factory()->make(['code' => '99999999']);

        $this->mock(FetchNcmFromReceita::class, function ($mock) use ($fetched) {
            $mock->shouldReceive('execute')
                ->once()
                ->with('99999999')
                ->andReturn($fetched);
        });

        $result = app(ValidateNCM::class)->execute('99999999', 'ncm');

        expect($result->code)->toBe('99999999', 'ncm');
    });

    it('throws when ncm is not found locally nor in receita', function () {
        $this->mock(FetchNcmFromReceita::class, function ($mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->with('00000000')
                ->andReturn(null);
        });

        expect(fn () => app(ValidateNCM::class)->execute('00000000', 'ncm'))
            ->toThrow(
                ValidationException::class, __('validation.ncm.not_found', ['value' => '00000000'])
            );

    });

    it('does not call the fetch fallback when ncm already exists locally', function () {
        NcmCode::factory()->create(['code' => '84713012']);

        $this->mock(FetchNcmFromReceita::class, function ($mock) {
            $mock->shouldNotReceive('execute', 'ncm');
        });

        app(ValidateNCM::class)->execute('84713012', 'ncm');
    });
});
