<?php

use App\Enum\Status\SupplierStatus;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Exceptions\InvalidStatusException;
use App\Helpers\DocumentHelper;
use App\Helpers\Test\StateRegistrationHelper;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SupplierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->service = app(SupplierService::class);

});

describe('SupplierService', function () {
    describe('create', function () {
        it('should create a Supplier successfully', function () {
            $data = [
                'legal_name' => 'supplierLegalName',
                'trade_name' => 'supplierTradeName',
                'document' => fake()->cnpj(false),
            ];
            $supplier = $this->service->create($data, $this->user);

            expect($supplier)
                ->toBeInstanceOf(Supplier::class)
                ->legal_name->toBe($data['legal_name'])
                ->trade_name->toBe($data['trade_name'])

                ->document->toBe($data['document'])
                ->state_registration->toBeNull()
                ->email->toBeNull()
                ->phone->toBeNull();

        });

        it('should create a Supplier with optional values successfully', function () {
            $data = [
                'legal_name' => 'supplierLegalName',
                'trade_name' => 'supplierTradeName',
                'document' => fake()->cnpj(false),
                'state_registration' => $this->validIE(),
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
            ];
            $supplier = $this->service->create($data, $this->user);

            expect($supplier)
                ->toBeInstanceOf(Supplier::class)
                ->legal_name->toBe($data['legal_name'])
                ->trade_name->toBe($data['trade_name'])
                ->document->toBe($data['document'])
                ->state_registration->toBe($data['state_registration'])
                ->email->toBe($data['email'])
                ->phone->toBe($data['phone']);
        });
        it('should remove spaces from names', function () {
            $data = [
                'legal_name' => ' supplierLegalName ',
                'trade_name' => ' supplierTradeName ',
                'document' => fake()->cnpj(false),
            ];
            $supplier = $this->service->create($data, $this->user);
            expect($supplier)
                ->toBeInstanceOf(Supplier::class)
                ->legal_name->toBe('supplierLegalName')
                ->trade_name->toBe('supplierTradeName');
        });

        it('should remove pontuation from document', function () {
            $data = [
                'legal_name' => 'supplierLegalName',
                'trade_name' => 'supplierTradeName',
                'document' => fake()->cpf(true),
                'state_registration' => fake()->rg(true),
            ];
            $supplier = $this->service->create($data, $this->user);
            expect($supplier)
                ->toBeInstanceOf(Supplier::class)
                ->document->toBe(DocumentHelper::remove_pontuation($data['document']))
                ->state_registration->toBe(DocumentHelper::remove_pontuation($data['state_registration']));
        });

        it('throws an error when pass an invalid document ', function () {
            expect(fn () => $this->service->create([
                'legal_name' => 'invalidDocument',
                'trade_name' => 'invalidDocument',
                'document' => '111111111',
            ], $this->user))->toThrow(InvalidFieldException::class, 'Invalid document');
        });
        it('throws an error when pass an used document  ', function () {
            $document = Supplier::factory()->create(['organization_id' => $this->user->organization_id])->document;
            expect(fn () => $this->service->create([
                'legal_name' => 'invalidDocument',
                'trade_name' => 'invalidDocument',
                'document' => $document,
            ], $this->user))->toThrow(DuplicateFieldException::class, "Supplier document `$document` already exist");
        });
        it('throws an error when not pass a legal_name ', function () {
            expect(fn () => $this->service->create([
                'legal_name' => '',
                'trade_name' => 'trade_name',
                'document' => fake()->cnpj(),
            ], $this->user))->toThrow(InvalidFieldException::class, 'The field `legal_name` must have a value');
        });

        it('throws an error when not pass a trade_name ', function () {
            expect(fn () => $this->service->create([
                'legal_name' => 'some_legal_name',
                'trade_name' => '',
                'document' => fake()->cpf(),
            ], $this->user))->toThrow(InvalidFieldException::class, 'The field `trade_name` must have a value');
        });

        it('throws an error when not pass a document ', function () {
            expect(fn () => $this->service->create([
                'legal_name' => 'some_legal_name',
                'trade_name' => 'some_trade_name',
                'document' => '',
            ], $this->user))->toThrow(InvalidFieldException::class, 'The field `document` must have a value');
        });
    });
    describe('update', function () {
        it('should update a Supplier successfully', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $data =
                [
                    'status' => SupplierStatus::Active->value,
                    'document' => fake()->cnpj(false),
                    'legal_name' => fake()->company,
                    'trade_name' => fake()->name,
                    'state_registration' => StateRegistrationHelper::generateIE(),
                    'phone' => fake()->phoneNumber,
                    'email' => fake()->email,
                ];
            $supplier = $this->service->update(
                $supplier,
                $data
            );
            expect($supplier)
                ->toBeInstanceOf(Supplier::class)
                ->status->toBe($data['status'])
                ->document->toBe($data['document'])
                ->legal_name->toBe($data['legal_name'])
                ->trade_name->toBe($data['trade_name'])
                ->state_registration->toBe($data['state_registration'])
                ->phone->toBe($data['phone'])
                ->email->toBe($data['email']);

            $this->assertDatabaseHas('suppliers', $data);
        });
        it('should throw an error when pass an invalid status', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($supplier, ['status' => 'invalid_status']))->toThrow(InvalidStatusException::class);
        });
        it('should throw an error when pass an used document', function () {
            $document = Supplier::factory()->create(['organization_id' => $this->user->organization_id])->document;
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($supplier, ['document' => $document]))->toThrow(DuplicateFieldException::class);
        });
        it('should throw an error when pass an used state_registration', function () {
            $state_registration = Supplier::factory()->create(['organization_id' => $this->user->organization_id])->state_registration;
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            expect(fn () => $this->service->update($supplier, ['state_registration' => $state_registration]))->toThrow(DuplicateFieldException::class);
        });
        it('should throw an error when pass an invalid document', function () {

            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($supplier, ['document' => '111111111']))
                ->toThrow(InvalidFieldException::class, 'Invalid document');
        });

        it('should update successfully when passing the same document as before', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $document = $supplier->document;

            $updated = $this->service->update($supplier, ['document' => $document]);

            expect($updated)
                ->toBeInstanceOf(Supplier::class)
                ->document->toBe($document);
        });

        it('should update successfully when passing the same state_registration as before', function () {
            $supplier = Supplier::factory()->create([
                'organization_id' => $this->user->organization_id,
                'state_registration' => $this->validIE(),
            ]);
            $stateRegistration = $supplier->state_registration;

            $updated = $this->service->update($supplier, ['state_registration' => $stateRegistration]);

            expect($updated)
                ->toBeInstanceOf(Supplier::class)
                ->state_registration->toBe($stateRegistration);
        });

        it('should set state_registration to null when passing an empty value', function () {
            $supplier = Supplier::factory()->create([
                'organization_id' => $this->user->organization_id,
                'state_registration' => $this->validIE(),
            ]);

            $updated = $this->service->update($supplier, ['state_registration' => '   ']);

            expect($updated)
                ->toBeInstanceOf(Supplier::class)
                ->state_registration->toBeNull();
        });

        it('throws an error when pass an empty legal_name', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($supplier, ['legal_name' => '']))
                ->toThrow(InvalidFieldException::class, 'The field `legal_name` must have a value');
        });

        it('throws an error when pass an empty trade_name', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($supplier, ['trade_name' => '']))
                ->toThrow(InvalidFieldException::class, 'The field `trade_name` must have a value');
        });

        it('should update the phone and email of a supplier', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $data = [
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
            ];

            $updated = $this->service->update($supplier, $data);

            expect($updated)
                ->toBeInstanceOf(Supplier::class)
                ->phone->toBe($data['phone'])
                ->email->toBe($data['email']);
        });
    });
    describe('delete', function () {
        it('should delete some seller', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $this->service->delete($supplier);
            $this->assertSoftDeleted($supplier);
        });
    });
});
