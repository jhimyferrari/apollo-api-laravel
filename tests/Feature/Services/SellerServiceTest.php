<?php

use App\Enum\Status\SellerStatus;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Exceptions\InvalidStatusException;
use App\Helpers\DocumentHelper;
use App\Helpers\Test\StateRegistrationHelper;
use App\Models\Seller;
use App\Models\User;
use App\Services\SellerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->service = app(SellerService::class);

});

describe('SellerService', function () {
    describe('create', function () {
        it('should create a Seller successfully', function () {
            $data = [
                'legal_name' => 'sellerLegalName',
                'trade_name' => 'sellerTradeName',
                'document' => fake()->cnpj(false),
            ];
            $seller = $this->service->create($data, $this->user);

            expect($seller)
                ->toBeInstanceOf(Seller::class)
                ->legal_name->toBe($data['legal_name'])
                ->trade_name->toBe($data['trade_name'])

                ->document->toBe($data['document'])
                ->state_registration->toBeNull()
                ->email->toBeNull()
                ->phone->toBeNull();

        });

        it('should create a Seller with optional values successfully', function () {
            $data = [
                'legal_name' => 'sellerLegalName',
                'trade_name' => 'sellerTradeName',
                'document' => fake()->cnpj(false),
                'state_registration' => $this->validIE(),
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
            ];
            $seller = $this->service->create($data, $this->user);

            expect($seller)
                ->toBeInstanceOf(Seller::class)
                ->legal_name->toBe($data['legal_name'])
                ->trade_name->toBe($data['trade_name'])
                ->document->toBe($data['document'])
                ->state_registration->toBe($data['state_registration'])
                ->email->toBe($data['email'])
                ->phone->toBe($data['phone']);
        });
        it('should remove spaces from names', function () {
            $data = [
                'legal_name' => ' sellerLegalName ',
                'trade_name' => ' sellerTradeName ',
                'document' => fake()->cnpj(false),
            ];
            $seller = $this->service->create($data, $this->user);
            expect($seller)
                ->toBeInstanceOf(Seller::class)
                ->legal_name->toBe('sellerLegalName')
                ->trade_name->toBe('sellerTradeName');
        });

        it('should remove pontuation from document', function () {
            $data = [
                'legal_name' => 'sellerLegalName',
                'trade_name' => 'sellerTradeName',
                'document' => fake()->cpf(true),
                'state_registration' => fake()->rg(true),
            ];
            $seller = $this->service->create($data, $this->user);
            expect($seller)
                ->toBeInstanceOf(Seller::class)
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
            $document = Seller::factory()->create(['organization_id' => $this->user->organization_id])->document;
            expect(fn () => $this->service->create([
                'legal_name' => 'invalidDocument',
                'trade_name' => 'invalidDocument',
                'document' => $document,
            ], $this->user))->toThrow(DuplicateFieldException::class, "Seller document `$document` already exist");
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
        it('should update a Seller successfully', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $data =
                [
                    'status' => SellerStatus::Active->value,
                    'document' => fake()->cnpj(false),
                    'legal_name' => fake()->company,
                    'trade_name' => fake()->name,
                    'state_registration' => StateRegistrationHelper::generateIE(),
                    'phone' => fake()->phoneNumber,
                    'email' => fake()->email,
                ];
            $seller = $this->service->update(
                $seller,
                $data
            );
            expect($seller)
                ->toBeInstanceOf(Seller::class)
                ->status->toBe($data['status'])
                ->document->toBe($data['document'])
                ->legal_name->toBe($data['legal_name'])
                ->trade_name->toBe($data['trade_name'])
                ->state_registration->toBe($data['state_registration'])
                ->phone->toBe($data['phone'])
                ->email->toBe($data['email']);

            $this->assertDatabaseHas('sellers', $data);
        });

        it('should throw an error when pass an invalid status', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            expect(fn () => $this->service->update($seller, ['status' => 'invalid_status']))->toThrow(InvalidStatusException::class);
        });
        it('should throw an error when pass an used document', function () {
            $document = Seller::factory()->create(['organization_id' => $this->user->organization_id])->document;
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($seller, ['document' => $document]))->toThrow(DuplicateFieldException::class);
        });
        it('should throw an error when pass an used state_registration', function () {
            $state_registration = Seller::factory()->create(['organization_id' => $this->user->organization_id])->state_registration;
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            expect(fn () => $this->service->update($seller, ['state_registration' => $state_registration]))->toThrow(DuplicateFieldException::class);
        });
        it('should throw an error when pass an invalid document', function () {

            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($seller, ['document' => '111111111']))
                ->toThrow(InvalidFieldException::class, 'Invalid document');
        });

        it('should update successfully when passing the same document as before', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $document = $seller->document;

            $updated = $this->service->update($seller, ['document' => $document]);

            expect($updated)
                ->toBeInstanceOf(Seller::class)
                ->document->toBe($document);
        });

        it('should update successfully when passing the same state_registration as before', function () {
            $seller = Seller::factory()->create([
                'organization_id' => $this->user->organization_id,
                'state_registration' => $this->validIE(),
            ]);
            $stateRegistration = $seller->state_registration;

            $updated = $this->service->update($seller, ['state_registration' => $stateRegistration]);

            expect($updated)
                ->toBeInstanceOf(Seller::class)
                ->state_registration->toBe($stateRegistration);
        });

        it('should set state_registration to null when passing an empty value', function () {
            $seller = Seller::factory()->create([
                'organization_id' => $this->user->organization_id,
                'state_registration' => $this->validIE(),
            ]);

            $updated = $this->service->update($seller, ['state_registration' => '   ']);

            expect($updated)
                ->toBeInstanceOf(Seller::class)
                ->state_registration->toBeNull();
        });

        it('throws an error when pass an empty legal_name', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($seller, ['legal_name' => '']))
                ->toThrow(InvalidFieldException::class, 'The field `legal_name` must have a value');
        });

        it('throws an error when pass an empty trade_name', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($seller, ['trade_name' => '']))
                ->toThrow(InvalidFieldException::class, 'The field `trade_name` must have a value');
        });

        it('should update the phone and email of a seller', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $data = [
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
            ];

            $updated = $this->service->update($seller, $data);

            expect($updated)
                ->toBeInstanceOf(Seller::class)
                ->phone->toBe($data['phone'])
                ->email->toBe($data['email']);
        });
    });
    describe('delete', function () {
        it('should delete some seller', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $this->service->delete($seller);
            $this->assertSoftDeleted($seller);
        });
    });

});
