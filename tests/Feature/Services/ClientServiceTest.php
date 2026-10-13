<?php

use App\Enum\Status\ClientStatus;
use App\Helpers\DocumentHelper;
use App\Helpers\Test\StateRegistrationHelper;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->service = app(ClientService::class);

});

describe('ClientService', function () {
    describe('create', function () {
        it('should create a Client successfully', function () {
            $data = [
                'legal_name' => 'clientLegalName',
                'trade_name' => 'clientTradeName',
                'document' => fake()->cnpj(false),
            ];
            $client = $this->service->create($data, $this->user);

            expect($client)
                ->toBeInstanceOf(Client::class)
                ->legal_name->toBe($data['legal_name'])
                ->trade_name->toBe($data['trade_name'])

                ->document->toBe($data['document'])
                ->state_registration->toBeNull()
                ->email->toBeNull()
                ->phone->toBeNull();

        });

        it('should create a Client with optional values successfully', function () {
            $data = [
                'legal_name' => 'clientLegalName',
                'trade_name' => 'clientTradeName',
                'document' => fake()->cnpj(false),
                'state_registration' => $this->validIE(),
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
            ];
            $client = $this->service->create($data, $this->user);

            expect($client)
                ->toBeInstanceOf(Client::class)
                ->legal_name->toBe($data['legal_name'])
                ->trade_name->toBe($data['trade_name'])
                ->document->toBe($data['document'])
                ->state_registration->toBe($data['state_registration'])
                ->email->toBe($data['email'])
                ->phone->toBe($data['phone']);
        });
        it('should remove spaces from names', function () {
            $data = [
                'legal_name' => ' clientLegalName ',
                'trade_name' => ' clientTradeName ',
                'document' => fake()->cnpj(false),
            ];
            $client = $this->service->create($data, $this->user);
            expect($client)
                ->toBeInstanceOf(Client::class)
                ->legal_name->toBe('clientLegalName')
                ->trade_name->toBe('clientTradeName');
        });

        it('should remove pontuation from document', function () {
            $data = [
                'legal_name' => 'clientLegalName',
                'trade_name' => 'clientTradeName',
                'document' => fake()->cpf(true),
                'state_registration' => fake()->rg(true),
            ];
            $client = $this->service->create($data, $this->user);
            expect($client)
                ->toBeInstanceOf(Client::class)
                ->document->toBe(DocumentHelper::remove_pontuation($data['document']))
                ->state_registration->toBe(DocumentHelper::remove_pontuation($data['state_registration']));
        });

        it('throws an error when pass an invalid document ', function () {
            expect(fn () => $this->service->create([
                'legal_name' => 'invalidDocument',
                'trade_name' => 'invalidDocument',
                'document' => '111111111',
            ], $this->user))->toThrow(ValidationException::class, __('validation.document', ['attribute' => '111111111']));
        });
        it('throws an error when pass an used document  ', function () {
            $document = Client::factory()->create(['organization_id' => $this->user->organization_id])->document;
            expect(fn () => $this->service->create([
                'legal_name' => 'invalidDocument',
                'trade_name' => 'invalidDocument',
                'document' => $document,
            ], $this->user))
                ->toThrow(ValidationException::class, __('validation.unique', ['attribute' => 'documento']));
        });
        it('throws an error when not pass a legal_name ', function () {
            expect(fn () => $this->service->create([
                'legal_name' => '',
                'trade_name' => 'trade_name',
                'document' => fake()->cnpj(),
            ], $this->user))->toThrow(ValidationException::class, __('validation.required', ['attribute' => __('validation.attributes.legal_name')]));
        });

        it('throws an error when not pass a trade_name ', function () {
            expect(fn () => $this->service->create([
                'legal_name' => 'some_legal_name',
                'trade_name' => '',
                'document' => fake()->cpf(),
            ], $this->user))->toThrow(ValidationException::class, __('validation.required', ['attribute' => __('validation.attributes.trade_name')]));
        });

        it('throws an error when not pass a document ', function () {
            expect(fn () => $this->service->create([
                'legal_name' => 'some_legal_name',
                'trade_name' => 'some_trade_name',
                'document' => '',
            ], $this->user))->toThrow(ValidationException::class, __('validation.required', ['attribute' => __('validation.attributes.document')]));
        });
    });
    describe('update', function () {
        it('should update a Client successfully', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $data =
                [
                    'status' => ClientStatus::Active->value,
                    'document' => fake()->cnpj(false),
                    'legal_name' => fake()->company,
                    'trade_name' => fake()->name,
                    'state_registration' => StateRegistrationHelper::generateIE(),
                    'phone' => fake()->phoneNumber,
                    'email' => fake()->email,
                ];
            $client = $this->service->update(
                $client,
                $data
            );
            expect($client)
                ->toBeInstanceOf(Client::class)
                ->status->toBe(ClientStatus::Active)
                ->document->toBe($data['document'])
                ->legal_name->toBe($data['legal_name'])
                ->trade_name->toBe($data['trade_name'])
                ->state_registration->toBe($data['state_registration'])
                ->phone->toBe($data['phone'])
                ->email->toBe($data['email']);

            $this->assertDatabaseHas('clients', $data);
        });
        it('should throw an error when pass an invalid status', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            expect(fn () => $this->service->update($client, ['status' => 'invalid_status']))->toThrow(ValidationException::class);
        });
        it('should throw an error when pass an used document', function () {
            $document = Client::factory()->create(['organization_id' => $this->user->organization_id])->document;
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($client, ['document' => $document]))->toThrow(ValidationException::class);
        });

        it('should throw an error when pass an used state_registration', function () {
            $state_registration = Client::factory()->create(['organization_id' => $this->user->organization_id])->state_registration;
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($client, ['state_registration' => $state_registration]))->toThrow(ValidationException::class);
        });
        it('should throw an error when pass an invalid document', function () {

            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($client, ['document' => '111111111']))
                ->toThrow(ValidationException::class, __('validation.document', ['attribute' => '111111111']));

        });

        it('should update successfully when passing the same document as before', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $document = $client->document;

            $updated = $this->service->update($client, ['document' => $document]);

            expect($updated)
                ->toBeInstanceOf(Client::class)
                ->document->toBe($document);
        });

        it('should update successfully when passing the same state_registration as before', function () {
            $client = Client::factory()->create([
                'organization_id' => $this->user->organization_id,
                'state_registration' => $this->validIE(),
            ]);
            $stateRegistration = $client->state_registration;

            $updated = $this->service->update($client, ['state_registration' => $stateRegistration]);

            expect($updated)
                ->toBeInstanceOf(Client::class)
                ->state_registration->toBe($stateRegistration);
        });

        it('should set state_registration to null when passing an empty value', function () {
            $client = Client::factory()->create([
                'organization_id' => $this->user->organization_id,
                'state_registration' => $this->validIE(),
            ]);

            $updated = $this->service->update($client, ['state_registration' => '   ']);

            expect($updated)
                ->toBeInstanceOf(Client::class)
                ->state_registration->toBeNull();
        });

        it('throws an error when pass an empty legal_name', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($client, ['legal_name' => '']))
                ->toThrow(ValidationException::class, __('validation.required', ['attribute' => __('validation.attributes.legal_name')]));
        });

        it('throws an error when pass an empty trade_name', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($client, ['trade_name' => '']))
                ->toThrow(ValidationException::class, __('validation.required', ['attribute' => __('validation.attributes.trade_name')]));
        });

        it('should update the phone and email of a client', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $data = [
                'phone' => fake()->phoneNumber,
                'email' => fake()->email,
            ];

            $updated = $this->service->update($client, $data);

            expect($updated)
                ->toBeInstanceOf(Client::class)
                ->phone->toBe($data['phone'])
                ->email->toBe($data['email']);
        });
    });
    describe('delete', function () {
        it('should delete some client', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $this->service->delete($client);
            $this->assertSoftDeleted($client);
        });
    });

});
