<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'transaction_id',
        'status',
        'amount',
        'currency',
        'gateway_response',
        'notes',
        'paid_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        $methods = [
            'stripe' => 'Credit Card (Stripe)',
            'paypal' => 'PayPal',
            'cod' => 'Cash on Delivery',
            'bank_transfer' => 'Bank Transfer',
        ];

        return $methods[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function complete(?string $transactionId = null): void
    {
        $this->update([
            'status' => 'completed',
            'transaction_id' => $transactionId ?? $this->transaction_id,
            'paid_at' => now(),
        ]);

        // Update order payment status
        $this->order->update(['payment_status' => 'paid']);
    }

    public function fail(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'notes' => $reason,
        ]);

        // Update order payment status
        $this->order->update(['payment_status' => 'failed']);
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason,
        ]);
    }

    public function refund(string $reason = null): void
    {
        $this->update([
            'status' => 'refunded',
            'notes' => $reason,
        ]);

        // Update order payment status
        $this->order->update(['payment_status' => 'refunded']);
    }

    public function canBeCompleted(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function canBeFailed(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function canBeRefunded(): bool
    {
        return $this->status === 'completed';
    }
}
