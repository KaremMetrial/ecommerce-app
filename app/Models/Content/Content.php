<?php

namespace App\Models\Content;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Tags\HasTags;

class Content extends Model
{
    use HasFactory, SoftDeletes, HasMedia, HasSlug, HasTags;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'type',
        'status',
        'featured_image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'published_at',
        'author_id',
        'parent_id',
        'sort_order',
        'is_featured',
        'allow_comments',
        'require_login',
        'password_protected',
        'view_count',
        'like_count',
        'share_count',
        'seo_score',
        'reading_time',
        'schema_data',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'schema_data' => 'array',
        'meta_data' => 'array',
        'password_protected' => 'boolean',
        'allow_comments' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $dates = [
        'deleted_at',
        'published_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'id' => 'integer',
        'author_id' => 'integer',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'share_count' => 'integer',
        'seo_score' => 'decimal:2',
        'reading_time' => 'integer',
    ];

    /**
     * The relationships that should always be loaded.
     */
    protected $with = [
        'author',
        'parent',
        'children',
        'media',
        'tags',
        'comments',
        'analytics',
    ];

    /**
     * Content types.
     */
    const TYPE_PAGE = 'page';
    const TYPE_BLOG_POST = 'blog_post';
    const TYPE_PRODUCT = 'product';
    const TYPE_CATEGORY = 'category';
    const TYPE_NEWS = 'news';
    const TYPE_FAQ = 'faq';
    const TYPE_TESTIMONIAL = 'testimonial';
    const TYPE_LANDING_PAGE = 'landing_page';
    const TYPE_BANNER = 'banner';
    const TYPE_POPUP = 'popup';
    const TYPE_FORM = 'form';
    const TYPE_WIDGET = 'widget';

    /**
     * Content statuses.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_REVIEW = 'review';
    const STATUS_PUBLISHED = 'published';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        static::addGlobalScope('published', function ($query) {
            return $query->where('status', static::STATUS_PUBLISHED);
        });

        static::addGlobalScope('featured', function ($query) {
            return $query->where('is_featured', true);
        });

        static::addGlobalScope('type', function ($query, $type) {
            return $query->where('type', $type);
        });
    }

    /**
     * Get the author of the content.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the parent content.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'parent_id');
    }

    /**
     * Get the children of the content.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Content::class, 'parent_id');
    }

    /**
     * Get the media for the content.
     */
    public function media(): MorphMany
    {
        return $this->morphToMany(config('media-library.media_model_type'), 'media');
    }

    /**
     * Get the tags for the content.
     */
    public function tags(): MorphMany
    {
        return $this->morphToMany(config('tags.tag_model_type'), 'tags');
    }

    /**
     * Get the comments for the content.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ContentComment::class, 'content_id');
    }

    /**
     * Get the analytics for the content.
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(ContentAnalytics::class, 'content_id');
    }

    /**
     * Get the SEO data for the content.
     */
    public function seo(): HasOne
    {
        return $this->hasOne(ContentSeo::class, 'content_id');
    }

    /**
     * Scope a query to only include published content.
     */
    public function scopePublished($query): void
    {
        $query->where('status', static::STATUS_PUBLISHED);
    }

    /**
     * Scope a query to only include featured content.
     */
    public function scopeFeatured($query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include content of a specific type.
     */
    public function scopeType($query, $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Check if content is published.
     */
    public function isPublished(): bool
    {
        return $this->status === static::STATUS_PUBLISHED;
    }

    /**
     * Check if content is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === static::STATUS_DRAFT;
    }

    /**
     * Get the URL for the content.
     */
    public function getUrlAttribute(): string
    {
        return url('/content/' . $this->slug);
    }

    /**
     * Get the featured image URL.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('featured_image');
    }

    /**
     * Get the reading time in minutes.
     */
    public function getReadingTimeMinutesAttribute(): int
    {
        return $this->reading_time ?? 5;
    }

    /**
     * Increment view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment like count.
     */
    public function incrementLikeCount(): void
    {
        $this->increment('like_count');
    }

    /**
     * Increment share count.
     */
    public function incrementShareCount(): void
    {
        $this->increment('share_count');
    }

    /**
     * Update SEO score.
     */
    public function updateSeoScore(): void
    {
        $score = $this->calculateSeoScore();
        $this->update(['seo_score' => $score]);
    }

    /**
     * Calculate SEO score based on various factors.
     */
    private function calculateSeoScore(): float
    {
        $score = 50.0; // Base score

        // Title optimization
        if ($this->meta_title && strlen($this->meta_title) >= 30 && strlen($this->meta_title) <= 60) {
            $score += 10;
        }

        // Meta description
        if ($this->meta_description && strlen($this->meta_description) >= 120 && strlen($this->meta_description) <= 160) {
            $score += 10;
        }

        // Content length
        $contentLength = strlen(strip_tags($this->content));
        if ($contentLength >= 300 && $contentLength <= 2000) {
            $score += 10;
        }

        // Featured image
        if ($this->featured_image) {
            $score += 5;
        }

        // Reading time
        if ($this->reading_time <= 3) {
            $score += 5;
        }

        // Word count
        $wordCount = str_word_count($this->content);
        if ($wordCount >= 300) {
            $score += 5;
        }

        return min(100.0, $score);
    }
}
