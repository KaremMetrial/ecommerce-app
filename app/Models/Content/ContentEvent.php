<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'analytics_id',
        'content_id',
        'event_type',
        'event_name',
        'event_data',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * Event types.
     */
    const TYPE_PAGE_VIEW = 'page_view';
    const TYPE_CONTENT_SHARE = 'content_share';
    const TYPE_FORM_SUBMIT = 'form_submit';
    const TYPE_SEARCH = 'search';
    const TYPE_DOWNLOAD = 'download';
    const TYPE_VIDEO_PLAY = 'video_play';
    const TYPE_PURCHASE = 'purchase';
    const TYPE_ADD_TO_CART = 'add_to_cart';
    const TYPE_CHECKOUT_START = 'checkout_start';
    const TYPE_CHECKOUT_COMPLETE = 'checkout_complete';
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_REGISTRATION = 'registration';
    const TYPE_NEWSLETTER_SUBSCRIBE = 'newsletter_subscribe';
    const TYPE_SOCIAL_SHARE = 'social_share';
    const TYPE_COMMENT = 'comment';
    const TYPE_REVIEW = 'review';
    const TYPE_WISHLIST_ADD = 'wishlist_add';
    const TYPE_COUPON_APPLY = 'coupon_apply';

    /**
     * Get the content this event belongs to.
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * Get the user this event belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the analytics this event belongs to.
     */
    public function analytics(): BelongsTo
    {
        return $this->belongsTo(ContentAnalytics::class, 'analytics_id');
    }

    /**
     * Scope a query to only include events of a specific type.
     */
    public function scopeType($query, $type): void
    {
        $query->where('event_type', $type);
    }

    /**
     * Scope a query to only include events in a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate): void
    {
        $query->whereBetween('occurred_at', $startDate, $endDate);
    }

    /**
     * Track a content event.
     */
    public static function track(
        string $eventType,
        int $contentId,
        array $eventData = [],
        ?User $user = null
    ): void {
        $event = [
            'event_type' => $eventType,
            'content_id' => $contentId,
            'event_data' => $eventData,
            'user_id' => $user?->id,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'occurred_at' => now(),
        ];

        // Create event record
        $record = static::create($event);

        // Fire event for real-time processing
        event(new \App\Events\ContentEventTracked($record));

        // Log for analytics
        \Log::info('Content event tracked', [
            'event_type' => $eventType,
            'content_id' => $contentId,
            'user_id' => $user?->id,
        ]);
    }
}
