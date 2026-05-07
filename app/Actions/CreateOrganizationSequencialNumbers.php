<?php

namespace App\Actions;

use App\Models\Organization;

class CreateOrganizationSequencialNumbers
{
    private const array TABLES = [
        ['table' => 'clients'],
        ['table' => 'sellers'],
    ];

    public function execute(Organization $organization, ?array $options = null): void
    {
        $tables = self::TABLES;
        if (isset($options)) {
            $tables = array_filter($tables, fn ($n) => \in_array($n, $options));
        }

        $organization->sequencialNumber()->createMany($tables);

    }
}
