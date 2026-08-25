<?php

namespace Database\Factories;

use App\Models\NcmCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NcmCode
 */
class NcmCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('\d{8}$'),
            'description' => fake()->sentence(),
            'valid_from' => now(),
        ];
    }

    public function code(string $code): static
    {
        return $this->state(['code' => $code]);
    }

    public function inactive(): static
    {
        return $this->state(['isActive' => false]);
    }
}
