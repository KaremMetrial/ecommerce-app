<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['fixed', 'percentage']);
        $value = $type === 'fixed'
            ? fake()->randomFloat(2, 5, 100)
            : fake()->randomFloat(0, 5, 50);

        return [
            'code' => strtoupper(fake()->unique()->bothify('??????')),
            'name' => fake()->catchPhrase(),
            'description' => fake()->sentence(10),
            'type' => $type,
            'value' => $value,
            'minimum_amount' => fake()->optional(0.7)->randomFloat(2, 10, 200),
            'maximum_discount' => fake()->optional(0.3)->randomFloat(2, 50, 500),
            'usage_limit' => fake()->optional(0.6)->numberBetween(10, 1000),
            'usage_limit_per_customer' => fake()->optional(0.8)->numberBetween(1, 5),
            'used_count' => 0,
            'starts_at' => fake()->optional(0.8)->dateTimeBetween('-1 month', 'now'),
            'expires_at' => fake()->optional(0.9)->dateTimeBetween('now', '+6 months'),
            'is_active' => true,
            'applicable_to' => fake()->randomElement(['all', 'categories', 'products', 'users']),
            'applicable_ids' => fake()->optional(0.5)->randomElements([1, 2, 3, 4, 5], fake()->numberBetween(1, 3)),
            'excluded_categories' => fake()->optional(0.2)->randomElements([1, 2, 3, 4, 5], fake()->numberBetween(1, 2)),
            'excluded_products' => fake()->optional(0.2)->randomElements([1, 2, 3, 4, 5], fake()->numberBetween(1, 2)),
            'free_shipping' => fake()->boolean(10), // 10% chance of free shipping
            'first_time_customer_only' => fake()->boolean(20), // 20% chance for first-time customers only
            'metadata' => [
                'created_by' => fake()->randomElement(['admin', 'system', 'marketing_team']),
                'campaign' => fake()->optional(0.6)->randomElement(['summer_sale', 'black_friday', 'new_year', 'clearance', 'flash_sale']),
                'source' => fake()->randomElement(['email', 'social_media', 'website', 'affiliate', 'direct']),
                'target_audience' => fake()->randomElement(['all', 'vip', 'new_customers', 'returning_customers']),
            ],
        ];
    }

    /**
     * Indicate that the coupon should be a fixed amount discount.
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'value' => fake()->randomFloat(2, 5, 100),
        ]);
    }

    /**
     * Indicate that the coupon should be a percentage discount.
     */
    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'value' => fake()->randomFloat(0, 5, 50),
        ]);
    }

    /**
     * Indicate that the coupon should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'starts_at' => fake()->optional(0.8)->dateTimeBetween('-1 month', 'now'),
            'expires_at' => fake()->optional(0.9)->dateTimeBetween('now', '+6 months'),
        ]);
    }

    /**
     * Indicate that the coupon should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the coupon should be expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the coupon should be scheduled for future use.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
            'expires_at' => fake()->dateTimeBetween('+2 months', '+6 months'),
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the coupon should have free shipping.
     */
    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'free_shipping' => true,
        ]);
    }

    /**
     * Indicate that the coupon should be for first-time customers only.
     */
    public function firstTimeCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_time_customer_only' => true,
        ]);
    }

    /**
     * Indicate that the coupon should be applicable to all products.
     */
    public function applicableToAll(): static
    {
        return $this->state(fn (array $attributes) => [
            'applicable_to' => 'all',
            'applicable_ids' => null,
            'excluded_categories' => null,
            'excluded_products' => null,
        ]);
    }

    /**
     * Indicate that the coupon should be applicable to specific categories.
     */
    public function applicableToCategories(): static
    {
        return $this->state(fn (array $attributes) => [
            'applicable_to' => 'categories',
            'applicable_ids' => fake()->randomElements([1, 2, 3, 4, 5], fake()->numberBetween(1, 3)),
        ]);
    }

    /**
     * Indicate that the coupon should be applicable to specific products.
     */
    public function applicableToProducts(): static
    {
        return $this->state(fn (array $attributes) => [
            'applicable_to' => 'products',
            'applicable_ids' => fake()->randomElements([1, 2, 3, 4, 5], fake()->numberBetween(1, 3)),
        ]);
    }

    /**
     * Indicate that the coupon should have usage limits.
     */
    public function withUsageLimit(): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => fake()->numberBetween(10, 1000),
            'usage_limit_per_customer' => fake()->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that the coupon should have a minimum amount requirement.
     */
    public function withMinimumAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'minimum_amount' => fake()->randomFloat(2, 10, 200),
        ]);
    }

    /**
     * Indicate that the coupon should have a maximum discount limit.
     */
    public function withMaximumDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'maximum_discount' => fake()->randomFloat(2, 50, 500),
        ]);
    }

    /**
     * Indicate that the coupon should be partially used.
     */
    public function partiallyUsed(): static
    {
        $usageLimit = fake()->numberBetween(10, 100);
        return $this->state(fn (array $attributes) => [
            'usage_limit' => $usageLimit,
            'used_count' => fake()->numberBetween(1, $usageLimit - 1),
        ]);
    }

    /**
     * Indicate that the coupon should be fully used.
     */
    public function fullyUsed(): static
    {
        $usageLimit = fake()->numberBetween(10, 100);
        return $this->state(fn (array $attributes) => [
            'usage_limit' => $usageLimit,
            'used_count' => $usageLimit,
            'is_active' => false,
        ]);
    }
}
