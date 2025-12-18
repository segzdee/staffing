<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dispute Model
 *
 * FIN-010: Dispute Resolution System
 *
 * Handles financial disagreements between workers and businesses including
 * payment disputes, hours disagreements, deductions, bonuses, and expenses.
 *
 * @property int $id
 * @property int $shift_id
 * @property int $worker_id
 * @property int $business_id
 * @property string $type
 * @property string $status
 * @property float $disputed_amount
 * @property string $worker_description
 * @property string|null $business_response
 * @property int|null $assigned_to
 * @property int|null $admin_queue_id
 * @property array|null $evidence_worker
 * @property array|null $evidence_business
 * @property string|null $resolution
 * @property float|null $resolution_amount
 * @property string|null $resolution_notes
 * @property \Illuminate\Support\Carbon|null $evidence_deadline
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Shift $shift
 * @property-read \App\Models\User $worker
 * @property-read \App\Models\User $business
 * @property-read \App\Models\User|null $assignedAdmin
 * @property-read \App\Models\AdminDisputeQueue|null $adminQueue
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DisputeTimeline> $timeline
 */
class Dispute extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'disputes';

    /**
     * Dispute type constants.
     */
    public const TYPE_PAYMENT = 'payment';

    public const TYPE_HOURS = 'hours';

    public const TYPE_DEDUCTION = 'deduction';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_EXPENSES = 'expenses';

    public const TYPE_OTHER = 'other';

    /**
     * Status constants.
     */
    public const STATUS_OPEN = 'open';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_AWAITING_EVIDENCE = 'awaiting_evidence';

    public const STATUS_MEDIATION = 'mediation';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_ESCALATED = 'escalated';

    public const STATUS_CLOSED = 'closed';

    /**
     * Resolution constants.
     */
    public const RESOLUTION_WORKER_FAVOR = 'worker_favor';

    public const RESOLUTION_BUSINESS_FAVOR = 'business_favor';

    public const RESOLUTION_SPLIT = 'split';

    public const RESOLUTION_WITHDRAWN = 'withdrawn';

    public const RESOLUTION_EXPIRED = 'expired';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shift_id',
        'worker_id',
        'business_id',
        'type',
        'status',
        'disputed_amount',
        'worker_description',
        'business_response',
        'assigned_to',
        'admin_queue_id',
        'evidence_worker',
        'evidence_business',
        'resolution',
        'resolution_amount',
        'resolution_notes',
        'evidence_deadline',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'disputed_amount' => 'decimal:2',
        'resolution_amount' => 'decimal:2',
        'evidence_worker' => 'array',
        'evidence_business' => 'array',
        'evidence_deadline' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * All available dispute types.
     *
     * @var array<string, string>
     */
    public static array $types = [
        self::TYPE_PAYMENT => 'Payment Issue',
        self::TYPE_HOURS => 'Hours Disagreement',
        self::TYPE_DEDUCTION => 'Unauthorized Deduction',
        self::TYPE_BONUS => 'Bonus Dispute',
        self::TYPE_EXPENSES => 'Expense Reimbursement',
        self::TYPE_OTHER => 'Other',
    ];

    /**
     * All available statuses.
     *
     * @var array<string, string>
     */
    public static array $statuses = [
        self::STATUS_OPEN => 'Open',
        self::STATUS_UNDER_REVIEW => 'Under Review',
        self::STATUS_AWAITING_EVIDENCE => 'Awaiting Evidence',
        self::STATUS_MEDIATION => 'In Mediation',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_ESCALATED => 'Escalated',
        self::STATUS_CLOSED => 'Closed',
    ];

    /**
     * All available resolutions.
     *
     * @var array<string, string>
     */
    public static array $resolutions = [
        self::RESOLUTION_WORKER_FAVOR => 'Resolved in Worker\'s Favor',
        self::RESOLUTION_BUSINESS_FAVOR => 'Resolved in Business\'s Favor',
        self::RESOLUTION_SPLIT => 'Split Resolution',
        self::RESOLUTION_WITHDRAWN => 'Withdrawn',
        self::RESOLUTION_EXPIRED => 'Expired',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the shift associated with this dispute.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the worker who filed the dispute.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the business involved in the dispute.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the admin assigned to mediate this dispute.
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the linked admin dispute queue entry.
     */
    public function adminQueue(): BelongsTo
    {
        return $this->belongsTo(AdminDisputeQueue::class, 'admin_queue_id');
    }

    /**
     * Get the timeline entries for this dispute.
     */
    public function timeline(): HasMany
    {
        return $this->hasMany(DisputeTimeline::class)->orderBy('created_at', 'asc');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Open disputes.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope: Active disputes (not resolved or closed).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ]);
    }

    /**
     * Scope: Resolved disputes.
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope: Escalated disputes.
     */
    public function scopeEscalated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ESCALATED);
    }

    /**
     * Scope: Awaiting evidence.
     */
    public function scopeAwaitingEvidence(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AWAITING_EVIDENCE);
    }

    /**
     * Scope: Disputes for a specific worker.
     */
    public function scopeForWorker(Builder $query, int $workerId): Builder
    {
        return $query->where('worker_id', $workerId);
    }

    /**
     * Scope: Disputes for a specific business.
     */
    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope: Disputes assigned to a specific admin.
     */
    public function scopeAssignedTo(Builder $query, int $adminId): Builder
    {
        return $query->where('assigned_to', $adminId);
    }

    /**
     * Scope: Unassigned disputes.
     */
    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Scope: Disputes by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Disputes past evidence deadline.
     */
    public function scopePastDeadline(Builder $query): Builder
    {
        return $query->whereNotNull('evidence_deadline')
            ->where('evidence_deadline', '<', now())
            ->whereIn('status', [self::STATUS_OPEN, self::STATUS_AWAITING_EVIDENCE]);
    }

    /**
     * Scope: Stale disputes (no activity for X days).
     */
    public function scopeStale(Builder $query, int $days = 30): Builder
    {
        return $query->active()
            ->where('updated_at', '<', now()->subDays($days));
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get the human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::$types[$this->type] ?? 'Unknown';
    }

    /**
     * Get the human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::$statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Get the human-readable resolution label.
     */
    public function getResolutionLabelAttribute(): ?string
    {
        if (! $this->resolution) {
            return null;
        }

        return self::$resolutions[$this->resolution] ?? 'Unknown';
    }

    /**
     * Get the formatted disputed amount.
     */
    public function getFormattedDisputedAmountAttribute(): string
    {
        return '$'.number_format($this->disputed_amount, 2);
    }

    /**
     * Get the formatted resolution amount.
     */
    public function getFormattedResolutionAmountAttribute(): ?string
    {
        if ($this->resolution_amount === null) {
            return null;
        }

        return '$'.number_format($this->resolution_amount, 2);
    }

    /**
     * Get the status badge class for UI styling.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'badge-warning',
            self::STATUS_UNDER_REVIEW => 'badge-info',
            self::STATUS_AWAITING_EVIDENCE => 'badge-primary',
            self::STATUS_MEDIATION => 'badge-secondary',
            self::STATUS_RESOLVED => 'badge-success',
            self::STATUS_ESCALATED => 'badge-danger',
            self::STATUS_CLOSED => 'badge-dark',
            default => 'badge-secondary',
        };
    }

    /**
     * Get days since dispute was opened.
     */
    public function getDaysOpenAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if evidence deadline has passed.
     */
    public function getIsDeadlinePassedAttribute(): bool
    {
        if (! $this->evidence_deadline) {
            return false;
        }

        return now()->isAfter($this->evidence_deadline);
    }

    /**
     * Get hours remaining until evidence deadline.
     */
    public function getDeadlineHoursRemainingAttribute(): ?int
    {
        if (! $this->evidence_deadline) {
            return null;
        }

        if ($this->is_deadline_passed) {
            return 0;
        }

        return now()->diffInHours($this->evidence_deadline);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if dispute is active (not resolved/closed).
     */
    public function isActive(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ]);
    }

    /**
     * Check if dispute is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if dispute is escalated.
     */
    public function isEscalated(): bool
    {
        return $this->status === self::STATUS_ESCALATED;
    }

    /**
     * Check if dispute is awaiting business response.
     */
    public function isAwaitingResponse(): bool
    {
        return $this->status === self::STATUS_OPEN && is_null($this->business_response);
    }

    /**
     * Check if worker can submit evidence.
     */
    public function canWorkerSubmitEvidence(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->evidence_deadline && now()->isAfter($this->evidence_deadline)) {
            return false;
        }

        return true;
    }

    /**
     * Check if business can submit evidence.
     */
    public function canBusinessSubmitEvidence(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->evidence_deadline && now()->isAfter($this->evidence_deadline)) {
            return false;
        }

        return true;
    }

    /**
     * Check if dispute can be escalated.
     */
    public function canBeEscalated(): bool
    {
        // Can only escalate active disputes that aren't already escalated
        if (! $this->isActive() || $this->isEscalated()) {
            return false;
        }

        // Must be at least under review or past deadline
        $eligibleStatuses = [
            self::STATUS_UNDER_REVIEW,
            self::STATUS_AWAITING_EVIDENCE,
            self::STATUS_MEDIATION,
        ];

        return in_array($this->status, $eligibleStatuses) || $this->is_deadline_passed;
    }

    /**
     * Add a timeline entry.
     */
    public function addTimelineEntry(
        string $action,
        ?int $userId = null,
        ?string $description = null,
        ?array $metadata = null
    ): DisputeTimeline {
        return $this->timeline()->create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get the opposing party for a user.
     */
    public function getOpposingParty(int $userId): ?User
    {
        if ($userId === $this->worker_id) {
            return $this->business;
        }

        if ($userId === $this->business_id) {
            return $this->worker;
        }

        return null;
    }

    /**
     * Check if a user is a party to this dispute.
     */
    public function isParty(int $userId): bool
    {
        return $userId === $this->worker_id || $userId === $this->business_id;
    }

    /**
     * Get the user's role in this dispute.
     */
    public function getUserRole(int $userId): ?string
    {
        if ($userId === $this->worker_id) {
            return 'worker';
        }

        if ($userId === $this->business_id) {
            return 'business';
        }

        if ($userId === $this->assigned_to) {
            return 'mediator';
        }

        return null;
    }

    /**
     * Calculate the split amount for a split resolution.
     */
    public function calculateSplitAmount(float $percentage = 50.0): float
    {
        return round($this->disputed_amount * ($percentage / 100), 2);
    }
}
