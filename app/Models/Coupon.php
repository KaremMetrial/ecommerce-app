<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_amount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'applicable_products',
        'applicable_categories',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeNotUsedUp($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('usage_limit')
              ->orWhere('used_count', '<', \DB::raw('usage_limit'));
        });
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function isValidForUser(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->usage_limit_per_user) {
            $userUsageCount = $user->orders()
                ->whereHas('cart', function ($query) {
                    $query->whereJsonContains('coupon_data->code', $this->code);
                })
                ->count();

            if ($userUsageCount >= $this->usage_limit_per_user) {
                return false;
            }
        }

        return true;
    }

    public function isValidForAmount(float $amount): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->minimum_amount && $amount < $this->minimum_amount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'fixed') {
            return min($this->value, $amount);
        }

        if ($this->type === 'percentage') {
            return ($amount * $this->value) / 100;
        }

        return 0;
    }

    public function isApplicableToProduct(Product $product): bool
    {
        if (!$this->applicable_products && !$this->applicable_categories) {
            return true; // Applies to all products
        }

        if ($this->applicable_products && in_array($product->id, $this->applicable_products)) {
            return true;
        }

        if ($this->applicable_categories) {
            $productCategoryIds = $product->categories()->pluck('categories.id')->toArray();
            return !empty(array_intersect($productCategoryIds, $this->applicable_categories));
        }

        return false;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'fixed') {
            return '$' . number_format($this->value, 2);
        }

        if ($this->type === 'percentage') {
            return number_format($this->value, 0) . '%';
        }

        return (string) $this->value;
    }

    public function getFormattedMinimumAmountAttribute(): ?string
    {
        return $this->minimum_amount ? '$' . number_format($this->minimum_amount, 2) : null;
    }

    public function getUsageRemainingAttribute(): ?int
    {
        if (!$this->usage_limit) {
            return null;
        }

        return max(0, $this->usage_limit - $this->used_count);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    public function getIsUsedUpAttribute(): bool
    {
        return $this->usage_limit && $this->used_count >= $this->usage_limit;
    }
}
