<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
    }

    public function process(Payment $payment, array $data): array
    {
        if (!config('services.stripe.enabled')) {
            return [
                'success' => false,
                'error' => 'Stripe is disabled',
                'response' => ['error' => 'gateway_disabled'],
            ];
        }

        // In a real implementation, create a PaymentIntent, etc.
        // Here we just simulate a successful approval tied to transaction_id
        Log::info('Stripe process simulated', [
            'payment_id' => $payment->id,
        ]);

        return [
            'success' => true,
            'response' => [
                'status' => 'approved',
                'transaction_id' => $payment->transaction_id,
                'processor' => 'stripe',
                'processed_at' => now()->toISOString(),
            ],
        ];
    }

    public function refund(Payment $payment, float $amount, array $data): array
    {
        if (!config('services.stripe.enabled')) {
            return [
                'success' => false,
                'error' => 'Stripe is disabled',
                'response' => ['error' => 'gateway_disabled'],
            ];
        }

        Log::info('Stripe refund simulated', [
            'payment_id' => $payment->id,
            'amount' => $amount,
        ]);

        return [
            'success' => true,
            'response' => [
                'status' => 'approved',
                'refund_id' => 're_str_' . uniqid('', true),
                'refund_amount' => $amount,
                'processor' => 'stripe',
                'processed_at' => now()->toISOString(),
            ],
        ];
    }
}
