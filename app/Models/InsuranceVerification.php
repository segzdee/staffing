<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Insurance Verification Model
 * BIZ-REG-005: Insurance & Compliance
 *
 * Tracks overall insurance compliance status for a business
 */
class InsuranceVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_profile_id',
        'user_id',
        'jurisdiction',
        'status',
        'is_fully_compliant',
        'compliant_since',
        'last_compliance_check',
        'compliance_summary',
        'missing_coverages',
        'expiring_soon',
        'is_suspended',
        'suspended_at',
        'suspension_reason',
        'suspension_lifted_at',
        'notification_history',
        'last_reminder_sent_at',
        'reminders_sent',
    ];

    protected $casts = [
        'is_fully_compliant' => 'boolean',
        'is_suspended' => 'boolean',
        'compliant_since' => 'datetime',
        'last_compliance_check' => 'datetime',
        'suspended_at' => 'datetime',
        'suspension_lifted_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'compliance_summary' => 'array',
        'missing_coverages' => 'array',
        'expiring_soon' => 'array',
        'notification_history' => 'array',
    ];

    // ==================== STATUS CONSTANTS ====================

    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_COMPLIANT = 'compliant';
    const STATUS_NON_COMPLIANT = 'non_compliant';
    const STATUS_EXPIRED = 'expired';

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the business profile.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all insurance certificates.
     */
    public function certificates()
    {
        return $this->hasMany(InsuranceCertificate::class);
    }

    /**
     * Get active certificates.
     */
    public function activeCertificates()
    {
        return $this->certificates()
            ->where('status', InsuranceCertificate::STATUS_VERIFIED)
            ->where('is_expired', false);
    }

    /**
     * Get expiring certificates.
     */
    public function expiringCertificates()
    {
        return $this->certificates()
            ->where('status', InsuranceCertificate::STATUS_VERIFIED)
            ->where('is_expired', false)
            ->where('expiry_date', '<=', now()->addDays(30));
    }

    // ==================== SCOPES ====================

    /**
     * Scope to compliant businesses.
     */
    public function scopeCompliant($query)
    {
        return $query->where('is_fully_compliant', true)
            ->where('status', self::STATUS_COMPLIANT);
    }

    /**
     * Scope to non-compliant businesses.
     */
    public function scopeNonCompliant($query)
    {
        return $query->where(function ($q) {
            $q->where('is_fully_compliant', false)
              ->orWhere('status', self::STATUS_NON_COMPLIANT);
        });
    }

    /**
     * Scope to suspended businesses.
     */
    public function scopeSuspended($query)
    {
        return $query->where('is_suspended', true);
    }

    /**
     * Scope by jurisdiction.
     */
    public function scopeForJurisdiction($query, string $jurisdiction)
    {
        return $query->where('jurisdiction', strtoupper($jurisdiction));
    }

    /**
     * Scope to verifications with expiring coverage.
     */
    public function scopeWithExpiringCoverage($query, int $days = 30)
    {
        return $query->whereHas('certificates', function ($q) use ($days) {
            $q->where('status', InsuranceCertificate::STATUS_VERIFIED)
              ->where('is_expired', false)
              ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
        });
    }

    // ==================== STATUS METHODS ====================

    /**
     * Check if pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if partially compliant.
     */
    public function isPartial(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    /**
     * Check if fully compliant.
     */
    public function isCompliant(): bool
    {
        return $this->status === self::STATUS_COMPLIANT && $this->is_fully_compliant;
    }

    /**
     * Check if non-compliant.
     */
    public function isNonCompliant(): bool
    {
        return $this->status === self::STATUS_NON_COMPLIANT;
    }

    /**
     * Check if suspended.
     */
    public function isSuspended(): bool
    {
        return $this->is_suspended;
    }

    // ==================== COMPLIANCE METHODS ====================

    /**
     * Check compliance against requirements.
     */
    public function checkCompliance(): array
    {
        $requirements = InsuranceRequirement::getRequirements(
            $this->jurisdiction,
            $this->businessProfile->business_type ?? null,
            $this->businessProfile->industry ?? null,
            $this->businessProfile->business_state ?? null
        );

        $requiredTypes = $requirements->where('is_required', true)->pluck('insurance_type')->toArray();
        $activeCertificates = $this->activeCertificates()->get();

        $compliant = [];
        $missing = [];
        $expiringSoon = [];

        foreach ($requiredTypes as $type) {
            $certificate = $activeCertificates->firstWhere('insurance_type', $type);

            if (!$certificate) {
                $missing[] = $type;
                continue;
            }

            // Check if expiring soon (within 30 days)
            if ($certificate->expiry_date && $certificate->expiry_date->lte(now()->addDays(30))) {
                $expiringSoon[] = [
                    'type' => $type,
                    'expiry_date' => $certificate->expiry_date->toDateString(),
                    'days_remaining' => now()->diffInDays($certificate->expiry_date),
                ];
            }

            // Validate coverage amount
            $requirement = $requirements->firstWhere('insurance_type', $type);
            if ($requirement && $requirement->minimum_coverage_amount) {
                if ($certificate->coverage_amount < $requirement->minimum_coverage_amount) {
                    $missing[] = $type; // Insufficient coverage = missing
                    continue;
                }
            }

            $compliant[] = $type;
        }

        $isFullyCompliant = empty($missing);

        return [
            'is_compliant' => $isFullyCompliant,
            'compliant_coverages' => $compliant,
            'missing_coverages' => $missing,
            'expiring_soon' => $expiringSoon,
            'total_required' => count($requiredTypes),
            'total_compliant' => count($compliant),
        ];
    }

    /**
     * Update compliance status.
     */
    public function updateComplianceStatus(): self
    {
        $result = $this->checkCompliance();

        $status = self::STATUS_PENDING;
        if ($result['is_compliant']) {
            $status = self::STATUS_COMPLIANT;
        } elseif ($result['total_compliant'] > 0) {
            $status = self::STATUS_PARTIAL;
        } elseif (!empty($result['missing_coverages'])) {
            $status = self::STATUS_NON_COMPLIANT;
        }

        $this->update([
            'status' => $status,
            'is_fully_compliant' => $result['is_compliant'],
            'compliant_since' => $result['is_compliant'] && !$this->is_fully_compliant ? now() : $this->compliant_since,
            'last_compliance_check' => now(),
            'compliance_summary' => $result,
            'missing_coverages' => $result['missing_coverages'],
            'expiring_soon' => $result['expiring_soon'],
        ]);

        return $this;
    }

    /**
     * Suspend for non-compliance.
     */
    public function suspend(string $reason): self
    {
        $this->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        // Update business profile
        $this->businessProfile->update([
            'can_post_shifts' => false,
            'account_warning_message' => 'Insurance compliance issue: ' . $reason,
        ]);

        return $this;
    }

    /**
     * Lift suspension.
     */
    public function liftSuspension(): self
    {
        $this->update([
            'is_suspended' => false,
            'suspension_lifted_at' => now(),
            'suspension_reason' => null,
        ]);

        // Update business profile if compliant
        if ($this->is_fully_compliant) {
            $this->businessProfile->update([
                'can_post_shifts' => true,
                'account_warning_message' => null,
            ]);
        }

        return $this;
    }

    /**
     * Record notification sent.
     */
    public function recordNotification(string $type, array $details = []): self
    {
        $history = $this->notification_history ?? [];
        $history[] = [
            'type' => $type,
            'sent_at' => now()->toIso8601String(),
            'details' => $details,
        ];

        $this->update([
            'notification_history' => $history,
            'last_reminder_sent_at' => now(),
            'reminders_sent' => $this->reminders_sent + 1,
        ]);

        return $this;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PARTIAL => 'Partially Compliant',
            self::STATUS_COMPLIANT => 'Fully Compliant',
            self::STATUS_NON_COMPLIANT => 'Non-Compliant',
            self::STATUS_EXPIRED => 'Expired',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get status color.
     */
    public function getStatusColor(): string
    {
        $colors = [
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PARTIAL => 'orange',
            self::STATUS_COMPLIANT => 'green',
            self::STATUS_NON_COMPLIANT => 'red',
            self::STATUS_EXPIRED => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get compliance percentage.
     */
    public function getCompliancePercentage(): int
    {
        $summary = $this->compliance_summary;

        if (!$summary || !isset($summary['total_required']) || $summary['total_required'] === 0) {
            return 0;
        }

        return (int) round(($summary['total_compliant'] / $summary['total_required']) * 100);
    }

    /**
     * Get certificate by type.
     */
    public function getCertificateByType(string $type): ?InsuranceCertificate
    {
        return $this->activeCertificates()->where('insurance_type', $type)->first();
    }

    /**
     * Check if specific coverage exists.
     */
    public function hasCoverage(string $type): bool
    {
        return $this->getCertificateByType($type) !== null;
    }
}
