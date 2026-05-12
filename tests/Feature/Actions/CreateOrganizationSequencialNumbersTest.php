<?php

use App\Actions\CreateOrganizationSequencialNumbers;
use App\Models\Organization;
use App\Models\SequencialNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
describe('CreateOrganizationSequencialNumbers', function () {
    it('should create sequential numbers for all default tables', function () {
        $organization = Organization::factory()->create();

        SequencialNumber::query()->delete();

        (new CreateOrganizationSequencialNumbers)->execute($organization);

        expect(SequencialNumber::count())->toBe(CreateOrganizationSequencialNumbers::count())
            ->and(SequencialNumber::pluck('table')->toArray())
            ->toEqual(array_column(CreateOrganizationSequencialNumbers::tables(), 'table'));
    });

    it('should create sequential numbers only for specified tables existed on constant', function () {
        $organization = Organization::factory()->create();

        SequencialNumber::query()->delete();

        $table = CreateOrganizationSequencialNumbers::tables()[0];
        (new CreateOrganizationSequencialNumbers)->execute($organization, [$table]);

        expect(SequencialNumber::count())->toBe(1)
            ->and(SequencialNumber::first()->table)->toBe($table['table']);
    });

    it('should create sequential numbers with last_number starting at zero', function () {
        $organization = Organization::factory()->create();

        SequencialNumber::query()->delete();

        (new CreateOrganizationSequencialNumbers)->execute($organization);

        expect(SequencialNumber::pluck('last_number')->toArray())
            ->each->toBe(0);
    });

    it('should not create sequential numbers for tables non existed on constant', function () {
        $organization = Organization::factory()->create();

        SequencialNumber::query()->delete();

        (new CreateOrganizationSequencialNumbers)->execute($organization, ['non_existed_table']);

        expect(SequencialNumber::count())->toBe(0);
    });
});
