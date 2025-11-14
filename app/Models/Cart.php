<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total',
        'currency',
        'coupon_data',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'coupon_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('total_price');

        // Apply coupon discount if exists
        if ($this->coupon_data) {
            $this->discount_amount = $this->calculateCouponDiscount();
        }

        $this->total = max(0, $this->subtotal + $this->tax_amount + $this->shipping_amount - $this->discount_amount);
        $this->save();
    }

    private function calculateCouponDiscount(): float
    {
        if (!$this->coupon_data) {
            return 0;
        }

        $coupon = Coupon::where('code', $this->coupon_data['code'])
            ->active()
            ->first();

        if (!$coupon) {
            return 0;
        }

        $discount = 0;

        if ($coupon->type === 'fixed') {
            $discount = min($coupon->value, $this->subtotal);
        } elseif ($coupon->type === 'percentage') {
            $discount = ($this->subtotal * $coupon->value) / 100;
        }

        // Check minimum amount requirement
        if ($coupon->minimum_amount && $this->subtotal < $coupon->minimum_amount) {
            return 0;
        }

        return $discount;
    }

    public function addItem(Product $product, int $quantity = 1, ?ProductVariant $variant = null): CartItem
    {
        $existingItem = $this->items()
            ->where('product_id', $product->id)
            ->when($variant, function ($query) use ($variant) {
                return $query->where('product_variant_id', $variant->id);
            })
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            $existingItem->updateTotalPrice();
            return $existingItem;
        }

        $unitPrice = $variant ? $variant->price : $product->price;

        return $this->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'product_data' => $this->getProductSnapshot($product, $variant),
        ]);
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
        $this->calculateTotals();
    }

    public function updateItemQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($item);
            return;
        }

        $item->update(['quantity' => $quantity]);
        $item->updateTotalPrice();
        $this->calculateTotals();
    }

    public function clear(): void
    {
        $this->items()->delete();
        $this->update([
            'subtotal' => 0,
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total' => 0,
            'coupon_data' => null,
        ]);
    }

    public function applyCoupon(Coupon $coupon): bool
    {
        if (!$coupon->isValid()) {
            return false;
        }

        if ($coupon->minimum_amount && $this->subtotal < $coupon->minimum_amount) {
            return false;
        }

        $this->coupon_data = [
            'code' => $coupon->code,
            'name' => $coupon->name,
            'type' => $coupon->type,
            'value' => $coupon->value,
        ];

        $this->calculateTotals();
        return true;
    }

    public function removeCoupon(): void
    {
        $this->coupon_data = null;
        $this->calculateTotals();
    }

    public function getItemCount(): int
    {
        return $this->items()->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    private function getProductSnapshot(Product $product, ?ProductVariant $variant): array
    {
        $snapshot = [
            'name' => $product->name,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'image' => $product->first_image,
        ];

        if ($variant) {
            $snapshot['variant_name'] = $variant->name;
            $snapshot['variant_sku'] = $variant->sku;
            $snapshot['variant_attributes'] = $variant->attributes;
            $snapshot['image'] = $variant->image ?? $snapshot['image'];
        }

        return $snapshot;
    }

    public static function getForUser(User $user): self
    {
        return static::firstOrCreate(['user_id' => $user->id]);
    }

    public static function getForSession(string $sessionId): self
    {
        return static::firstOrCreate(['session_id' => $sessionId]);
    }
}
