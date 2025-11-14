<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'payment_status',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total',
        'currency',
        'shipping_address',
        'billing_address',
        'notes',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(Str::random(10));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->payment_status));
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total, 2);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return number_format($this->subtotal, 2);
    }

    public function getFormattedTaxAmountAttribute(): string
    {
        return number_format($this->tax_amount, 2);
    }

    public function getFormattedShippingAmountAttribute(): string
    {
        return number_format($this->shipping_amount, 2);
    }

    public function getFormattedDiscountAmountAttribute(): string
    {
        return number_format($this->discount_amount, 2);
    }

    public function getShippingAddressStringAttribute(): string
    {
        if (!$this->shipping_address) {
            return '';
        }

        $address = $this->shipping_address;
        $parts = [
            $address['first_name'] . ' ' . $address['last_name'],
            $address['address_line_1'],
            $address['address_line_2'] ?? null,
            $address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code'],
            $address['country'],
        ];

        return implode("\n", array_filter($parts));
    }

    public function getBillingAddressStringAttribute(): string
    {
        if (!$this->billing_address) {
            return $this->shipping_address_string;
        }

        $address = $this->billing_address;
        $parts = [
            $address['first_name'] . ' ' . $address['last_name'],
            $address['address_line_1'],
            $address['address_line_2'] ?? null,
            $address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code'],
            $address['country'],
        ];

        return implode("\n", array_filter($parts));
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeProcessed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function canBeShipped(): bool
    {
        return $this->status === 'processing';
    }

    public function canBeDelivered(): bool
    {
        return $this->status === 'shipped';
    }

    public function canBeRefunded(): bool
    {
        return in_array($this->status, ['confirmed', 'processing', 'shipped', 'delivered']) &&
               $this->payment_status === 'paid';
    }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    public function process(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function ship(): void
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);
    }

    public function deliver(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);

        // Restore stock for all items
        foreach ($this->items as $item) {
            $item->restoreStock();
        }
    }

    public function refund(): void
    {
        $this->update([
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);
    }

    public function getTotalQuantityAttribute(): int
    {
        return $this->items()->sum('quantity');
    }

    public function getItemCountAttribute(): int
    {
        return $this->items()->count();
    }
}
