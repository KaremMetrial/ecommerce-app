<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Process a payment with the gateway.
     * Should return ['success' => bool, 'response' => array] or ['success' => false, 'error' => string, 'response' => array]
     */
    public function process(Payment $payment, array $data): array;

    /**
     * Process a refund with the gateway.
     */
    public function refund(Payment $payment, float $amount, array $data): array;
}
