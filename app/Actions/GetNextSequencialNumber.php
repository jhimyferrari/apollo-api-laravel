<?php

namespace App\Actions;

use App\Models\Organization;
use App\Models\SequencialNumber;
use RuntimeException;

class GetNextSequencialNumber
{
    public function execute(Organization $organization, string $table)
    {
        $sequence = SequencialNumber::where('organization_id', $organization->id)
            ->where('table', $table)
            ->lockForUpdate()
            ->first();

        if (empty($sequence)) {
            \report(new RuntimeException(__('errors.sequence_number_not_found', ['table' => $table, 'org' => $organization->id])));
            throw new RuntimeException(__('errors.internal_error'));
        }

        $sequence->last_number++;
        $sequence->save();

        return $sequence->last_number;
    }
}
