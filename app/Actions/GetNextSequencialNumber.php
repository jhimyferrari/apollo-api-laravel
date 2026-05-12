<?php

namespace App\Actions;

use App\Models\Organization;
use App\Models\SequencialNumber;

class GetNextSequencialNumber
{
    public function execute(Organization $organization, string $table)
    {
        $sequence = SequencialNumber::where('organization_id', $organization->id)
            ->where('table', $table)
            ->lockForUpdate()
            ->first();

        throw_if(
            empty($sequence),
            \RuntimeException::class,
            "Sequence '{$table}' not found for organization {$organization->id}."
        );
        $sequence->last_number++;
        $sequence->save();

        return $sequence->last_number;
    }
}
