<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentCommentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'reporter_id',
        'report_type',
        'reason',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
        'action_taken',
        'metadata',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Report types.
     */
    const TYPE_SPAM = 'spam';
    const TYPE_INAPPROPRIATE = 'inappropriate';
    const TYPE_OFF_TOPIC = 'off_topic';
    const TYPE_HARASSMENT = 'harassment';
    const TYPE_OTHER = 'other';

    /**
     * Report statuses.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_DISMISSED = 'dismissed';

    /**
     * Get the comment this report belongs to.
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(ContentComment::class, 'comment_id');
    }

    /**
     * Get the user who made this report.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the admin who reviewed this report.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope a query to only include pending reports.
     */
    public function scopePending($query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include resolved reports.
     */
    public function scopeResolved($query): void
    {
        $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Check if report is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if report is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Approve the report.
     */
    public function approve(?User $reviewedBy = null): void
    {
        $this->update([
            'status' => self::STATUS_REVIEWED,
            'reviewed_by' => $reviewedBy?->id : auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Dismiss the report.
     */
    public function dismiss(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_DISMISSED,
            'action_taken' => 'dismissed',
            'metadata' => array_merge($this->metadata ?? [], [
                'dismissal_reason' => $reason,
                'dismissed_at' => now(),
            ]),
        ]);
    }

    /**
     * Resolve the report.
     */
    public function resolve(string $action): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'action_taken' => $action,
            'metadata' => array_merge($this->metadata ?? [], [
                'resolved_at' => now(),
            ]),
        ]);
    }
}
