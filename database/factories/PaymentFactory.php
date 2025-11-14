<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_method' => fake()->randomElement(['credit_card', 'debit_card', 'paypal', 'stripe', 'bank_transfer']),
            'amount' => fake()->randomFloat(100, 2, 1000),
            'currency' => 'USD',
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed', 'refunded']),
            'transaction_id' => 'txn_' . fake()->unique()->bothify('????????########'),
            'gateway_response' => [
                'status' => fake()->randomElement(['approved', 'declined', 'pending']),
                'transaction_id' => 'gw_' . fake()->unique()->bothify('????????########'),
                'auth_code' => fake()->bothify('######'),
                'response_code' => fake()->bothify('##'),
                'response_message' => fake()->sentence(),
                'avs_result' => fake()->randomElement(['Y', 'N', 'U', 'S']),
                'cvv_result' => fake()->randomElement(['M', 'N', 'P', 'S', 'U']),
            ],
            'paid_at' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'failed_at' => fake()->optional(0.1)->dateTimeBetween('-1 year', 'now'),
            'refunded_at' => fake()->optional(0.1)->dateTimeBetween('-6 months', 'now'),
            'refund_amount' => fake()->optional(0.1)->randomFloat(50, 2, 500),
            'refund_reason' => fake()->optional(0.1)->sentence(),
            'metadata' => [
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
                'payment_gateway' => fake()->randomElement(['stripe', 'paypal', 'square']),
            ],
        ];
    }

    /**
     * Indicate that the payment should be pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
            'failed_at' => null,
            'refunded_at' => null,
        ]);
    }

    /**
     * Indicate that the payment should be completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'paid_at' => now(),
            'failed_at' => null,
            'refunded_at' => null,
        ]);
    }

    /**
     * Indicate that the payment should be failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'paid_at' => null,
            'failed_at' => now(),
            'refunded_at' => null,
        ]);
    }

    /**
     * Indicate that the payment should be refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'paid_at' => now()->subDays(1),
            'failed_at' => null,
            'refunded_at' => now(),
            'refund_amount' => $attributes['amount'] ?? fake()->randomFloat(50, 2, 500),
        ]);
    }

    /**
     * Indicate that the payment should be via credit card.
     */
    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'credit_card',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'card_type' => fake()->randomElement(['visa', 'mastercard', 'amex', 'discover']),
                'last_four' => fake()->numerify('####'),
                'expiry_month' => fake()->numberBetween(1, 12),
                'expiry_year' => fake()->numberBetween(date('Y'), date('Y') + 5),
            ]),
        ]);
    }

    /**
     * Indicate that the payment should be via PayPal.
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'paypal',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'paypal_email' => fake()->email(),
                'paypal_payer_id' => 'PAYER_' . fake()->bothify('????????########'),
            ]),
        ]);
    }

    /**
     * Indicate that the payment should be via Stripe.
     */
    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'stripe',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'stripe_payment_intent_id' => 'pi_' . fake()->bothify('????????########'),
                'stripe_customer_id' => 'cus_' . fake()->bothify('????????########'),
                'stripe_charge_id' => 'ch_' . fake()->bothify('????????########'),
            ]),
        ]);
    }
}
