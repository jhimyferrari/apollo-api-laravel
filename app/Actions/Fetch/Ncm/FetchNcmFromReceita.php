<?php

namespace App\Actions\Fetch\Ncm;

use App\Models\NcmCode;

class FetchNcmFromReceita
{
    public function __construct(
        private readonly FetchRawFromReceita $fetchRaw
    ) {}

    public function execute(string $ncm): ?NcmCode
    {

        $match = collect($this->fetchRaw->execute())->firstWhere('Codigo', $ncm);

        if (! $match) {
            return null;
        }

        return NcmCode::updateOrCreate(
            ['code' => $match['Codigo']],
            ['description' => $match['Descricao'],
                'valid_from' => $match['Data_Inicio'],
                'isActive' => true]
        );
    }
}
