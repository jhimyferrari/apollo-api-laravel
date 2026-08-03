<?php

namespace Database\Factories;

use App\Helpers\Test\StateRegistrationHelper;
use App\Models\Client;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
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
            'state_registration' => ($random_int == 0) ? StateRegistrationHelper::generateIE() : fake()->rg(false),
            'organization_id' => Organization::factory(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->email(),
        ];
    }
}
