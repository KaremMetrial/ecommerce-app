<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PayPalGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
    }

    public function process(Payment $payment, array $data): array
    {
        if (!config('services.paypal.enabled')) {
            return [
                'success' => false,
                'error' => 'PayPal is disabled',
                'response' => ['error' => 'gateway_disabled'],
            ];
        }

        Log::info('PayPal process simulated', [
            'payment_id' => $payment->id,
        ]);

        return [
            'success' => true,
            'response' => [
                'status' => 'approved',
                'transaction_id' => $payment->transaction_id,
                'processor' => 'paypal',
                'processed_at' => now()->toISOString(),
            ],
        ];
    }

    public function refund(Payment $payment, float $amount, array $data): array
    {
        if (!config('services.paypal.enabled')) {
            return [
                'success' => false,
                'error' => 'PayPal is disabled',
                'response' => ['error' => 'gateway_disabled'],
            ];
        }

        Log::info('PayPal refund simulated', [
            'payment_id' => $payment->id,
            'amount' => $amount,
        ]);

        return [
            'success' => true,
            'response' => [
                'status' => 'approved',
                'refund_id' => 're_pp_' . uniqid('', true),
                'refund_amount' => $amount,
                'processor' => 'paypal',
                'processed_at' => now()->toISOString(),
            ],
        ];
    }
}
