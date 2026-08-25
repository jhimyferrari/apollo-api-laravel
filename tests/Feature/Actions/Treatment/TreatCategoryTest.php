<?php

use App\Actions\Treatment\TreatCategory;
use App\Exceptions\InvalidFieldException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('TreatCategory', function () {
    it('returns the categories when all ids exist', function () {
        $categories = Category::factory()->count(3)->create();

        $result = app(TreatCategory::class)->execute(
            $categories->map(fn ($category) => ['id' => $category->id])->toArray()
        );

        expect($result)->toHaveCount(3)
            ->and($result->pluck('id')->sort()->values()->toArray())
            ->toBe($categories->pluck('id')->sort()->values()->toArray());
    });

    it('throws when a category id does not exist', function () {
        $category = Category::factory()->create();
        $invalidId = (string) Str::uuid();

        expect(fn () => app(TreatCategory::class)->execute([
            ['id' => $category->id],
            ['id' => $invalidId],
        ]))->toThrow(ResourceNotFoundException::class);
    });

    it('includes the missing id in the exception message', function () {
        $invalidId = (string) Str::uuid();

        try {
            app(TreatCategory::class)->execute([['id' => $invalidId]]);
        } catch (ResourceNotFoundException $exception) {
            expect($exception->getMessage())->toContain($invalidId);
        }
    });

    it('returns an empty collection when given an empty array', function () {
        $result = app(TreatCategory::class)->execute([]);

        expect($result)->toBeEmpty();
    });
    it('throws an exception when given an empty with the mustBeNotNull option', function () {

        expect(fn () => app(TreatCategory::class)->execute([], mustBeNotNull: true))->toThrow(InvalidFieldException::class);

    });
});
