<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'user_id',
        'parent_id',
        'author_name',
        'author_email',
        'author_url',
        'author_ip',
        'content',
        'status',
        'approved_at',
        'approved_by',
        'spam_score',
        'helpful_count',
        'report_count',
        'metadata',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'metadata' => 'array',
        'spam_score' => 'decimal:2',
    ];

    protected $dates = [
        'approved_at',
    ];

    /**
     * The relationships that should always be loaded.
     */
    protected $with = [
        'content',
        'user',
        'parent',
        'children',
        'reports',
    ];

    /**
     * Get the content this comment belongs to.
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    /**
     * Get the user who wrote this comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the parent comment.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ContentComment::class, 'parent_id');
    }

    /**
     * Get the child comments.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ContentComment::class, 'parent_id');
    }

    /**
     * Get the reports for this comment.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(ContentCommentReport::class, 'comment_id');
    }

    /**
     * Scope a query to only include approved comments.
     */
    public function scopeApproved($query): void
    {
        $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include pending comments.
     */
    public function scopePending($query): void
    {
        $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include spam comments.
     */
    public function scopeSpam($query): void
    {
        $query->where('spam_score', '>', 50);
    }

    /**
     * Check if comment is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if comment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if comment is spam.
     */
    public function isSpam(): bool
    {
        return $this->spam_score > 50;
    }

    /**
     * Increment helpful count.
     */
    public function incrementHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Increment report count.
     */
    public function incrementReport(): void
    {
        $this->increment('report_count');
    }

    /**
     * Approve the comment.
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
    }

    /**
     * Reject the comment.
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'metadata' => array_merge($this->metadata ?? [], [
                'rejection_reason' => $reason,
                'rejected_at' => now(),
                'rejected_by' => auth()->id(),
            ]),
        ]);
    }
}
