<?php

use App\Actions\Treatment\TreatBrand;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Models\Brand;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('TreatBrand', function () {
    it('returns null when value is null and mustBeNotNull is false', function () {
        $result = app(TreatBrand::class)->execute(null);

        expect($result)->toBeNull();
    });

    it('calls the validator when mustBeNotNull is true', function () {
        $this->mock(ValidateFieldIsNotNull::class)
            ->shouldReceive('execute')
            ->once()
            ->with(null, 'brand_id');

        app(TreatBrand::class)->execute(null, mustBeNotNull: true);
    });

    it('does not call the validator when mustBeNotNull is false', function () {
        $this->mock(ValidateFieldIsNotNull::class)
            ->shouldNotReceive('execute');

        app(TreatBrand::class)->execute(null);
    });

    it('returns the brand when it exists', function () {
        $brand = Brand::factory()->create();

        $result = app(TreatBrand::class)->execute($brand->id);

        expect($result->id)->toBe($brand->id);
    });

    it('throws ModelNotFoundException with the brand id in the message when it does not exist', function () {
        $invalidId = (string) Str::uuid();

        expect(fn () => app(TreatBrand::class)->execute($invalidId))
            ->toThrow(ModelNotFoundException::class, "Brand id $invalidId not found");
    });
    it('throws ModelNotFoundException when the brand is out of format', function () {

        $value = (string) Str::ulid();

        expect(fn () => app(TreatBrand::class)->execute($value))
            ->toThrow(ModelNotFoundException::class, "Brand id $value not found");
    });

});
