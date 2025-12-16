<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * STAFF-REG-006: Adjudication Case Model
 *
 * Manages the review workflow for background checks with "consider" results.
 *
 * @property int $id
 * @property int $background_check_id
 * @property int $user_id
 * @property string $case_number
 * @property string $case_type
 * @property string $status
 * @property int|null $assigned_to
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property int|null $escalated_to
 * @property \Illuminate\Support\Carbon|null $escalated_at
 * @property string|null $findings_encrypted
 * @property array|null $record_details
 * @property string|null $severity
 * @property string|null $worker_response
 * @property array|null $worker_documents
 * @property \Illuminate\Support\Carbon|null $worker_responded_at
 * @property string $decision
 * @property string|null $decision_rationale
 * @property int|null $decided_by
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property array|null $conditions
 * @property \Illuminate\Support\Carbon|null $review_date
 * @property \Illuminate\Support\Carbon|null $pre_adverse_notice_sent_at
 * @property \Illuminate\Support\Carbon|null $waiting_period_ends_at
 * @property \Illuminate\Support\Carbon|null $final_notice_sent_at
 * @property array|null $communications_log
 * @property \Illuminate\Support\Carbon|null $sla_deadline
 * @property bool $sla_breached
 * @property array|null $audit_log
 */
class AdjudicationCase extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'background_check_id',
        'user_id',
        'case_number',
        'case_type',
        'status',
        'assigned_to',
        'assigned_at',
        'escalated_to',
        'escalated_at',
        'findings_encrypted',
        'record_details',
        'severity',
        'worker_response',
        'worker_documents',
        'worker_responded_at',
        'decision',
        'decision_rationale',
        'decided_by',
        'decided_at',
        'conditions',
        'review_date',
        'pre_adverse_notice_sent_at',
        'waiting_period_ends_at',
        'final_notice_sent_at',
        'communications_log',
        'sla_deadline',
        'sla_breached',
        'audit_log',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'assigned_at' => 'datetime',
        'escalated_at' => 'datetime',
        'worker_responded_at' => 'datetime',
        'decided_at' => 'datetime',
        'review_date' => 'date',
        'pre_adverse_notice_sent_at' => 'datetime',
        'waiting_period_ends_at' => 'date',
        'final_notice_sent_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'sla_breached' => 'boolean',
        'record_details' => 'array',
        'worker_documents' => 'array',
        'conditions' => 'array',
        'communications_log' => 'array',
        'audit_log' => 'array',
    ];

    /**
     * Case type constants.
     */
    public const TYPE_CRIMINAL_RECORD = 'criminal_record';
    public const TYPE_IDENTITY_MISMATCH = 'identity_mismatch';
    public const TYPE_EMPLOYMENT_DISCREPANCY = 'employment_discrepancy';
    public const TYPE_EDUCATION_DISCREPANCY = 'education_discrepancy';
    public const TYPE_MOTOR_VEHICLE = 'motor_vehicle';
    public const TYPE_SEX_OFFENDER = 'sex_offender';
    public const TYPE_OTHER = 'other';

    /**
     * Status constants.
     */
    public const STATUS_OPEN = 'open';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_PENDING_WORKER_RESPONSE = 'pending_worker_response';
    public const STATUS_PRE_ADVERSE_ACTION = 'pre_adverse_action';
    public const STATUS_WAITING_PERIOD = 'waiting_period';
    public const STATUS_FINAL_REVIEW = 'final_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ADVERSE_ACTION = 'adverse_action';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ESCALATED = 'escalated';

    /**
     * Decision constants.
     */
    public const DECISION_PENDING = 'pending';
    public const DECISION_APPROVED = 'approved';
    public const DECISION_CONDITIONALLY_APPROVED = 'conditionally_approved';
    public const DECISION_DENIED = 'denied';

    /**
     * Severity constants.
     */
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($case) {
            if (!$case->case_number) {
                $case->case_number = static::generateCaseNumber();
            }
            if (!$case->sla_deadline) {
                // Default SLA: 5 business days for adjudication
                $case->sla_deadline = now()->addWeekdays(5);
            }
        });
    }

    /**
     * Generate unique case number.
     */
    public static function generateCaseNumber(): string
    {
        $prefix = 'ADJ';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Get the background check.
     */
    public function backgroundCheck()
    {
        return $this->belongsTo(BackgroundCheck::class);
    }

    /**
     * Get the worker (user).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the assigned adjudicator.
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the escalation recipient.
     */
    public function escalatedToUser()
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    /**
     * Get the decision maker.
     */
    public function decisionMaker()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    // ========== Scopes ==========

    /**
     * Scope for open cases.
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            self::STATUS_OPEN,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_PENDING_WORKER_RESPONSE,
            self::STATUS_PRE_ADVERSE_ACTION,
            self::STATUS_WAITING_PERIOD,
            self::STATUS_FINAL_REVIEW,
        ]);
    }

    /**
     * Scope for cases assigned to a user.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for cases with breached SLA.
     */
    public function scopeSlaBreached($query)
    {
        return $query->where(function ($q) {
            $q->where('sla_breached', true)
              ->orWhere(function ($qq) {
                  $qq->where('sla_deadline', '<', now())
                     ->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_APPROVED, self::STATUS_ADVERSE_ACTION]);
              });
        });
    }

    /**
     * Scope by severity.
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    // ========== Encrypted Field Accessors ==========

    /**
     * Get decrypted findings.
     */
    public function getFindingsAttribute(): ?string
    {
        if (!$this->findings_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->findings_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted findings.
     */
    public function setFindingsAttribute(?string $value): void
    {
        $this->attributes['findings_encrypted'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    // ========== Status Helpers ==========

    /**
     * Check if case is open.
     */
    public function isOpen(): bool
    {
        return !in_array($this->status, [
            self::STATUS_CLOSED,
            self::STATUS_APPROVED,
            self::STATUS_ADVERSE_ACTION,
        ]);
    }

    /**
     * Check if in waiting period.
     */
    public function isInWaitingPeriod(): bool
    {
        return $this->status === self::STATUS_WAITING_PERIOD
            && $this->waiting_period_ends_at
            && now()->lt($this->waiting_period_ends_at);
    }

    /**
     * Check if waiting period is complete.
     */
    public function isWaitingPeriodComplete(): bool
    {
        return $this->waiting_period_ends_at && now()->gte($this->waiting_period_ends_at);
    }

    /**
     * Check if SLA is breached.
     */
    public function checkSlaStatus(): bool
    {
        if ($this->sla_breached) {
            return true;
        }

        if ($this->sla_deadline && now()->gt($this->sla_deadline) && $this->isOpen()) {
            $this->update(['sla_breached' => true]);
            return true;
        }

        return false;
    }

    // ========== State Transitions ==========

    /**
     * Assign case to adjudicator.
     */
    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'assigned_at' => now(),
            'status' => self::STATUS_UNDER_REVIEW,
        ]);

        $this->addAuditLog('assigned', ['assigned_to' => $userId]);
    }

    /**
     * Escalate case.
     */
    public function escalate(int $toUserId, string $reason): void
    {
        $this->update([
            'escalated_to' => $toUserId,
            'escalated_at' => now(),
            'status' => self::STATUS_ESCALATED,
        ]);

        $this->addCommunicationLog('escalated', $reason);
        $this->addAuditLog('escalated', [
            'escalated_to' => $toUserId,
            'reason' => $reason,
        ]);
    }

    /**
     * Request worker response.
     */
    public function requestWorkerResponse(string $message): void
    {
        $this->update([
            'status' => self::STATUS_PENDING_WORKER_RESPONSE,
        ]);

        $this->addCommunicationLog('worker_response_requested', $message);
        $this->addAuditLog('worker_response_requested');
    }

    /**
     * Record worker response.
     */
    public function recordWorkerResponse(string $response, ?array $documents = null): void
    {
        $this->update([
            'worker_response' => $response,
            'worker_documents' => $documents,
            'worker_responded_at' => now(),
            'status' => self::STATUS_UNDER_REVIEW,
        ]);

        $this->addCommunicationLog('worker_response_received', $response);
        $this->addAuditLog('worker_response_received');
    }

    /**
     * Initiate pre-adverse action.
     */
    public function initiatePreAdverseAction(): void
    {
        $waitingDays = config('background_check.fcra_waiting_days', 5);

        $this->update([
            'status' => self::STATUS_PRE_ADVERSE_ACTION,
            'pre_adverse_notice_sent_at' => now(),
            'waiting_period_ends_at' => now()->addWeekdays($waitingDays),
        ]);

        // Also update parent background check
        $this->backgroundCheck->initiatePreAdverseAction();

        $this->addAuditLog('pre_adverse_action_initiated', [
            'waiting_period_ends' => $this->waiting_period_ends_at->toDateString(),
        ]);
    }

    /**
     * Move to waiting period status.
     */
    public function enterWaitingPeriod(): void
    {
        $this->update(['status' => self::STATUS_WAITING_PERIOD]);
        $this->addAuditLog('entered_waiting_period');
    }

    /**
     * Move to final review after waiting period.
     */
    public function enterFinalReview(): void
    {
        $this->update(['status' => self::STATUS_FINAL_REVIEW]);
        $this->addAuditLog('entered_final_review');
    }

    /**
     * Approve the case.
     */
    public function approve(?string $rationale = null, ?array $conditions = null): void
    {
        $decision = $conditions ? self::DECISION_CONDITIONALLY_APPROVED : self::DECISION_APPROVED;

        $this->update([
            'status' => self::STATUS_APPROVED,
            'decision' => $decision,
            'decision_rationale' => $rationale,
            'conditions' => $conditions,
            'decided_by' => auth()->id(),
            'decided_at' => now(),
        ]);

        // Update parent background check
        $this->backgroundCheck->approveAfterAdjudication(auth()->id(), $rationale);

        $this->addAuditLog('approved', [
            'decision' => $decision,
            'conditions' => $conditions,
        ]);
    }

    /**
     * Deny and initiate adverse action.
     */
    public function deny(string $rationale): void
    {
        $this->update([
            'status' => self::STATUS_ADVERSE_ACTION,
            'decision' => self::DECISION_DENIED,
            'decision_rationale' => $rationale,
            'decided_by' => auth()->id(),
            'decided_at' => now(),
            'final_notice_sent_at' => now(),
        ]);

        // Update parent background check
        $this->backgroundCheck->denyAfterAdjudication(auth()->id(), $rationale);
        $this->backgroundCheck->completeAdverseAction();

        $this->addAuditLog('denied', ['rationale' => $rationale]);
    }

    /**
     * Close case.
     */
    public function close(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
        ]);

        $this->addCommunicationLog('case_closed', $reason);
        $this->addAuditLog('closed', ['reason' => $reason]);
    }

    // ========== Logging ==========

    /**
     * Add communication to log.
     */
    public function addCommunicationLog(string $type, string $content): void
    {
        $log = $this->communications_log ?? [];

        $log[] = [
            'type' => $type,
            'content' => $content,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
        ];

        $this->update(['communications_log' => $log]);
    }

    /**
     * Add an entry to the audit log.
     */
    public function addAuditLog(string $action, array $details = []): void
    {
        $log = $this->audit_log ?? [];

        $log[] = [
            'action' => $action,
            'details' => $details,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ];

        $this->update(['audit_log' => $log]);
    }

    // ========== Display Helpers ==========

    /**
     * Get status display name.
     */
    public function getStatusNameAttribute(): string
    {
        $names = [
            self::STATUS_OPEN => 'Open',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_PENDING_WORKER_RESPONSE => 'Awaiting Worker Response',
            self::STATUS_PRE_ADVERSE_ACTION => 'Pre-Adverse Action',
            self::STATUS_WAITING_PERIOD => 'Waiting Period',
            self::STATUS_FINAL_REVIEW => 'Final Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_ADVERSE_ACTION => 'Adverse Action Taken',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_ESCALATED => 'Escalated',
        ];

        return $names[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }

    /**
     * Get case type display name.
     */
    public function getCaseTypeNameAttribute(): string
    {
        $names = [
            self::TYPE_CRIMINAL_RECORD => 'Criminal Record',
            self::TYPE_IDENTITY_MISMATCH => 'Identity Mismatch',
            self::TYPE_EMPLOYMENT_DISCREPANCY => 'Employment Discrepancy',
            self::TYPE_EDUCATION_DISCREPANCY => 'Education Discrepancy',
            self::TYPE_MOTOR_VEHICLE => 'Motor Vehicle Record',
            self::TYPE_SEX_OFFENDER => 'Sex Offender Registry',
            self::TYPE_OTHER => 'Other',
        ];

        return $names[$this->case_type] ?? ucwords(str_replace('_', ' ', $this->case_type));
    }

    /**
     * Get severity badge class.
     */
    public function getSeverityBadgeClassAttribute(): string
    {
        return match ($this->severity) {
            self::SEVERITY_LOW => 'badge-success',
            self::SEVERITY_MEDIUM => 'badge-warning',
            self::SEVERITY_HIGH => 'badge-danger',
            self::SEVERITY_CRITICAL => 'badge-dark',
            default => 'badge-secondary',
        };
    }
}
