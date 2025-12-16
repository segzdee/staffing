<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Business Verification Model
 * BIZ-REG-004: Business Verification (KYB)
 *
 * Tracks the overall verification status and extracted business data
 */
class BusinessVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_profile_id',
        'user_id',
        'jurisdiction',
        'status',
        'verification_type',

        // Extracted Business Data
        'legal_business_name',
        'trading_name',
        'registration_number',
        'tax_id',
        'business_type',
        'incorporation_state',
        'incorporation_country',
        'incorporation_date',
        'registered_address',
        'registered_city',
        'registered_state',
        'registered_postal_code',
        'registered_country',

        // Workflow
        'submitted_at',
        'review_started_at',
        'reviewer_id',
        'reviewed_at',
        'review_notes',
        'rejection_reason',
        'rejection_details',

        // Auto-Verification
        'auto_verification_results',
        'auto_verified',
        'auto_verified_at',

        // Manual Review
        'requires_manual_review',
        'manual_review_reason',
        'review_priority',

        // Expiry
        'valid_until',
        'expiry_notified',

        // Attempts
        'submission_attempts',
        'last_attempt_at',
    ];

    protected $casts = [
        'incorporation_date' => 'date',
        'submitted_at' => 'datetime',
        'review_started_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'auto_verified_at' => 'datetime',
        'valid_until' => 'date',
        'last_attempt_at' => 'datetime',
        'auto_verification_results' => 'array',
        'rejection_details' => 'array',
        'auto_verified' => 'boolean',
        'requires_manual_review' => 'boolean',
        'expiry_notified' => 'boolean',
    ];

    // ==================== STATUS CONSTANTS ====================

    const STATUS_PENDING = 'pending';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_DOCUMENTS_REQUIRED = 'documents_required';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    const TYPE_KYB = 'kyb';
    const TYPE_REVALIDATION = 'revalidation';

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the business profile.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the user who owns this verification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviewer (admin).
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get all documents for this verification.
     */
    public function documents()
    {
        return $this->hasMany(BusinessDocument::class);
    }

    /**
     * Get pending documents.
     */
    public function pendingDocuments()
    {
        return $this->documents()->where('status', 'pending');
    }

    /**
     * Get verified documents.
     */
    public function verifiedDocuments()
    {
        return $this->documents()->where('status', 'verified');
    }

    /**
     * Get rejected documents.
     */
    public function rejectedDocuments()
    {
        return $this->documents()->where('status', 'rejected');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to pending verifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to in-review verifications.
     */
    public function scopeInReview($query)
    {
        return $query->where('status', self::STATUS_IN_REVIEW);
    }

    /**
     * Scope to approved verifications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to rejected verifications.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope to verifications requiring manual review.
     */
    public function scopeRequiringManualReview($query)
    {
        return $query->where('requires_manual_review', true)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_REVIEW]);
    }

    /**
     * Scope to expiring verifications.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [now(), now()->addDays($days)]);
    }

    /**
     * Scope to expired verifications.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
              ->orWhere(function ($subQ) {
                  $subQ->whereNotNull('valid_until')
                       ->where('valid_until', '<', now());
              });
        });
    }

    /**
     * Scope by jurisdiction.
     */
    public function scopeForJurisdiction($query, string $jurisdiction)
    {
        return $query->where('jurisdiction', strtoupper($jurisdiction));
    }

    /**
     * Scope to review queue (ordered by priority).
     */
    public function scopeReviewQueue($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_REVIEW])
            ->where('requires_manual_review', true)
            ->orderByDesc('review_priority')
            ->orderBy('submitted_at');
    }

    // ==================== STATUS METHODS ====================

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

        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Check if verification is valid (approved and not expired).
     */
    public function isValid(): bool
    {
        return $this->isApproved() && !$this->isExpired();
    }

    // ==================== WORKFLOW METHODS ====================

    /**
     * Submit verification for review.
     */
    public function submit(): self
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'submitted_at' => now(),
            'submission_attempts' => $this->submission_attempts + 1,
            'last_attempt_at' => now(),
        ]);

        return $this;
    }

    /**
     * Start manual review.
     */
    public function startReview(User $reviewer): self
    {
        $this->update([
            'status' => self::STATUS_IN_REVIEW,
            'reviewer_id' => $reviewer->id,
            'review_started_at' => now(),
        ]);

        return $this;
    }

    /**
     * Approve verification.
     */
    public function approve(?string $notes = null, ?int $validityMonths = 12): self
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'valid_until' => now()->addMonths($validityMonths),
            'requires_manual_review' => false,
        ]);

        // Update business profile
        $this->businessProfile->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verification_status' => 'verified',
        ]);

        return $this;
    }

    /**
     * Reject verification.
     */
    public function reject(string $reason, ?array $details = null, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'rejection_details' => $details,
            'review_notes' => $notes,
        ]);

        // Update business profile
        $this->businessProfile->update([
            'verification_status' => 'rejected',
            'verification_notes' => $reason,
        ]);

        return $this;
    }

    /**
     * Request additional documents.
     */
    public function requestDocuments(array $requiredDocuments, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_DOCUMENTS_REQUIRED,
            'review_notes' => $notes,
            'rejection_details' => ['required_documents' => $requiredDocuments],
        ]);

        return $this;
    }

    /**
     * Mark as requiring manual review.
     */
    public function routeToManualReview(string $reason, int $priority = 0): self
    {
        $this->update([
            'requires_manual_review' => true,
            'manual_review_reason' => $reason,
            'review_priority' => $priority,
        ]);

        return $this;
    }

    /**
     * Record auto-verification result.
     */
    public function recordAutoVerification(array $results, bool $passed): self
    {
        $this->update([
            'auto_verification_results' => $results,
            'auto_verified' => $passed,
            'auto_verified_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): self
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        // Update business profile
        $this->businessProfile->update([
            'is_verified' => false,
            'verification_status' => 'expired',
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
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_IN_REVIEW => 'Under Review',
            self::STATUS_DOCUMENTS_REQUIRED => 'Additional Documents Required',
            self::STATUS_APPROVED => 'Verified',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        $colors = [
            self::STATUS_PENDING => 'yellow',
            self::STATUS_IN_REVIEW => 'blue',
            self::STATUS_DOCUMENTS_REQUIRED => 'orange',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get document completion percentage.
     */
    public function getDocumentCompletionPercentage(): int
    {
        $requirements = VerificationRequirement::getKybRequirements(
            $this->jurisdiction,
            $this->businessProfile->business_type ?? null,
            $this->businessProfile->industry ?? null
        );

        $required = $requirements->where('is_required', true)->count();

        if ($required === 0) {
            return 100;
        }

        $submitted = $this->documents()
            ->whereIn('document_type', $requirements->pluck('document_type'))
            ->whereIn('status', ['pending', 'verified'])
            ->count();

        return min(100, (int) round(($submitted / $required) * 100));
    }

    /**
     * Get missing required documents.
     */
    public function getMissingDocuments(): \Illuminate\Support\Collection
    {
        $requirements = VerificationRequirement::getKybRequirements(
            $this->jurisdiction,
            $this->businessProfile->business_type ?? null,
            $this->businessProfile->industry ?? null
        )->where('is_required', true);

        $submittedTypes = $this->documents()
            ->whereIn('status', ['pending', 'verified'])
            ->pluck('document_type')
            ->toArray();

        return $requirements->filter(function ($req) use ($submittedTypes) {
            return !in_array($req->document_type, $submittedTypes);
        });
    }

    /**
     * Check if all required documents are submitted.
     */
    public function hasAllRequiredDocuments(): bool
    {
        return $this->getMissingDocuments()->isEmpty();
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->valid_until) {
            return null;
        }

        return now()->diffInDays($this->valid_until, false);
    }
}
