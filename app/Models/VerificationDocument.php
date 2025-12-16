<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * VerificationDocument Model - STAFF-REG-004
 *
 * Manages encrypted document storage for identity verification.
 *
 * @property int $id
 * @property int $identity_verification_id
 * @property int $user_id
 * @property string $document_type
 * @property string|null $document_subtype
 * @property string|null $provider_document_id
 * @property string|null $provider_file_id
 * @property string $storage_provider
 * @property string|null $storage_path
 * @property string|null $storage_key
 * @property string|null $storage_bucket
 * @property string|null $storage_region
 * @property string|null $original_filename
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property string|null $file_hash
 * @property string $encryption_algorithm
 * @property string|null $encryption_iv
 * @property string $status
 * @property string|null $verification_result
 * @property array|null $verification_details
 * @property float|null $authenticity_score
 * @property float|null $quality_score
 * @property string|null $extracted_data
 * @property \Carbon\Carbon|null $document_issue_date
 * @property \Carbon\Carbon|null $document_expiry_date
 * @property string|null $issuing_country
 * @property string|null $issuing_authority
 * @property array|null $ocr_results
 * @property array|null $mrz_data
 * @property array|null $fraud_signals
 * @property bool|null $is_authentic
 * @property bool $is_expired
 * @property bool $is_tampered
 * @property \Carbon\Carbon|null $retention_expires_at
 * @property \Carbon\Carbon|null $deletion_requested_at
 * @property \Carbon\Carbon|null $deleted_at_provider_at
 * @property array|null $metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class VerificationDocument extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'verification_documents';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'identity_verification_id',
        'user_id',
        'document_type',
        'document_subtype',
        'provider_document_id',
        'provider_file_id',
        'storage_provider',
        'storage_path',
        'storage_key',
        'storage_bucket',
        'storage_region',
        'original_filename',
        'mime_type',
        'file_size',
        'file_hash',
        'encryption_algorithm',
        'encryption_iv',
        'status',
        'verification_result',
        'verification_details',
        'authenticity_score',
        'quality_score',
        'extracted_data',
        'document_issue_date',
        'document_expiry_date',
        'issuing_country',
        'issuing_authority',
        'ocr_results',
        'mrz_data',
        'fraud_signals',
        'is_authentic',
        'is_expired',
        'is_tampered',
        'retention_expires_at',
        'deletion_requested_at',
        'deleted_at_provider_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'file_size' => 'integer',
        'verification_details' => 'array',
        'authenticity_score' => 'decimal:4',
        'quality_score' => 'decimal:4',
        'document_issue_date' => 'date',
        'document_expiry_date' => 'date',
        'ocr_results' => 'array',
        'mrz_data' => 'array',
        'fraud_signals' => 'array',
        'is_authentic' => 'boolean',
        'is_expired' => 'boolean',
        'is_tampered' => 'boolean',
        'retention_expires_at' => 'datetime',
        'deletion_requested_at' => 'datetime',
        'deleted_at_provider_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'storage_path',
        'storage_key',
        'encryption_iv',
        'extracted_data',
    ];

    // ==================== Constants ====================

    public const TYPE_PASSPORT = 'passport';
    public const TYPE_DRIVING_LICENSE = 'driving_license';
    public const TYPE_NATIONAL_ID = 'national_id';
    public const TYPE_RESIDENCE_PERMIT = 'residence_permit';
    public const TYPE_VISA = 'visa';
    public const TYPE_TAX_ID = 'tax_id';
    public const TYPE_UTILITY_BILL = 'utility_bill';
    public const TYPE_BANK_STATEMENT = 'bank_statement';
    public const TYPE_SELFIE = 'selfie';
    public const TYPE_LIVENESS_VIDEO = 'liveness_video';
    public const TYPE_OTHER = 'other';

    public const SUBTYPE_FRONT = 'front';
    public const SUBTYPE_BACK = 'back';
    public const SUBTYPE_FULL = 'full';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DELETED = 'deleted';

    public const RESULT_CLEAR = 'clear';
    public const RESULT_CONSIDER = 'consider';
    public const RESULT_REJECTED = 'rejected';

    public const STORAGE_S3 = 's3';
    public const STORAGE_CLOUDINARY = 'cloudinary';
    public const STORAGE_LOCAL = 'local';

    // ==================== Document Type Configurations ====================

    /**
     * Get accepted document types by jurisdiction.
     */
    public static function getAcceptedTypesByJurisdiction(string $countryCode): array
    {
        $universal = [
            self::TYPE_PASSPORT,
        ];

        $countrySpecific = [
            'US' => [self::TYPE_DRIVING_LICENSE, self::TYPE_NATIONAL_ID],
            'GB' => [self::TYPE_DRIVING_LICENSE, self::TYPE_NATIONAL_ID],
            'CA' => [self::TYPE_DRIVING_LICENSE, self::TYPE_NATIONAL_ID],
            'AU' => [self::TYPE_DRIVING_LICENSE, self::TYPE_NATIONAL_ID],
            'DE' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE, self::TYPE_RESIDENCE_PERMIT],
            'FR' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE, self::TYPE_RESIDENCE_PERMIT],
            'ES' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE],
            'IT' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE],
            'NL' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE],
            'BE' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE],
            'IN' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE, self::TYPE_TAX_ID],
            'BR' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE, self::TYPE_TAX_ID],
            'MX' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE],
            'ZA' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE],
            'NG' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE],
            'KE' => [self::TYPE_NATIONAL_ID, self::TYPE_DRIVING_LICENSE],
        ];

        return array_merge($universal, $countrySpecific[$countryCode] ?? [self::TYPE_NATIONAL_ID]);
    }

    /**
     * Get human-readable document type name.
     */
    public static function getDocumentTypeName(string $type): string
    {
        $names = [
            self::TYPE_PASSPORT => 'Passport',
            self::TYPE_DRIVING_LICENSE => 'Driver\'s License',
            self::TYPE_NATIONAL_ID => 'National ID Card',
            self::TYPE_RESIDENCE_PERMIT => 'Residence Permit',
            self::TYPE_VISA => 'Visa',
            self::TYPE_TAX_ID => 'Tax ID Document',
            self::TYPE_UTILITY_BILL => 'Utility Bill',
            self::TYPE_BANK_STATEMENT => 'Bank Statement',
            self::TYPE_SELFIE => 'Selfie Photo',
            self::TYPE_LIVENESS_VIDEO => 'Liveness Video',
            self::TYPE_OTHER => 'Other Document',
        ];

        return $names[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    // ==================== Relationships ====================

    /**
     * Get the identity verification this document belongs to.
     */
    public function identityVerification(): BelongsTo
    {
        return $this->belongsTo(IdentityVerification::class, 'identity_verification_id');
    }

    /**
     * Get the user that owns the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== Accessors & Mutators ====================

    /**
     * Get decrypted extracted data.
     */
    public function getDecryptedExtractedDataAttribute(): ?array
    {
        if (!$this->extracted_data) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($this->extracted_data), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted extracted data.
     */
    public function setExtractedDataAttribute($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $this->attributes['extracted_data'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Get decrypted storage path.
     */
    public function getDecryptedStoragePathAttribute(): ?string
    {
        if (!$this->storage_path) {
            return null;
        }

        try {
            return Crypt::decryptString($this->storage_path);
        } catch (\Exception $e) {
            return $this->storage_path;
        }
    }

    /**
     * Set encrypted storage path.
     */
    public function setStoragePathAttribute(?string $value): void
    {
        $this->attributes['storage_path'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    // ==================== Status Check Methods ====================

    /**
     * Check if document is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if document is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
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
     * Check if document has expired.
     */
    public function hasExpired(): bool
    {
        if ($this->is_expired) {
            return true;
        }

        return $this->document_expiry_date && $this->document_expiry_date->isPast();
    }

    /**
     * Check if document is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->document_expiry_date) {
            return false;
        }

        return $this->document_expiry_date->isBetween(now(), now()->addDays($days));
    }

    /**
     * Check if document should be retained.
     */
    public function shouldRetain(): bool
    {
        return !$this->retention_expires_at || $this->retention_expires_at->isFuture();
    }

    // ==================== Business Logic Methods ====================

    /**
     * Mark document as processing.
     */
    public function markProcessing(): self
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
        return $this;
    }

    /**
     * Mark document as verified.
     */
    public function markVerified(array $results = []): self
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verification_result' => self::RESULT_CLEAR,
            'verification_details' => $results,
            'is_authentic' => true,
        ]);
        return $this;
    }

    /**
     * Mark document as rejected.
     */
    public function markRejected(string $reason, array $details = []): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'verification_result' => self::RESULT_REJECTED,
            'verification_details' => array_merge($details, ['rejection_reason' => $reason]),
            'is_authentic' => false,
        ]);
        return $this;
    }

    /**
     * Store verification results.
     */
    public function storeVerificationResults(array $results): self
    {
        $this->update([
            'verification_result' => $results['result'] ?? null,
            'verification_details' => $results['details'] ?? null,
            'authenticity_score' => $results['authenticity_score'] ?? null,
            'quality_score' => $results['quality_score'] ?? null,
            'is_authentic' => $results['is_authentic'] ?? null,
            'is_tampered' => $results['is_tampered'] ?? false,
            'fraud_signals' => $results['fraud_signals'] ?? null,
        ]);

        return $this;
    }

    /**
     * Store OCR results.
     */
    public function storeOcrResults(array $ocrData, array $mrzData = null): self
    {
        $this->update([
            'ocr_results' => $ocrData,
            'mrz_data' => $mrzData,
        ]);

        return $this;
    }

    /**
     * Store document metadata.
     */
    public function storeDocumentMetadata(array $metadata): self
    {
        $this->update([
            'document_issue_date' => $metadata['issue_date'] ?? null,
            'document_expiry_date' => $metadata['expiry_date'] ?? null,
            'issuing_country' => $metadata['issuing_country'] ?? null,
            'issuing_authority' => $metadata['issuing_authority'] ?? null,
        ]);

        // Check if document has expired
        if ($this->document_expiry_date && $this->document_expiry_date->isPast()) {
            $this->update(['is_expired' => true]);
        }

        return $this;
    }

    /**
     * Request document deletion (GDPR compliance).
     */
    public function requestDeletion(): self
    {
        $this->update([
            'deletion_requested_at' => now(),
            'status' => self::STATUS_DELETED,
        ]);

        return $this;
    }

    /**
     * Delete the actual file from storage.
     */
    public function deleteFromStorage(): bool
    {
        if (!$this->decrypted_storage_path) {
            return true;
        }

        try {
            $disk = match ($this->storage_provider) {
                self::STORAGE_S3 => 's3',
                self::STORAGE_CLOUDINARY => 'cloudinary',
                default => 'local',
            };

            $deleted = Storage::disk($disk)->delete($this->decrypted_storage_path);

            if ($deleted) {
                $this->update([
                    'storage_path' => null,
                    'deleted_at_provider_at' => now(),
                ]);
            }

            return $deleted;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Generate a temporary signed URL for the document.
     */
    public function getTemporaryUrl(int $minutes = 15): ?string
    {
        if (!$this->decrypted_storage_path) {
            return null;
        }

        try {
            $disk = match ($this->storage_provider) {
                self::STORAGE_S3 => 's3',
                self::STORAGE_CLOUDINARY => 'cloudinary',
                default => 'local',
            };

            return Storage::disk($disk)->temporaryUrl(
                $this->decrypted_storage_path,
                now()->addMinutes($minutes)
            );
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    // ==================== Scopes ====================

    /**
     * Scope to documents of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope to ID documents (passport, license, national ID).
     */
    public function scopeIdDocuments($query)
    {
        return $query->whereIn('document_type', [
            self::TYPE_PASSPORT,
            self::TYPE_DRIVING_LICENSE,
            self::TYPE_NATIONAL_ID,
            self::TYPE_RESIDENCE_PERMIT,
        ]);
    }

    /**
     * Scope to verified documents.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Scope to expired documents.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('is_expired', true)
              ->orWhere('document_expiry_date', '<', now());
        });
    }

    /**
     * Scope to documents pending deletion.
     */
    public function scopePendingDeletion($query)
    {
        return $query->whereNotNull('deletion_requested_at')
            ->whereNull('deleted_at_provider_at');
    }

    /**
     * Scope to documents past retention period.
     */
    public function scopePastRetention($query)
    {
        return $query->whereNotNull('retention_expires_at')
            ->where('retention_expires_at', '<', now());
    }
}
