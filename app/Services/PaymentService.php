<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Process a payment for an order.
     */
    public function processPayment(Order $order, array $data): Payment
    {
        return DB::transaction(function () use ($order, $data) {
            // Create payment record
            $payment = $order->payments()->create([
                'payment_method' => $data['payment_method'],
                'amount' => $order->total,
                'currency' => $order->currency,
                'status' => 'processing',
                'transaction_id' => $this->generateTransactionId(),
                'gateway_response' => $data['gateway_response'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            // Process payment with gateway
            $gatewayResult = $this->processWithGateway($payment, $data);

            if ($gatewayResult['success']) {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        $gatewayResult['response']
                    ),
                ]);

                // Update order status
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                ]);

                // Fire payment completed event
                event(new \App\Events\PaymentCompleted($payment));
            } else {
                $payment->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        $gatewayResult['response']
                    ),
                ]);

                // Fire payment failed event
                event(new \App\Events\PaymentFailed($payment, $gatewayResult['error']));

                throw new Exception($gatewayResult['error']);
            }

            return $payment;
        });
    }

    /**
     * Process a refund for a payment.
     */
    public function processRefund(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            $refundAmount = $data['amount'] ?? $payment->amount;

            if ($refundAmount > $payment->amount) {
                throw new Exception('Refund amount cannot exceed payment amount');
            }

            // Process refund with gateway
            $gatewayResult = $this->processRefundWithGateway($payment, $refundAmount, $data);

            if ($gatewayResult['success']) {
                $payment->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $data['reason'] ?? 'Customer requested refund',
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        $gatewayResult['response']
                    ),
                ]);

                // Update order status
                $order = $payment->order;
                $order->update([
                    'payment_status' => 'refunded',
                    'status' => 'refunded',
                ]);

                // Fire refund completed event
                event(new \App\Events\RefundCompleted($payment, $refundAmount));
            } else {
                throw new Exception($gatewayResult['error']);
            }

            return $payment;
        });
    }

    /**
     * Retry a failed payment.
     */
    public function retryPayment(Payment $payment): Payment
    {
        if ($payment->status !== 'failed') {
            throw new Exception('Only failed payments can be retried');
        }

        return DB::transaction(function () use ($payment) {
            // Reset payment status
            $payment->update([
                'status' => 'processing',
                'failed_at' => null,
                'transaction_id' => $this->generateTransactionId(),
            ]);

            // Get original payment data
            $data = [
                'payment_method' => $payment->payment_method,
                'gateway_response' => $payment->gateway_response,
                'metadata' => $payment->metadata,
            ];

            // Process payment with gateway
            $gatewayResult = $this->processWithGateway($payment, $data);

            if ($gatewayResult['success']) {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        $gatewayResult['response']
                    ),
                ]);

                // Update order status
                $order = $payment->order;
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                ]);

                // Fire payment completed event
                event(new \App\Events\PaymentCompleted($payment));
            } else {
                $payment->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        $gatewayResult['response']
                    ),
                ]);

                // Fire payment failed event
                event(new \App\Events\PaymentFailed($payment, $gatewayResult['error']));

                throw new Exception($gatewayResult['error']);
            }

            return $payment;
        });
    }

    /**
     * Calculate payment fees.
     */
    public function calculateFees(float $amount, string $method, string $currency = 'USD'): array
    {
        $fees = [
            'credit_card' => [
                'type' => 'percentage',
                'value' => 2.9,
                'fixed' => 0.30,
            ],
            'debit_card' => [
                'type' => 'percentage',
                'value' => 2.9,
                'fixed' => 0.30,
            ],
            'paypal' => [
                'type' => 'percentage',
                'value' => 3.4,
                'fixed' => 0.30,
            ],
            'stripe' => [
                'type' => 'percentage',
                'value' => 2.9,
                'fixed' => 0.30,
            ],
            'bank_transfer' => [
                'type' => 'fixed',
                'value' => 5.0,
                'fixed' => 0,
            ],
        ];

        $feeConfig = $fees[$method] ?? $fees['credit_card'];

        if ($feeConfig['type'] === 'percentage') {
            $percentageFee = ($amount * $feeConfig['value']) / 100;
            $totalFee = $percentageFee + $feeConfig['fixed'];
        } else {
            $totalFee = $feeConfig['value'];
        }

        return [
            'type' => $feeConfig['type'],
            'percentage' => $feeConfig['value'] ?? 0,
            'fixed' => $feeConfig['fixed'] ?? 0,
            'total' => round($totalFee, 2),
        ];
    }

    /**
     * Process payment with payment gateway.
     */
    private function processWithGateway(Payment $payment, array $data): array
    {
        // This would integrate with actual payment gateways like Stripe, PayPal, etc.
        // For now, we'll simulate the process

        try {
            // Simulate API call to payment gateway
            $response = $this->simulateGatewayCall($payment, $data);

            if ($response['status'] === 'approved') {
                return [
                    'success' => true,
                    'response' => $response,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? 'Payment declined',
                    'response' => $response,
                ];
            }
        } catch (Exception $e) {
            Log::error('Payment gateway error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment processing failed',
                'response' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Process refund with payment gateway.
     */
    private function processRefundWithGateway(Payment $payment, float $amount, array $data): array
    {
        // This would integrate with actual payment gateways for refunds
        // For now, we'll simulate the process

        try {
            // Simulate API call to payment gateway for refund
            $response = $this->simulateRefundCall($payment, $amount, $data);

            if ($response['status'] === 'approved') {
                return [
                    'success' => true,
                    'response' => $response,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? 'Refund declined',
                    'response' => $response,
                ];
            }
        } catch (Exception $e) {
            Log::error('Refund gateway error', [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Refund processing failed',
                'response' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Simulate payment gateway call.
     */
    private function simulateGatewayCall(Payment $payment, array $data): array
    {
        // Simulate different scenarios based on payment method
        $successRate = match ($payment->payment_method) {
            'credit_card' => 95,
            'debit_card' => 97,
            'paypal' => 96,
            'stripe' => 98,
            'bank_transfer' => 90,
            default => 95,
        };

        $random = rand(1, 100);

        if ($random <= $successRate) {
            return [
                'status' => 'approved',
                'transaction_id' => 'gw_' . uniqid(),
                'auth_code' => rand(100000, 999999),
                'response_code' => '00',
                'response_message' => 'Approved',
                'avs_result' => 'Y',
                'cvv_result' => 'M',
                'processed_at' => now()->toISOString(),
            ];
        } else {
            return [
                'status' => 'declined',
                'response_code' => '05',
                'response_message' => 'Do Not Honor',
                'error' => 'Payment declined by issuer',
            ];
        }
    }

    /**
     * Simulate refund gateway call.
     */
    private function simulateRefundCall(Payment $payment, float $amount, array $data): array
    {
        // Simulate refund processing
        $successRate = 98; // 98% success rate for refunds
        $random = rand(1, 100);

        if ($random <= $successRate) {
            return [
                'status' => 'approved',
                'refund_id' => 'refund_' . uniqid(),
                'refund_amount' => $amount,
                'response_code' => '00',
                'response_message' => 'Refund Approved',
                'processed_at' => now()->toISOString(),
            ];
        } else {
            return [
                'status' => 'declined',
                'response_code' => '15',
                'response_message' => 'Refund Not Allowed',
                'error' => 'Refund declined by issuer',
            ];
        }
    }

    /**
     * Generate unique transaction ID.
     */
    private function generateTransactionId(): string
    {
        return 'txn_' . uniqid() . '_' . time();
    }
}
