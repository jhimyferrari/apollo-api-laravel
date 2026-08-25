<?php

use App\Exceptions\DuplicateFieldException;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->service = app(OrganizationService::class);
});
describe('OrganizationService', function () {
    describe('create', function () {
        it('should create a organization with a AdminUser successfully', function () {
            /** @var OrganizationService $this->service */
            $document = fake()->cnpj(false);
            [$organization, $adminUser] = $this->service->create([
                'name' => 'organizationName',
                'document' => $document,
                'email' => 'admin@organization.com',
                'password' => 'Str$on%gP4ssw0rd',
            ]);
            expect($organization)
                ->toBeInstanceOf(Organization::class)
                ->document->toBe($document);
            expect($adminUser)
                ->toBeInstanceOf(User::class)
                ->organization_id->toBe($organization->id);
        });

        it('should create password with hash', function () {
            /** @var OrganizationService $this->service */
            $document = fake()->cnpj(false);
            [$organization, $adminUser] = $this->service->create([
                'name' => 'organizationName',
                'document' => $document,
                'email' => 'admin@organization.com',
                'password' => 'Str$on%gP4ssw0rd',
            ]);
            expect(Hash::check('Str$on%gP4ssw0rd', $adminUser->password))->toBeTrue();
        });
        it('should remove spaces from a name', function () {
            [$organization, $df] = $this->service->create([
                'name' => ' nameWithSpaces ',
                'document' => fake()->cnpj,
                'email' => 'admin@organization.com',
                'password' => 'Str$on%gP4ssw0rd',
            ]);

            expect($organization)
                ->toBeInstanceOf(Organization::class)
                ->name->toBe('nameWithSpaces');
        });
        it('throw an exception when using an used document', function () {
            $document = Organization::factory()->create()->document;
            expect(fn () => $this->service->create([
                'name' => 'duplicatedDocument',
                'document' => $document,
                'email' => 'admin@organization.com',
                'password' => 'Str$on%gP4ssw0rd',
            ]))->toThrow(DuplicateFieldException::class, "Organization document `$document` already exist");
        });
    });

});
