<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * STAFF-REG-005: Right-to-Work Verification Model
 *
 * Manages right-to-work verification status for workers across multiple jurisdictions.
 *
 * @property int $id
 * @property int $user_id
 * @property string $jurisdiction
 * @property string $verification_type
 * @property string $status
 * @property string|null $document_combination
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int|null $verified_by
 * @property string|null $online_verification_code
 * @property string|null $online_verification_reference
 * @property \Illuminate\Support\Carbon|null $online_verified_at
 * @property bool $has_work_restrictions
 * @property array|null $work_restrictions
 * @property \Illuminate\Support\Carbon|null $work_permit_expiry
 * @property string|null $verification_notes
 * @property string|null $rejection_reason
 * @property string|null $verification_method
 * @property array|null $audit_log
 * @property int $expiry_reminder_level
 * @property \Illuminate\Support\Carbon|null $last_reminder_sent_at
 * @property \Illuminate\Support\Carbon|null $retention_expires_at
 * @property bool $is_archived
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class RightToWorkVerification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'jurisdiction',
        'verification_type',
        'status',
        'document_combination',
        'verified_at',
        'expires_at',
        'verified_by',
        'online_verification_code',
        'online_verification_reference',
        'online_verified_at',
        'has_work_restrictions',
        'work_restrictions',
        'work_permit_expiry',
        'verification_notes',
        'rejection_reason',
        'verification_method',
        'audit_log',
        'expiry_reminder_level',
        'last_reminder_sent_at',
        'retention_expires_at',
        'is_archived',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'verified_at' => 'date',
        'expires_at' => 'date',
        'online_verified_at' => 'datetime',
        'work_permit_expiry' => 'date',
        'last_reminder_sent_at' => 'datetime',
        'retention_expires_at' => 'date',
        'has_work_restrictions' => 'boolean',
        'work_restrictions' => 'array',
        'audit_log' => 'array',
        'is_archived' => 'boolean',
        'expiry_reminder_level' => 'integer',
    ];

    /**
     * Status constants for clarity.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_DOCUMENTS_SUBMITTED = 'documents_submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ADDITIONAL_DOCS_REQUIRED = 'additional_docs_required';

    /**
     * Jurisdiction constants.
     */
    public const JURISDICTION_US = 'US';
    public const JURISDICTION_UK = 'UK';
    public const JURISDICTION_EU = 'EU';
    public const JURISDICTION_AU = 'AU';
    public const JURISDICTION_UAE = 'UAE';
    public const JURISDICTION_SG = 'SG';

    /**
     * Get the user that owns this verification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who verified this.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the documents for this verification.
     */
    public function documents()
    {
        return $this->hasMany(RTWDocument::class, 'rtw_verification_id');
    }

    /**
     * Scope for active (verified, not expired) verifications.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_VERIFIED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for expired verifications.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', '!=', self::STATUS_EXPIRED);
    }

    /**
     * Scope for verifications expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', self::STATUS_VERIFIED)
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    /**
     * Scope by jurisdiction.
     */
    public function scopeJurisdiction($query, string $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    /**
     * Check if verification is active.
     */
    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_VERIFIED) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if verification is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isBetween(now(), now()->addDays($days));
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return (int) now()->diffInDays($this->expires_at, false);
    }

    /**
     * Check if worker has work restrictions.
     */
    public function hasRestrictions(): bool
    {
        return $this->has_work_restrictions && !empty($this->work_restrictions);
    }

    /**
     * Add an entry to the audit log.
     */
    public function addAuditLog(string $action, array $details = [], ?int $userId = null): void
    {
        $log = $this->audit_log ?? [];

        $log[] = [
            'action' => $action,
            'details' => $details,
            'user_id' => $userId ?? auth()->id(),
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ];

        $this->update(['audit_log' => $log]);
    }

    /**
     * Mark as verified.
     */
    public function markVerified(?int $verifierId = null, ?string $method = 'manual'): void
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_at' => now(),
            'verified_by' => $verifierId ?? auth()->id(),
            'verification_method' => $method,
        ]);

        $this->addAuditLog('verified', [
            'method' => $method,
            'verified_by' => $verifierId ?? auth()->id(),
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function markRejected(string $reason, ?int $rejectedBy = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);

        $this->addAuditLog('rejected', [
            'reason' => $reason,
            'rejected_by' => $rejectedBy ?? auth()->id(),
        ]);
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);

        $this->addAuditLog('expired', [
            'expired_at' => $this->expires_at?->toDateString(),
        ]);
    }

    /**
     * Request additional documents.
     */
    public function requestAdditionalDocuments(string $notes): void
    {
        $this->update([
            'status' => self::STATUS_ADDITIONAL_DOCS_REQUIRED,
            'verification_notes' => $notes,
        ]);

        $this->addAuditLog('additional_docs_requested', [
            'notes' => $notes,
        ]);
    }

    /**
     * Get the earliest expiry date (verification or work permit).
     */
    public function getEffectiveExpiryDateAttribute(): ?\Carbon\Carbon
    {
        $dates = array_filter([
            $this->expires_at,
            $this->work_permit_expiry,
        ]);

        if (empty($dates)) {
            return null;
        }

        return min($dates);
    }

    /**
     * Get verified document count.
     */
    public function getVerifiedDocumentCountAttribute(): int
    {
        return $this->documents()->where('status', 'verified')->count();
    }

    /**
     * Get pending document count.
     */
    public function getPendingDocumentCountAttribute(): int
    {
        return $this->documents()->where('status', 'pending')->count();
    }

    /**
     * Get jurisdiction display name.
     */
    public function getJurisdictionNameAttribute(): string
    {
        return config("rtw.jurisdictions.{$this->jurisdiction}.name", $this->jurisdiction);
    }

    /**
     * Get verification type display name.
     */
    public function getVerificationTypeNameAttribute(): string
    {
        return config("rtw.verification_types.{$this->verification_type}.name", $this->verification_type);
    }
}
