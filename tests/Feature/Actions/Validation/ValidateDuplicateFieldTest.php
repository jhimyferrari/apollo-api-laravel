<?php

use App\Actions\Validation\ValidateDuplicateField;
use App\Exceptions\DuplicateFieldException;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Uf;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
describe('ValidateDuplicateField', function () {
    it('throw an error when pass a non existed field', function () {
        expect(fn () => app(ValidateDuplicateField::class)
            ->execute(new Client, 'non_existedField', 'value'))
            ->toThrow(RuntimeException::class, 'Attribute `non_existedField` does not exist in Client');
    });
    it('throw an error when pass a organization_id when does not exist in model', function () {
        expect(fn () => app(ValidateDuplicateField::class)
            ->execute(new Uf, 'name', 'value', null, '1'))
            ->toThrow(RuntimeException::class, 'Attribute `organization_id` does not exist in Uf');
    });

    it('throw an error when pass duplicated values', function () {
        $organization = Organization::factory()->create();
        expect(fn () => app(ValidateDuplicateField::class)
            ->execute(new Organization, 'document', $organization->document))
            ->toThrow(DuplicateFieldException::class, 'Organization document `'.$organization->document.'` already exist');
    });
    it('not throw an error when using unique value  ', function () {
        expect(fn () => app(ValidateDuplicateField::class)
            ->execute(new Client, 'document', '12345678'))
            ->not
            ->toThrow(DuplicateFieldException::class);
    });
    it('not throw an error when using duplicated values from differents organization', function () {
        $organization = Organization::factory()->create();
        $document = Client::factory()->create()->document;
        expect(fn () => app(ValidateDuplicateField::class)
            ->execute(new Client, 'document', $document, null, $organization->id))
            ->not

            ->toThrow(DuplicateFieldException::class);
    });
    it('throw an error when using duplicated values from same organizations  ', function () {
        $client = Client::factory()->create();
        expect(fn () => app(ValidateDuplicateField::class)
            ->execute(new Client, 'document', $client->document, $client->organization_id))
            ->toThrow(DuplicateFieldException::class);
    });
});
