
<?php

use App\Actions\Fetch\Ncm\FetchRawFromReceita;
use App\Jobs\SyncNcmTableJob;
use App\Models\NcmCode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function runSyncJob(): void
{
    app()->call([app(SyncNcmTableJob::class), 'handle']);
}
describe('SyncNcmTableJob', function () {

    it('does nothing when receita returns an empty payload', function () {
        NcmCode::factory()->create(['code' => '84713012', 'isActive' => true]);

        $this->mock(FetchRawFromReceita::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->andReturn([]);
        });

        runSyncJob();
        expect(NcmCode::where('code', '84713012')->first()->isActive)->toBeTrue();
        expect(NcmCode::count())->toBe(1);
    });

    it('creates new ncm codes that do not exist locally', function () {
        $this->mock(FetchRawFromReceita::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->andReturn([
                ['Codigo' => '84713012', 'Descricao' => 'Máquinas de processamento de dados', 'Data_Inicio' => '2020/04/27'],
                ['Codigo' => '99999999', 'Descricao' => 'Produto de teste', 'Data_Inicio' => '2018/12/18'],
            ]);
        });

        runSyncJob();
        $this->assertDatabaseHas('ncm_codes', [
            'code' => '84713012',
            'description' => 'Máquinas de processamento de dados',
            'isActive' => true,
            'valid_from' => '2020/04/27',
        ]);
        $this->assertDatabaseHas('ncm_codes', [
            'code' => '99999999',
            'description' => 'Produto de teste',
            'isActive' => true,
            'valid_from' => '2018/12/18',
        ]);
    });

    it('updates the description of an existing ncm code', function () {
        NcmCode::factory()->create([
            'code' => '84713012',
            'description' => 'Descrição antiga',

        ]);

        $this->mock(FetchRawFromReceita::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->andReturn([
                ['Codigo' => '84713012', 'Descricao' => 'Descrição atualizada', 'Data_Inicio' => '2023/02/23'],
            ]);
        });

        runSyncJob();
        expect(NcmCode::where('code', '84713012')->first()->description)
            ->toBe('Descrição atualizada');
    });

    it('reactivates a locally inactive ncm code that reappears in receita', function () {
        NcmCode::factory()->create([
            'code' => '84713012',
            'isActive' => false,
        ]);

        $this->mock(FetchRawFromReceita::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->andReturn([
                ['Codigo' => '84713012', 'Descricao' => 'Voltou a existir', 'Data_Inicio' => '2024/04/24'],
            ]);
        });

        runSyncJob();
        expect(NcmCode::where('code', '84713012')->first()->isActive)->toBeTrue();
    });

    it('marks as inactive local codes that no longer come from receita', function () {
        NcmCode::factory()->create(['code' => '11111111', 'isActive' => true]);

        $this->mock(FetchRawFromReceita::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->andReturn([
                ['Codigo' => '22222222', 'Descricao' => 'Novo código', 'Data_Inicio' => '2013/03/13'],
            ]);
        });

        runSyncJob();
        expect(NcmCode::where('code', '11111111')->first()->isActive)->toBeFalse();
    });

    it('does not touch codes still present in receita', function () {
        NcmCode::factory()->create([
            'code' => '84713012',
            'description' => 'Original',
            'isActive' => true,
        ]);

        $this->mock(FetchRawFromReceita::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->andReturn([
                ['Codigo' => '84713012', 'Descricao' => 'Original', 'Data_Inicio' => '2013/03/13'],
            ]);
        });

        runSyncJob();
        expect(NcmCode::where('code', '84713012')->first()->isActive)->toBeTrue();
    });

    it('processes large payloads in chunks without errors', function () {
        $nomenclaturas = collect(range(1, 1200))->map(fn ($i) => [
            'Codigo' => str_pad((string) $i, 8, '0', STR_PAD_LEFT),
            'Descricao' => "Produto {$i}",
            'Data_Inicio' => fake()->date,
        ])->toArray();

        $this->mock(FetchRawFromReceita::class, function ($mock) use ($nomenclaturas) {
            $mock->shouldReceive('execute')->once()->andReturn($nomenclaturas);
        });

        runSyncJob();
        expect(NcmCode::count())->toBe(1200);
    });
});
