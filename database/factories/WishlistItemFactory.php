<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wishlist_id' => Wishlist::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the wishlist item should have high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the wishlist item should have medium priority.
     */
    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'medium',
        ]);
    }

    /**
     * Indicate that the wishlist item should have low priority.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'low',
        ]);
    }

    /**
     * Indicate that the wishlist item should have a product variant.
     */
    public function withVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => ProductVariant::factory()->create()->id,
        ]);
    }

    /**
     * Indicate that the wishlist item should have price drop notifications enabled.
     */
    public function withPriceNotification(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'price_drop_notification' => true,
                'target_price' => fake()->randomFloat(2, 5, 300),
            ]),
        ]);
    }

    /**
     * Indicate that the wishlist item should have back-in-stock notifications enabled.
     */
    public function withStockNotification(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'back_in_stock_notification' => true,
                'in_stock_when_added' => false,
            ]),
        ]);
    }

    /**
     * Indicate that the wishlist item should have notes.
     */
    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the wishlist item was added recently.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'added_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the wishlist item was added a long time ago.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'added_at' => fake()->dateTimeBetween('-3 months', '-1 month'),
        ]);
    }

    /**
     * Indicate that the wishlist item should have a higher quantity.
     */
    public function highQuantity(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(3, 10),
        ]);
    }

    /**
     * Indicate that the wishlist item should have a single quantity.
     */
    public function singleQuantity(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 1,
        ]);
    }

    /**
     * Indicate that the wishlist item should have notifications already sent.
     */
    public function notificationSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'notification_sent' => true,
                'notification_sent_at' => fake()->dateTimeBetween('-1 month', 'now'),
            ]),
        ]);
    }

    /**
     * Indicate that the wishlist item should have a target price set.
     */
    public function withTargetPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'target_price' => fake()->randomFloat(2, 5, 300),
                'price_drop_notification' => true,
            ]),
        ]);
    }

    /**
     * Indicate that the wishlist item should be for an out-of-stock product.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'in_stock_when_added' => false,
                'back_in_stock_notification' => true,
            ]),
        ]);
    }

    /**
     * Indicate that the wishlist item should be for a product on sale.
     */
    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'sale_price_when_added' => fake()->randomFloat(2, 5, 300),
                'price_drop_notification' => true,
            ]),
        ]);
    }
}
