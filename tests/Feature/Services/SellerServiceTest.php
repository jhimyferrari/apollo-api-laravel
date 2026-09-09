<?php

use App\Enum\Status\SellerStatus;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Exceptions\InvalidStatusException;
use App\Helpers\DocumentHelper;
use App\Helpers\Test\StateRegistrationHelper;
use App\Models\Address;
use App\Models\City;
use App\Models\Seller;
use App\Models\User;
use App\Services\SellerService;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
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

        it('should update the phone and email of a seller to null', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $data = [
                'phone' => null,
                'email' => null,
            ];

            $updated = $this->service->update($seller, $data);

            expect($updated)
                ->toBeInstanceOf(Seller::class)
                ->phone->toBeNull()
                ->email->toBeNull();
        });
    });
    describe('delete', function () {
        it('should delete some seller', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $this->service->delete($seller);
            $this->assertSoftDeleted($seller);
        });
    });

    describe('address', function () {
        beforeEach(function () {
            $this->seed(UfSeeder::class);
            new CitiesSeeder()->run(2);
        });
        describe('createAddress', function () {
            it('should create and add an address to a seller', function () {
                $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
                $city_ibge_code = City::first()->ibge_code;
                $data = [
                    'street' => 'Rua A',
                    'number' => '213',
                    'neighborhood' => 'centro',
                    'cep' => '87500000',
                    'is_default' => false,
                    'city_ibge_code' => $city_ibge_code,
                ];
                $address = $this->service->createAddress($seller, $data);

                expect($address)
                    ->toBeInstanceOf(Address::class)
                    ->street->toBe($data['street'])
                    ->number->toBe($data['number'])
                    ->neighborhood->toBe($data['neighborhood'])
                    ->cep->toBe($data['cep'])
                    ->is_default->toBeFalse()
                    ->city->ibge_code->toBe($city_ibge_code)
                    ->addressable->id->toBe($seller->id);
            });

            it('should create add 2 address and change default', function () {
                $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
                $city_ibge_code = City::first()->ibge_code;
                $oldAddress = $seller->addresses()->create(Address::factory()->turnDefault()->make(['organization_id' => $this->user->organization_id])->toArray());
                $data = [
                    'street' => 'Rua A',
                    'number' => '213',
                    'neighborhood' => 'centro',
                    'cep' => '87500000',
                    'is_default' => true,
                    'city_ibge_code' => $city_ibge_code,
                ];
                $address = $this->service->createAddress($seller, $data);

                $this->assertDatabaseCount('addresses', 2);
                expect($address)
                    ->toBeInstanceOf(Address::class)
                    ->street->toBe($data['street'])
                    ->number->toBe($data['number'])
                    ->neighborhood->toBe($data['neighborhood'])
                    ->cep->toBe($data['cep'])
                    ->is_default->toBeTrue()
                    ->city->ibge_code->toBe($city_ibge_code)
                    ->addressable_type->toBe(Seller::class)
                    ->addressable_id->toBe($seller->id);

                $oldAddress->refresh();

                expect($oldAddress)->is_default->toBeFalse();

                expect(
                    Address::query()
                        ->where('addressable_id', $seller->id)
                        ->where('is_default', true)
                        ->count()
                )->toBe(1);

                expect($seller->defaultAddress)->id->toBe($address->id);

            });
        });
        describe('setDefaultAddress', function () {

            it('should turn an Address to default', function () {
                $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
                $address = $seller->addresses()->create(Address::factory()->for($this->user->organization)->make()->toArray());
                $this->service->setDefaultAddress($seller, $address);
                $address->refresh();
                expect($address)->is_default->toBeTrue();
            });

            it('should turn false a default address after turn default other  ', function () {
                $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
                $addressOld = $seller->addresses()->create(Address::factory()->turnDefault()->make(['organization_id' => $this->user->organization_id])->toArray());
                $address = $seller->addresses()->create(Address::factory()->for($this->user->organization)->make()->toArray());

                $this->service->setDefaultAddress($seller, $address);
                $address->refresh();
                expect($address)->is_default->toBeTrue();

                $addressOld->refresh();

                expect($addressOld)->is_default->toBeFalse();

            });
        });
    });

});
