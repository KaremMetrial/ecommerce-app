<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'wishlist_id',
        'product_id',
        'product_variant_id',
        'notes',
    ];

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getProductNameAttribute(): string
    {
        return $this->product?->name ?? 'Unknown Product';
    }

    public function getProductSkuAttribute(): string
    {
        return $this->product?->sku ?? 'Unknown SKU';
    }

    public function getProductSlugAttribute(): string
    {
        return $this->product?->slug ?? '';
    }

    public function getProductImageAttribute(): ?string
    {
        return $this->variant?->image ?? $this->product?->first_image ?? null;
    }

    public function getVariantNameAttribute(): ?string
    {
        return $this->variant?->name ?? null;
    }

    public function getVariantSkuAttribute(): ?string
    {
        return $this->variant?->sku ?? null;
    }

    public function getVariantAttributesAttribute(): ?array
    {
        return $this->variant?->attributes ?? null;
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->product_name;

        if ($this->variant_name) {
            $name .= ' - ' . $this->variant_name;
        }

        return $name;
    }

    public function getPriceAttribute(): float
    {
        return $this->variant?->price ?? $this->product?->price ?? 0;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    public function getComparePriceAttribute(): ?float
    {
        return $this->variant?->compare_price ?? $this->product?->compare_price ?? null;
    }

    public function getFormattedComparePriceAttribute(): ?string
    {
        return $this->compare_price ? number_format($this->compare_price, 2) : null;
    }

    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return null;
        }

        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
    }

    public function getIsAvailableAttribute(): bool
    {
        if ($this->variant) {
            return $this->variant->is_active && $this->variant->is_in_stock;
        }

        return $this->product?->is_active && $this->product?->is_in_stock ?? false;
    }

    public function getStockLevelAttribute(): int
    {
        if ($this->variant) {
            return $this->variant->quantity;
        }

        return $this->product?->quantity ?? 0;
    }

    public function canBeAddedToCart(): bool
    {
        if (!$this->is_available) {
            return false;
        }

        if ($this->variant) {
            return $this->variant->canBePurchased();
        }

        return $this->product?->canBePurchased() ?? false;
    }

    public function addToCart(Cart $cart, int $quantity = 1): ?CartItem
    {
        if (!$this->canBeAddedToCart()) {
            return null;
        }

        return $cart->addItem(
            $this->product,
            $quantity,
            $this->variant
        );
    }
}
