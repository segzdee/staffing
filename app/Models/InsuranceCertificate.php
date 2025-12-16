<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Insurance Certificate Model
 * BIZ-REG-005: Insurance & Compliance
 *
 * Stores encrypted insurance certificate data
 */
class InsuranceCertificate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'insurance_verification_id',
        'business_profile_id',
        'requirement_id',
        'insurance_type',
        'policy_number',
        'carrier_name',
        'carrier_naic_code',
        'carrier_am_best_rating',
        'named_insured',
        'insured_address',
        'coverage_amount',
        'coverage_currency',
        'per_occurrence_limit',
        'aggregate_limit',
        'deductible_amount',
        'coverage_details',
        'effective_date',
        'expiry_date',
        'is_expired',
        'auto_renews',
        'has_additional_insured',
        'additional_insured_verified',
        'additional_insured_text',
        'has_waiver_of_subrogation',
        'waiver_verified',
        'file_path_encrypted',
        'file_hash',
        'original_filename',
        'mime_type',
        'file_size',
        'storage_provider',
        'status',
        'carrier_verified',
        'carrier_verified_at',
        'carrier_verification_response',
        'extracted_data',
        'extraction_confidence',
        'extracted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'rejection_reason',
        'meets_minimum_coverage',
        'coverage_validation_details',
        'access_token',
        'access_token_expires_at',
        'download_count',
        'last_accessed_at',
        'expiry_90_day_notified',
        'expiry_60_day_notified',
        'expiry_30_day_notified',
        'expiry_14_day_notified',
        'expiry_7_day_notified',
        'expired_notified',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'carrier_verified_at' => 'datetime',
        'extracted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'access_token_expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'coverage_details' => 'array',
        'carrier_verification_response' => 'array',
        'extracted_data' => 'array',
        'coverage_validation_details' => 'array',
        'is_expired' => 'boolean',
        'auto_renews' => 'boolean',
        'has_additional_insured' => 'boolean',
        'additional_insured_verified' => 'boolean',
        'has_waiver_of_subrogation' => 'boolean',
        'waiver_verified' => 'boolean',
        'carrier_verified' => 'boolean',
        'meets_minimum_coverage' => 'boolean',
        'expiry_90_day_notified' => 'boolean',
        'expiry_60_day_notified' => 'boolean',
        'expiry_30_day_notified' => 'boolean',
        'expiry_14_day_notified' => 'boolean',
        'expiry_7_day_notified' => 'boolean',
        'expired_notified' => 'boolean',
        'extraction_confidence' => 'float',
    ];

    protected $hidden = [
        'file_path_encrypted',
        'access_token',
    ];

    // ==================== STATUS CONSTANTS ====================

    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            if (empty($certificate->access_token)) {
                $certificate->access_token = Str::random(64);
                $certificate->access_token_expires_at = now()->addHours(24);
            }
        });

        static::saving(function ($certificate) {
            // Auto-update expired status
            if ($certificate->expiry_date && $certificate->expiry_date->isPast()) {
                $certificate->is_expired = true;
                if ($certificate->status === self::STATUS_VERIFIED) {
                    $certificate->status = self::STATUS_EXPIRED;
                }
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the insurance verification.
     */
    public function insuranceVerification()
    {
        return $this->belongsTo(InsuranceVerification::class);
    }

    /**
     * Get the business profile.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the requirement.
     */
    public function requirement()
    {
        return $this->belongsTo(InsuranceRequirement::class, 'requirement_id');
    }

    /**
     * Get the reviewer.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the carrier.
     */
    public function carrier()
    {
        return $this->belongsTo(InsuranceCarrier::class, 'carrier_naic_code', 'naic_code');
    }

    /**
     * Get access logs.
     */
    public function accessLogs()
    {
        return $this->hasMany(InsuranceCertificateAccessLog::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to pending certificates.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to verified certificates.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Scope to active (verified and not expired).
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_VERIFIED)
            ->where('is_expired', false);
    }

    /**
     * Scope to expiring certificates.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_VERIFIED)
            ->where('is_expired', false)
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope to expired certificates.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('is_expired', true)
              ->orWhere('status', self::STATUS_EXPIRED)
              ->orWhere(function ($subQ) {
                  $subQ->whereNotNull('expiry_date')
                       ->where('expiry_date', '<', now());
              });
        });
    }

    /**
     * Scope by insurance type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('insurance_type', $type);
    }

    /**
     * Scope requiring expiry notification.
     */
    public function scopeNeedsExpiryNotification($query, int $days)
    {
        $column = match ($days) {
            90 => 'expiry_90_day_notified',
            60 => 'expiry_60_day_notified',
            30 => 'expiry_30_day_notified',
            14 => 'expiry_14_day_notified',
            7 => 'expiry_7_day_notified',
            default => null,
        };

        if (!$column) {
            return $query;
        }

        return $query->where('status', self::STATUS_VERIFIED)
            ->where('is_expired', false)
            ->where($column, false)
            ->whereBetween('expiry_date', [now()->addDays($days - 1), now()->addDays($days + 1)]);
    }

    // ==================== ENCRYPTION METHODS ====================

    /**
     * Set file path with encryption.
     */
    public function setFilePath(string $path): void
    {
        $this->file_path_encrypted = Crypt::encryptString($path);
    }

    /**
     * Get decrypted file path.
     */
    public function getFilePath(): ?string
    {
        if (empty($this->file_path_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->file_path_encrypted);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt file path for certificate: ' . $this->id);
            return null;
        }
    }

    // ==================== ACCESS TOKEN METHODS ====================

    /**
     * Regenerate access token.
     */
    public function regenerateAccessToken(int $hours = 24): string
    {
        $this->access_token = Str::random(64);
        $this->access_token_expires_at = now()->addHours($hours);
        $this->save();

        return $this->access_token;
    }

    /**
     * Get secure download URL.
     */
    public function getSecureUrl(int $expiryMinutes = 60): string
    {
        if (!$this->access_token_expires_at || $this->access_token_expires_at->isPast()) {
            $this->regenerateAccessToken(1);
        }

        return route('api.business.insurance.certificates.download', [
            'token' => $this->access_token,
            'expires' => now()->addMinutes($expiryMinutes)->timestamp,
            'signature' => hash_hmac('sha256', $this->access_token . now()->addMinutes($expiryMinutes)->timestamp, config('app.key')),
        ]);
    }

    /**
     * Validate access token.
     */
    public function validateAccessToken(string $token): bool
    {
        if ($this->access_token !== $token) {
            return false;
        }

        if ($this->access_token_expires_at && $this->access_token_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Record access.
     */
    public function recordAccess(string $action, ?User $user = null, ?array $metadata = null): void
    {
        InsuranceCertificateAccessLog::create([
            'insurance_certificate_id' => $this->id,
            'user_id' => $user?->id,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);

        if ($action === 'download') {
            $this->increment('download_count');
        }

        $this->update(['last_accessed_at' => now()]);
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
     * Check if verified.
     */
    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    /**
     * Check if expired.
     */
    public function isExpired(): bool
    {
        if ($this->is_expired || $this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if active (verified and not expired).
     */
    public function isActive(): bool
    {
        return $this->isVerified() && !$this->isExpired();
    }

    // ==================== WORKFLOW METHODS ====================

    /**
     * Record extracted data from OCR.
     */
    public function recordExtractedData(array $data, float $confidence): self
    {
        $this->update([
            'extracted_data' => $data,
            'extraction_confidence' => $confidence,
            'extracted_at' => now(),
        ]);

        return $this;
    }

    /**
     * Record carrier verification result.
     */
    public function recordCarrierVerification(bool $verified, array $response): self
    {
        $this->update([
            'carrier_verified' => $verified,
            'carrier_verified_at' => now(),
            'carrier_verification_response' => $response,
        ]);

        return $this;
    }

    /**
     * Validate coverage against requirements.
     */
    public function validateCoverage(): array
    {
        $requirement = $this->requirement;

        if (!$requirement) {
            // Get requirement from type
            $requirement = InsuranceRequirement::active()
                ->ofType($this->insurance_type)
                ->forJurisdiction($this->insuranceVerification->jurisdiction ?? 'US')
                ->first();
        }

        if (!$requirement) {
            return [
                'valid' => true,
                'details' => ['No specific requirements found'],
            ];
        }

        $issues = [];

        // Check minimum coverage
        if ($requirement->minimum_coverage_amount && $this->coverage_amount < $requirement->minimum_coverage_amount) {
            $issues[] = sprintf(
                'Coverage amount (%s) is below minimum required (%s)',
                $this->getCoverageFormatted(),
                $requirement->getMinimumCoverageFormatted()
            );
        }

        // Check per occurrence limit
        if ($requirement->minimum_per_occurrence && $this->per_occurrence_limit < $requirement->minimum_per_occurrence) {
            $issues[] = 'Per occurrence limit is below minimum required';
        }

        // Check aggregate limit
        if ($requirement->minimum_aggregate && $this->aggregate_limit < $requirement->minimum_aggregate) {
            $issues[] = 'Aggregate limit is below minimum required';
        }

        // Check additional insured
        if ($requirement->additional_insured_required && !$this->additional_insured_verified) {
            $issues[] = 'Additional insured clause required but not verified';
        }

        // Check waiver of subrogation
        if ($requirement->waiver_of_subrogation_required && !$this->waiver_verified) {
            $issues[] = 'Waiver of subrogation required but not verified';
        }

        $valid = empty($issues);

        $this->update([
            'meets_minimum_coverage' => $valid,
            'coverage_validation_details' => [
                'valid' => $valid,
                'issues' => $issues,
                'validated_at' => now()->toIso8601String(),
            ],
        ]);

        return [
            'valid' => $valid,
            'issues' => $issues,
        ];
    }

    /**
     * Verify certificate.
     */
    public function verify(User $reviewer, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        $this->recordAccess('verify', $reviewer);

        // Update parent verification status
        $this->insuranceVerification->updateComplianceStatus();

        return $this;
    }

    /**
     * Reject certificate.
     */
    public function reject(User $reviewer, string $reason, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'review_notes' => $notes,
        ]);

        $this->recordAccess('reject', $reviewer, ['reason' => $reason]);

        // Update parent verification status
        $this->insuranceVerification->updateComplianceStatus();

        return $this;
    }

    /**
     * Mark expiry notification as sent.
     */
    public function markExpiryNotificationSent(int $days): self
    {
        $column = match ($days) {
            90 => 'expiry_90_day_notified',
            60 => 'expiry_60_day_notified',
            30 => 'expiry_30_day_notified',
            14 => 'expiry_14_day_notified',
            7 => 'expiry_7_day_notified',
            0 => 'expired_notified',
            default => null,
        };

        if ($column) {
            $this->update([$column => true]);
        }

        return $this;
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): self
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
            'is_expired' => true,
        ]);

        // Update parent verification status
        $this->insuranceVerification->updateComplianceStatus();

        return $this;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_REJECTED => 'Rejected',
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
            self::STATUS_VERIFIED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get coverage amount formatted.
     */
    public function getCoverageFormatted(): string
    {
        $symbols = [
            'USD' => '$',
            'GBP' => '£',
            'EUR' => '€',
            'AUD' => 'A$',
            'AED' => 'AED ',
            'SGD' => 'S$',
        ];

        $symbol = $symbols[$this->coverage_currency] ?? $this->coverage_currency . ' ';
        $amount = $this->coverage_amount / 100;

        return $symbol . number_format($amount, 0);
    }

    /**
     * Get insurance type label.
     */
    public function getInsuranceTypeLabel(): string
    {
        return InsuranceRequirement::getInsuranceTypeName($this->insurance_type);
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get file size formatted.
     */
    public function getFileSizeFormatted(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Calculate file hash.
     */
    public static function calculateFileHash(string $contents): string
    {
        return hash('sha256', $contents);
    }
}
