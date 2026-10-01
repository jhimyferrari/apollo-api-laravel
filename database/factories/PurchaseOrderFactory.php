<?php

namespace Database\Factories;

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
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
            'supplier_id' => Supplier::factory(),
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

    public function replacing(PurchaseOrder $originalOrder): static
    {
        return $this->state(fn (array $attributes) => [
            'replaces_order_id' => $originalOrder->id,
            'supplier_id' => $originalOrder->supplier_id,
        ]);
    }
}
