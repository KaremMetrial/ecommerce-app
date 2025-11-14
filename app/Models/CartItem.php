<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'total_price',
        'product_data',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'product_data' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function updateTotalPrice(): void
    {
        $this->total_price = $this->unit_price * $this->quantity;
        $this->save();
    }

    public function getProductNameAttribute(): string
    {
        return $this->product_data['name'] ?? $this->product?->name ?? 'Unknown Product';
    }

    public function getProductSkuAttribute(): string
    {
        return $this->product_data['sku'] ?? $this->product?->sku ?? 'Unknown SKU';
    }

    public function getProductSlugAttribute(): string
    {
        return $this->product_data['slug'] ?? $this->product?->slug ?? '';
    }

    public function getProductImageAttribute(): ?string
    {
        return $this->product_data['image'] ?? $this->product?->first_image ?? null;
    }

    public function getVariantNameAttribute(): ?string
    {
        return $this->product_data['variant_name'] ?? $this->variant?->name ?? null;
    }

    public function getVariantSkuAttribute(): ?string
    {
        return $this->product_data['variant_sku'] ?? $this->variant?->sku ?? null;
    }

    public function getVariantAttributesAttribute(): ?array
    {
        return $this->product_data['variant_attributes'] ?? $this->variant?->attributes ?? null;
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2);
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return number_format($this->total_price, 2);
    }

    public function getIsAvailableAttribute(): bool
    {
        if ($this->variant) {
            return $this->variant->canBePurchased($this->quantity);
        }

        return $this->product?->canBePurchased($this->quantity) ?? false;
    }

    public function getStockLevelAttribute(): int
    {
        if ($this->variant) {
            return $this->variant->quantity;
        }

        return $this->product?->quantity ?? 0;
    }

    public function canIncreaseQuantity(int $additionalQuantity = 1): bool
    {
        $newQuantity = $this->quantity + $additionalQuantity;

        if ($this->variant) {
            return $this->variant->canBePurchased($newQuantity);
        }

        return $this->product?->canBePurchased($newQuantity) ?? false;
    }
}
