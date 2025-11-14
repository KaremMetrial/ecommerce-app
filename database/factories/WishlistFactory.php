<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Wishlist>
 */
class WishlistFactory extends Factory
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
            'name' => fake()->optional(0.7)->randomElement(['My Wishlist', 'Favorites', 'Wish List', 'Products I Love', 'Save for Later']),
            'description' => fake()->optional(0.5)->sentence(10),
            'is_public' => fake()->boolean(20), // 20% chance of being public
            'is_default' => fake()->boolean(30), // 30% chance of being default
            'metadata' => [
                'created_from' => fake()->randomElement(['website', 'mobile_app', 'social_media', 'email']),
                'occasion' => fake()->optional(0.4)->randomElement(['birthday', 'wedding', 'holiday', 'anniversary', 'just_because']),
                'share_count' => fake()->numberBetween(0, 50),
                'view_count' => fake()->numberBetween(0, 200),
            ],
        ];
    }

    /**
     * Indicate that the wishlist should be public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the wishlist should be private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Indicate that the wishlist should be the default wishlist.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'name' => 'My Wishlist',
        ]);
    }

    /**
     * Indicate that the wishlist should have items.
     */
    public function withItems(int $itemCount = 1): static
    {
        return $this->afterCreating(function (Wishlist $wishlist) use ($itemCount) {
            for ($i = 0; $i < $itemCount; $i++) {
                $wishlist->items()->create([
                    'product_id' => Product::factory()->create()->id,
                    'product_variant_id' => null,
                    'quantity' => fake()->numberBetween(1, 5),
                    'priority' => fake()->randomElement(['low', 'medium', 'high']),
                    'notes' => fake()->optional(0.3)->sentence(),
                    'added_at' => fake()->dateTimeBetween('-3 months', 'now'),
                    'metadata' => [
                        'price_when_added' => fake()->randomFloat(2, 10, 500),
                        'sale_price_when_added' => fake()->optional(0.3)->randomFloat(2, 5, 300),
                        'in_stock_when_added' => fake()->boolean(80),
                        'notification_sent' => false,
                        'price_drop_notification' => fake()->boolean(30),
                        'back_in_stock_notification' => fake()->boolean(50),
                    ],
                ]);
            }
        });
    }

    /**
     * Indicate that the wishlist should be for a specific occasion.
     */
    public function forBirthday(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Birthday Wishlist',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'occasion' => 'birthday',
            ]),
        ]);
    }

    /**
     * Indicate that the wishlist should be for wedding.
     */
    public function forWedding(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Wedding Registry',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'occasion' => 'wedding',
            ]),
        ]);
    }

    /**
     * Indicate that the wishlist should be for holidays.
     */
    public function forHolidays(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Holiday Wishlist',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'occasion' => 'holiday',
            ]),
        ]);
    }

    /**
     * Indicate that the wishlist should be shared.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'share_count' => fake()->numberBetween(1, 50),
                'view_count' => fake()->numberBetween(10, 200),
                'shared_with' => fake()->randomElements(['family', 'friends', 'coworkers'], fake()->numberBetween(1, 2)),
            ]),
        ]);
    }

    /**
     * Indicate that the wishlist should have high-priority items.
     */
    public function withHighPriorityItems(): static
    {
        return $this->afterCreating(function (Wishlist $wishlist) {
            for ($i = 0; $i < fake()->numberBetween(1, 3); $i++) {
                $wishlist->items()->create([
                    'product_id' => Product::factory()->create()->id,
                    'quantity' => fake()->numberBetween(1, 5),
                    'priority' => 'high',
                    'notes' => fake()->optional(0.5)->sentence(),
                    'added_at' => fake()->dateTimeBetween('-3 months', 'now'),
                ]);
            }
        });
    }

    /**
     * Indicate that the wishlist should have price drop notifications enabled.
     */
    public function withPriceNotifications(): static
    {
        return $this->afterCreating(function (Wishlist $wishlist) {
            for ($i = 0; $i < fake()->numberBetween(1, 3); $i++) {
                $wishlist->items()->create([
                    'product_id' => Product::factory()->create()->id,
                    'quantity' => fake()->numberBetween(1, 5),
                    'priority' => fake()->randomElement(['low', 'medium', 'high']),
                    'notes' => fake()->optional(0.3)->sentence(),
                    'added_at' => fake()->dateTimeBetween('-3 months', 'now'),
                    'metadata' => [
                        'price_when_added' => fake()->randomFloat(2, 10, 500),
                        'price_drop_notification' => true,
                        'target_price' => fake()->randomFloat(2, 5, 300),
                    ],
                ]);
            }
        });
    }

    /**
     * Indicate that the wishlist should have back-in-stock notifications enabled.
     */
    public function withStockNotifications(): static
    {
        return $this->afterCreating(function (Wishlist $wishlist) {
            for ($i = 0; $i < fake()->numberBetween(1, 3); $i++) {
                $wishlist->items()->create([
                    'product_id' => Product::factory()->create(['stock_quantity' => 0])->id,
                    'quantity' => fake()->numberBetween(1, 5),
                    'priority' => fake()->randomElement(['low', 'medium', 'high']),
                    'notes' => fake()->optional(0.3)->sentence(),
                    'added_at' => fake()->dateTimeBetween('-3 months', 'now'),
                    'metadata' => [
                        'back_in_stock_notification' => true,
                        'in_stock_when_added' => false,
                    ],
                ]);
            }
        });
    }
}
