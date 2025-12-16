<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Business Document Model
 * BIZ-REG-004: Business Verification (KYB)
 *
 * Stores encrypted document references and extracted data
 */
class BusinessDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_verification_id',
        'business_profile_id',
        'requirement_id',
        'document_type',
        'document_name',
        'file_path_encrypted',
        'file_hash',
        'original_filename',
        'mime_type',
        'file_size',
        'storage_provider',
        'status',
        'extracted_data',
        'ocr_confidence',
        'extracted_at',
        'data_validated',
        'validation_results',
        'validated_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'rejection_reason',
        'document_date',
        'expiry_date',
        'expiry_notified',
        'access_token',
        'access_token_expires_at',
        'download_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'validation_results' => 'array',
        'ocr_confidence' => 'float',
        'data_validated' => 'boolean',
        'expiry_notified' => 'boolean',
        'extracted_at' => 'datetime',
        'validated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'document_date' => 'date',
        'expiry_date' => 'date',
        'access_token_expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    protected $hidden = [
        'file_path_encrypted',
        'access_token',
    ];

    // ==================== STATUS CONSTANTS ====================

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    // ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            // Generate unique access token
            if (empty($document->access_token)) {
                $document->access_token = Str::random(64);
                $document->access_token_expires_at = now()->addHours(24);
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the business verification.
     */
    public function businessVerification()
    {
        return $this->belongsTo(BusinessVerification::class);
    }

    /**
     * Get the business profile.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the requirement this document fulfills.
     */
    public function requirement()
    {
        return $this->belongsTo(VerificationRequirement::class, 'requirement_id');
    }

    /**
     * Get the reviewer.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get access logs.
     */
    public function accessLogs()
    {
        return $this->hasMany(BusinessDocumentAccessLog::class);
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
     * Scope to expiring documents.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope to expired documents.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
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
            \Log::error('Failed to decrypt file path for document: ' . $this->id);
            return null;
        }
    }

    // ==================== ACCESS TOKEN METHODS ====================

    /**
     * Generate a new access token.
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
        // Regenerate token if expired or expiring soon
        if (!$this->access_token_expires_at || $this->access_token_expires_at->isPast()) {
            $this->regenerateAccessToken(1); // 1 hour for download URL
        }

        return route('api.business.documents.download', [
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
     * Record document access.
     */
    public function recordAccess(string $action, ?User $user = null, ?array $metadata = null): void
    {
        BusinessDocumentAccessLog::create([
            'business_document_id' => $this->id,
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
     * Check if document is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
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

        return $this->expiry_date && $this->expiry_date->isPast();
    }

    // ==================== WORKFLOW METHODS ====================

    /**
     * Mark as processing (OCR in progress).
     */
    public function markProcessing(): self
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
        return $this;
    }

    /**
     * Record extracted data from OCR.
     */
    public function recordExtractedData(array $data, float $confidence): self
    {
        $this->update([
            'extracted_data' => $data,
            'ocr_confidence' => $confidence,
            'extracted_at' => now(),
            'status' => self::STATUS_PENDING, // Back to pending for validation
        ]);

        return $this;
    }

    /**
     * Record validation results.
     */
    public function recordValidation(array $results, bool $passed): self
    {
        $this->update([
            'validation_results' => $results,
            'data_validated' => $passed,
            'validated_at' => now(),
        ]);

        return $this;
    }

    /**
     * Verify document.
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

        return $this;
    }

    /**
     * Reject document.
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
            self::STATUS_PROCESSING => 'Processing',
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
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_VERIFIED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
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
     * Get OCR confidence label.
     */
    public function getConfidenceLabel(): string
    {
        if ($this->ocr_confidence === null) {
            return 'Not processed';
        }

        if ($this->ocr_confidence >= 90) {
            return 'High';
        } elseif ($this->ocr_confidence >= 70) {
            return 'Medium';
        } else {
            return 'Low';
        }
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
     * Calculate file hash.
     */
    public static function calculateFileHash(string $contents): string
    {
        return hash('sha256', $contents);
    }
}
