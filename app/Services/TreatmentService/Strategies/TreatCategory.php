<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Category;
use Illuminate\Support\Collection;
use RuntimeException;

class TreatCategory implements TreatmentStrategy
{
    /**
     * @throws ResourceNotFoundException
     */
    public function handle(mixed $array): ?Collection
    {
        if (! \is_array($array)) {
            throw new RuntimeException('The value must be a array');
        }
        $array = collect($array)->pluck('id')->toArray();

        $categories = Category::findMany($array);
        if ($categories->pluck('id')->count() != \count($array)) {
            $invalidId = array_diff($array, $categories->pluck('id')->toArray());
            throw new ResourceNotFoundException('Categories not found: '.implode(',', $invalidId));
        }

        return $categories;

    }
}
