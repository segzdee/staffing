<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * STAFF-REG-005: Right-to-Work Document Model
 *
 * Stores encrypted document information for RTW verification.
 * Sensitive fields are encrypted at rest.
 *
 * @property int $id
 * @property int $rtw_verification_id
 * @property int $user_id
 * @property string $document_type
 * @property string|null $document_list
 * @property string|null $document_number_encrypted
 * @property string|null $issuing_authority_encrypted
 * @property string|null $issuing_country
 * @property \Illuminate\Support\Carbon|null $issue_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property string $file_path_encrypted
 * @property string $file_hash
 * @property string $file_mime_type
 * @property int $file_size
 * @property string|null $encryption_key_id
 * @property string $status
 * @property string|null $verification_notes
 * @property int|null $verified_by
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property string|null $extracted_data_encrypted
 * @property float|null $ocr_confidence_score
 * @property array|null $audit_log
 * @property string|null $upload_ip
 * @property string|null $upload_user_agent
 */
class RTWDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rtw_documents';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'rtw_verification_id',
        'user_id',
        'document_type',
        'document_list',
        'document_number_encrypted',
        'issuing_authority_encrypted',
        'issuing_country',
        'issue_date',
        'expiry_date',
        'file_path_encrypted',
        'file_hash',
        'file_mime_type',
        'file_size',
        'encryption_key_id',
        'status',
        'verification_notes',
        'verified_by',
        'verified_at',
        'extracted_data_encrypted',
        'ocr_confidence_score',
        'audit_log',
        'upload_ip',
        'upload_user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
        'file_size' => 'integer',
        'ocr_confidence_score' => 'decimal:2',
        'audit_log' => 'array',
    ];

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    /**
     * US I-9 Document Lists.
     */
    public const LIST_A = 'list_a';    // Identity + Work Authorization
    public const LIST_B = 'list_b';    // Identity only
    public const LIST_C = 'list_c';    // Work Authorization only

    /**
     * Document types by jurisdiction.
     */
    public const DOCUMENT_TYPES = [
        'US' => [
            // List A - Identity and Employment Authorization
            'us_passport' => ['list' => 'list_a', 'name' => 'U.S. Passport'],
            'us_passport_card' => ['list' => 'list_a', 'name' => 'U.S. Passport Card'],
            'permanent_resident_card' => ['list' => 'list_a', 'name' => 'Permanent Resident Card (Green Card)'],
            'employment_auth_doc' => ['list' => 'list_a', 'name' => 'Employment Authorization Document'],
            'foreign_passport_i94' => ['list' => 'list_a', 'name' => 'Foreign Passport with I-94'],
            // List B - Identity Only
            'drivers_license' => ['list' => 'list_b', 'name' => 'Driver\'s License'],
            'state_id' => ['list' => 'list_b', 'name' => 'State ID Card'],
            'school_id_photo' => ['list' => 'list_b', 'name' => 'School ID with Photo'],
            'voter_registration' => ['list' => 'list_b', 'name' => 'Voter Registration Card'],
            'military_id' => ['list' => 'list_b', 'name' => 'Military ID'],
            // List C - Work Authorization Only
            'social_security_card' => ['list' => 'list_c', 'name' => 'Social Security Card'],
            'birth_certificate' => ['list' => 'list_c', 'name' => 'Birth Certificate'],
            'native_american_tribal_doc' => ['list' => 'list_c', 'name' => 'Native American Tribal Document'],
        ],
        'UK' => [
            'uk_passport' => ['name' => 'UK Passport'],
            'brp' => ['name' => 'Biometric Residence Permit'],
            'share_code' => ['name' => 'Share Code'],
            'settled_status' => ['name' => 'Settled/Pre-Settled Status'],
            'eu_passport' => ['name' => 'EU/EEA Passport'],
            'national_id' => ['name' => 'EU National ID'],
            'frontier_worker_permit' => ['name' => 'Frontier Worker Permit'],
        ],
        'EU' => [
            'eu_passport' => ['name' => 'EU Passport'],
            'national_id' => ['name' => 'National ID Card'],
            'work_permit' => ['name' => 'Work Permit'],
            'residence_permit' => ['name' => 'Residence Permit'],
            'blue_card' => ['name' => 'EU Blue Card'],
        ],
        'AU' => [
            'au_passport' => ['name' => 'Australian Passport'],
            'visa_grant_notice' => ['name' => 'Visa Grant Notice'],
            'immicard' => ['name' => 'ImmiCard'],
            'foreign_passport_visa' => ['name' => 'Foreign Passport with Visa'],
        ],
        'UAE' => [
            'emirates_id' => ['name' => 'Emirates ID'],
            'work_permit' => ['name' => 'Work Permit'],
            'residence_visa' => ['name' => 'Residence Visa'],
            'passport' => ['name' => 'Passport'],
        ],
        'SG' => [
            'employment_pass' => ['name' => 'Employment Pass'],
            's_pass' => ['name' => 'S Pass'],
            'work_permit' => ['name' => 'Work Permit'],
            'nric' => ['name' => 'NRIC'],
            'fin' => ['name' => 'FIN'],
        ],
    ];

    /**
     * Get the RTW verification this document belongs to.
     */
    public function verification()
    {
        return $this->belongsTo(RightToWorkVerification::class, 'rtw_verification_id');
    }

    /**
     * Get the user who uploaded this document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who verified this document.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope for verified documents.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Scope for pending documents.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope by document list (US I-9).
     */
    public function scopeList($query, string $list)
    {
        return $query->where('document_list', $list);
    }

    // ========== Encrypted Field Accessors ==========

    /**
     * Get decrypted document number.
     */
    public function getDocumentNumberAttribute(): ?string
    {
        if (!$this->document_number_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->document_number_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted document number.
     */
    public function setDocumentNumberAttribute(?string $value): void
    {
        $this->attributes['document_number_encrypted'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Get decrypted issuing authority.
     */
    public function getIssuingAuthorityAttribute(): ?string
    {
        if (!$this->issuing_authority_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->issuing_authority_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted issuing authority.
     */
    public function setIssuingAuthorityAttribute(?string $value): void
    {
        $this->attributes['issuing_authority_encrypted'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Get decrypted file path.
     */
    public function getFilePathAttribute(): ?string
    {
        if (!$this->file_path_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->file_path_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted file path.
     */
    public function setFilePathAttribute(string $value): void
    {
        $this->attributes['file_path_encrypted'] = Crypt::encryptString($value);
    }

    /**
     * Get decrypted extracted data.
     */
    public function getExtractedDataAttribute(): ?array
    {
        if (!$this->extracted_data_encrypted) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($this->extracted_data_encrypted);
            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted extracted data.
     */
    public function setExtractedDataAttribute(?array $value): void
    {
        $this->attributes['extracted_data_encrypted'] = $value
            ? Crypt::encryptString(json_encode($value))
            : null;
    }

    // ========== Helper Methods ==========

    /**
     * Check if document is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Check if document is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isBetween(now(), now()->addDays($days));
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return (int) now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get document type display name.
     */
    public function getDocumentTypeNameAttribute(): string
    {
        $jurisdiction = $this->verification?->jurisdiction ?? 'US';

        return self::DOCUMENT_TYPES[$jurisdiction][$this->document_type]['name']
            ?? ucwords(str_replace('_', ' ', $this->document_type));
    }

    /**
     * Mark document as verified.
     */
    public function markVerified(?int $verifierId = null, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_by' => $verifierId ?? auth()->id(),
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);

        $this->addAuditLog('verified', [
            'verified_by' => $verifierId ?? auth()->id(),
        ]);
    }

    /**
     * Mark document as rejected.
     */
    public function markRejected(string $reason, ?int $rejectedBy = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'verification_notes' => $reason,
        ]);

        $this->addAuditLog('rejected', [
            'reason' => $reason,
            'rejected_by' => $rejectedBy ?? auth()->id(),
        ]);
    }

    /**
     * Add an entry to the audit log.
     */
    public function addAuditLog(string $action, array $details = []): void
    {
        $log = $this->audit_log ?? [];

        $log[] = [
            'action' => $action,
            'details' => $details,
            'user_id' => auth()->id(),
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip(),
        ];

        $this->update(['audit_log' => $log]);
    }

    /**
     * Verify file integrity using stored hash.
     */
    public function verifyFileIntegrity(string $fileContents): bool
    {
        return hash('sha256', $fileContents) === $this->file_hash;
    }

    /**
     * Get file size in human readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if this is a List A document (US I-9).
     */
    public function isListA(): bool
    {
        return $this->document_list === self::LIST_A;
    }

    /**
     * Check if this is a List B document (US I-9).
     */
    public function isListB(): bool
    {
        return $this->document_list === self::LIST_B;
    }

    /**
     * Check if this is a List C document (US I-9).
     */
    public function isListC(): bool
    {
        return $this->document_list === self::LIST_C;
    }
}
