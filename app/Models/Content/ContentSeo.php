<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentSeo extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'robots_meta',
        'structured_data',
        'json_ld',
        'hreflang_tags',
        'alt_tags',
        'last_modified',
        'priority',
        'change_frequency',
        'sitemap_priority',
    ];

    protected $casts = [
        'structured_data' => 'array',
        'json_ld' => 'array',
        'robots_meta' => 'array',
    ];

    /**
     * Get the content this SEO data belongs to.
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * Get the structured data as array.
     */
    public function getStructuredDataAttribute(): array
    {
        return $this->structured_data ?? [];
    }

    /**
     * Get the JSON-LD data as array.
     */
    public function getJsonLdAttribute(): array
    {
        return $this->json_ld ?? [];
    }

    /**
     * Get the robots meta as array.
     */
    public function getRobotsMetaAttribute(): array
    {
        return $this->robots_meta ?? [];
    }

    /**
     * Generate meta tags for the content.
     */
    public function generateMetaTags(): string
    {
        $tags = [];

        if ($this->meta_keywords) {
            $keywords = array_map('trim', explode(',', $this->meta_keywords));
            foreach ($keywords as $keyword) {
                $tags[] = '<meta name="keywords" content="' . htmlspecialchars($keyword) . '">';
            }
        }

        return implode("\n", $tags);
    }

    /**
     * Generate Open Graph tags.
     */
    public function generateOpenGraphTags(): string
    {
        $tags = [];

        if ($this->og_title) {
            $tags[] = '<meta property="og:title" content="' . htmlspecialchars($this->og_title) . '">';
        }

        if ($this->og_description) {
            $tags[] = '<meta property="og:description" content="' . htmlspecialchars($this->og_description) . '">';
        }

        if ($this->og_image) {
            $tags[] = '<meta property="og:image" content="' . htmlspecialchars($this->og_image) . '">';
        }

        if ($this->og_type) {
            $tags[] = '<meta property="og:type" content="' . htmlspecialchars($this->og_type) . '">';
        }

        if ($this->og_url) {
            $tags[] = '<meta property="og:url" content="' . htmlspecialchars($this->og_url) . '">';
        }

        return implode("\n", $tags);
    }

    /**
     * Generate Twitter Card tags.
     */
    public function generateTwitterCardTags(): string
    {
        $tags = [];

        if ($this->twitter_title) {
            $tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($this->twitter_title) . '">';
        }

        if ($this->twitter_description) {
            $tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($this->twitter_description) . '">';
        }

        if ($this->twitter_image) {
            $tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($this->twitter_image) . '">';
        }

        if ($this->twitter_card) {
            $tags[] = '<meta name="twitter:card" content="' . htmlspecialchars($this->twitter_card) . '">';
        }

        return implode("\n", $tags);
    }

    /**
     * Generate canonical URL.
     */
    public function generateCanonicalUrl(): string
    {
        if ($this->canonical_url) {
            return '<link rel="canonical" href="' . htmlspecialchars($this->canonical_url) . '">';
        }

        return '';
    }

    /**
     * Generate hreflang tags.
     */
    public function generateHreflangTags(): string
    {
        $tags = [];

        if ($this->hreflang_tags) {
            foreach ($this->hreflang_tags as $lang => $url) {
                $tags[] = '<link rel="alternate" hreflang="' . htmlspecialchars($lang) . '" href="' . htmlspecialchars($url) . '">';
            }
        }

        return implode("\n", $tags);
    }

    /**
     * Generate robots meta tag.
     */
    public function generateRobotsMeta(): string
    {
        $robots = $this->robots_meta ?? ['index' => true, 'follow' => true];

        $content = '';
        if ($robots['index'] === false) {
            $content .= 'noindex, ';
        }
        if ($robots['follow'] === false) {
            $content .= 'nofollow, ';
        }

        return '<meta name="robots" content="' . rtrim($content, ', ') . '">';
    }

    /**
     * Generate JSON-LD structured data.
     */
    public function generateJsonLd(): string
    {
        $data = $this->json_ld ?? [];

        if (empty($data)) {
            return '';
        }

        return '<script type="application/ld+json">' . json_encode($data) . '</script>';
    }

    /**
     * Generate alt tags for accessibility.
     */
    public function generateAltTags(): string
    {
        $tags = [];

        if ($this->alt_tags) {
            foreach ($this->alt_tags as $lang => $alt) {
                $tags[] = '<img src="' . htmlspecialchars($this->og_image) . '" alt="' . htmlspecialchars($alt) . '" lang="' . htmlspecialchars($lang) . '">';
            }
        }

        return implode("\n", $tags);
    }
}
