<?php

namespace App\Jobs;

use App\Actions\Fetch\Ncm\FetchRawFromReceita;
use App\Models\NcmCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncNcmTableJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FetchRawFromReceita $fetchRaw): void
    {

        $ncmsRaw = $fetchRaw->execute();
        if (empty($ncmsRaw)) {
            return;
        }
        $codeCollection = collect($ncmsRaw)->pluck('Codigo');
        collect($ncmsRaw)->chunk(500)->each(function ($chunk) {

            $rows = $chunk->map(fn ($item) => [
                'code' => $item['Codigo'],
                'description' => $item['Descricao'],
                'valid_from' => $item['Data_Inicio'],
                'isActive' => true]
            )->toArray();
            NcmCode::upsert($rows, ['code'],
                ['description', 'valid_from', 'isActive',
                ]);
        });

        NcmCode::whereNotIn('code', $codeCollection)->update(['isActive' => false]);

    }
}
