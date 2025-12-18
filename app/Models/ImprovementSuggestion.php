<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * QUA-005: Continuous Improvement System
 * Model for tracking platform improvement suggestions from users.
 *
 * @property int $id
 * @property int $submitted_by
 * @property string $category
 * @property string $priority
 * @property string $title
 * @property string $description
 * @property string|null $expected_impact
 * @property string $status
 * @property int $votes
 * @property int|null $assigned_to
 * @property string|null $admin_notes
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $submitter
 * @property-read \App\Models\User|null $assignee
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SuggestionVote[] $suggestionVotes
 */
class ImprovementSuggestion extends Model
{
    use HasFactory;

    // Categories
    public const CATEGORY_FEATURE = 'feature';

    public const CATEGORY_BUG = 'bug';

    public const CATEGORY_UX = 'ux';

    public const CATEGORY_PROCESS = 'process';

    public const CATEGORY_PERFORMANCE = 'performance';

    public const CATEGORY_OTHER = 'other';

    // Priorities
    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_CRITICAL = 'critical';

    // Statuses
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_DEFERRED = 'deferred';

    protected $fillable = [
        'submitted_by',
        'category',
        'priority',
        'title',
        'description',
        'expected_impact',
        'status',
        'votes',
        'assigned_to',
        'admin_notes',
        'rejection_reason',
        'reviewed_at',
        'completed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'votes' => 'integer',
    ];

    /**
     * Get all available categories.
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_FEATURE => 'Feature Request',
            self::CATEGORY_BUG => 'Bug Report',
            self::CATEGORY_UX => 'User Experience',
            self::CATEGORY_PROCESS => 'Process Improvement',
            self::CATEGORY_PERFORMANCE => 'Performance',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    /**
     * Get all available priorities.
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical',
        ];
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_DEFERRED => 'Deferred',
        ];
    }

    /**
     * User who submitted this suggestion.
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * User assigned to implement this suggestion.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Votes on this suggestion.
     */
    public function suggestionVotes(): HasMany
    {
        return $this->hasMany(SuggestionVote::class, 'suggestion_id');
    }

    /**
     * Upvotes on this suggestion.
     */
    public function upvotes(): HasMany
    {
        return $this->suggestionVotes()->where('vote_type', 'up');
    }

    /**
     * Downvotes on this suggestion.
     */
    public function downvotes(): HasMany
    {
        return $this->suggestionVotes()->where('vote_type', 'down');
    }

    /**
     * Check if a user has voted on this suggestion.
     */
    public function hasUserVoted(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->suggestionVotes()->where('user_id', $userId)->exists();
    }

    /**
     * Get the vote type for a specific user.
     */
    public function getUserVote(User|int $user): ?string
    {
        $userId = $user instanceof User ? $user->id : $user;
        $vote = $this->suggestionVotes()->where('user_id', $userId)->first();

        return $vote?->vote_type;
    }

    /**
     * Recalculate and update the votes count.
     */
    public function recalculateVotes(): int
    {
        $upvotes = $this->upvotes()->count();
        $downvotes = $this->downvotes()->count();
        $total = $upvotes - $downvotes;

        $this->update(['votes' => $total]);

        return $total;
    }

    /**
     * Mark the suggestion as under review.
     */
    public function markUnderReview(): void
    {
        $this->update([
            'status' => self::STATUS_UNDER_REVIEW,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Approve the suggestion.
     */
    public function approve(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'admin_notes' => $notes ?? $this->admin_notes,
            'reviewed_at' => $this->reviewed_at ?? now(),
        ]);
    }

    /**
     * Reject the suggestion.
     */
    public function reject(string $reason, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'admin_notes' => $notes ?? $this->admin_notes,
            'reviewed_at' => $this->reviewed_at ?? now(),
        ]);
    }

    /**
     * Defer the suggestion.
     */
    public function defer(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_DEFERRED,
            'admin_notes' => $notes ?? $this->admin_notes,
            'reviewed_at' => $this->reviewed_at ?? now(),
        ]);
    }

    /**
     * Start working on the suggestion.
     */
    public function startProgress(?int $assigneeId = null): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'assigned_to' => $assigneeId ?? $this->assigned_to,
        ]);
    }

    /**
     * Mark the suggestion as completed.
     */
    public function markCompleted(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'admin_notes' => $notes ?? $this->admin_notes,
        ]);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeWithCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by priority.
     */
    public function scopeWithPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: Pending review (submitted or under review).
     */
    public function scopePendingReview($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Scope: Active (not rejected, completed, or deferred).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_REJECTED,
            self::STATUS_COMPLETED,
            self::STATUS_DEFERRED,
        ]);
    }

    /**
     * Scope: Public suggestions (visible to all users).
     */
    public function scopePublic($query)
    {
        return $query->whereNotIn('status', [self::STATUS_REJECTED]);
    }

    /**
     * Scope: Order by votes (highest first).
     */
    public function scopeTopVoted($query)
    {
        return $query->orderBy('votes', 'desc');
    }

    /**
     * Scope: Recently submitted.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }

    /**
     * Get the priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return self::getPriorities()[$this->priority] ?? $this->priority;
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Get the priority badge color class.
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_CRITICAL => 'bg-red-100 text-red-800',
            self::PRIORITY_HIGH => 'bg-orange-100 text-orange-800',
            self::PRIORITY_MEDIUM => 'bg-yellow-100 text-yellow-800',
            self::PRIORITY_LOW => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get the status badge color class.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED => 'bg-blue-100 text-blue-800',
            self::STATUS_UNDER_REVIEW => 'bg-purple-100 text-purple-800',
            self::STATUS_APPROVED => 'bg-green-100 text-green-800',
            self::STATUS_IN_PROGRESS => 'bg-yellow-100 text-yellow-800',
            self::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-800',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            self::STATUS_DEFERRED => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if the suggestion can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
        ]);
    }

    /**
     * Check if the suggestion can be voted on.
     */
    public function canBeVotedOn(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_REJECTED,
            self::STATUS_COMPLETED,
        ]);
    }
}
