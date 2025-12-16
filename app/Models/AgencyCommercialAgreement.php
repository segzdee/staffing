<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Agency Commercial Agreement Model
 * AGY-REG: Agency Registration & Onboarding System
 *
 * Tracks commercial agreements between the platform and agencies including:
 * - Commission rates and fee structures
 * - Service level agreements
 * - Terms and conditions acceptance
 * - Digital signatures
 *
 * @property int $id
 * @property int $agency_application_id
 * @property string $agreement_type
 * @property string $version
 * @property string $status
 * @property string|null $document_url
 * @property string|null $document_hash
 * @property array|null $terms
 * @property numeric $commission_rate
 * @property numeric|null $minimum_commission
 * @property numeric|null $maximum_commission
 * @property string|null $payment_terms
 * @property int|null $payment_due_days
 * @property array|null $fee_structure
 * @property array|null $sla_terms
 * @property \Illuminate\Support\Carbon|null $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_until
 * @property bool $auto_renew
 * @property int|null $renewal_notice_days
 * @property string|null $agency_signatory_name
 * @property string|null $agency_signatory_title
 * @property string|null $agency_signatory_email
 * @property string|null $agency_signature
 * @property string|null $agency_signature_ip
 * @property \Illuminate\Support\Carbon|null $agency_signed_at
 * @property string|null $platform_signatory_name
 * @property string|null $platform_signatory_title
 * @property string|null $platform_signature
 * @property \Illuminate\Support\Carbon|null $platform_signed_at
 * @property int|null $platform_signed_by
 * @property \Illuminate\Support\Carbon|null $countersigned_at
 * @property bool $is_fully_executed
 * @property string|null $signed_document_url
 * @property string|null $reference_number
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\AgencyApplication $application
 * @property-read \App\Models\User|null $platformSignedBy
 */
class AgencyCommercialAgreement extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'agency_application_id',
        'agreement_type',
        'version',
        'status',
        'document_url',
        'document_hash',
        'terms',
        // Commercial terms
        'commission_rate',
        'minimum_commission',
        'maximum_commission',
        'payment_terms',
        'payment_due_days',
        'fee_structure',
        'sla_terms',
        // Validity
        'effective_from',
        'effective_until',
        'auto_renew',
        'renewal_notice_days',
        // Agency signature
        'agency_signatory_name',
        'agency_signatory_title',
        'agency_signatory_email',
        'agency_signature',
        'agency_signature_ip',
        'agency_signed_at',
        // Platform signature
        'platform_signatory_name',
        'platform_signatory_title',
        'platform_signature',
        'platform_signed_at',
        'platform_signed_by',
        'countersigned_at',
        // Status
        'is_fully_executed',
        'signed_document_url',
        'reference_number',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'terms' => 'array',
        'fee_structure' => 'array',
        'sla_terms' => 'array',
        'commission_rate' => 'decimal:2',
        'minimum_commission' => 'decimal:2',
        'maximum_commission' => 'decimal:2',
        'payment_due_days' => 'integer',
        'renewal_notice_days' => 'integer',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'auto_renew' => 'boolean',
        'agency_signed_at' => 'datetime',
        'platform_signed_at' => 'datetime',
        'countersigned_at' => 'datetime',
        'is_fully_executed' => 'boolean',
    ];

    // ==================== AGREEMENT TYPE CONSTANTS ====================

    /**
     * Standard agency partnership agreement
     */
    const TYPE_STANDARD = 'standard';

    /**
     * Premium partnership with enhanced terms
     */
    const TYPE_PREMIUM = 'premium';

    /**
     * Enterprise custom agreement
     */
    const TYPE_ENTERPRISE = 'enterprise';

    /**
     * Pilot/trial agreement
     */
    const TYPE_PILOT = 'pilot';

    /**
     * Amendment to existing agreement
     */
    const TYPE_AMENDMENT = 'amendment';

    /**
     * All valid agreement types
     */
    const AGREEMENT_TYPES = [
        self::TYPE_STANDARD,
        self::TYPE_PREMIUM,
        self::TYPE_ENTERPRISE,
        self::TYPE_PILOT,
        self::TYPE_AMENDMENT,
    ];

    // ==================== STATUS CONSTANTS ====================

    /**
     * Agreement draft (not yet sent for signature)
     */
    const STATUS_DRAFT = 'draft';

    /**
     * Agreement sent to agency for signature
     */
    const STATUS_PENDING_AGENCY_SIGNATURE = 'pending_agency_signature';

    /**
     * Agency signed, awaiting platform countersignature
     */
    const STATUS_PENDING_COUNTERSIGNATURE = 'pending_countersignature';

    /**
     * Fully executed (both parties signed)
     */
    const STATUS_EXECUTED = 'executed';

    /**
     * Agreement expired
     */
    const STATUS_EXPIRED = 'expired';

    /**
     * Agreement terminated
     */
    const STATUS_TERMINATED = 'terminated';

    /**
     * Agreement superseded by newer version
     */
    const STATUS_SUPERSEDED = 'superseded';

    /**
     * All valid statuses
     */
    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_AGENCY_SIGNATURE,
        self::STATUS_PENDING_COUNTERSIGNATURE,
        self::STATUS_EXECUTED,
        self::STATUS_EXPIRED,
        self::STATUS_TERMINATED,
        self::STATUS_SUPERSEDED,
    ];

    // ==================== MODEL BOOT ====================

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->reference_number)) {
                $model->reference_number = self::generateReferenceNumber();
            }
            if (empty($model->version)) {
                $model->version = '1.0';
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the agency application this agreement belongs to.
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
     * Get the platform user who signed this agreement.
     */
    public function platformSignedBy()
    {
        return $this->belongsTo(User::class, 'platform_signed_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to draft agreements.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to agreements pending agency signature.
     */
    public function scopePendingAgencySignature($query)
    {
        return $query->where('status', self::STATUS_PENDING_AGENCY_SIGNATURE);
    }

    /**
     * Scope to agreements pending countersignature.
     */
    public function scopePendingCountersignature($query)
    {
        return $query->where('status', self::STATUS_PENDING_COUNTERSIGNATURE);
    }

    /**
     * Scope to fully executed agreements.
     */
    public function scopeExecuted($query)
    {
        return $query->where('status', self::STATUS_EXECUTED);
    }

    /**
     * Scope to active agreements (executed and not expired).
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_EXECUTED)
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', now());
            });
    }

    /**
     * Scope to expired agreements.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
            ->orWhere(function ($q) {
                $q->whereNotNull('effective_until')
                  ->where('effective_until', '<', now());
            });
    }

    /**
     * Scope by agreement type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('agreement_type', $type);
    }

    // ==================== STATUS HELPER METHODS ====================

    /**
     * Check if agreement is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if agreement is pending agency signature.
     */
    public function isPendingAgencySignature(): bool
    {
        return $this->status === self::STATUS_PENDING_AGENCY_SIGNATURE;
    }

    /**
     * Check if agreement is pending countersignature.
     */
    public function isPendingCountersignature(): bool
    {
        return $this->status === self::STATUS_PENDING_COUNTERSIGNATURE;
    }

    /**
     * Check if agreement is fully executed.
     */
    public function isExecuted(): bool
    {
        return $this->status === self::STATUS_EXECUTED;
    }

    /**
     * Check if agreement is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->effective_until && $this->effective_until->isPast();
    }

    /**
     * Check if agreement is terminated.
     */
    public function isTerminated(): bool
    {
        return $this->status === self::STATUS_TERMINATED;
    }

    /**
     * Check if agreement is active (executed and not expired).
     */
    public function isActive(): bool
    {
        return $this->isExecuted() && !$this->isExpired();
    }

    /**
     * Check if agreement is fully executed (both parties signed).
     */
    public function isFullyExecuted(): bool
    {
        return $this->is_fully_executed
            || ($this->agency_signed_at !== null && $this->platform_signed_at !== null);
    }

    /**
     * Check if agency has signed.
     */
    public function hasAgencySigned(): bool
    {
        return $this->agency_signed_at !== null;
    }

    /**
     * Check if platform has signed.
     */
    public function hasPlatformSigned(): bool
    {
        return $this->platform_signed_at !== null;
    }

    // ==================== TRANSITION METHODS ====================

    /**
     * Send agreement for agency signature.
     */
    public function sendForSignature(): self
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Only draft agreements can be sent for signature.');
        }

        $this->update([
            'status' => self::STATUS_PENDING_AGENCY_SIGNATURE,
        ]);

        return $this;
    }

    /**
     * Record agency signature.
     */
    public function sign(
        string $signatoryName,
        string $signatoryTitle,
        string $signatoryEmail,
        ?string $signature = null,
        ?string $ip = null
    ): self {
        if ($this->status !== self::STATUS_PENDING_AGENCY_SIGNATURE) {
            throw new \InvalidArgumentException('Agreement is not pending agency signature.');
        }

        $this->update([
            'status' => self::STATUS_PENDING_COUNTERSIGNATURE,
            'agency_signatory_name' => $signatoryName,
            'agency_signatory_title' => $signatoryTitle,
            'agency_signatory_email' => $signatoryEmail,
            'agency_signature' => $signature ?? $this->generateSignatureHash($signatoryName, $signatoryEmail),
            'agency_signature_ip' => $ip ?? request()->ip(),
            'agency_signed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Record platform countersignature and execute agreement.
     */
    public function countersign(
        User $signedBy,
        string $signatoryName,
        string $signatoryTitle,
        ?string $signature = null
    ): self {
        if ($this->status !== self::STATUS_PENDING_COUNTERSIGNATURE) {
            throw new \InvalidArgumentException('Agreement is not pending countersignature.');
        }

        $this->update([
            'status' => self::STATUS_EXECUTED,
            'platform_signed_by' => $signedBy->id,
            'platform_signatory_name' => $signatoryName,
            'platform_signatory_title' => $signatoryTitle,
            'platform_signature' => $signature ?? $this->generateSignatureHash($signatoryName, $signedBy->email),
            'platform_signed_at' => now(),
            'countersigned_at' => now(),
            'is_fully_executed' => true,
            'effective_from' => $this->effective_from ?? now(),
        ]);

        return $this;
    }

    /**
     * Terminate the agreement.
     */
    public function terminate(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_TERMINATED,
            'notes' => $reason,
        ]);

        return $this;
    }

    /**
     * Mark agreement as expired.
     */
    public function markExpired(): self
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        return $this;
    }

    /**
     * Mark agreement as superseded.
     */
    public function supersede(): self
    {
        $this->update([
            'status' => self::STATUS_SUPERSEDED,
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
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_AGENCY_SIGNATURE => 'Pending Agency Signature',
            self::STATUS_PENDING_COUNTERSIGNATURE => 'Pending Countersignature',
            self::STATUS_EXECUTED => 'Executed',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_TERMINATED => 'Terminated',
            self::STATUS_SUPERSEDED => 'Superseded',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        $colors = [
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING_AGENCY_SIGNATURE => 'yellow',
            self::STATUS_PENDING_COUNTERSIGNATURE => 'blue',
            self::STATUS_EXECUTED => 'green',
            self::STATUS_EXPIRED => 'gray',
            self::STATUS_TERMINATED => 'red',
            self::STATUS_SUPERSEDED => 'purple',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get agreement type label for display.
     */
    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_STANDARD => 'Standard Partnership',
            self::TYPE_PREMIUM => 'Premium Partnership',
            self::TYPE_ENTERPRISE => 'Enterprise Agreement',
            self::TYPE_PILOT => 'Pilot Agreement',
            self::TYPE_AMENDMENT => 'Amendment',
        ];

        return $labels[$this->agreement_type] ?? ucfirst($this->agreement_type);
    }

    /**
     * Generate a unique reference number.
     */
    public static function generateReferenceNumber(): string
    {
        $prefix = 'AGY-AGR';
        $year = now()->format('Y');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$year}-{$random}";
    }

    /**
     * Generate a signature hash for digital signature.
     */
    protected function generateSignatureHash(string $name, string $email): string
    {
        $data = implode('|', [
            $name,
            $email,
            $this->reference_number,
            now()->toIso8601String(),
        ]);

        return hash('sha256', $data);
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->effective_until) {
            return null;
        }

        return now()->diffInDays($this->effective_until, false);
    }

    /**
     * Check if agreement needs renewal notification.
     */
    public function needsRenewalNotification(): bool
    {
        if (!$this->effective_until || !$this->renewal_notice_days) {
            return false;
        }

        $daysUntilExpiry = $this->getDaysUntilExpiry();

        return $daysUntilExpiry !== null
            && $daysUntilExpiry <= $this->renewal_notice_days
            && $daysUntilExpiry > 0;
    }

    /**
     * Get commission rate as percentage string.
     */
    public function getCommissionRateDisplay(): string
    {
        return number_format($this->commission_rate, 2) . '%';
    }

    /**
     * Calculate commission for a given amount.
     */
    public function calculateCommission(float $amount): float
    {
        $commission = ($amount * $this->commission_rate) / 100;

        // Apply minimum if set
        if ($this->minimum_commission && $commission < $this->minimum_commission) {
            $commission = $this->minimum_commission;
        }

        // Apply maximum if set
        if ($this->maximum_commission && $commission > $this->maximum_commission) {
            $commission = $this->maximum_commission;
        }

        return round($commission, 2);
    }

    /**
     * Get all agreement types with labels.
     */
    public static function getAgreementTypeOptions(): array
    {
        return [
            self::TYPE_STANDARD => 'Standard Partnership',
            self::TYPE_PREMIUM => 'Premium Partnership',
            self::TYPE_ENTERPRISE => 'Enterprise Agreement',
            self::TYPE_PILOT => 'Pilot Agreement',
            self::TYPE_AMENDMENT => 'Amendment',
        ];
    }
}
