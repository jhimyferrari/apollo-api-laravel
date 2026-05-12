<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SequencialNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SequencialNumber>
 */
class SequencialNumberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'table' => fake()->word(),
            'last_number' => 0,
        ];
    }
}
