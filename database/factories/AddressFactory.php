<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
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
            'type' => fake()->randomElement(['shipping', 'billing', 'both']),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company' => fake()->optional(0.3)->company(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional(0.3)->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->email(),
            'is_default' => fake()->boolean(20), // 20% chance of being default
            'address_name' => fake()->optional(0.5)->randomElement(['Home', 'Work', 'Office', 'Apartment', 'Parents House']),
            'notes' => fake()->optional(0.2)->sentence(),
            'coordinates' => [
                'latitude' => fake()->latitude(-90, 90),
                'longitude' => fake()->longitude(-180, 180),
            ],
            'metadata' => [
                'verified' => fake()->boolean(80),
                'verification_date' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
                'delivery_instructions' => fake()->optional(0.3)->sentence(),
                'gate_code' => fake()->optional(0.1)->bothify('####'),
                'building_type' => fake()->randomElement(['house', 'apartment', 'office', 'condo', 'townhouse']),
            ],
        ];
    }

    /**
     * Indicate that the address should be a shipping address.
     */
    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'shipping',
        ]);
    }

    /**
     * Indicate that the address should be a billing address.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'billing',
        ]);
    }

    /**
     * Indicate that the address should be both shipping and billing.
     */
    public function both(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'both',
        ]);
    }

    /**
     * Indicate that the address should be the default address.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the address should be verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'verified' => true,
                'verification_date' => now(),
            ]),
        ]);
    }

    /**
     * Indicate that the address should be in the United States.
     */
    public function us(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'United States',
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
        ]);
    }

    /**
     * Indicate that the address should be in Canada.
     */
    public function canada(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'Canada',
            'state' => fake()->randomElement(['Ontario', 'Quebec', 'British Columbia', 'Alberta', 'Manitoba']),
            'postal_code' => fake()->bothify('?#? #?#'),
        ]);
    }

    /**
     * Indicate that the address should be in the United Kingdom.
     */
    public function uk(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'United Kingdom',
            'state' => fake()->randomElement(['England', 'Scotland', 'Wales', 'Northern Ireland']),
            'postal_code' => fake()->postcode(),
        ]);
    }

    /**
     * Indicate that the address should be a business address.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'company' => fake()->company(),
            'address_name' => fake()->randomElement(['Work', 'Office', 'Business']),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'building_type' => 'office',
                'business_hours' => [
                    'monday' => '9:00 AM - 5:00 PM',
                    'tuesday' => '9:00 AM - 5:00 PM',
                    'wednesday' => '9:00 AM - 5:00 PM',
                    'thursday' => '9:00 AM - 5:00 PM',
                    'friday' => '9:00 AM - 5:00 PM',
                    'saturday' => 'Closed',
                    'sunday' => 'Closed',
                ],
            ]),
        ]);
    }

    /**
     * Indicate that the address should be a residential address.
     */
    public function residential(): static
    {
        return $this->state(fn (array $attributes) => [
            'company' => null,
            'address_name' => fake()->randomElement(['Home', 'Apartment', 'House']),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'building_type' => fake()->randomElement(['house', 'apartment', 'condo', 'townhouse']),
            ]),
        ]);
    }
}
