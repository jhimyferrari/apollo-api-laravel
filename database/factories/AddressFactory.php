<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Seller;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'neighborhood' => fake()->word,
            'street' => fake()->streetName,
            'number' => fake()->buildingNumber,
            'complement' => fake()->optional()->words(3, true),
            'cep' => fake()->numerify('########'),
            'city_ibge_code' => City::inRandomOrder()->first()->ibgeCode(),
            'organization_id' => Organization::factory(),
            'is_default' => false,
        ];
    }

    public function forSupplier(): static
    {
        return $this->for(
            Supplier::factory(),
            'addressable'
        );
    }

    public function turnDefault(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function forSeller(): static
    {
        return $this->for(
            Seller::factory(),
            'addressable'
        );
    }

    public function forClient(): static
    {
        return $this->for(
            Client::factory(),
            'addressable'
        );
    }
}
