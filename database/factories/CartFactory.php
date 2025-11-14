<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subtotal' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total' => 0,
            'currency' => 'USD',
            'coupon_data' => null,
        ];
    }

    /**
     * Indicate that the cart should be empty.
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'subtotal' => 0,
            'total' => 0,
        ]));
    }

    /**
     * Indicate that the cart should have items.
     */
    public function withItems(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'subtotal' => 100.00,
            'total' => 100.00,
        ]));
    }

    /**
     * Indicate that the cart should have a coupon applied.
     */
    public function withCoupon(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'discount_amount' => 10.00,
            'coupon_data' => [
                'code' => 'TEST10',
                'name' => 'Test Coupon',
                'type' => 'percentage',
                'value' => 10.00,
            ],
        ]));
    }

    /**
     * Create a cart for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'user_id' => $user->id,
        ]));
    }
}
