<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Agency Document Model
 * AGY-REG: Agency Registration & Onboarding System
 *
 * Tracks documents uploaded during the agency registration process including:
 * - Business registration certificates
 * - Tax identification documents
 * - Insurance certificates
 * - Professional licenses
 * - Proof of address
 *
 * @property int $id
 * @property int $agency_application_id
 * @property string $document_type
 * @property string $name
 * @property string|null $description
 * @property string $file_path
 * @property string|null $file_name
 * @property string|null $file_type
 * @property int|null $file_size
 * @property string $status
 * @property int|null $verified_by
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property string|null $verification_notes
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property bool $expiry_notified
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\AgencyApplication $application
 * @property-read \App\Models\User|null $verifiedBy
 */
class AgencyDocument extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'agency_application_id',
        'document_type',
        'name',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'status',
        'verified_by',
        'verified_at',
        'verification_notes',
        'rejection_reason',
        'expires_at',
        'expiry_notified',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'expiry_notified' => 'boolean',
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    // ==================== DOCUMENT TYPE CONSTANTS ====================

    /**
     * Business registration certificate
     */
    const TYPE_BUSINESS_REGISTRATION = 'business_registration';

    /**
     * Tax identification document (EIN, VAT, etc.)
     */
    const TYPE_TAX_ID = 'tax_id';

    /**
     * Professional staffing license
     */
    const TYPE_STAFFING_LICENSE = 'staffing_license';

    /**
     * General liability insurance certificate
     */
    const TYPE_INSURANCE_LIABILITY = 'insurance_liability';

    /**
     * Workers compensation insurance
     */
    const TYPE_INSURANCE_WORKERS_COMP = 'insurance_workers_comp';

    /**
     * Professional indemnity insurance
     */
    const TYPE_INSURANCE_INDEMNITY = 'insurance_indemnity';

    /**
     * Proof of business address
     */
    const TYPE_PROOF_OF_ADDRESS = 'proof_of_address';

    /**
     * Bank account verification letter
     */
    const TYPE_BANK_VERIFICATION = 'bank_verification';

    /**
     * Director/owner identification
     */
    const TYPE_DIRECTOR_ID = 'director_id';

    /**
     * Other supporting document
     */
    const TYPE_OTHER = 'other';

    /**
     * All valid document types
     */
    const DOCUMENT_TYPES = [
        self::TYPE_BUSINESS_REGISTRATION,
        self::TYPE_TAX_ID,
        self::TYPE_STAFFING_LICENSE,
        self::TYPE_INSURANCE_LIABILITY,
        self::TYPE_INSURANCE_WORKERS_COMP,
        self::TYPE_INSURANCE_INDEMNITY,
        self::TYPE_PROOF_OF_ADDRESS,
        self::TYPE_BANK_VERIFICATION,
        self::TYPE_DIRECTOR_ID,
        self::TYPE_OTHER,
    ];

    // ==================== STATUS CONSTANTS ====================

    /**
     * Document pending review
     */
    const STATUS_PENDING = 'pending';

    /**
     * Document under review
     */
    const STATUS_UNDER_REVIEW = 'under_review';

    /**
     * Document verified and approved
     */
    const STATUS_VERIFIED = 'verified';

    /**
     * Document rejected
     */
    const STATUS_REJECTED = 'rejected';

    /**
     * Document expired
     */
    const STATUS_EXPIRED = 'expired';

    /**
     * All valid statuses
     */
    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_VERIFIED,
        self::STATUS_REJECTED,
        self::STATUS_EXPIRED,
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the agency application this document belongs to.
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
     * Get the user who verified this document.
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to pending documents.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to documents under review.
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    /**
     * Scope to verified documents.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Scope to rejected documents.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope to expired documents.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
            ->orWhere(function ($q) {
                $q->whereNotNull('expires_at')
                  ->where('expires_at', '<', now());
            });
    }

    /**
     * Scope to documents expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_VERIFIED)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    /**
     * Scope by document type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    // ==================== STATUS HELPER METHODS ====================

    /**
     * Check if document is pending review.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if document is under review.
     */
    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    /**
     * Check if document is verified.
     */
    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    /**
     * Check if document is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if document is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if document is valid (verified and not expired).
     */
    public function isValid(): bool
    {
        return $this->isVerified() && !$this->isExpired();
    }

    // ==================== TRANSITION METHODS ====================

    /**
     * Start review of the document.
     */
    public function startReview(): self
    {
        $this->update([
            'status' => self::STATUS_UNDER_REVIEW,
        ]);

        return $this;
    }

    /**
     * Verify the document.
     */
    public function verify(User $verifier, ?string $notes = null, ?\DateTimeInterface $expiresAt = null): self
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'verification_notes' => $notes,
            'expires_at' => $expiresAt,
            'rejection_reason' => null,
        ]);

        return $this;
    }

    /**
     * Reject the document.
     */
    public function reject(User $verifier, string $reason, ?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'rejection_reason' => $reason,
            'verification_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Mark document as expired.
     */
    public function markExpired(): self
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        return $this;
    }

    /**
     * Reset document to pending (for re-upload).
     */
    public function resetToPending(): self
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'verified_by' => null,
            'verified_at' => null,
            'verification_notes' => null,
            'rejection_reason' => null,
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
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_REJECTED => 'Rejected',
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
            self::STATUS_PENDING => 'yellow',
            self::STATUS_UNDER_REVIEW => 'blue',
            self::STATUS_VERIFIED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get document type label for display.
     */
    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_BUSINESS_REGISTRATION => 'Business Registration',
            self::TYPE_TAX_ID => 'Tax ID Document',
            self::TYPE_STAFFING_LICENSE => 'Staffing License',
            self::TYPE_INSURANCE_LIABILITY => 'Liability Insurance',
            self::TYPE_INSURANCE_WORKERS_COMP => 'Workers Compensation Insurance',
            self::TYPE_INSURANCE_INDEMNITY => 'Professional Indemnity Insurance',
            self::TYPE_PROOF_OF_ADDRESS => 'Proof of Address',
            self::TYPE_BANK_VERIFICATION => 'Bank Verification',
            self::TYPE_DIRECTOR_ID => 'Director Identification',
            self::TYPE_OTHER => 'Other Document',
        ];

        return $labels[$this->document_type] ?? ucfirst(str_replace('_', ' ', $this->document_type));
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeForHumans(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Check if document requires expiry notification.
     */
    public function requiresExpiryNotification(int $daysBeforeExpiry = 30): bool
    {
        if ($this->expiry_notified) {
            return false;
        }

        if (!$this->expires_at) {
            return false;
        }

        $daysUntilExpiry = $this->getDaysUntilExpiry();

        return $daysUntilExpiry !== null && $daysUntilExpiry <= $daysBeforeExpiry && $daysUntilExpiry > 0;
    }

    /**
     * Mark expiry notification as sent.
     */
    public function markExpiryNotified(): self
    {
        $this->update(['expiry_notified' => true]);

        return $this;
    }

    /**
     * Get all document types with labels.
     */
    public static function getDocumentTypeOptions(): array
    {
        return [
            self::TYPE_BUSINESS_REGISTRATION => 'Business Registration',
            self::TYPE_TAX_ID => 'Tax ID Document',
            self::TYPE_STAFFING_LICENSE => 'Staffing License',
            self::TYPE_INSURANCE_LIABILITY => 'Liability Insurance',
            self::TYPE_INSURANCE_WORKERS_COMP => 'Workers Compensation Insurance',
            self::TYPE_INSURANCE_INDEMNITY => 'Professional Indemnity Insurance',
            self::TYPE_PROOF_OF_ADDRESS => 'Proof of Address',
            self::TYPE_BANK_VERIFICATION => 'Bank Verification',
            self::TYPE_DIRECTOR_ID => 'Director Identification',
            self::TYPE_OTHER => 'Other Document',
        ];
    }
}
