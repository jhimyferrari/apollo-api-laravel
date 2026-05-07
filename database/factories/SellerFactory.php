<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seller>
 */
class SellerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $random_int = random_int(0, 1);

        return [
            'status' => 'active',
            'document' => ($random_int == 0) ? fake()->cnpj(false) : fake()->cpf(false),
            'legal_name' => fake()->domainName(),
            'trade_name' => fake()->name(),
            'state_registration' => ($random_int == 0) ? fake()->regexify('[0-9]{9,12}') : fake()->rg(),
            'organization_id' => Organization::factory(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->email(),
            'started_at' => fake()->dateTimeBetween('-10 years', now()),
            'ended_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => fake()->dateTimeBetween($attributes['started_at'], 'now'),
        ]);
    }
}
