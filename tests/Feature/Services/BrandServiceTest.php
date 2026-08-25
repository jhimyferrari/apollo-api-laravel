<?php

use App\Exceptions\DuplicateFieldException;
use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use App\Services\BrandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);

    $this->service = app(BrandService::class);
});

describe('BrandService', function () {
    describe('create', function () {
        it('should create a Brand successfully', function () {
            /** @var BrandService $this->service */
            /** @var User $this->user */
            $brand = $this->service->create([
                'name' => 'validName',
                'description' => 'Some description',
            ], $this->user);
            expect($brand)
                ->toBeInstanceOf(Brand::class)
                ->name->toBe('validName')
                ->organization_id->toBe($this->user->organization_id);
        });

        it('should remove spaces from a name', function () {
            /** @var BrandService $this->service */
            /** @var User $this->user */
            $brand = $this->service->create([
                'name' => ' nameWithSpaces ',
            ], $this->user);
            expect($brand)
                ->toBeInstanceOf(Brand::class)
                ->name->toBe('nameWithSpaces')
                ->organization_id->toBe($this->user->organization_id);
        });
        it('throw an exception when using an used name ', function () {
            /** @var BrandService $this->service */
            /** @var User $this->user */
            Brand::factory()->create(['organization_id' => $this->user->organization_id, 'name' => 'duplicateName']);
            expect(fn () => $this->service->create([
                'name' => ' duplicateName ',
            ], $this->user))->toThrow(DuplicateFieldException::class, 'Brand name `duplicateName` already exist');
        });
        it('should allow to use same name in differents organizations', function () {
            /** @var BrandService $this->service */
            /** @var User $this->user */
            Brand::factory()->create(['name' => 'validName']);
            $brand = $this->service->create([
                'name' => 'validName',
            ], $this->user);

            expect($brand)
                ->toBeInstanceOf(Brand::class)
                ->name->toBe('validName')
                ->organization_id->toBe($this->user->organization_id);
        });
    });
    describe('update', function () {

        it('should update a Brand successfully', function () {
            /** @var BrandService $this->service */
            /** @var User $this->user */
            /** @var Brand $brand */
            $brand = Brand::factory()->create(['organization_id' => $this->user->organization_id]);

            $brand = $this->service->update($brand, [
                'name' => 'validName',
                'description' => 'Some description',
            ]);
            expect($brand)
                ->toBeInstanceOf(Brand::class)
                ->name->toBe('validName')
                ->organization_id->toBe($this->user->organization_id);
        });

        it('should remove spaces from a name', function () {
            /** @var BrandService $this->service */
            /** @var User $this->user */
            /** @var Brand $brand */
            $brand = Brand::factory()->create(['organization_id' => $this->user->organization_id]);

            $brand = $this->service->update($brand, [
                'name' => ' nameWithSpaces ',
            ]);
            expect($brand)
                ->toBeInstanceOf(Brand::class)
                ->name->toBe('nameWithSpaces')
                ->organization_id->toBe($this->user->organization_id);
        });
        it('throw an exception when using an used name ', function () {
            /** @var BrandService $this->service */
            /** @var User $this->user */
            /** @var Brand $brand */
            Brand::factory()->create(['organization_id' => $this->user->organization_id, 'name' => 'duplicateName']);

            $brand = Brand::factory()->create(['organization_id' => $this->user->organization_id, 'name' => 'nonDuplicateName']);

            expect(fn () => $this->service->update($brand, [
                'name' => ' duplicateName ',
            ]))->toThrow(DuplicateFieldException::class, 'Brand name `duplicateName` already exist');
        });

        it('should allow to use same name in differents organizations', function () {
            /** @var BrandService $this->service */
            /** @var User $this->user */
            Brand::factory()->create(['name' => 'validName']);
            $brand = Brand::factory()->create(['organization_id' => $this->user->organization_id, 'name' => 'alsoValidName']);
            $brand = $this->service->update($brand, [
                'name' => 'validName',
            ]);

            expect($brand)
                ->toBeInstanceOf(Brand::class)
                ->name->toBe('validName')
                ->organization_id->toBe($this->user->organization_id);
        });
        it('should allow to use the same name', function () {
            /** @var BrandService $this->service */
            /** @var User $this->user */
            /** @var Brand $brand */
            $brand = Brand::factory()->create(['name' => 'sameName', 'organization_id' => $this->user->organization_id]);
            $brand = $this->service->update($brand, [
                'name' => 'sameName',
                'description' => 'some description',
            ]);

            expect($brand)
                ->toBeInstanceOf(Brand::class)
                ->description->toBe('some description')
                ->organization_id->toBe($this->user->organization_id);
        });
    });
    describe('delete', function () {
        it('should softDelete some brand and remove brand_id from owned products', function () {
            /** @var BrandService $this->service */
            /** @var User $this->user */
            /** @var Brand $brand */
            $brand = Brand::factory()->create(['organization_id' => $this->user->organization_id]);

            $products = Product::factory()->count(10)->withBrand($brand)->create();
            $this->service->delete($brand);
            $this->assertSoftDeleted($brand);

            $products->each(fn (Product $p) => expect($p->fresh()->brand_id)->toBe(null));
        });
    });
});
