<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(10),
            'short_description' => fake()->sentence(5),
            'sku' => 'PRD-' . fake()->bothify('??????'),
            'price' => fake()->randomFloat(10, 2, 1000),
            'compare_price' => fake()->randomFloat(10, 2, 2000),
            'cost_price' => fake()->randomFloat(10, 2, 500),
            'track_quantity' => true,
            'quantity' => fake()->numberBetween(0, 1000),
            'min_stock_level' => fake()->numberBetween(0, 50),
            'is_active' => true,
            'is_featured' => fake()->boolean(25), // 25% chance of being featured
            'is_digital' => fake()->boolean(10), // 10% chance of being digital
            'weight' => fake()->randomFloat(1, 2, 10),
            'dimensions' => [
                'length' => fake()->randomFloat(5, 1, 100),
                'width' => fake()->randomFloat(5, 1, 100),
                'height' => fake()->randomFloat(1, 1, 10),
            ],
            'images' => [
                fake()->imageUrl(400, 400, 'products'),
                fake()->imageUrl(400, 400, 'products'),
                fake()->imageUrl(400, 400, 'products'),
            ],
            'attributes' => [
                'brand' => fake()->company(),
                'color' => fake()->colorName(),
                'size' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
                'material' => fake()->randomElement(['Cotton', 'Polyester', 'Wool', 'Silk']),
            ],
            'meta' => [
                'title' => fake()->sentence(5),
                'description' => fake()->sentence(10),
                'keywords' => implode(', ', fake()->words(5)),
            ],
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the model should be created with a specific state.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'is_active' => false,
        ]));
    }

    /**
     * Indicate that the model should be out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'quantity' => 0,
        ]));
    }

    /**
     * Indicate that the model should be featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'is_featured' => true,
        ]));
    }

    /**
     * Indicate that the model should be digital.
     */
    public function digital(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'is_digital' => true,
            'track_quantity' => false,
        ]));
    }

    /**
     * Indicate that the model should have a specific price.
     */
    public function price(float $price): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'price' => $price,
        ]));
    }

    /**
     * Indicate that the model should have a specific quantity.
     */
    public function quantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'quantity' => $quantity,
        ]));
    }
}
