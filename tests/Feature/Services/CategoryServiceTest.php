<?php

use App\Exceptions\DuplicateFieldException;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);

    $this->service = app(CategoryService::class);
});
describe('CategoryService', function () {
    describe('create', function () {
        it('should create a Category successfully', function () {

            $category = $this->service->create([
                'name' => 'validName',
                'description' => 'Some description',
            ], $this->user);
            expect($category)
                ->toBeInstanceOf(Category::class)
                ->name->toBe('validName')
                ->description->toBe('Some description')
                ->organization_id->toBe($this->user->organization_id);
        });
        it('should remove spaces from a name', function () {
            $category = $this->service->create([
                'name' => ' nameWithSpaces ',
            ], $this->user);
            expect($category)
                ->toBeInstanceOf(Category::class)
                ->name->toBe('nameWithSpaces')
                ->organization_id->toBe($this->user->organization_id);
        });
        it('throw an exception when using an used name', function () {
            Category::factory()->create(['organization_id' => $this->user->organization_id, 'name' => 'duplicateName']);
            expect(fn () => $this->service->create([
                'name' => ' duplicateName',
            ], $this->user))->toThrow(DuplicateFieldException::class, 'Category name `duplicateName` already exist');
        });
        it('should allow to use same name in differents organizations', function () {
            Category::factory()->create(['name' => 'validName']);
            $category = $this->service->create(
                [
                    'name' => 'validName',
                ],
                $this->user
            );

            expect($category)
                ->toBeInstanceOf(Category::class)
                ->name->toBe('validName')
                ->organization_id->toBe($this->user->organization_id);
        });
    });
    describe('update', function () {
        it('should update a Category successfully', function () {
            $category = Category::factory()->create(['organization_id' => $this->user->organization_id]);

            $category = $this->service->update($category, [
                'name' => 'validName',
                'description' => 'Some description',
            ]);
            expect($category)
                ->toBeInstanceOf(Category::class)
                ->name->toBe('validName')
                ->description->toBe('Some description')
                ->organization_id->toBe($this->user->organization_id);

        });
        it('should remove spaces from a name', function () {
            $category = Category::factory()->create(['organization_id' => $this->user->organization_id]);

            $brand = $this->service->update($category, [
                'name' => ' nameWithSpaces ',
            ]);

            expect($category)
                ->toBeInstanceOf(Category::class)
                ->name->toBe('nameWithSpaces')
                ->organization_id->toBe($this->user->organization_id);
        });
        it('throw an exception when using an used name', function () {
            Category::factory()->create(['organization_id' => $this->user->organization_id, 'name' => 'duplicateName']);

            $category = Category::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update(
                $category,
                ['name' => ' duplicateName']
            ))->toThrow(DuplicateFieldException::class, 'Category name `duplicateName` already exist');
        });
        it('should allow to use same name in differents organization', function () {
            Category::factory()->create(['name' => 'validName']);
            $category = Category::factory()->create(['organization_id' => $this->user->organization_id]);
            $category = $this->service->update($category, [
                'name' => 'validName',
            ]);

            expect($category)
                ->toBeInstanceOf(Category::class);
        });
        it('should allow to use the same name', function () {
            $category = Category::factory()->create(['name' => 'sameName', 'organization_id' => $this->user->organization_id]);
            $category = $this->service->update($category, ['name' => 'sameName']);

            expect($category)
                ->toBeInstanceOf(Category::class)
                ->name->toBe('sameName');
        });
    });
    describe('delete', function () {
        it('should softDelete some category and detached from products tables', function () {
            $categories = Category::factory()->count(10)->create(['organization_id' => $this->user->organization_id]);
            $product = Product::factory()->withCategories($categories)->create();

            $category = $categories->pop();
            $this->service->delete($category);
            $this->assertSoftDeleted($category)
                ->assertFalse($product->categories->contains($category));
            $this->assertEmpty($product->categories->diff($categories));
        });
    });

});
