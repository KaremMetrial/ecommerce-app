<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'analytics_id',
        'name',
        'description',
        'type',
        'target_value',
        'current_value',
        'target_date',
        'is_completed',
        'completed_at',
        'priority',
        'category',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Goal types.
     */
    const TYPE_REVENUE = 'revenue';
    const TYPE_CONVERSION = 'conversion';
    const TYPE_ENGAGEMENT = 'engagement';
    const TYPE_TRAFFIC = 'traffic';
    const TYPE_LEAD_GENERATION = 'lead_generation';
    const TYPE_USER_REGISTRATION = 'user_registration';
    const TYPE_CONTENT_CONSUMPTION = 'content_consumption';
    const TYPE_SOCIAL_SHARING = 'social_sharing';
    const TYPE_EMAIL_SUBSCRIPTION = 'email_subscription';

    /**
     * Goal categories.
     */
    const CATEGORY_BUSINESS = 'business';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_SALES = 'sales';
    const CATEGORY_USER_EXPERIENCE = 'user_experience';
    const CATEGORY_CONTENT = 'content';
    const CATEGORY_TECHNICAL = 'technical';

    /**
     * Goal priorities.
     */
    const PRIORITY_CRITICAL = 1;
    const PRIORITY_HIGH = 2;
    const PRIORITY_MEDIUM = 3;
    const PRIORITY_LOW = 4;

    /**
     * Get the analytics this goal belongs to.
     */
    public function analytics(): BelongsTo
    {
        return $this->belongsTo(ContentAnalytics::class, 'analytics_id');
    }

    /**
     * Get the goal category.
     */
    public function getCategoryAttribute(): string
    {
        return $this->category ?? self::CATEGORY_BUSINESS;
    }

    /**
     * Get the goal priority.
     */
    public function getPriorityAttribute(): int
    {
        return $this->priority ?? self::PRIORITY_MEDIUM;
    }

    /**
     * Get the completion percentage.
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->target_value <= 0) {
            return 0;
        }

        return ($this->current_value / $this->target_value) * 100;
    }

    /**
     * Check if goal is completed.
     */
    public function isCompleted(): bool
    {
        return $this->is_completed;
    }

    /**
     * Check if goal is on track.
     */
    public function isOnTrack(): bool
    {
        return !$this->is_completed && $this->target_date && $this->target_date->isFuture();
    }

    /**
     * Mark goal as completed.
     */
    public function complete(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'current_value' => $this->target_value,
        ]);
    }

    /**
     * Update current progress.
     */
    public function updateProgress(float $value): void
    {
        $this->update(['current_value' => $value]);
    }
}
