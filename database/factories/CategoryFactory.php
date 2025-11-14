<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(10),
            'image' => fake()->imageUrl(400, 400, 'categories'),
            'is_active' => true,
            'is_featured' => fake()->boolean(20), // 20% chance of being featured
            'sort_order' => fake()->numberBetween(0, 100),
            'meta' => [
                'title' => fake()->sentence(5),
                'description' => fake()->sentence(10),
                'keywords' => implode(', ', fake()->words(5)),
            ],
        ];
    }

    /**
     * Indicate that the category should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'is_active' => false,
        ]));
    }

    /**
     * Indicate that the category should be featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'is_featured' => true,
        ]));
    }

    /**
     * Indicate that the category should be a subcategory.
     */
    public function withParent(Category $parent): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'parent_id' => $parent->id,
        ]));
    }

    /**
     * Indicate that the category should have a specific sort order.
     */
    public function sortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'sort_order' => $order,
        ]));
    }
}
