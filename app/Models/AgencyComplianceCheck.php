<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Agency Compliance Check Model
 * AGY-REG: Agency Registration & Onboarding System
 *
 * Tracks compliance checks performed during agency registration including:
 * - Business license verification
 * - Insurance coverage validation
 * - Background checks on directors
 * - Financial stability checks
 * - Regulatory compliance (GDPR, labor laws, etc.)
 *
 * @property int $id
 * @property int $agency_application_id
 * @property string $check_type
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string|null $provider
 * @property string|null $external_reference
 * @property array|null $check_data
 * @property array|null $results
 * @property int|null $score
 * @property string|null $risk_level
 * @property \Illuminate\Support\Carbon|null $initiated_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int|null $performed_by
 * @property string|null $notes
 * @property string|null $failure_reason
 * @property array|null $failure_details
 * @property bool $is_required
 * @property bool $can_override
 * @property int|null $overridden_by
 * @property \Illuminate\Support\Carbon|null $overridden_at
 * @property string|null $override_reason
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\AgencyApplication $application
 * @property-read \App\Models\User|null $performedBy
 * @property-read \App\Models\User|null $overriddenBy
 */
class AgencyComplianceCheck extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'agency_application_id',
        'check_type',
        'name',
        'description',
        'status',
        'provider',
        'external_reference',
        'check_data',
        'results',
        'score',
        'risk_level',
        'initiated_at',
        'completed_at',
        'performed_by',
        'notes',
        'failure_reason',
        'failure_details',
        'is_required',
        'can_override',
        'overridden_by',
        'overridden_at',
        'override_reason',
        'valid_until',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'check_data' => 'array',
        'results' => 'array',
        'failure_details' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'overridden_at' => 'datetime',
        'valid_until' => 'datetime',
        'is_required' => 'boolean',
        'can_override' => 'boolean',
        'score' => 'integer',
    ];

    // ==================== CHECK TYPE CONSTANTS ====================

    /**
     * Business license verification
     */
    const TYPE_LICENSE_VERIFICATION = 'license_verification';

    /**
     * Insurance coverage validation
     */
    const TYPE_INSURANCE_VERIFICATION = 'insurance_verification';

    /**
     * Director/owner background check
     */
    const TYPE_BACKGROUND_CHECK = 'background_check';

    /**
     * Financial stability/credit check
     */
    const TYPE_FINANCIAL_CHECK = 'financial_check';

    /**
     * AML (Anti-Money Laundering) check
     */
    const TYPE_AML_CHECK = 'aml_check';

    /**
     * Sanctions list screening
     */
    const TYPE_SANCTIONS_SCREENING = 'sanctions_screening';

    /**
     * PEP (Politically Exposed Persons) check
     */
    const TYPE_PEP_CHECK = 'pep_check';

    /**
     * Business registration verification
     */
    const TYPE_BUSINESS_REGISTRATION = 'business_registration';

    /**
     * Tax compliance check
     */
    const TYPE_TAX_COMPLIANCE = 'tax_compliance';

    /**
     * Labor law compliance
     */
    const TYPE_LABOR_COMPLIANCE = 'labor_compliance';

    /**
     * GDPR/data protection compliance
     */
    const TYPE_DATA_PROTECTION = 'data_protection';

    /**
     * Reference check
     */
    const TYPE_REFERENCE_CHECK = 'reference_check';

    /**
     * All valid check types
     */
    const CHECK_TYPES = [
        self::TYPE_LICENSE_VERIFICATION,
        self::TYPE_INSURANCE_VERIFICATION,
        self::TYPE_BACKGROUND_CHECK,
        self::TYPE_FINANCIAL_CHECK,
        self::TYPE_AML_CHECK,
        self::TYPE_SANCTIONS_SCREENING,
        self::TYPE_PEP_CHECK,
        self::TYPE_BUSINESS_REGISTRATION,
        self::TYPE_TAX_COMPLIANCE,
        self::TYPE_LABOR_COMPLIANCE,
        self::TYPE_DATA_PROTECTION,
        self::TYPE_REFERENCE_CHECK,
    ];

    // ==================== STATUS CONSTANTS ====================

    /**
     * Check is pending (not yet started)
     */
    const STATUS_PENDING = 'pending';

    /**
     * Check is in progress
     */
    const STATUS_IN_PROGRESS = 'in_progress';

    /**
     * Check passed
     */
    const STATUS_PASSED = 'passed';

    /**
     * Check failed
     */
    const STATUS_FAILED = 'failed';

    /**
     * Check requires manual review
     */
    const STATUS_MANUAL_REVIEW = 'manual_review';

    /**
     * Check was overridden/bypassed
     */
    const STATUS_OVERRIDDEN = 'overridden';

    /**
     * Check expired (needs to be re-run)
     */
    const STATUS_EXPIRED = 'expired';

    /**
     * All valid statuses
     */
    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_PASSED,
        self::STATUS_FAILED,
        self::STATUS_MANUAL_REVIEW,
        self::STATUS_OVERRIDDEN,
        self::STATUS_EXPIRED,
    ];

    // ==================== RISK LEVEL CONSTANTS ====================

    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    const RISK_LEVELS = [
        self::RISK_LOW,
        self::RISK_MEDIUM,
        self::RISK_HIGH,
        self::RISK_CRITICAL,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the agency application this check belongs to.
     */
    public function application()
    {
        return $this->belongsTo(AgencyApplication::class, 'agency_application_id');
    }

    /**
     * Alias for application() relationship.
     */
    public function agencyApplication()
    {
        return $this->application();
    }

    /**
     * Get the user who performed this check.
     */
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the user who overrode this check.
     */
    public function overriddenBy()
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to pending checks.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to in-progress checks.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope to passed checks.
     */
    public function scopePassed($query)
    {
        return $query->where('status', self::STATUS_PASSED);
    }

    /**
     * Scope to failed checks.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to checks requiring manual review.
     */
    public function scopeRequiringManualReview($query)
    {
        return $query->where('status', self::STATUS_MANUAL_REVIEW);
    }

    /**
     * Scope to required checks only.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope by check type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('check_type', $type);
    }

    /**
     * Scope by risk level.
     */
    public function scopeByRiskLevel($query, string $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    /**
     * Scope to high-risk checks.
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    /**
     * Scope to completed checks (passed, failed, or overridden).
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PASSED,
            self::STATUS_FAILED,
            self::STATUS_OVERRIDDEN,
        ]);
    }

    // ==================== STATUS HELPER METHODS ====================

    /**
     * Check if status is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if status is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if check passed.
     */
    public function isPassed(): bool
    {
        return $this->status === self::STATUS_PASSED;
    }

    /**
     * Check if check failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if requires manual review.
     */
    public function requiresManualReview(): bool
    {
        return $this->status === self::STATUS_MANUAL_REVIEW;
    }

    /**
     * Check if was overridden.
     */
    public function isOverridden(): bool
    {
        return $this->status === self::STATUS_OVERRIDDEN;
    }

    /**
     * Check if check is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Check if check is complete (passed, failed, or overridden).
     */
    public function isComplete(): bool
    {
        return in_array($this->status, [
            self::STATUS_PASSED,
            self::STATUS_FAILED,
            self::STATUS_OVERRIDDEN,
        ]);
    }

    /**
     * Check if check effectively passed (passed or overridden).
     */
    public function effectivelyPassed(): bool
    {
        return in_array($this->status, [
            self::STATUS_PASSED,
            self::STATUS_OVERRIDDEN,
        ]);
    }

    // ==================== TRANSITION METHODS ====================

    /**
     * Start the compliance check.
     */
    public function start(?string $provider = null, ?string $externalReference = null): self
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'initiated_at' => now(),
            'provider' => $provider,
            'external_reference' => $externalReference,
        ]);

        return $this;
    }

    /**
     * Mark the check as passed.
     */
    public function markPassed(
        ?User $performedBy = null,
        ?array $results = null,
        ?int $score = null,
        ?string $notes = null,
        ?\DateTimeInterface $validUntil = null
    ): self {
        $this->update([
            'status' => self::STATUS_PASSED,
            'completed_at' => now(),
            'performed_by' => $performedBy?->id,
            'results' => $results,
            'score' => $score,
            'notes' => $notes,
            'valid_until' => $validUntil,
            'failure_reason' => null,
            'failure_details' => null,
        ]);

        return $this;
    }

    /**
     * Mark the check as failed.
     */
    public function markFailed(
        ?User $performedBy = null,
        string $reason = 'Check failed',
        ?array $details = null,
        ?array $results = null,
        ?string $riskLevel = null,
        ?string $notes = null
    ): self {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'performed_by' => $performedBy?->id,
            'results' => $results,
            'failure_reason' => $reason,
            'failure_details' => $details,
            'risk_level' => $riskLevel ?? self::RISK_HIGH,
            'notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Route to manual review.
     */
    public function routeToManualReview(?string $reason = null, ?array $results = null): self
    {
        $this->update([
            'status' => self::STATUS_MANUAL_REVIEW,
            'results' => $results,
            'notes' => $reason,
        ]);

        return $this;
    }

    /**
     * Override the check (bypass failure).
     */
    public function override(User $overriddenBy, string $reason): self
    {
        if (!$this->can_override) {
            throw new \InvalidArgumentException('This compliance check cannot be overridden.');
        }

        $this->update([
            'status' => self::STATUS_OVERRIDDEN,
            'overridden_by' => $overriddenBy->id,
            'overridden_at' => now(),
            'override_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Mark check as expired.
     */
    public function markExpired(): self
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        return $this;
    }

    /**
     * Reset check to pending (for re-run).
     */
    public function reset(): self
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'initiated_at' => null,
            'completed_at' => null,
            'results' => null,
            'score' => null,
            'failure_reason' => null,
            'failure_details' => null,
            'overridden_by' => null,
            'overridden_at' => null,
            'override_reason' => null,
        ]);

        return $this;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get status label for display.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_PASSED => 'Passed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_MANUAL_REVIEW => 'Manual Review Required',
            self::STATUS_OVERRIDDEN => 'Overridden',
            self::STATUS_EXPIRED => 'Expired',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        $colors = [
            self::STATUS_PENDING => 'gray',
            self::STATUS_IN_PROGRESS => 'blue',
            self::STATUS_PASSED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_MANUAL_REVIEW => 'orange',
            self::STATUS_OVERRIDDEN => 'purple',
            self::STATUS_EXPIRED => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get check type label for display.
     */
    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_LICENSE_VERIFICATION => 'License Verification',
            self::TYPE_INSURANCE_VERIFICATION => 'Insurance Verification',
            self::TYPE_BACKGROUND_CHECK => 'Background Check',
            self::TYPE_FINANCIAL_CHECK => 'Financial Check',
            self::TYPE_AML_CHECK => 'AML Check',
            self::TYPE_SANCTIONS_SCREENING => 'Sanctions Screening',
            self::TYPE_PEP_CHECK => 'PEP Check',
            self::TYPE_BUSINESS_REGISTRATION => 'Business Registration',
            self::TYPE_TAX_COMPLIANCE => 'Tax Compliance',
            self::TYPE_LABOR_COMPLIANCE => 'Labor Law Compliance',
            self::TYPE_DATA_PROTECTION => 'Data Protection Compliance',
            self::TYPE_REFERENCE_CHECK => 'Reference Check',
        ];

        return $labels[$this->check_type] ?? ucfirst(str_replace('_', ' ', $this->check_type));
    }

    /**
     * Get risk level label for display.
     */
    public function getRiskLevelLabel(): string
    {
        $labels = [
            self::RISK_LOW => 'Low Risk',
            self::RISK_MEDIUM => 'Medium Risk',
            self::RISK_HIGH => 'High Risk',
            self::RISK_CRITICAL => 'Critical Risk',
        ];

        return $labels[$this->risk_level] ?? 'Unknown';
    }

    /**
     * Get risk level color for UI.
     */
    public function getRiskLevelColor(): string
    {
        $colors = [
            self::RISK_LOW => 'green',
            self::RISK_MEDIUM => 'yellow',
            self::RISK_HIGH => 'orange',
            self::RISK_CRITICAL => 'red',
        ];

        return $colors[$this->risk_level] ?? 'gray';
    }

    /**
     * Get all check types with labels.
     */
    public static function getCheckTypeOptions(): array
    {
        return [
            self::TYPE_LICENSE_VERIFICATION => 'License Verification',
            self::TYPE_INSURANCE_VERIFICATION => 'Insurance Verification',
            self::TYPE_BACKGROUND_CHECK => 'Background Check',
            self::TYPE_FINANCIAL_CHECK => 'Financial Check',
            self::TYPE_AML_CHECK => 'AML Check',
            self::TYPE_SANCTIONS_SCREENING => 'Sanctions Screening',
            self::TYPE_PEP_CHECK => 'PEP Check',
            self::TYPE_BUSINESS_REGISTRATION => 'Business Registration',
            self::TYPE_TAX_COMPLIANCE => 'Tax Compliance',
            self::TYPE_LABOR_COMPLIANCE => 'Labor Law Compliance',
            self::TYPE_DATA_PROTECTION => 'Data Protection Compliance',
            self::TYPE_REFERENCE_CHECK => 'Reference Check',
        ];
    }

    /**
     * Get required checks for a jurisdiction.
     */
    public static function getRequiredChecksForJurisdiction(string $country): array
    {
        // Base checks required for all jurisdictions
        $required = [
            self::TYPE_LICENSE_VERIFICATION,
            self::TYPE_INSURANCE_VERIFICATION,
            self::TYPE_BUSINESS_REGISTRATION,
            self::TYPE_AML_CHECK,
            self::TYPE_SANCTIONS_SCREENING,
        ];

        // Add jurisdiction-specific checks
        switch (strtoupper($country)) {
            case 'US':
                $required[] = self::TYPE_TAX_COMPLIANCE;
                $required[] = self::TYPE_BACKGROUND_CHECK;
                break;

            case 'GB':
            case 'UK':
                $required[] = self::TYPE_TAX_COMPLIANCE;
                $required[] = self::TYPE_DATA_PROTECTION; // GDPR
                $required[] = self::TYPE_LABOR_COMPLIANCE;
                break;

            case 'AU':
                $required[] = self::TYPE_BACKGROUND_CHECK;
                $required[] = self::TYPE_LABOR_COMPLIANCE;
                break;

            default:
                // EU countries - require GDPR compliance
                if (in_array(strtoupper($country), ['DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'PL', 'IE'])) {
                    $required[] = self::TYPE_DATA_PROTECTION;
                }
                break;
        }

        return array_unique($required);
    }
}
