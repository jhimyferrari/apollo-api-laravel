<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Category;
use Illuminate\Support\Collection;
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
        $array = collect($array)->pluck('id')->toArray();

        $categories = Category::findMany($array);
        if ($categories->pluck('id')->count() != \count($array)) {
            $invalidId = array_diff($array, $categories->pluck('id')->toArray());
            throw ValidationException::withMessages([$field => __('errors.not_found_array', ['attribute' => __('validation.attributes.categories_id'), 'values' => implode(',', $invalidId)])]);
        }

        return $categories;

    }
}
