<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'is_public',
        'notes',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function getItemCount(): int
    {
        return $this->items()->count();
    }

    public function addItem(Product $product, ?ProductVariant $variant = null, ?string $notes = null): WishlistItem
    {
        return $this->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'notes' => $notes,
        ]);
    }

    public function removeItem(WishlistItem $item): void
    {
        $item->delete();
    }

    public function hasProduct(Product $product, ?ProductVariant $variant = null): bool
    {
        return $this->items()
            ->where('product_id', $product->id)
            ->when($variant, function ($query) use ($variant) {
                return $query->where('product_variant_id', $variant->id);
            })
            ->exists();
    }

    public function getItemForProduct(Product $product, ?ProductVariant $variant = null): ?WishlistItem
    {
        return $this->items()
            ->where('product_id', $product->id)
            ->when($variant, function ($query) use ($variant) {
                return $query->where('product_variant_id', $variant->id);
            })
            ->first();
    }

    public static function getDefaultForUser(User $user): self
    {
        return static::firstOrCreate([
            'user_id' => $user->id,
            'name' => 'My Wishlist',
        ], [
            'is_public' => false,
        ]);
    }

    public function clear(): void
    {
        $this->items()->delete();
    }

    public function getTotalValueAttribute(): float
    {
        return $this->items()->sum(function ($item) {
            return $item->product->price;
        });
    }

    public function getFormattedTotalValueAttribute(): string
    {
        return number_format($this->total_value, 2);
    }
}
