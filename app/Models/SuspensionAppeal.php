<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-009: Suspension Appeal Model
 *
 * Represents an appeal submitted by a worker against a suspension.
 *
 * @property int $id
 * @property int $suspension_id
 * @property int $user_id
 * @property string $appeal_reason
 * @property array|null $supporting_evidence
 * @property string $status
 * @property int|null $reviewed_by
 * @property string|null $review_notes
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SuspensionAppeal extends Model
{
    use HasFactory;

    // Status Values
    public const STATUS_PENDING = 'pending';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'suspension_id',
        'user_id',
        'appeal_reason',
        'supporting_evidence',
        'status',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'supporting_evidence' => 'array',
        'reviewed_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the suspension being appealed.
     */
    public function suspension(): BelongsTo
    {
        return $this->belongsTo(WorkerSuspension::class, 'suspension_id');
    }

    /**
     * Get the worker who submitted the appeal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() - get the worker who submitted the appeal.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin who reviewed the appeal.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to pending appeals.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to appeals under review.
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    /**
     * Scope to approved appeals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to denied appeals.
     */
    public function scopeDenied($query)
    {
        return $query->where('status', self::STATUS_DENIED);
    }

    /**
     * Scope to unresolved appeals (pending or under review).
     */
    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Scope to resolved appeals (approved or denied).
     */
    public function scopeResolved($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_DENIED]);
    }

    /**
     * Scope to appeals for a specific worker.
     */
    public function scopeForWorker($query, User|int $worker)
    {
        $workerId = $worker instanceof User ? $worker->id : $worker;

        return $query->where('user_id', $workerId);
    }

    /**
     * Scope to appeals reviewed by a specific admin.
     */
    public function scopeReviewedBy($query, User|int $admin)
    {
        $adminId = $admin instanceof User ? $admin->id : $admin;

        return $query->where('reviewed_by', $adminId);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if appeal is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if appeal is under review.
     */
    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    /**
     * Check if appeal is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if appeal is denied.
     */
    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    /**
     * Check if appeal is unresolved.
     */
    public function isUnresolved(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Check if appeal has been resolved (approved or denied).
     */
    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_DENIED]);
    }

    /**
     * Check if appeal has supporting evidence.
     */
    public function hasEvidence(): bool
    {
        return ! empty($this->supporting_evidence);
    }

    /**
     * Get the count of evidence items.
     */
    public function getEvidenceCount(): int
    {
        return count($this->supporting_evidence ?? []);
    }

    /**
     * Get days since appeal was submitted.
     */
    public function daysSinceSubmission(): int
    {
        return $this->created_at->diffInDays(Carbon::now());
    }

    /**
     * Get hours since appeal was submitted.
     */
    public function hoursSinceSubmission(): int
    {
        return $this->created_at->diffInHours(Carbon::now());
    }

    /**
     * Mark the appeal as under review.
     */
    public function markUnderReview(User $admin): self
    {
        $this->update([
            'status' => self::STATUS_UNDER_REVIEW,
            'reviewed_by' => $admin->id,
        ]);

        return $this;
    }

    /**
     * Get the human-readable status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DENIED => 'Denied',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the status badge color for UI.
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_UNDER_REVIEW => 'blue',
            self::STATUS_APPROVED => 'green',
            self::STATUS_DENIED => 'red',
            default => 'gray',
        };
    }

    /**
     * Get available statuses.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DENIED => 'Denied',
        ];
    }
}
