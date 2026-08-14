<?php

namespace App\Actions\Fetch\Ncm;

use Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class FetchRawFromReceita
{
    public function execute(): array
    {
        $endpoint = config('services.siscomex.ncm_endpoint');
        $ttl = config('services.siscomex.ncm_cache_ttl');

        return Cache::remember('ncm.receita.raw', $ttl, function () use ($endpoint) {
            try {
                $response = Http::timeout(config('services.siscomex.ncm_timeout'))->get($endpoint);
            } catch (ConnectionException $e) {
                report($e);

                return [];
            }
            if ($response->failed()) {
                return [];
            }

            $items = $response->json('Nomenclaturas') ?? [];

            return collect($items)
                ->map(fn ($item) => [
                    ...$item,
                    'Codigo' => $this->normalizeCode($item['Codigo']),
                    'Data_Inicio' => $this->parseDate($item['Data_Inicio'] ?? null),
                ])
                ->toArray();
        });
    }

    private function parseDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizeCode(string $code): string
    {
        return preg_replace('/\D/', '', $code);
    }
}
