<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'date',
        'page_views',
        'unique_visitors',
        'return_visitors',
        'bounce_rate',
        'avg_time_on_page',
        'exit_rate',
        'conversion_rate',
        'revenue',
        'source',
        'medium',
        'campaign',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'referral',
        'search_terms',
        'events',
        'goals',
        'custom_data',
    ];

    protected $casts = [
        'date' => 'date',
        'custom_data' => 'array',
    ];

    /**
     * The relationships that should always be loaded.
     */
    protected $with = [
        'content',
    ];

    /**
     * Get the content this analytics belongs to.
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * Get the goals for this content.
     */
    public function goals(): HasMany
    {
        return $this->hasMany(ContentGoal::class, 'analytics_id');
    }

    /**
     * Get the events for this content.
     */
    public function events(): HasMany
    {
        return $this->hasMany(ContentEvent::class, 'analytics_id');
    }

    /**
     * Track a page view.
     */
    public function trackPageView(): void
    {
        $this->increment('page_views');

        // Update content analytics
        $this->updateContentAnalytics();
    }

    /**
     * Track a unique visitor.
     */
    public function trackUniqueVisitor(): void
    {
        $this->increment('unique_visitors');

        // Update content analytics
        $this->updateContentAnalytics();
    }

    /**
     * Track a returning visitor.
     */
    public function trackReturningVisitor(): void
    {
        $this->increment('return_visitors');

        // Update content analytics
        $this->updateContentAnalytics();
    }

    /**
     * Track a conversion.
     */
    public function trackConversion(float $revenue): void
    {
        $this->increment('conversion_rate');
        $this->increment('revenue', ['amount' => $revenue]);

        // Update content analytics
        $this->updateContentAnalytics();
    }

    /**
     * Track time spent on page.
     */
    public function trackTimeOnPage(int $seconds): void
    {
        // Update average time
        $currentAvg = $this->avg_time_on_page ?? 0;
        $totalViews = $this->page_views ?? 1;

        $newAvg = (($currentAvg * ($totalViews - 1)) + $seconds) / $totalViews;

        $this->update(['avg_time_on_page' => $newAvg]);
    }

    /**
     * Track a bounce.
     */
    public function trackBounce(): void
    {
        $this->increment('bounce_rate');

        // Update content analytics
        $this->updateContentAnalytics();
    }

    /**
     * Update content analytics.
     */
    private function updateContentAnalytics(): void
    {
        $content = $this->content;

        if ($content) {
            $content->analytics()->update([
                'page_views' => \DB::raw('page_views + 1'),
                'avg_time_on_page' => \DB::raw('(avg_time_on_page * page_views + time_on_page) / (page_views + 1)'),
            ]);
        }
    }

    /**
     * Get the conversion rate as percentage.
     */
    public function getConversionRateAttribute(): float
    {
        $totalVisitors = $this->unique_visitors ?? 1;
        $conversions = $this->conversion_rate ?? 0;

        return $totalVisitors > 0 ? ($conversions / $totalVisitors) * 100 : 0;
    }

    /**
     * Get the bounce rate as percentage.
     */
    public function getBounceRateAttribute(): float
    {
        $totalViews = $this->page_views ?? 1;
        $bounces = $this->bounce_rate ?? 0;

        return $totalViews > 0 ? ($bounces / $totalViews) * 100 : 0;
    }

    /**
     * Get the revenue formatted.
     */
    public function getRevenueAttribute(): string
    {
        $revenue = $this->revenue;

        if (is_array($revenue) && isset($revenue['amount'])) {
            return '$' . number_format($revenue['amount'], 2);
        }

        return '$0.00';
    }

    /**
     * Get the average time on page in seconds.
     */
    public function getAvgTimeOnPageAttribute(): float
    {
        return $this->avg_time_on_page ?? 0;
    }

    /**
     * Get the total page views.
     */
    public function getPageViewsAttribute(): int
    {
        return $this->page_views ?? 0;
    }

    /**
     * Get the total unique visitors.
     */
    public function getUniqueVisitorsAttribute(): int
    {
        return $this->unique_visitors ?? 0;
    }

    /**
     * Get the total returning visitors.
     */
    public function getReturningVisitorsAttribute(): int
    {
        return $this->return_visitors ?? 0;
    }

    /**
     * Get the total conversions.
     */
    public function getConversionsAttribute(): int
    {
        return $this->conversion_rate ?? 0;
    }
}
