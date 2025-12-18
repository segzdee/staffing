<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-001: KYC Verification Model
 *
 * Represents a KYC (Know Your Customer) verification record for a user.
 * Supports multiple document types, provider integrations, and admin review workflow.
 *
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string $document_type
 * @property string|null $document_number
 * @property string $document_country
 * @property \Carbon\Carbon|null $document_expiry
 * @property string $document_front_path
 * @property string|null $document_back_path
 * @property string|null $selfie_path
 * @property array|null $verification_result
 * @property float|null $confidence_score
 * @property string $provider
 * @property string|null $provider_reference
 * @property string|null $provider_applicant_id
 * @property string|null $provider_check_id
 * @property string|null $rejection_reason
 * @property array|null $rejection_codes
 * @property int|null $reviewed_by
 * @property \Carbon\Carbon|null $reviewed_at
 * @property string|null $reviewer_notes
 * @property \Carbon\Carbon|null $expires_at
 * @property int $attempt_count
 * @property int $max_attempts
 * @property \Carbon\Carbon|null $last_attempt_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read User|null $reviewer
 */
class KycVerification extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    // Document type constants
    public const DOC_TYPE_PASSPORT = 'passport';

    public const DOC_TYPE_DRIVERS_LICENSE = 'drivers_license';

    public const DOC_TYPE_NATIONAL_ID = 'national_id';

    public const DOC_TYPE_RESIDENCE_PERMIT = 'residence_permit';

    // Provider constants
    public const PROVIDER_MANUAL = 'manual';

    public const PROVIDER_ONFIDO = 'onfido';

    public const PROVIDER_JUMIO = 'jumio';

    public const PROVIDER_VERIFF = 'veriff';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'document_type',
        'document_number',
        'document_country',
        'document_expiry',
        'document_front_path',
        'document_back_path',
        'selfie_path',
        'verification_result',
        'confidence_score',
        'provider',
        'provider_reference',
        'provider_applicant_id',
        'provider_check_id',
        'rejection_reason',
        'rejection_codes',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
        'expires_at',
        'attempt_count',
        'max_attempts',
        'last_attempt_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_expiry' => 'date',
            'verification_result' => 'array',
            'confidence_score' => 'decimal:4',
            'rejection_codes' => 'array',
            'reviewed_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who owns this verification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed this verification.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ==================== STATUS HELPERS ====================

    /**
     * Check if verification is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if verification is in review.
     */
    public function isInReview(): bool
    {
        return $this->status === self::STATUS_IN_REVIEW;
    }

    /**
     * Check if verification is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if verification is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if verification is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->expires_at && Carbon::now()->gte($this->expires_at)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user can retry verification.
     */
    public function canRetry(): bool
    {
        return $this->isRejected() && $this->attempt_count < $this->max_attempts;
    }

    /**
     * Check if document is expiring soon.
     */
    public function isDocumentExpiringSoon(?int $warningDays = null): bool
    {
        if (! $this->document_expiry) {
            return false;
        }

        $warningDays = $warningDays ?? config('kyc.expiry_warning_days', 30);

        return Carbon::now()->addDays($warningDays)->gte($this->document_expiry);
    }

    /**
     * Check if document has expired.
     */
    public function isDocumentExpired(): bool
    {
        if (! $this->document_expiry) {
            return false;
        }

        return Carbon::now()->gte($this->document_expiry);
    }

    // ==================== STATUS MUTATIONS ====================

    /**
     * Mark verification as in review.
     */
    public function markInReview(): void
    {
        $this->update([
            'status' => self::STATUS_IN_REVIEW,
        ]);
    }

    /**
     * Approve the verification.
     */
    public function approve(?int $adminId = null, ?string $notes = null): void
    {
        $expiresAt = $this->calculateExpirationDate();

        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $adminId,
            'reviewed_at' => Carbon::now(),
            'reviewer_notes' => $notes,
            'expires_at' => $expiresAt,
        ]);

        // Update user KYC status
        $this->updateUserKycStatus();
    }

    /**
     * Reject the verification.
     */
    public function reject(string $reason, ?array $codes = null, ?int $adminId = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'rejection_codes' => $codes,
            'reviewed_by' => $adminId,
            'reviewed_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark verification as expired.
     */
    public function markExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        // Reset user KYC status if this was the active verification
        $latestApproved = self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->where('status', self::STATUS_APPROVED)
            ->latest()
            ->first();

        if (! $latestApproved) {
            $this->user->update([
                'kyc_verified' => false,
                'kyc_level' => 'none',
            ]);
        }
    }

    /**
     * Submit verification for review.
     */
    public function submitForReview(): void
    {
        $this->update([
            'status' => self::STATUS_IN_REVIEW,
            'last_attempt_at' => Carbon::now(),
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Calculate the expiration date for approved verification.
     */
    protected function calculateExpirationDate(): Carbon
    {
        $autoExpireDays = config('kyc.auto_expire_days', 365);

        // Use document expiry if earlier than auto expiry
        if ($this->document_expiry) {
            $docExpiry = Carbon::parse($this->document_expiry);
            $autoExpiry = Carbon::now()->addDays($autoExpireDays);

            return $docExpiry->lt($autoExpiry) ? $docExpiry : $autoExpiry;
        }

        return Carbon::now()->addDays($autoExpireDays);
    }

    /**
     * Update the user's KYC verification status.
     */
    protected function updateUserKycStatus(): void
    {
        $kycLevel = $this->determineKycLevel();

        $this->user->update([
            'kyc_verified' => true,
            'kyc_verified_at' => Carbon::now(),
            'kyc_level' => $kycLevel,
        ]);
    }

    /**
     * Determine the KYC level based on document type and verification.
     */
    protected function determineKycLevel(): string
    {
        // Full: passport with selfie and high confidence
        if ($this->document_type === self::DOC_TYPE_PASSPORT
            && $this->selfie_path
            && $this->confidence_score >= 0.95) {
            return 'full';
        }

        // Enhanced: government ID with selfie
        if ($this->selfie_path && $this->confidence_score >= 0.85) {
            return 'enhanced';
        }

        // Basic: document only
        return 'basic';
    }

    /**
     * Get document type display name.
     */
    public function getDocumentTypeNameAttribute(): string
    {
        return match ($this->document_type) {
            self::DOC_TYPE_PASSPORT => 'Passport',
            self::DOC_TYPE_DRIVERS_LICENSE => 'Driver\'s License',
            self::DOC_TYPE_NATIONAL_ID => 'National ID',
            self::DOC_TYPE_RESIDENCE_PERMIT => 'Residence Permit',
            default => ucfirst(str_replace('_', ' ', $this->document_type)),
        };
    }

    /**
     * Get status display name.
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_REVIEW => 'In Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_IN_REVIEW => 'blue',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Store verification result from provider.
     */
    public function storeVerificationResult(array $result): void
    {
        $this->update([
            'verification_result' => $result,
            'confidence_score' => $result['confidence_score'] ?? null,
        ]);
    }

    /**
     * Add metadata to the verification.
     */
    public function addMetadata(string $key, mixed $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;

        $this->update(['metadata' => $metadata]);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter pending verifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to filter verifications in review.
     */
    public function scopeInReview($query)
    {
        return $query->where('status', self::STATUS_IN_REVIEW);
    }

    /**
     * Scope to filter approved verifications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to filter rejected verifications.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope to filter verifications requiring review (pending or in_review).
     */
    public function scopeRequiringReview($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_REVIEW]);
    }

    /**
     * Scope to filter expiring verifications.
     */
    public function scopeExpiringSoon($query, ?int $days = null)
    {
        $days = $days ?? config('kyc.expiry_warning_days', 30);

        return $query->where('status', self::STATUS_APPROVED)
            ->where(function ($q) use ($days) {
                $q->where('document_expiry', '<=', Carbon::now()->addDays($days))
                    ->orWhere('expires_at', '<=', Carbon::now()->addDays($days));
            });
    }

    /**
     * Scope to filter expired verifications.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere(function ($q2) {
                    $q2->where('status', self::STATUS_APPROVED)
                        ->where(function ($q3) {
                            $q3->where('document_expiry', '<', Carbon::now())
                                ->orWhere('expires_at', '<', Carbon::now());
                        });
                });
        });
    }

    /**
     * Scope to filter by provider.
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to filter by document country.
     */
    public function scopeCountry($query, string $countryCode)
    {
        return $query->where('document_country', $countryCode);
    }
}
