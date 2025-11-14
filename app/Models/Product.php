<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'price',
        'compare_price',
        'cost_price',
        'track_quantity',
        'quantity',
        'min_stock_level',
        'is_active',
        'is_featured',
        'is_digital',
        'weight',
        'dimensions',
        'images',
        'attributes',
        'meta',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'track_quantity' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_digital' => 'boolean',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'images' => 'array',
        'attributes' => 'array',
        'meta' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'PRD-' . strtoupper(Str::random(8));
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_quantity', false)
              ->orWhere('quantity', '>', 0);
        });
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_quantity', true)
            ->where('quantity', '<=', \DB::raw('min_stock_level'))
            ->where('quantity', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('track_quantity', true)
            ->where('quantity', '<=', 0);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }

    public function scopePriceRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    public function getIsInStockAttribute(): bool
    {
        return !$this->track_quantity || $this->quantity > 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->track_quantity &&
               $this->quantity <= $this->min_stock_level &&
               $this->quantity > 0;
    }

    public function getHasVariantsAttribute(): bool
    {
        return $this->variants()->count() > 0;
    }

    public function getFirstImageAttribute(): ?string
    {
        return $this->images ? $this->images[0] : null;
    }

    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return null;
        }

        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    public function getFormattedComparePriceAttribute(): ?string
    {
        return $this->compare_price ? number_format($this->compare_price, 2) : null;
    }

    public function getTotalStockAttribute(): int
    {
        if (!$this->track_quantity) {
            return 999; // Unlimited stock
        }

        if ($this->has_variants) {
            return $this->activeVariants()->sum('quantity');
        }

        return $this->quantity;
    }

    public function canBePurchased(int $quantity = 1): bool
    {
        if (!$this->is_active || !$this->published_at) {
            return false;
        }

        if (!$this->track_quantity) {
            return true;
        }

        if ($this->has_variants) {
            return $this->activeVariants()->where('quantity', '>=', $quantity)->exists();
        }

        return $this->quantity >= $quantity;
    }

    public function decreaseStock(int $quantity): void
    {
        if ($this->track_quantity) {
            $this->decrement('quantity', $quantity);
        }
    }

    public function increaseStock(int $quantity): void
    {
        if ($this->track_quantity) {
            $this->increment('quantity', $quantity);
        }
    }
}
