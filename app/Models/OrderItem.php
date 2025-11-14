<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'product_sku',
        'variant_name',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2);
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return number_format($this->total_price, 2);
    }

    public function getProductImageAttribute(): ?string
    {
        return $this->product_data['image'] ?? null;
    }

    public function getProductSlugAttribute(): string
    {
        return $this->product_data['slug'] ?? '';
    }

    public function getVariantAttributesAttribute(): ?array
    {
        return $this->product_data['variant_attributes'] ?? null;
    }

    public function restoreStock(): void
    {
        if ($this->variant) {
            $this->variant->increaseStock($this->quantity);
        } elseif ($this->product) {
            $this->product->increaseStock($this->quantity);
        }
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->product_name;

        if ($this->variant_name) {
            $name .= ' - ' . $this->variant_name;
        }

        return $name;
    }
}
