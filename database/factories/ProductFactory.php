<?php

namespace Database\Factories;

use App\Enum\ProductUnit;
use App\Models\Brand;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost_price = fake()->randomNumber(7);

        return [
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'unit' => fake()->randomElement(ProductUnit::values()),
            'ncm' => fake()->regexify('\d{8}$'),
            'ean' => fake()->regexify('^\d{13}$'),
            'cost_price' => $cost_price,
            'sale_price' => fake()->numberBetween($cost_price, '100000000'),
            'stock_quantity' => fake()->randomNumber(5),
            'organization_id' => Organization::factory(),
            'brand_id' => Brand::factory(),

        ];
    }
}
