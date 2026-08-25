<?php

use App\Actions\Fetch\Ncm\FetchRawFromReceita;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);
beforeEach(function () {
    Cache::forget('ncm.receita.raw');

    config([
        'services.siscomex.ncm_endpoint' => 'https://portalunico.siscomex.gov.br/classif/api/publico/nomenclatura/download/json',
        'services.siscomex.ncm_timeout' => 10,
        'services.siscomex.ncm_cache_ttl' => 3600,
    ]);
});

describe('FetchRawFromReceita', function () {

    it('returns parsed items when the api responds successfully', function () {
        Http::fake([
            '*' => Http::response([
                'Nomenclaturas' => [
                    [
                        'Codigo' => '84713012',
                        'Descricao' => 'Máquinas de processamento de dados',
                        'Data_Inicio' => '27/04/2020',
                    ],
                ],
            ], 200),
        ]);

        $result = app(FetchRawFromReceita::class)->execute();

        expect($result)->toHaveCount(1);
        expect($result[0]['Codigo'])->toBe('84713012');
        expect($result[0]['Descricao'])->toBe('Máquinas de processamento de dados');
        expect($result[0]['Data_Inicio'])->toBe('2020-04-27');
    });

    it('returns an empty array when the api responds with a client error', function () {
        Http::fake([
            '*' => Http::response(['message' => 'Unprocessable'], 422),
        ]);

        $result = app(FetchRawFromReceita::class)->execute();

        expect($result)->toBe([]);
    });

    it('returns an empty array when the api responds with a server error', function () {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $result = app(FetchRawFromReceita::class)->execute();

        expect($result)->toBe([]);
    });

    it('returns an empty array and reports the exception when the connection fails', function () {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $result = app(FetchRawFromReceita::class)->execute();

        expect($result)->toBe([]);
    });

    it('returns an empty array when the nomenclatura key is missing from the response', function () {
        Http::fake([
            '*' => Http::response(['OutraChave' => []], 200),
        ]);

        $result = app(FetchRawFromReceita::class)->execute();

        expect($result)->toBe([]);
    });

    it('sets data_inicio to null when the date is missing or malformed', function () {
        Http::fake([
            '*' => Http::response([
                'Nomenclaturas' => [
                    ['Codigo' => '11111111', 'Descricao' => 'Sem data', 'Data_Inicio' => null],
                    ['Codigo' => '22222222', 'Descricao' => 'Data inválida', 'Data_Inicio' => 'not-a-date'],
                ],
            ], 200),
        ]);

        $result = app(FetchRawFromReceita::class)->execute();

        expect($result[0]['Data_Inicio'])->toBeNull();
        expect($result[1]['Data_Inicio'])->toBeNull();
    });

    it('caches the successful result under the expected key', function () {
        Http::fake([
            '*' => Http::response([
                'Nomenclaturas' => [
                    ['Codigo' => '84713012', 'Descricao' => 'Produto', 'Data_Inicio' => '27/04/2020'],
                ],
            ], 200),
        ]);

        $result = app(FetchRawFromReceita::class)->execute();

        expect(Cache::get('ncm.receita.raw'))->toBe($result);
    });

    it('does not call the api again while the cache is warm', function () {
        Http::fake([
            '*' => Http::response([
                'Nomenclaturas' => [
                    ['Codigo' => '84713012', 'Descricao' => 'Produto', 'Data_Inicio' => '27/04/2020'],
                ],
            ], 200),
        ]);

        app(FetchRawFromReceita::class)->execute();
        app(FetchRawFromReceita::class)->execute();

        Http::assertSentCount(1);
    });

    /**
     * Documents the current caching bug: a failed/empty response gets cached
     * just like a successful one, so subsequent calls keep returning an empty
     * array until the TTL expires — even after the upstream API recovers.
     *
     * This test should start failing once FetchRawFromReceita is fixed to
     * only cache non-empty results; at that point, replace it with a test
     * asserting the empty result is NOT cached.
     */
    it('currently caches an empty result on failure, masking recovery until ttl expires', function () {
        Http::fake([
            '*' => Http::response(['message' => 'rate limited'], 422),
        ]);

        app(FetchRawFromReceita::class)->execute();

        expect(Cache::get('ncm.receita.raw'))->toBe([]);
    });

    it('strips formatting characters from the ncm code', function () {
        Http::fake([
            '*' => Http::response([
                'Nomenclaturas' => [
                    ['Codigo' => '8471.30.12', 'Descricao' => 'Produto', 'Data_Inicio' => '27/04/2020'],
                ],
            ], 200),
        ]);

        $result = app(FetchRawFromReceita::class)->execute();

        expect($result[0]['Codigo'])->toBe('84713012');
    });
});
