<?php

use App\Actions\GetNextSequencialNumber;
use App\Models\Organization;
use App\Models\SequencialNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
describe('GetNextSequencialNumber', function () {
    it('should increment and returns the next number', function () {
        $organization = Organization::factory()->create();
        SequencialNumber::factory()->create([
            'organization_id' => $organization->id,
            'table' => 'test',
        ]);

        $result = (new GetNextSequencialNumber)->execute($organization, 'test');
        expect($result)->toBe(1)
            ->and(SequencialNumber::where('table', 'test')->first()->last_number)->toBe(1);

        SequencialNumber::factory()->create([
            'organization_id' => $organization->id,
            'table' => 'test2',
            'last_number' => '2',
        ]);

        $result = (new GetNextSequencialNumber)->execute($organization, 'test2');
        expect($result)->toBe(3)
            ->and(SequencialNumber::where('table', 'test2')->first()->last_number)->toBe(3);

    });

    it('throws RuntimeException when sequence does not exist', function () {
        $organization = Organization::factory()->create();

        expect(fn () => (new GetNextSequencialNumber)->execute($organization, 'non_created_table'))
            ->toThrow(
                RuntimeException::class,
                "Sequence 'non_created_table' not found for organization {$organization->id}."
            );
    });
});
