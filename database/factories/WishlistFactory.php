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
            'name' => fake()->words(2, true),
            'is_public' => fake()->boolean(20), // 20% chance of being public
            'notes' => fake()->optional(0.5)->sentence(10),
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
                    'notes' => fake()->optional(0.3)->sentence(),
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
        ]);
    }

    /**
     * Indicate that the wishlist should be for wedding.
     */
    public function forWedding(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Wedding Registry',
        ]);
    }

    /**
     * Indicate that the wishlist should be for holidays.
     */
    public function forHolidays(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Holiday Wishlist',
        ]);
    }

    /**
     * Indicate that the wishlist should be shared.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
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
                    'product_variant_id' => null,
                    'notes' => fake()->optional(0.5)->sentence(),
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
                    'product_variant_id' => null,
                    'notes' => fake()->optional(0.3)->sentence(),
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
                    'product_id' => Product::factory()->create()->id,
                    'product_variant_id' => null,
                    'notes' => fake()->optional(0.3)->sentence(),
                ]);
            }
        });
    }
}
