<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * STAFF-REG-006: Background Check Model
 *
 * Tracks background check status and results from various providers.
 *
 * @property int $id
 * @property int $user_id
 * @property string $jurisdiction
 * @property string $provider
 * @property string|null $provider_candidate_id
 * @property string|null $provider_report_id
 * @property string $check_type
 * @property array|null $check_components
 * @property string $status
 * @property string|null $result
 * @property string $adjudication_status
 * @property int|null $adjudicated_by
 * @property \Illuminate\Support\Carbon|null $adjudicated_at
 * @property string|null $adjudication_notes
 * @property bool $adverse_action_required
 * @property \Illuminate\Support\Carbon|null $pre_adverse_action_sent_at
 * @property \Illuminate\Support\Carbon|null $pre_adverse_action_deadline
 * @property \Illuminate\Support\Carbon|null $adverse_action_sent_at
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int|null $cost_cents
 * @property string $cost_currency
 * @property int|null $billed_to
 * @property string|null $result_data_encrypted
 * @property string|null $report_url_encrypted
 * @property \Illuminate\Support\Carbon|null $last_webhook_at
 * @property string|null $last_webhook_event
 * @property array|null $webhook_log
 * @property array|null $audit_log
 */
class BackgroundCheck extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'jurisdiction',
        'provider',
        'provider_candidate_id',
        'provider_report_id',
        'check_type',
        'check_components',
        'status',
        'result',
        'adjudication_status',
        'adjudicated_by',
        'adjudicated_at',
        'adjudication_notes',
        'adverse_action_required',
        'pre_adverse_action_sent_at',
        'pre_adverse_action_deadline',
        'adverse_action_sent_at',
        'submitted_at',
        'completed_at',
        'expires_at',
        'cost_cents',
        'cost_currency',
        'billed_to',
        'result_data_encrypted',
        'report_url_encrypted',
        'last_webhook_at',
        'last_webhook_event',
        'webhook_log',
        'audit_log',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'check_components' => 'array',
        'webhook_log' => 'array',
        'audit_log' => 'array',
        'adverse_action_required' => 'boolean',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'pre_adverse_action_sent_at' => 'datetime',
        'pre_adverse_action_deadline' => 'datetime',
        'adverse_action_sent_at' => 'datetime',
        'adjudicated_at' => 'datetime',
        'last_webhook_at' => 'datetime',
        'cost_cents' => 'integer',
    ];

    /**
     * Status constants.
     */
    public const STATUS_PENDING_CONSENT = 'pending_consent';
    public const STATUS_CONSENT_RECEIVED = 'consent_received';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_CONSIDER = 'consider';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DISPUTE = 'dispute';

    /**
     * Result constants.
     */
    public const RESULT_CLEAR = 'clear';
    public const RESULT_CONSIDER = 'consider';
    public const RESULT_FAIL = 'fail';
    public const RESULT_PENDING = 'pending';

    /**
     * Adjudication status constants.
     */
    public const ADJ_NOT_APPLICABLE = 'not_applicable';
    public const ADJ_PENDING = 'pending';
    public const ADJ_IN_REVIEW = 'in_review';
    public const ADJ_APPROVED = 'approved';
    public const ADJ_DENIED = 'denied';

    /**
     * Provider constants.
     */
    public const PROVIDER_CHECKR = 'checkr';
    public const PROVIDER_DBS = 'dbs';
    public const PROVIDER_POLICE_CLEARANCE = 'police_clearance';

    /**
     * Check types by jurisdiction.
     */
    public const CHECK_TYPES = [
        'US' => [
            'basic' => ['ssn_trace', 'national_criminal'],
            'standard' => ['ssn_trace', 'national_criminal', 'sex_offender'],
            'professional' => ['ssn_trace', 'national_criminal', 'sex_offender', 'county_criminal', 'motor_vehicle'],
            'comprehensive' => ['ssn_trace', 'national_criminal', 'sex_offender', 'county_criminal', 'motor_vehicle', 'education', 'employment'],
        ],
        'UK' => [
            'dbs_basic' => ['basic'],
            'dbs_standard' => ['basic', 'spent_convictions'],
            'dbs_enhanced' => ['basic', 'spent_convictions', 'police_intelligence'],
            'dbs_enhanced_barred' => ['basic', 'spent_convictions', 'police_intelligence', 'barred_list'],
        ],
        'AU' => [
            'police_check' => ['national_police'],
            'working_with_children' => ['national_police', 'wwcc'],
        ],
    ];

    /**
     * Get the worker this check belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the adjudicator.
     */
    public function adjudicator()
    {
        return $this->belongsTo(User::class, 'adjudicated_by');
    }

    /**
     * Get the billing user/business.
     */
    public function billedToUser()
    {
        return $this->belongsTo(User::class, 'billed_to');
    }

    /**
     * Get the consents for this check.
     */
    public function consents()
    {
        return $this->hasMany(BackgroundCheckConsent::class);
    }

    /**
     * Get the adjudication cases.
     */
    public function adjudicationCases()
    {
        return $this->hasMany(AdjudicationCase::class);
    }

    /**
     * Scope for clear checks.
     */
    public function scopeClear($query)
    {
        return $query->where('result', self::RESULT_CLEAR);
    }

    /**
     * Scope for checks requiring review.
     */
    public function scopeRequiresReview($query)
    {
        return $query->where('result', self::RESULT_CONSIDER)
            ->where('adjudication_status', self::ADJ_PENDING);
    }

    /**
     * Scope by provider.
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope for active (not expired) checks.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('result', [self::RESULT_CLEAR, self::RESULT_CONSIDER])
            ->where('adjudication_status', '!=', self::ADJ_DENIED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    // ========== Encrypted Field Accessors ==========

    /**
     * Get decrypted result data.
     */
    public function getResultDataAttribute(): ?array
    {
        if (!$this->result_data_encrypted) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($this->result_data_encrypted), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted result data.
     */
    public function setResultDataAttribute(?array $value): void
    {
        $this->attributes['result_data_encrypted'] = $value
            ? Crypt::encryptString(json_encode($value))
            : null;
    }

    /**
     * Get decrypted report URL.
     */
    public function getReportUrlAttribute(): ?string
    {
        if (!$this->report_url_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->report_url_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted report URL.
     */
    public function setReportUrlAttribute(?string $value): void
    {
        $this->attributes['report_url_encrypted'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    // ========== Status Helpers ==========

    /**
     * Check if consent has been received.
     */
    public function hasConsent(): bool
    {
        return $this->consents()->where('consented', true)->exists();
    }

    /**
     * Check if check is complete.
     */
    public function isComplete(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETE, self::STATUS_CONSIDER]);
    }

    /**
     * Check if check is clear.
     */
    public function isClear(): bool
    {
        return $this->result === self::RESULT_CLEAR;
    }

    /**
     * Check if check requires adjudication.
     */
    public function requiresAdjudication(): bool
    {
        return $this->result === self::RESULT_CONSIDER
            && $this->adjudication_status === self::ADJ_PENDING;
    }

    /**
     * Check if adverse action is in progress.
     */
    public function isInAdverseActionPeriod(): bool
    {
        return $this->adverse_action_required
            && $this->pre_adverse_action_sent_at
            && !$this->adverse_action_sent_at
            && now()->lt($this->pre_adverse_action_deadline);
    }

    /**
     * Check if check is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get cost in dollars (or appropriate currency).
     */
    public function getCostAttribute(): float
    {
        return $this->cost_cents ? $this->cost_cents / 100 : 0;
    }

    // ========== State Transitions ==========

    /**
     * Mark consent as received and ready to submit.
     */
    public function markConsentReceived(): void
    {
        $this->update(['status' => self::STATUS_CONSENT_RECEIVED]);
        $this->addAuditLog('consent_received');
    }

    /**
     * Mark as submitted to provider.
     */
    public function markSubmitted(string $candidateId, ?string $reportId = null): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'provider_candidate_id' => $candidateId,
            'provider_report_id' => $reportId,
            'submitted_at' => now(),
        ]);
        $this->addAuditLog('submitted', [
            'candidate_id' => $candidateId,
            'report_id' => $reportId,
        ]);
    }

    /**
     * Update status from webhook.
     */
    public function updateFromWebhook(string $status, ?string $result = null, ?array $data = null): void
    {
        $updates = [
            'status' => $status,
            'last_webhook_at' => now(),
            'last_webhook_event' => $status,
        ];

        if ($result) {
            $updates['result'] = $result;
        }

        if (in_array($status, [self::STATUS_COMPLETE, self::STATUS_CONSIDER])) {
            $updates['completed_at'] = now();
            // Default expiry 1 year for US, varies by jurisdiction
            $updates['expires_at'] = now()->addYear();
        }

        if ($data) {
            $this->result_data = $data;
        }

        // If result is "consider", set up adjudication
        if ($result === self::RESULT_CONSIDER) {
            $updates['adjudication_status'] = self::ADJ_PENDING;
        }

        $this->update($updates);

        // Add to webhook log
        $webhookLog = $this->webhook_log ?? [];
        $webhookLog[] = [
            'status' => $status,
            'result' => $result,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->update(['webhook_log' => $webhookLog]);

        $this->addAuditLog('webhook_received', [
            'status' => $status,
            'result' => $result,
        ]);
    }

    /**
     * Start pre-adverse action process.
     */
    public function initiatePreAdverseAction(): void
    {
        // FCRA requires 5 business days waiting period
        $waitingPeriodDays = config('background_check.fcra_waiting_days', 5);

        $this->update([
            'adverse_action_required' => true,
            'pre_adverse_action_sent_at' => now(),
            'pre_adverse_action_deadline' => now()->addWeekdays($waitingPeriodDays),
        ]);

        $this->addAuditLog('pre_adverse_action_initiated', [
            'deadline' => $this->pre_adverse_action_deadline->toDateString(),
        ]);
    }

    /**
     * Complete adverse action.
     */
    public function completeAdverseAction(): void
    {
        $this->update([
            'adverse_action_sent_at' => now(),
            'adjudication_status' => self::ADJ_DENIED,
        ]);

        $this->addAuditLog('adverse_action_completed');
    }

    /**
     * Approve after adjudication.
     */
    public function approveAfterAdjudication(?int $adjudicatorId = null, ?string $notes = null): void
    {
        $this->update([
            'adjudication_status' => self::ADJ_APPROVED,
            'adjudicated_by' => $adjudicatorId ?? auth()->id(),
            'adjudicated_at' => now(),
            'adjudication_notes' => $notes,
            'adverse_action_required' => false,
        ]);

        $this->addAuditLog('adjudication_approved', [
            'adjudicator' => $adjudicatorId ?? auth()->id(),
            'notes' => $notes,
        ]);
    }

    /**
     * Deny after adjudication.
     */
    public function denyAfterAdjudication(?int $adjudicatorId = null, ?string $notes = null): void
    {
        $this->update([
            'adjudication_status' => self::ADJ_DENIED,
            'adjudicated_by' => $adjudicatorId ?? auth()->id(),
            'adjudicated_at' => now(),
            'adjudication_notes' => $notes,
            'adverse_action_required' => true,
        ]);

        $this->addAuditLog('adjudication_denied', [
            'adjudicator' => $adjudicatorId ?? auth()->id(),
            'notes' => $notes,
        ]);
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

    /**
     * Get check type display name.
     */
    public function getCheckTypeNameAttribute(): string
    {
        $names = [
            'basic' => 'Basic Check',
            'standard' => 'Standard Check',
            'professional' => 'Professional Check',
            'comprehensive' => 'Comprehensive Check',
            'dbs_basic' => 'DBS Basic',
            'dbs_standard' => 'DBS Standard',
            'dbs_enhanced' => 'DBS Enhanced',
            'dbs_enhanced_barred' => 'DBS Enhanced with Barred List',
            'police_check' => 'Police Check',
            'working_with_children' => 'Working with Children Check',
        ];

        return $names[$this->check_type] ?? ucwords(str_replace('_', ' ', $this->check_type));
    }

    /**
     * Get status display name.
     */
    public function getStatusNameAttribute(): string
    {
        $names = [
            self::STATUS_PENDING_CONSENT => 'Pending Consent',
            self::STATUS_CONSENT_RECEIVED => 'Consent Received',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETE => 'Complete',
            self::STATUS_CONSIDER => 'Requires Review',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_DISPUTE => 'Under Dispute',
        ];

        return $names[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }
}
