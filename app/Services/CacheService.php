<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CacheService
{
    /**
     * Cache tags for different types of data
     */
    const TAGS = [
        'products' => 'products',
        'categories' => 'categories',
        'cart' => 'cart',
        'orders' => 'orders',
        'users' => 'users',
        'coupons' => 'coupons',
        'settings' => 'settings',
        'search' => 'search',
        'featured' => 'featured',
    ];

    /**
     * Cache TTL values in seconds
     */
    const TTL = [
        'short' => 300,      // 5 minutes
        'medium' => 1800,    // 30 minutes
        'long' => 3600,     // 1 hour
        'very_long' => 86400, // 24 hours
        'daily' => 86400,    // 24 hours
        'weekly' => 604800,   // 7 days
        'monthly' => 2592000, // 30 days
    ];

    /**
     * Remember data with tags
     */
    public static function remember(string $key, $callback, int $ttl = self::TTL['medium'], array $tags = []): mixed
    {
        if (config('cache.default') === 'redis') {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Remember data forever with tags
     */
    public static function rememberForever(string $key, $callback, array $tags = []): mixed
    {
        if (config('cache.default') === 'redis') {
            return Cache::tags($tags)->rememberForever($key, $callback);
        }

        return Cache::rememberForever($key, $callback);
    }

    /**
     * Cache a value with tags
     */
    public static function put(string $key, $value, int $ttl = self::TTL['medium'], array $tags = []): bool
    {
        if (config('cache.default') === 'redis') {
            return Cache::tags($tags)->put($key, $value, $ttl);
        }

        return Cache::put($key, $value, $ttl);
    }

    /**
     * Cache a value forever with tags
     */
    public static function putForever(string $key, $value, array $tags = []): bool
    {
        if (config('cache.default') === 'redis') {
            return Cache::tags($tags)->forever($key, $value);
        }

        return Cache::forever($key, $value);
    }

    /**
     * Get cached data
     */
    public static function get(string $key, $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Check if key exists in cache
     */
    public static function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Forget cached data
     */
    public static function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Clear cache by tags
     */
    public static function clearTags(array $tags): bool
    {
        if (config('cache.default') === 'redis') {
            return Cache::tags($tags)->flush();
        }

        return true; // Fallback for file cache
    }

    /**
     * Clear all cache
     */
    public static function clear(): bool
    {
        return Cache::flush();
    }

    /**
     * Cache product data
     */
    public static function cacheProduct(Model $product): void
    {
        $key = "product_{$product->id}";
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => $product->price,
            'compare_price' => $product->compare_price,
            'quantity' => $product->quantity,
            'is_active' => $product->is_active,
            'is_featured' => $product->is_featured,
            'images' => $product->images,
            'first_image' => $product->first_image,
            'categories' => $product->categories->pluck('id')->toArray(),
            'updated_at' => $product->updated_at->timestamp,
        ];

        self::put($key, $data, self::TTL['long'], [self::TAGS['products']]);
    }

    /**
     * Get cached product data
     */
    public static function getCachedProduct(int $productId): ?array
    {
        $key = "product_{$productId}";
        return self::get($key);
    }

    /**
     * Cache category data
     */
    public static function cacheCategory(Model $category): void
    {
        $key = "category_{$category->id}";
        $data = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image' => $category->image,
            'is_active' => $category->is_active,
            'is_featured' => $category->is_featured,
            'parent_id' => $category->parent_id,
            'children_count' => $category->children()->count(),
            'products_count' => $category->products()->count(),
            'updated_at' => $category->updated_at->timestamp,
        ];

        self::put($key, $data, self::TTL['very_long'], [self::TAGS['categories']]);
    }

    /**
     * Get cached category data
     */
    public static function getCachedCategory(int $categoryId): ?array
    {
        $key = "category_{$categoryId}";
        return self::get($key);
    }

    /**
     * Cache featured products
     */
    public static function cacheFeaturedProducts(Collection $products): void
    {
        $key = 'featured_products';
        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'compare_price' => $product->compare_price,
                'first_image' => $product->first_image,
                'discount_percentage' => $product->discount_percentage,
            ];
        })->toArray();

        self::put($key, $data, self::TTL['medium'], [self::TAGS['products'], self::TAGS['featured']]);
    }

    /**
     * Get cached featured products
     */
    public static function getCachedFeaturedProducts(): ?array
    {
        return self::get('featured_products');
    }

    /**
     * Cache search results
     */
    public static function cacheSearchResults(string $query, Collection $results): void
    {
        $key = "search_" . md5($query);
        $data = [
            'query' => $query,
            'results' => $results->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'first_image' => $product->first_image,
                    'discount_percentage' => $product->discount_percentage,
                ];
            })->toArray(),
            'count' => $results->count(),
            'cached_at' => now()->timestamp,
        ];

        self::put($key, $data, self::TTL['short'], [self::TAGS['search']]);
    }

    /**
     * Get cached search results
     */
    public static function getCachedSearchResults(string $query): ?array
    {
        $key = "search_" . md5($query);
        return self::get($key);
    }

    /**
     * Cache user cart data
     */
    public static function cacheUserCart(int $userId, array $cartData): void
    {
        $key = "user_cart_{$userId}";
        self::put($key, $cartData, self::TTL['short'], [self::TAGS['cart']]);
    }

    /**
     * Get cached user cart data
     */
    public static function getCachedUserCart(int $userId): ?array
    {
        $key = "user_cart_{$userId}";
        return self::get($key);
    }

    /**
     * Invalidate product cache
     */
    public static function invalidateProduct(int $productId): void
    {
        $key = "product_{$productId}";
        self::forget($key);
    }

    /**
     * Invalidate category cache
     */
    public static function invalidateCategory(int $categoryId): void
    {
        $key = "category_{$categoryId}";
        self::forget($key);
    }

    /**
     * Invalidate all product caches
     */
    public static function invalidateProducts(): void
    {
        self::clearTags([self::TAGS['products']]);
    }

    /**
     * Invalidate all category caches
     */
    public static function invalidateCategories(): void
    {
        self::clearTags([self::TAGS['categories']]);
    }

    /**
     * Invalidate search cache
     */
    public static function invalidateSearch(): void
    {
        self::clearTags([self::TAGS['search']]);
    }

    /**
     * Invalidate user cart cache
     */
    public static function invalidateUserCart(int $userId): void
    {
        $key = "user_cart_{$userId}";
        self::forget($key);
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        if (config('cache.default') === 'redis') {
            $redis = Redis::connection('cache');
            $info = $redis->info('memory');

            return [
                'driver' => 'redis',
                'used_memory' => $info['used_memory'] ?? 0,
                'max_memory' => $info['maxmemory'] ?? 0,
                'memory_usage_percentage' => isset($info['used_memory']) && isset($info['maxmemory'])
                    ? round(($info['used_memory'] / $info['maxmemory']) * 100, 2)
                    : 0,
                'connected_clients' => $redis->info('clients')['connected_clients'] ?? 0,
            ];
        }

        return [
            'driver' => config('cache.default'),
            'status' => 'File-based caching (consider Redis for better performance)',
        ];
    }
}
