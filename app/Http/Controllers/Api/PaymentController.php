<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Requests\Payment\RefundPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CacheService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        private CacheService $cacheService,
        private PaymentService $paymentService
    ) {}

    /**
     * Display a listing of the user's payments.
     */
    public function index(Request $request): JsonResource
    {
        $user = Auth::user();
        $cacheKey = "user_payments_{$user->id}";

        $payments = $this->cacheService->remember($cacheKey, now()->addHours(2), function () use ($user, $request) {
            return $user->payments()
                ->with(['order'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));
        });

        return PaymentResource::collection($payments);
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);

        $cacheKey = "payment_{$payment->id}";

        $cachedPayment = $this->cacheService->remember($cacheKey, now()->addHour(), function () use ($payment) {
            return $payment->load(['order', 'order.items', 'order.user']);
        });

        return $this->successResponse(
            new PaymentResource($cachedPayment)
        );
    }

    /**
     * Process a payment for an order.
     */
    public function process(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        // Validate order status
        if ($order->payment_status === 'paid') {
            return $this->errorResponse(__('Order is already paid'), 422);
        }

        if ($order->payment_status === 'refunded') {
            return $this->errorResponse(__('Cannot process payment for refunded order'), 422);
        }

        try {
            $payment = $this->paymentService->processPayment($order, $request->validated());

            // Clear caches
            $this->cacheService->forget("order_{$order->id}");
            $this->cacheService->forget("user_payments_{$order->user_id}");

            return $this->successResponse(
                new PaymentResource($payment),
                __('Payment processed successfully'),
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                __('Payment processing failed: :message', ['message' => $e->getMessage()]),
                500
            );
        }
    }

    /**
     * Refund a payment.
     */
    public function refund(RefundPaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('refund', $payment);

        if ($payment->status !== 'completed') {
            return $this->errorResponse(__('Only completed payments can be refunded'), 422);
        }

        if ($payment->refunded_at) {
            return $this->errorResponse(__('Payment has already been refunded'), 422);
        }

        try {
            $refund = $this->paymentService->processRefund($payment, $request->validated());

            // Clear caches
            $this->cacheService->forget("payment_{$payment->id}");
            $this->cacheService->forget("order_{$payment->order_id}");
            $this->cacheService->forget("user_payments_{$payment->order->user_id}");

            return $this->successResponse(
                new PaymentResource($refund),
                __('Payment refunded successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                __('Refund processing failed: :message', ['message' => $e->getMessage()]),
                500
            );
        }
    }

    /**
     * Get payment methods available.
     */
    public function methods(): JsonResponse
    {
        $cacheKey = 'payment_methods';

        $methods = $this->cacheService->remember($cacheKey, now()->addDay(), function () {
            return [
                [
                    'id' => 'credit_card',
                    'name' => __('Credit Card'),
                    'description' => __('Pay with Visa, Mastercard, Amex, etc.'),
                    'icon' => 'credit-card',
                    'enabled' => true,
                    'fees' => [
                        'type' => 'percentage',
                        'value' => 2.9,
                        'currency' => 'USD',
                    ],
                ],
                [
                    'id' => 'debit_card',
                    'name' => __('Debit Card'),
                    'description' => __('Pay with your debit card'),
                    'icon' => 'credit-card',
                    'enabled' => true,
                    'fees' => [
                        'type' => 'percentage',
                        'value' => 2.9,
                        'currency' => 'USD',
                    ],
                ],
                [
                    'id' => 'paypal',
                    'name' => __('PayPal'),
                    'description' => __('Pay with your PayPal account'),
                    'icon' => 'paypal',
                    'enabled' => true,
                    'fees' => [
                        'type' => 'percentage',
                        'value' => 3.4,
                        'currency' => 'USD',
                    ],
                ],
                [
                    'id' => 'stripe',
                    'name' => __('Stripe'),
                    'description' => __('Secure payment via Stripe'),
                    'icon' => 'stripe',
                    'enabled' => true,
                    'fees' => [
                        'type' => 'percentage',
                        'value' => 2.9,
                        'currency' => 'USD',
                    ],
                ],
                [
                    'id' => 'bank_transfer',
                    'name' => __('Bank Transfer'),
                    'description' => __('Direct bank transfer'),
                    'icon' => 'bank',
                    'enabled' => true,
                    'fees' => [
                        'type' => 'fixed',
                        'value' => 5.0,
                        'currency' => 'USD',
                    ],
                ],
            ];
        });

        return $this->successResponse($methods);
    }

    /**
     * Get payment status for an order.
     */
    public function status(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $cacheKey = "order_payment_status_{$order->id}";

        $status = $this->cacheService->remember($cacheKey, now()->addMinutes(5), function () use ($order) {
            $payment = $order->payment;

            return [
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
                'payment_method' => $payment?->payment_method,
                'amount' => $payment?->amount,
                'currency' => $payment?->currency,
                'transaction_id' => $payment?->transaction_id,
                'paid_at' => $payment?->paid_at,
                'failed_at' => $payment?->failed_at,
                'refunded_at' => $payment?->refunded_at,
                'refund_amount' => $payment?->refund_amount,
                'can_refund' => $payment?->status === 'completed' && !$payment?->refunded_at,
                'can_retry' => $payment?->status === 'failed',
            ];
        });

        return $this->successResponse($status);
    }

    /**
     * Retry a failed payment.
     */
    public function retry(Payment $payment): JsonResponse
    {
        $this->authorize('retry', $payment);

        if ($payment->status !== 'failed') {
            return $this->errorResponse(__('Only failed payments can be retried'), 422);
        }

        try {
            $retryPayment = $this->paymentService->retryPayment($payment);

            // Clear caches
            $this->cacheService->forget("payment_{$payment->id}");
            $this->cacheService->forget("order_{$payment->order_id}");
            $this->cacheService->forget("user_payments_{$payment->order->user_id}");

            return $this->successResponse(
                new PaymentResource($retryPayment),
                __('Payment retry initiated successfully')
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                __('Payment retry failed: :message', ['message' => $e->getMessage()]),
                500
            );
        }
    }

    /**
     * Get payment history for an order.
     */
    public function history(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $cacheKey = "order_payment_history_{$order->id}";

        $history = $this->cacheService->remember($cacheKey, now()->addHour(), function () use ($order) {
            return $order->payments()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_method' => $payment->payment_method,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'transaction_id' => $payment->transaction_id,
                        'created_at' => $payment->created_at,
                        'paid_at' => $payment->paid_at,
                        'failed_at' => $payment->failed_at,
                        'refunded_at' => $payment->refunded_at,
                        'refund_amount' => $payment->refund_amount,
                        'refund_reason' => $payment->refund_reason,
                    ];
                });
        });

        return $this->successResponse($history);
    }

    /**
     * Calculate payment fees.
     */
    public function calculateFees(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:credit_card,debit_card,paypal,stripe,bank_transfer',
            'currency' => 'string|size:3',
        ]);

        $amount = $request->get('amount');
        $method = $request->get('payment_method');
        $currency = $request->get('currency', 'USD');

        $fees = $this->paymentService->calculateFees($amount, $method, $currency);

        return $this->successResponse([
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $method,
            'fees' => $fees,
            'total_amount' => $amount + $fees['total'],
        ]);
    }
}
