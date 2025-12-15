<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * AgencyPerformanceNotification Model
 *
 * Tracks all performance-related notifications sent to agencies.
 * AGY-005: Agency Performance Notification System
 *
 * @property int $id
 * @property int $agency_id
 * @property int|null $scorecard_id
 * @property string $notification_type
 * @property string $severity
 * @property string|null $status_at_notification
 * @property string|null $previous_status
 * @property string $title
 * @property string $message
 * @property array|null $metrics_snapshot
 * @property array|null $action_items
 * @property \Illuminate\Support\Carbon|null $improvement_deadline
 * @property int $consecutive_yellow_weeks
 * @property int $consecutive_red_weeks
 * @property numeric|null $previous_commission_rate
 * @property numeric|null $new_commission_rate
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property string $sent_via
 * @property bool $email_delivered
 * @property \Illuminate\Support\Carbon|null $email_delivered_at
 * @property bool $requires_acknowledgment
 * @property bool $acknowledged
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property int|null $acknowledged_by
 * @property string|null $acknowledgment_notes
 * @property bool $escalated
 * @property \Illuminate\Support\Carbon|null $escalated_at
 * @property int|null $escalated_to
 * @property string|null $escalation_reason
 * @property int $escalation_level
 * @property \Illuminate\Support\Carbon|null $escalation_due_at
 * @property bool $admin_reviewed
 * @property \Illuminate\Support\Carbon|null $admin_reviewed_at
 * @property int|null $admin_reviewed_by
 * @property string|null $admin_notes
 * @property string|null $admin_decision
 * @property bool $appealed
 * @property \Illuminate\Support\Carbon|null $appealed_at
 * @property string|null $appeal_reason
 * @property string|null $appeal_status
 * @property string|null $appeal_response
 * @property \Illuminate\Support\Carbon|null $appeal_resolved_at
 * @property int $follow_up_count
 * @property \Illuminate\Support\Carbon|null $last_follow_up_at
 * @property \Illuminate\Support\Carbon|null $next_follow_up_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class AgencyPerformanceNotification extends Model
{
    use HasFactory, SoftDeletes;

    // Notification types
    const TYPE_YELLOW_WARNING = 'yellow_warning';
    const TYPE_RED_ALERT = 'red_alert';
    const TYPE_FEE_INCREASE = 'fee_increase';
    const TYPE_SUSPENSION = 'suspension';
    const TYPE_IMPROVEMENT = 'improvement';
    const TYPE_ESCALATION = 'escalation';
    const TYPE_ADMIN_REVIEW = 'admin_review';

    // Severity levels
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    // Admin decisions
    const DECISION_PENDING = 'pending';
    const DECISION_UPHOLD = 'uphold';
    const DECISION_REDUCE = 'reduce';
    const DECISION_DISMISS = 'dismiss';

    // Appeal statuses
    const APPEAL_PENDING = 'pending';
    const APPEAL_APPROVED = 'approved';
    const APPEAL_REJECTED = 'rejected';

    // Escalation timing (in hours)
    const ESCALATION_THRESHOLD_HOURS = 48;
    const ADMIN_REVIEW_THRESHOLD_DAYS = 7;

    protected $fillable = [
        'agency_id',
        'scorecard_id',
        'notification_type',
        'severity',
        'status_at_notification',
        'previous_status',
        'title',
        'message',
        'metrics_snapshot',
        'action_items',
        'improvement_deadline',
        'consecutive_yellow_weeks',
        'consecutive_red_weeks',
        'previous_commission_rate',
        'new_commission_rate',
        'sent_at',
        'sent_via',
        'email_delivered',
        'email_delivered_at',
        'requires_acknowledgment',
        'acknowledged',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledgment_notes',
        'escalated',
        'escalated_at',
        'escalated_to',
        'escalation_reason',
        'escalation_level',
        'escalation_due_at',
        'admin_reviewed',
        'admin_reviewed_at',
        'admin_reviewed_by',
        'admin_notes',
        'admin_decision',
        'appealed',
        'appealed_at',
        'appeal_reason',
        'appeal_status',
        'appeal_response',
        'appeal_resolved_at',
        'follow_up_count',
        'last_follow_up_at',
        'next_follow_up_at',
    ];

    protected $casts = [
        'metrics_snapshot' => 'array',
        'action_items' => 'array',
        'improvement_deadline' => 'date',
        'consecutive_yellow_weeks' => 'integer',
        'consecutive_red_weeks' => 'integer',
        'previous_commission_rate' => 'decimal:2',
        'new_commission_rate' => 'decimal:2',
        'sent_at' => 'datetime',
        'email_delivered' => 'boolean',
        'email_delivered_at' => 'datetime',
        'requires_acknowledgment' => 'boolean',
        'acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
        'escalated' => 'boolean',
        'escalated_at' => 'datetime',
        'escalation_level' => 'integer',
        'escalation_due_at' => 'datetime',
        'admin_reviewed' => 'boolean',
        'admin_reviewed_at' => 'datetime',
        'appealed' => 'boolean',
        'appealed_at' => 'datetime',
        'appeal_resolved_at' => 'datetime',
        'follow_up_count' => 'integer',
        'last_follow_up_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the agency (user) this notification belongs to.
     */
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    /**
     * Get the agency profile.
     */
    public function agencyProfile()
    {
        return $this->hasOneThrough(
            AgencyProfile::class,
            User::class,
            'id',
            'user_id',
            'agency_id',
            'id'
        );
    }

    /**
     * Get the associated scorecard.
     */
    public function scorecard()
    {
        return $this->belongsTo(AgencyPerformanceScorecard::class, 'scorecard_id');
    }

    /**
     * Get the user who acknowledged this notification.
     */
    public function acknowledgedByUser()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get the admin this was escalated to.
     */
    public function escalatedToUser()
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    /**
     * Get the admin who reviewed this.
     */
    public function reviewedByAdmin()
    {
        return $this->belongsTo(User::class, 'admin_reviewed_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope: Unacknowledged notifications requiring acknowledgment.
     */
    public function scopeUnacknowledged($query)
    {
        return $query->where('requires_acknowledgment', true)
            ->where('acknowledged', false);
    }

    /**
     * Scope: Notifications pending escalation.
     */
    public function scopePendingEscalation($query)
    {
        return $query->unacknowledged()
            ->where('escalated', false)
            ->where('escalation_due_at', '<=', now());
    }

    /**
     * Scope: Critical notifications.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope: By notification type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope: For specific agency.
     */
    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    /**
     * Scope: Recent notifications (last N days).
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Notifications with pending appeals.
     */
    public function scopeWithPendingAppeals($query)
    {
        return $query->where('appealed', true)
            ->where('appeal_status', self::APPEAL_PENDING);
    }

    /**
     * Scope: Notifications requiring admin review.
     */
    public function scopeRequiringAdminReview($query)
    {
        return $query->where('admin_reviewed', false)
            ->where(function ($q) {
                $q->where('notification_type', self::TYPE_SUSPENSION)
                    ->orWhere('escalation_level', '>=', 2)
                    ->orWhere('appealed', true);
            });
    }

    // =========================================================================
    // ATTRIBUTE ACCESSORS
    // =========================================================================

    /**
     * Get human-readable notification type.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->notification_type) {
            self::TYPE_YELLOW_WARNING => 'Performance Warning',
            self::TYPE_RED_ALERT => 'Critical Performance Alert',
            self::TYPE_FEE_INCREASE => 'Fee Increase Notice',
            self::TYPE_SUSPENSION => 'Account Suspension',
            self::TYPE_IMPROVEMENT => 'Performance Improvement',
            self::TYPE_ESCALATION => 'Escalation Notice',
            self::TYPE_ADMIN_REVIEW => 'Admin Review Required',
            default => ucfirst(str_replace('_', ' ', $this->notification_type)),
        };
    }

    /**
     * Get severity badge class.
     */
    public function getSeverityBadgeAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_INFO => 'badge-info',
            self::SEVERITY_WARNING => 'badge-warning',
            self::SEVERITY_CRITICAL => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    /**
     * Check if notification is overdue for acknowledgment.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->requires_acknowledgment || $this->acknowledged) {
            return false;
        }

        return $this->escalation_due_at && now()->gt($this->escalation_due_at);
    }

    /**
     * Get hours until escalation.
     */
    public function getHoursUntilEscalationAttribute(): ?int
    {
        if (!$this->escalation_due_at || $this->acknowledged || $this->escalated) {
            return null;
        }

        $hours = now()->diffInHours($this->escalation_due_at, false);
        return max(0, $hours);
    }

    // =========================================================================
    // ACTIONS
    // =========================================================================

    /**
     * Mark notification as sent.
     */
    public function markAsSent(string $via = 'email'): self
    {
        $this->update([
            'sent_at' => now(),
            'sent_via' => $via,
            'escalation_due_at' => $this->requires_acknowledgment
                ? now()->addHours(self::ESCALATION_THRESHOLD_HOURS)
                : null,
        ]);

        return $this;
    }

    /**
     * Mark email as delivered.
     */
    public function markEmailDelivered(): self
    {
        $this->update([
            'email_delivered' => true,
            'email_delivered_at' => now(),
        ]);

        return $this;
    }

    /**
     * Acknowledge the notification.
     */
    public function acknowledge(int $userId, ?string $notes = null): self
    {
        $this->update([
            'acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
            'acknowledgment_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Escalate the notification.
     */
    public function escalate(int $adminId, string $reason): self
    {
        $this->update([
            'escalated' => true,
            'escalated_at' => now(),
            'escalated_to' => $adminId,
            'escalation_reason' => $reason,
            'escalation_level' => $this->escalation_level + 1,
        ]);

        return $this;
    }

    /**
     * Record a follow-up.
     */
    public function recordFollowUp(): self
    {
        $this->update([
            'follow_up_count' => $this->follow_up_count + 1,
            'last_follow_up_at' => now(),
            'next_follow_up_at' => now()->addHours(24),
        ]);

        return $this;
    }

    /**
     * Submit an appeal.
     */
    public function submitAppeal(string $reason): self
    {
        $this->update([
            'appealed' => true,
            'appealed_at' => now(),
            'appeal_reason' => $reason,
            'appeal_status' => self::APPEAL_PENDING,
        ]);

        return $this;
    }

    /**
     * Resolve an appeal.
     */
    public function resolveAppeal(string $status, string $response): self
    {
        $this->update([
            'appeal_status' => $status,
            'appeal_response' => $response,
            'appeal_resolved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Record admin review.
     */
    public function recordAdminReview(int $adminId, string $decision, ?string $notes = null): self
    {
        $this->update([
            'admin_reviewed' => true,
            'admin_reviewed_at' => now(),
            'admin_reviewed_by' => $adminId,
            'admin_decision' => $decision,
            'admin_notes' => $notes,
        ]);

        return $this;
    }

    // =========================================================================
    // STATIC HELPERS
    // =========================================================================

    /**
     * Get all notification types.
     */
    public static function getNotificationTypes(): array
    {
        return [
            self::TYPE_YELLOW_WARNING,
            self::TYPE_RED_ALERT,
            self::TYPE_FEE_INCREASE,
            self::TYPE_SUSPENSION,
            self::TYPE_IMPROVEMENT,
            self::TYPE_ESCALATION,
            self::TYPE_ADMIN_REVIEW,
        ];
    }

    /**
     * Get severity options.
     */
    public static function getSeverityOptions(): array
    {
        return [
            self::SEVERITY_INFO => 'Info',
            self::SEVERITY_WARNING => 'Warning',
            self::SEVERITY_CRITICAL => 'Critical',
        ];
    }

    /**
     * Create a notification record for an agency.
     */
    public static function createForAgency(
        int $agencyId,
        string $type,
        string $title,
        string $message,
        array $options = []
    ): self {
        $defaults = [
            'agency_id' => $agencyId,
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
            'severity' => self::determineSeverity($type),
            'requires_acknowledgment' => self::requiresAcknowledgment($type),
        ];

        return self::create(array_merge($defaults, $options));
    }

    /**
     * Determine severity based on notification type.
     */
    protected static function determineSeverity(string $type): string
    {
        return match ($type) {
            self::TYPE_IMPROVEMENT => self::SEVERITY_INFO,
            self::TYPE_YELLOW_WARNING => self::SEVERITY_WARNING,
            self::TYPE_RED_ALERT,
            self::TYPE_FEE_INCREASE,
            self::TYPE_SUSPENSION,
            self::TYPE_ADMIN_REVIEW => self::SEVERITY_CRITICAL,
            default => self::SEVERITY_WARNING,
        };
    }

    /**
     * Determine if type requires acknowledgment.
     */
    protected static function requiresAcknowledgment(string $type): bool
    {
        return in_array($type, [
            self::TYPE_YELLOW_WARNING,
            self::TYPE_RED_ALERT,
            self::TYPE_FEE_INCREASE,
            self::TYPE_SUSPENSION,
        ]);
    }
}
