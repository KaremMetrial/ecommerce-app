<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => fake()->randomFloat(50, 2, 1000),
            'tax_amount' => fake()->randomFloat(5, 2, 100),
            'shipping_amount' => fake()->randomFloat(10, 2, 100),
            'discount_amount' => fake()->randomFloat(0, 2, 100),
            'total' => fake()->randomFloat(100, 2, 1000),
            'currency' => 'USD',
            'shipping_address' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address_line_1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'postal_code' => fake()->postcode(),
                'country' => fake()->country(),
                'phone' => fake()->phoneNumber(),
                'email' => fake()->email(),
            ],
            'billing_address' => null,
            'notes' => fake()->sentence(5),
        ];
    }

    /**
     * Indicate that the order should be pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]));
    }

    /**
     * Indicate that the order should be confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]));
    }

    /**
     * Indicate that the order should be processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'status' => 'processing',
            'payment_status' => 'pending',
        ]));
    }

    /**
     * Indicate that the order should be shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'status' => 'shipped',
            'payment_status' => 'paid',
            'shipped_at' => now(),
        ]));
    }

    /**
     * Indicate that the order should be delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'shipped_at' => now()->subDays(2),
            'delivered_at' => now(),
        ]));
    }

    /**
     * Indicate that the order should be cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ]));
    }

    /**
     * Indicate that the order should be refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]));
    }

    /**
     * Indicate that the order should be paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]));
    }

    /**
     * Create an order with items.
     */
    public function withItems(int $itemCount = 1): static
    {
        return $this->hasItems($itemCount);
    }

    /**
     * Indicate that the order should have items.
     */
    private function hasItems(int $count): static
    {
        return $this->afterMaking(function (Order $order) use ($count) {
            for ($i = 0; $i < $count; $i++) {
                $order->items()->create([
                    'product_id' => \App\Models\Product::factory()->create()->id,
                    'product_name' => fake()->words(3, true),
                    'product_sku' => 'PRD-' . fake()->unique()->bothify('strtoupper'),
                    'quantity' => fake()->numberBetween(1, 5),
                    'unit_price' => fake()->randomFloat(10, 2, 100),
                    'total_price' => fake()->randomFloat(10, 2, 1000),
                    'product_data' => [
                        'name' => fake()->words(3, true),
                        'slug' => fake()->slug(),
                        'image' => fake()->imageUrl(400, 400, 'products'),
                    ],
                ]);
            }
        });
    }
}
