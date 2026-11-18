<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TreatCategory implements TreatmentStrategy
{
    /**
     * @throws ResourceNotFoundException
     */
    public function handle(mixed $array, string $field): ?Collection
    {
        if (! \is_array($array)) {
            throw ValidationException::withMessages([$field => __('validation.array', ['attribute' => __('validation.attributes.categories_id')])]);
        }

        $ids = collect($array)->pluck('id')->toArray();

        $invalidUuids = array_filter($ids, fn (mixed $id) => ! is_string($id) || ! Str::isUuid($id));
        if (! empty($invalidUuids)) {
            $values = array_map(fn (mixed $id) => is_scalar($id) ? (string) $id : get_debug_type($id), $invalidUuids);
            throw ValidationException::withMessages([
                $field => __('errors.not_found_array', [
                    'attribute' => __('validation.attributes.categories_id'),
                    'values' => implode(',', $values),
                ]),
            ]);
        }

        $uniqueIds = array_values(array_unique($ids));
        $categories = Category::findMany($uniqueIds);
        if ($categories->pluck('id')->count() != \count($uniqueIds)) {
            $invalidId = array_diff($uniqueIds, $categories->pluck('id')->toArray());
            throw ValidationException::withMessages([$field => __('errors.not_found_array', ['attribute' => __('validation.attributes.categories_id'), 'values' => implode(',', $invalidId)])]);
        }

        return $categories;
    }
}
