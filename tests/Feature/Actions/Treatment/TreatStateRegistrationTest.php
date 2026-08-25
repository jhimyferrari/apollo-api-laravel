<?php

use App\Actions\Treatment\TreatStateRegistration;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Helpers\DocumentHelper;
use App\Models\Client;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('TreatStateRegistration', function () {
    it('should return null when passing an empty value', function () {
        $result = app(TreatStateRegistration::class)->execute(
            model: new Seller,
            field: 'state_registration',
            value: '',
            mustBeNotNull: false,
            mustBeUnique: false
        );

        expect($result)->toBeNull();
    });

    it('should return null when passing a value with only spaces', function () {
        $result = app(TreatStateRegistration::class)->execute(
            model: new Client,
            field: 'state_registration',
            value: ' ',
            mustBeNotNull: false,
            mustBeUnique: false
        );

        expect($result)->toBeNull();
    });

    it('should remove pontuation and return a clean value', function () {
        $stateRegistration = fake()->rg(true);

        $result = app(TreatStateRegistration::class)->execute(
            model: new Seller,
            field: 'state_registration',
            value: $stateRegistration,
            mustBeNotNull: false,
            mustBeUnique: false
        );

        expect($result)->toBe(DocumentHelper::remove_pontuation($stateRegistration));
    });

    it('should return a clean value when it does not have pontuation', function () {
        $stateRegistration = fake()->rg(false);

        $result = app(TreatStateRegistration::class)->execute(
            model: new Seller,
            field: 'state_registration',
            value: $stateRegistration,
            mustBeNotNull: false,
            mustBeUnique: false
        );

        expect($result)->toBe($stateRegistration);
    });

    it('throw an error when the state_registration already exists', function () {
        $seller = Seller::factory()->create(['state_registration' => $this->validIE()]);

        expect(fn () => app(TreatStateRegistration::class)->execute(
            model: new Seller,
            field: 'state_registration',
            value: $seller->state_registration,
            mustBeUnique: true,
            mustBeNotNull: false,
        ))->toThrow(DuplicateFieldException::class);
    });

    it('should not throw when passing the same state_registration as before, ignoring its own id', function () {
        $seller = Seller::factory()->create(['state_registration' => $this->validIE()]);

        $result = app(TreatStateRegistration::class)->execute(

            model: new Seller,
            field: 'state_registration',
            value: $seller->state_registration,
            mustBeUnique: true,
            mustBeNotNull: false,
            ignoredId: $seller->id
        );

        expect($result)->toBe($seller->state_registration);
    });

    it('throw an error when the state_registration already exists within the same organization', function () {
        $seller = Seller::factory()->create(['state_registration' => $this->validIE()]);

        expect(fn () => app(TreatStateRegistration::class)->execute(
            model: new Seller,
            field: 'state_registration',
            value: $seller->state_registration,
            organizationId: $seller->organization_id,
            mustBeUnique: true,
            mustBeNotNull: true
        ))->toThrow(DuplicateFieldException::class);
    });

    it('throw an error when the state_registration it`s null', function () {

        expect(fn () => app(TreatStateRegistration::class)->execute(
            model: new Seller,
            field: 'state_registration',
            value: ' ',
            mustBeUnique: true,
            mustBeNotNull: true
        ))->toThrow(InvalidFieldException::class);
    });
});
