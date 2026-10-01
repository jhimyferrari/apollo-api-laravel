<?php

namespace Database\Factories;

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Models\Client;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\Seller;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => OrderStatus::Draft,
            'payment_status' => PaymentStatus::Pending,
            'organization_id' => Organization::factory(),
            'client_id' => Client::factory(),
            'seller_id' => Seller::factory(),
            'total' => Money::fromDecimal(fake()->numberBetween(1, '1000')),
        ];
    }

    public function cancelled(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?? fake()->sentence(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'pyment_status' => PaymentStatus::Paid,
        ]);
    }

    public function replacing(SalesOrder $originalOrder): static
    {
        return $this->state(fn (array $attributes) => [
            'replaces_order_id' => $originalOrder->id,
            'client_id' => $originalOrder->client_id,
        ]);
    }
}
