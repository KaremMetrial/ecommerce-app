<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentEventTracked extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_event_id',
        'event_type',
        'tracked_at',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'tracked_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the content event this tracking belongs to.
     */
    public function contentEvent(): BelongsTo
    {
        return $this->belongsTo(ContentEvent::class, 'content_event_id');
    }

    /**
     * Get the user this tracking belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the analytics this tracking belongs to.
     */
    public function analytics(): BelongsTo
    {
        return $this->belongsTo(ContentAnalytics::class, 'analytics_id');
    }

    /**
     * Scope a query to only include events for a specific user.
     */
    public function scopeForUser($query, $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include events in a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate): void
    {
        $query->whereBetween('tracked_at', $startDate, $endDate);
    }

    /**
     * Scope a query to only include events of a specific type.
     */
    public function scopeType($query, $type): void
    {
        $query->where('event_type', $type);
    }
}
