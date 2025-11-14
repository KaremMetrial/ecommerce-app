<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'payment_method' => $this->payment_method,
            'payment_method_display' => $this->getPaymentMethodDisplay(),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'status_display' => $this->getStatusDisplay(),
            'transaction_id' => $this->transaction_id,
            'gateway_response' => $this->gateway_response,
            'paid_at' => $this->paid_at,
            'failed_at' => $this->failed_at,
            'refunded_at' => $this->refunded_at,
            'refund_amount' => $this->refund_amount,
            'refund_reason' => $this->refund_reason,
            'metadata' => $this->metadata,
            'can_refund' => $this->canRefund(),
            'can_retry' => $this->canRetry(),
            'is_refundable' => $this->isRefundable(),
            'refund_deadline' => $this->getRefundDeadline(),
            'formatted_amount' => $this->getFormattedAmount(),
            'formatted_refund_amount' => $this->getFormattedRefundAmount(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'status' => $this->order->status,
                    'total' => $this->order->total,
                    'currency' => $this->order->currency,
                ];
            }),
        ];
    }

    /**
     * Get payment method display name.
     */
    private function getPaymentMethodDisplay(): string
    {
        return match ($this->payment_method) {
            'credit_card' => __('Credit Card'),
            'debit_card' => __('Debit Card'),
            'paypal' => __('PayPal'),
            'stripe' => __('Stripe'),
            'bank_transfer' => __('Bank Transfer'),
            default => ucfirst(str_replace('_', ' ', $this->payment_method)),
        };
    }

    /**
     * Get status display name.
     */
    private function getStatusDisplay(): string
    {
        return match ($this->status) {
            'pending' => __('Pending'),
            'processing' => __('Processing'),
            'completed' => __('Completed'),
            'failed' => __('Failed'),
            'refunded' => __('Refunded'),
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if payment can be refunded.
     */
    private function canRefund(): bool
    {
        return $this->status === 'completed'
            && !$this->refunded_at
            && $this->paid_at
            && $this->paid_at->diffInDays(now()) <= 180;
    }

    /**
     * Check if payment can be retried.
     */
    private function canRetry(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment is refundable.
     */
    private function isRefundable(): bool
    {
        return $this->canRefund();
    }

    /**
     * Get refund deadline.
     */
    private function getRefundDeadline(): ?string
    {
        if (!$this->paid_at) {
            return null;
        }

        return $this->paid_at->addDays(180)->toDateString();
    }

    /**
     * Get formatted amount.
     */
    private function getFormattedAmount(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get formatted refund amount.
     */
    private function getFormattedRefundAmount(): ?string
    {
        if (!$this->refund_amount) {
            return null;
        }

        return number_format($this->refund_amount, 2) . ' ' . $this->currency;
    }
}
