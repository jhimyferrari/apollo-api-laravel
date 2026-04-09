<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\Organization;
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
            'client_id' => Client::factory(),
        ];
    }
}
