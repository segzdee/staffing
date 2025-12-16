<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * IdentityVerification Model - STAFF-REG-004
 *
 * Manages KYC/identity verification records for workers.
 * Integrates with Onfido/Jumio for document verification.
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string|null $provider_applicant_id
 * @property string|null $provider_check_id
 * @property string|null $provider_report_id
 * @property string $status
 * @property string $verification_level
 * @property array|null $document_types
 * @property string|null $result
 * @property array|null $result_details
 * @property float|null $confidence_score
 * @property array|null $sub_results
 * @property string|null $extracted_first_name
 * @property string|null $extracted_last_name
 * @property string|null $extracted_date_of_birth
 * @property string|null $extracted_document_number
 * @property \Carbon\Carbon|null $extracted_expiry_date
 * @property string|null $extracted_nationality
 * @property string|null $extracted_gender
 * @property string|null $extracted_address
 * @property bool $face_match_performed
 * @property float|null $face_match_score
 * @property string|null $face_match_result
 * @property string|null $jurisdiction_country
 * @property array|null $compliance_flags
 * @property array|null $aml_check_results
 * @property int|null $reviewed_by
 * @property \Carbon\Carbon|null $reviewed_at
 * @property string|null $review_notes
 * @property string|null $rejection_reason
 * @property array|null $rejection_details
 * @property int $attempt_count
 * @property int $max_attempts
 * @property \Carbon\Carbon|null $last_attempt_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $reminder_sent_at
 * @property string|null $sdk_token
 * @property \Carbon\Carbon|null $sdk_token_expires_at
 * @property string|null $session_url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $device_info
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class IdentityVerification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'identity_verifications';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_applicant_id',
        'provider_check_id',
        'provider_report_id',
        'status',
        'verification_level',
        'document_types',
        'result',
        'result_details',
        'confidence_score',
        'sub_results',
        'extracted_first_name',
        'extracted_last_name',
        'extracted_date_of_birth',
        'extracted_document_number',
        'extracted_expiry_date',
        'extracted_nationality',
        'extracted_gender',
        'extracted_address',
        'face_match_performed',
        'face_match_score',
        'face_match_result',
        'jurisdiction_country',
        'compliance_flags',
        'aml_check_results',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'rejection_reason',
        'rejection_details',
        'attempt_count',
        'max_attempts',
        'last_attempt_at',
        'expires_at',
        'reminder_sent_at',
        'sdk_token',
        'sdk_token_expires_at',
        'session_url',
        'ip_address',
        'user_agent',
        'device_info',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'document_types' => 'array',
        'result_details' => 'array',
        'confidence_score' => 'decimal:4',
        'sub_results' => 'array',
        'extracted_expiry_date' => 'date',
        'face_match_performed' => 'boolean',
        'face_match_score' => 'decimal:4',
        'compliance_flags' => 'array',
        'aml_check_results' => 'array',
        'reviewed_at' => 'datetime',
        'rejection_details' => 'array',
        'attempt_count' => 'integer',
        'max_attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'expires_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'sdk_token_expires_at' => 'datetime',
        'device_info' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'sdk_token',
        'extracted_document_number',
        'extracted_address',
    ];

    // ==================== Status Constants ====================

    public const STATUS_PENDING = 'pending';
    public const STATUS_AWAITING_INPUT = 'awaiting_input';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_MANUAL_REVIEW = 'manual_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const LEVEL_BASIC = 'basic';
    public const LEVEL_STANDARD = 'standard';
    public const LEVEL_ENHANCED = 'enhanced';

    public const PROVIDER_ONFIDO = 'onfido';
    public const PROVIDER_JUMIO = 'jumio';

    public const RESULT_CLEAR = 'clear';
    public const RESULT_CONSIDER = 'consider';
    public const RESULT_REJECTED = 'rejected';

    // ==================== Relationships ====================

    /**
     * Get the user that owns the verification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the verification.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the verification documents.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VerificationDocument::class, 'identity_verification_id');
    }

    /**
     * Get the liveness checks.
     */
    public function livenessChecks(): HasMany
    {
        return $this->hasMany(LivenessCheck::class, 'identity_verification_id');
    }

    /**
     * Get the latest liveness check.
     */
    public function latestLivenessCheck(): HasOne
    {
        return $this->hasOne(LivenessCheck::class, 'identity_verification_id')
            ->latestOfMany();
    }

    // ==================== Accessors & Mutators ====================

    /**
     * Get decrypted first name.
     */
    public function getDecryptedFirstNameAttribute(): ?string
    {
        if (!$this->extracted_first_name) {
            return null;
        }

        try {
            return Crypt::decryptString($this->extracted_first_name);
        } catch (\Exception $e) {
            return $this->extracted_first_name;
        }
    }

    /**
     * Get decrypted last name.
     */
    public function getDecryptedLastNameAttribute(): ?string
    {
        if (!$this->extracted_last_name) {
            return null;
        }

        try {
            return Crypt::decryptString($this->extracted_last_name);
        } catch (\Exception $e) {
            return $this->extracted_last_name;
        }
    }

    /**
     * Get decrypted date of birth.
     */
    public function getDecryptedDateOfBirthAttribute(): ?string
    {
        if (!$this->extracted_date_of_birth) {
            return null;
        }

        try {
            return Crypt::decryptString($this->extracted_date_of_birth);
        } catch (\Exception $e) {
            return $this->extracted_date_of_birth;
        }
    }

    /**
     * Set encrypted first name.
     */
    public function setExtractedFirstNameAttribute(?string $value): void
    {
        $this->attributes['extracted_first_name'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Set encrypted last name.
     */
    public function setExtractedLastNameAttribute(?string $value): void
    {
        $this->attributes['extracted_last_name'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Set encrypted date of birth.
     */
    public function setExtractedDateOfBirthAttribute(?string $value): void
    {
        $this->attributes['extracted_date_of_birth'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Set encrypted document number.
     */
    public function setExtractedDocumentNumberAttribute(?string $value): void
    {
        $this->attributes['extracted_document_number'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Set encrypted address.
     */
    public function setExtractedAddressAttribute(?string $value): void
    {
        $this->attributes['extracted_address'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    // ==================== Status Check Methods ====================

    /**
     * Check if verification is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_AWAITING_INPUT,
            self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Check if verification requires manual review.
     */
    public function requiresManualReview(): bool
    {
        return $this->status === self::STATUS_MANUAL_REVIEW;
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
     * Check if verification has expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if verification can be retried.
     */
    public function canRetry(): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        return $this->attempt_count < $this->max_attempts;
    }

    /**
     * Check if SDK token is valid.
     */
    public function haValidSdkToken(): bool
    {
        return $this->sdk_token
            && $this->sdk_token_expires_at
            && $this->sdk_token_expires_at->isFuture();
    }

    // ==================== Business Logic Methods ====================

    /**
     * Mark verification as awaiting user input.
     */
    public function markAwaitingInput(): self
    {
        $this->update(['status' => self::STATUS_AWAITING_INPUT]);
        return $this;
    }

    /**
     * Mark verification as processing.
     */
    public function markProcessing(): self
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'last_attempt_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark for manual review.
     */
    public function markForManualReview(string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_MANUAL_REVIEW,
            'review_notes' => $reason,
        ]);
        return $this;
    }

    /**
     * Approve the verification.
     */
    public function approve(int $reviewerId = null, string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'expires_at' => now()->addYears(2), // Verification valid for 2 years
        ]);

        // Update worker profile
        $this->syncToWorkerProfile();

        return $this;
    }

    /**
     * Reject the verification.
     */
    public function reject(string $reason, array $details = [], int $reviewerId = null): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'rejection_details' => $details,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Cancel the verification.
     */
    public function cancel(): self
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
        return $this;
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): self
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
        return $this;
    }

    /**
     * Increment attempt count.
     */
    public function incrementAttempt(): self
    {
        $this->increment('attempt_count');
        $this->update(['last_attempt_at' => now()]);
        return $this;
    }

    /**
     * Store SDK token.
     */
    public function storeSdkToken(string $token, int $validityMinutes = 90): self
    {
        $this->update([
            'sdk_token' => $token,
            'sdk_token_expires_at' => now()->addMinutes($validityMinutes),
        ]);
        return $this;
    }

    /**
     * Store verification results from provider.
     */
    public function storeResults(array $results): self
    {
        $this->update([
            'result' => $results['result'] ?? null,
            'result_details' => $results['details'] ?? null,
            'confidence_score' => $results['confidence_score'] ?? null,
            'sub_results' => $results['sub_results'] ?? null,
            'compliance_flags' => $results['compliance_flags'] ?? null,
            'aml_check_results' => $results['aml_results'] ?? null,
        ]);

        return $this;
    }

    /**
     * Store extracted data from documents.
     */
    public function storeExtractedData(array $data): self
    {
        $this->update([
            'extracted_first_name' => $data['first_name'] ?? null,
            'extracted_last_name' => $data['last_name'] ?? null,
            'extracted_date_of_birth' => $data['date_of_birth'] ?? null,
            'extracted_document_number' => $data['document_number'] ?? null,
            'extracted_expiry_date' => $data['expiry_date'] ?? null,
            'extracted_nationality' => $data['nationality'] ?? null,
            'extracted_gender' => $data['gender'] ?? null,
            'extracted_address' => $data['address'] ?? null,
        ]);

        return $this;
    }

    /**
     * Store face match results.
     */
    public function storeFaceMatchResults(string $result, float $score = null): self
    {
        $this->update([
            'face_match_performed' => true,
            'face_match_result' => $result,
            'face_match_score' => $score,
        ]);

        return $this;
    }

    /**
     * Sync verified data to worker profile.
     */
    public function syncToWorkerProfile(): void
    {
        $profile = $this->user->workerProfile;

        if (!$profile) {
            return;
        }

        $profile->update([
            'identity_verified' => true,
            'identity_verified_at' => now(),
            'identity_verification_method' => $this->provider,
            'kyc_status' => 'approved',
            'kyc_level' => $this->verification_level,
            'kyc_expires_at' => $this->expires_at,
            'kyc_verification_id' => $this->id,
            'verified_first_name' => $this->decrypted_first_name,
            'verified_last_name' => $this->decrypted_last_name,
            'verified_date_of_birth' => $this->decrypted_date_of_birth,
            'verified_nationality' => $this->extracted_nationality,
        ]);
    }

    // ==================== Scopes ====================

    /**
     * Scope to pending verifications.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_AWAITING_INPUT,
            self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Scope to verifications requiring manual review.
     */
    public function scopeManualReview($query)
    {
        return $query->where('status', self::STATUS_MANUAL_REVIEW);
    }

    /**
     * Scope to approved verifications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to expired verifications.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
              ->orWhere(function ($q2) {
                  $q2->where('status', self::STATUS_APPROVED)
                     ->where('expires_at', '<', now());
              });
        });
    }

    /**
     * Scope to expiring soon (within 30 days).
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    /**
     * Scope to a specific provider.
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // ==================== Static Factory Methods ====================

    /**
     * Create a new verification for a user.
     */
    public static function createForUser(
        User $user,
        string $level = self::LEVEL_STANDARD,
        string $provider = self::PROVIDER_ONFIDO
    ): self {
        return self::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'verification_level' => $level,
            'status' => self::STATUS_PENDING,
            'attempt_count' => 1,
            'max_attempts' => 3,
        ]);
    }
}
