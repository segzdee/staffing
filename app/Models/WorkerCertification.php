<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * STAFF-REG-007: Enhanced WorkerCertification Model
 *
 * Worker's certification records with verification and expiry tracking.
 *
 * @property int $id
 * @property int $worker_id
 * @property int|null $certification_id
 * @property int|null $certification_type_id
 * @property string|null $certification_number
 * @property \Illuminate\Support\Carbon|null $issue_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string|null $issuing_authority
 * @property string|null $issuing_state
 * @property string|null $issuing_country
 * @property string|null $certificate_file
 * @property string $verification_status
 * @property string|null $verification_method
 * @property \Illuminate\Support\Carbon|null $verification_attempted_at
 * @property array|null $verification_response
 * @property string|null $document_url
 * @property bool $verified
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property int|null $verified_by
 * @property string|null $verification_notes
 * @property bool $expiry_reminder_sent
 * @property int $expiry_reminders_sent
 * @property \Illuminate\Support\Carbon|null $last_reminder_sent_at
 * @property bool $renewal_in_progress
 * @property int|null $renewal_of_certification_id
 * @property bool $is_primary
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class WorkerCertification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Verification status constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    /**
     * Verification method constants
     */
    public const METHOD_MANUAL = 'manual';

    public const METHOD_API = 'api';

    public const METHOD_OCR = 'ocr';

    public const METHOD_ISSUER_LOOKUP = 'issuer_lookup';

    protected $fillable = [
        'worker_id',
        'certification_id',
        'certification_type_id',
        'safety_certification_id',
        'certification_number',
        'issue_date',
        'expiry_date',
        'issuing_authority',
        'issuing_state',
        'issuing_country',
        'certificate_file',
        'verification_status',
        'verification_method',
        'verification_attempted_at',
        'verification_response',
        'document_url',
        'verified',
        'verified_at',
        'verified_by',
        'verification_notes',
        'rejection_reason',
        'expiry_reminder_sent',
        'expiry_reminders_sent',
        'last_reminder_sent_at',
        'renewal_in_progress',
        'renewal_of_certification_id',
        'extracted_cert_number',
        'extracted_name',
        'extracted_issue_date',
        'extracted_expiry_date',
        'ocr_confidence_score',
        'document_storage_path',
        'document_encryption_key_id',
        'document_encrypted',
        'is_primary',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'verification_attempted_at' => 'datetime',
        'verification_response' => 'array',
        'expiry_reminder_sent' => 'boolean',
        'expiry_reminders_sent' => 'integer',
        'last_reminder_sent_at' => 'datetime',
        'renewal_in_progress' => 'boolean',
        'extracted_issue_date' => 'date',
        'extracted_expiry_date' => 'date',
        'ocr_confidence_score' => 'decimal:2',
        'document_encrypted' => 'boolean',
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the worker who owns this certification.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the certification type (legacy relation).
     */
    public function certification()
    {
        return $this->belongsTo(Certification::class);
    }

    /**
     * Get the certification type (new relation).
     */
    public function certificationType()
    {
        return $this->belongsTo(CertificationType::class);
    }

    /**
     * SAF-003: Get the safety certification (new system).
     */
    public function safetyCertification()
    {
        return $this->belongsTo(SafetyCertification::class);
    }

    /**
     * Get the user who verified this certification.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the certification this renews.
     */
    public function renewalOf()
    {
        return $this->belongsTo(WorkerCertification::class, 'renewal_of_certification_id');
    }

    /**
     * Get renewal certifications.
     */
    public function renewals()
    {
        return $this->hasMany(WorkerCertification::class, 'renewal_of_certification_id');
    }

    /**
     * Get certification documents.
     */
    public function documents()
    {
        return $this->hasMany(CertificationDocument::class);
    }

    /**
     * Get the current (most recent) document.
     */
    public function currentDocument()
    {
        return $this->hasOne(CertificationDocument::class)
            ->where('is_current', true)
            ->where('status', 'active');
    }

    /**
     * Scope: Verified certifications only.
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true)
            ->where('verification_status', self::STATUS_VERIFIED);
    }

    /**
     * Scope: Pending verification.
     */
    public function scopePending($query)
    {
        return $query->where('verification_status', self::STATUS_PENDING);
    }

    /**
     * Scope: Valid (verified and not expired).
     */
    public function scopeValid($query)
    {
        return $query->verified()
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scope: Expired certifications.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    /**
     * Scope: Expiring soon (within N days).
     */
    public function scopeExpiringSoon($query, int $days = 60)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays($days));
    }

    /**
     * Scope: Primary certifications only.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Filter by certification type.
     */
    public function scopeOfType($query, int $certificationTypeId)
    {
        return $query->where('certification_type_id', $certificationTypeId);
    }

    /**
     * Check if certification is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if certification is valid (verified and not expired).
     */
    public function isValid(): bool
    {
        return $this->verified &&
               $this->verification_status === self::STATUS_VERIFIED &&
               ! $this->isExpired();
    }

    /**
     * Check if certification is expiring soon.
     */
    public function isExpiringSoon(int $days = 60): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return ! $this->isExpired() &&
               $this->expiry_date->lte(now()->addDays($days));
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return Carbon::today()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check expiry status and return details.
     */
    public function checkExpiry(): array
    {
        if (! $this->expiry_date) {
            return [
                'has_expiry' => false,
                'is_expired' => false,
                'days_until_expiry' => null,
                'status' => 'no_expiry',
            ];
        }

        $daysUntilExpiry = $this->getDaysUntilExpiry();
        $isExpired = $daysUntilExpiry < 0;

        return [
            'has_expiry' => true,
            'is_expired' => $isExpired,
            'is_expiring_soon' => ! $isExpired && $daysUntilExpiry <= 60,
            'days_until_expiry' => $isExpired ? 0 : $daysUntilExpiry,
            'expiry_date' => $this->expiry_date,
            'status' => $this->getExpiryStatus($daysUntilExpiry),
        ];
    }

    /**
     * Get expiry status label.
     */
    protected function getExpiryStatus(int $daysUntilExpiry): string
    {
        return match (true) {
            $daysUntilExpiry < 0 => 'expired',
            $daysUntilExpiry <= 7 => 'critical',
            $daysUntilExpiry <= 14 => 'urgent',
            $daysUntilExpiry <= 30 => 'warning',
            $daysUntilExpiry <= 60 => 'notice',
            default => 'valid',
        };
    }

    /**
     * Mark as verified.
     */
    public function markAsVerified(int $verifiedBy, string $method = self::METHOD_MANUAL, ?string $notes = null): void
    {
        $this->update([
            'verified' => true,
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
            'verification_status' => self::STATUS_VERIFIED,
            'verification_method' => $method,
            'verification_notes' => $notes,
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function markAsRejected(int $verifiedBy, string $reason): void
    {
        $this->update([
            'verified' => false,
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
            'verification_status' => self::STATUS_REJECTED,
            'verification_notes' => $reason,
        ]);
    }

    /**
     * Mark as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'verification_status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Record expiry reminder sent.
     */
    public function recordReminderSent(): void
    {
        $this->update([
            'expiry_reminder_sent' => true,
            'expiry_reminders_sent' => $this->expiry_reminders_sent + 1,
            'last_reminder_sent_at' => now(),
        ]);
    }

    /**
     * Start renewal process.
     */
    public function startRenewal(): void
    {
        $this->update(['renewal_in_progress' => true]);
    }

    /**
     * Get the certification name.
     */
    public function getCertificationNameAttribute(): string
    {
        if ($this->certificationType) {
            return $this->certificationType->name;
        }

        if ($this->certification) {
            return $this->certification->name;
        }

        return 'Unknown Certification';
    }

    /**
     * Get verification status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->verification_status) {
            self::STATUS_PENDING => 'Pending Verification',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Unknown',
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->verification_status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_VERIFIED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }
}
