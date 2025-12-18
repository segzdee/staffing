<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * MessageModerationLog Model
 *
 * COM-005: Communication Compliance
 * Tracks moderation actions on messages for audit and compliance purposes.
 *
 * @property int $id
 * @property string $moderatable_type
 * @property int $moderatable_id
 * @property int $user_id
 * @property string $original_content
 * @property string|null $moderated_content
 * @property array|null $detected_issues
 * @property string $action
 * @property string $severity
 * @property bool $requires_review
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property string|null $review_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\User|null $reviewer
 * @property-read Model $moderatable
 */
class MessageModerationLog extends Model
{
    use HasFactory;

    /**
     * Action constants.
     */
    public const ACTION_ALLOWED = 'allowed';

    public const ACTION_FLAGGED = 'flagged';

    public const ACTION_BLOCKED = 'blocked';

    public const ACTION_REDACTED = 'redacted';

    /**
     * Severity constants.
     */
    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_CRITICAL = 'critical';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'moderatable_type',
        'moderatable_id',
        'user_id',
        'original_content',
        'moderated_content',
        'detected_issues',
        'action',
        'severity',
        'requires_review',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'detected_issues' => 'array',
        'requires_review' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the moderated content (polymorphic).
     */
    public function moderatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who sent the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed this moderation.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Mark as reviewed by an admin.
     */
    public function markAsReviewed(User $reviewer, string $action, ?string $notes = null): bool
    {
        return $this->update([
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'action' => $action,
            'requires_review' => false,
            'review_notes' => $notes,
        ]);
    }

    /**
     * Check if this log has been reviewed.
     */
    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }

    /**
     * Check if content was blocked or redacted.
     */
    public function wasModified(): bool
    {
        return in_array($this->action, [self::ACTION_BLOCKED, self::ACTION_REDACTED]);
    }

    /**
     * Get the issue types detected.
     */
    public function getIssueTypes(): array
    {
        if (! $this->detected_issues) {
            return [];
        }

        return array_unique(array_column($this->detected_issues, 'type'));
    }

    /**
     * Get the highest confidence issue.
     */
    public function getHighestConfidenceIssue(): ?array
    {
        if (! $this->detected_issues || empty($this->detected_issues)) {
            return null;
        }

        return collect($this->detected_issues)
            ->sortByDesc('confidence')
            ->first();
    }

    /**
     * Scope for logs requiring review.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequiringReview($query)
    {
        return $query->where('requires_review', true)
            ->whereNull('reviewed_at');
    }

    /**
     * Scope for reviewed logs.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReviewed($query)
    {
        return $query->whereNotNull('reviewed_at');
    }

    /**
     * Scope for logs by action.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for logs by severity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for logs above a certain severity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAboveSeverity($query, string $severity)
    {
        $levels = [
            self::SEVERITY_LOW => 1,
            self::SEVERITY_MEDIUM => 2,
            self::SEVERITY_HIGH => 3,
            self::SEVERITY_CRITICAL => 4,
        ];

        $minLevel = $levels[$severity] ?? 1;

        return $query->whereIn('severity', array_keys(array_filter($levels, fn ($level) => $level >= $minLevel)));
    }

    /**
     * Scope for flagged content.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFlagged($query)
    {
        return $query->where('action', self::ACTION_FLAGGED);
    }

    /**
     * Scope for blocked content.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBlocked($query)
    {
        return $query->where('action', self::ACTION_BLOCKED);
    }

    /**
     * Scope for logs by user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for critical issues.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }
}
