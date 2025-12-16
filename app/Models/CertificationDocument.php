<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * STAFF-REG-007: CertificationDocument Model
 *
 * Stores encrypted certification document metadata.
 *
 * @property int $id
 * @property int $worker_certification_id
 * @property int $worker_id
 * @property string $document_type
 * @property string $original_filename
 * @property string $stored_filename
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $file_hash
 * @property string $storage_disk
 * @property string $storage_path
 * @property string|null $storage_url
 * @property bool $is_encrypted
 * @property string $encryption_algorithm
 * @property string|null $encryption_key_id
 * @property string|null $encryption_iv
 * @property bool $ocr_processed
 * @property \Illuminate\Support\Carbon|null $ocr_processed_at
 * @property array|null $ocr_results
 * @property float|null $ocr_confidence
 * @property string $status
 * @property bool $is_current
 * @property array|null $exif_data
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $document_date
 * @property int|null $uploaded_by
 * @property string|null $uploaded_from_ip
 * @property string|null $uploaded_user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class CertificationDocument extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Document type constants
     */
    public const TYPE_CERTIFICATE = 'certificate';
    public const TYPE_ID_CARD = 'id_card';
    public const TYPE_WALLET_CARD = 'wallet_card';
    public const TYPE_RENEWAL_PROOF = 'renewal_proof';
    public const TYPE_OTHER = 'other';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'worker_certification_id',
        'worker_id',
        'document_type',
        'original_filename',
        'stored_filename',
        'mime_type',
        'file_size',
        'file_hash',
        'storage_disk',
        'storage_path',
        'storage_url',
        'is_encrypted',
        'encryption_algorithm',
        'encryption_key_id',
        'encryption_iv',
        'ocr_processed',
        'ocr_processed_at',
        'ocr_results',
        'ocr_confidence',
        'status',
        'is_current',
        'exif_data',
        'metadata',
        'document_date',
        'uploaded_by',
        'uploaded_from_ip',
        'uploaded_user_agent',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_encrypted' => 'boolean',
        'ocr_processed' => 'boolean',
        'ocr_processed_at' => 'datetime',
        'ocr_results' => 'array',
        'ocr_confidence' => 'decimal:2',
        'is_current' => 'boolean',
        'exif_data' => 'array',
        'metadata' => 'array',
        'document_date' => 'datetime',
    ];

    /**
     * Hidden attributes.
     */
    protected $hidden = [
        'encryption_iv',
        'encryption_key_id',
    ];

    /**
     * Get the worker certification.
     */
    public function workerCertification()
    {
        return $this->belongsTo(WorkerCertification::class);
    }

    /**
     * Get the worker.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope: Only current documents.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope: Only active documents.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Filter by document type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope: Needs OCR processing.
     */
    public function scopeNeedsOcr($query)
    {
        return $query->where('ocr_processed', false)
            ->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);
    }

    /**
     * Get file size in human readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get full storage path.
     */
    public function getFullPathAttribute(): string
    {
        return $this->storage_path . '/' . $this->stored_filename;
    }

    /**
     * Check if document exists in storage.
     */
    public function exists(): bool
    {
        return Storage::disk($this->storage_disk)->exists($this->full_path);
    }

    /**
     * Get the document URL (generates temporary signed URL for S3).
     */
    public function getTemporaryUrl(int $expirationMinutes = 15): ?string
    {
        if (!$this->exists()) {
            return null;
        }

        if ($this->storage_disk === 'local') {
            return null; // Local files should be served through controller
        }

        try {
            return Storage::disk($this->storage_disk)
                ->temporaryUrl($this->full_path, now()->addMinutes($expirationMinutes));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mark this document as current and archive others.
     */
    public function markAsCurrent(): void
    {
        // Archive other documents for the same certification
        self::where('worker_certification_id', $this->worker_certification_id)
            ->where('id', '!=', $this->id)
            ->where('is_current', true)
            ->update(['is_current' => false, 'status' => self::STATUS_ARCHIVED]);

        // Mark this as current
        $this->update([
            'is_current' => true,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Archive this document.
     */
    public function archive(): void
    {
        $this->update([
            'is_current' => false,
            'status' => self::STATUS_ARCHIVED,
        ]);
    }

    /**
     * Soft delete document (keeps file but marks as deleted).
     */
    public function markAsDeleted(): void
    {
        $this->update(['status' => self::STATUS_DELETED]);
        $this->delete();
    }

    /**
     * Permanently delete document and file.
     */
    public function permanentlyDelete(): bool
    {
        // Delete the file from storage
        if ($this->exists()) {
            Storage::disk($this->storage_disk)->delete($this->full_path);
        }

        // Force delete the record
        return $this->forceDelete();
    }

    /**
     * Record OCR results.
     */
    public function recordOcrResults(array $results, float $confidence): void
    {
        $this->update([
            'ocr_processed' => true,
            'ocr_processed_at' => now(),
            'ocr_results' => $results,
            'ocr_confidence' => $confidence,
        ]);
    }

    /**
     * Get document type label.
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            self::TYPE_CERTIFICATE => 'Certificate',
            self::TYPE_ID_CARD => 'ID Card',
            self::TYPE_WALLET_CARD => 'Wallet Card',
            self::TYPE_RENEWAL_PROOF => 'Renewal Proof',
            self::TYPE_OTHER => 'Other Document',
            default => 'Document',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ARCHIVED => 'Archived',
            self::STATUS_DELETED => 'Deleted',
            default => 'Unknown',
        };
    }

    /**
     * Check if document is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if document is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Get allowed document types.
     */
    public static function getAllowedTypes(): array
    {
        return [
            self::TYPE_CERTIFICATE,
            self::TYPE_ID_CARD,
            self::TYPE_WALLET_CARD,
            self::TYPE_RENEWAL_PROOF,
            self::TYPE_OTHER,
        ];
    }

    /**
     * Get allowed mime types.
     */
    public static function getAllowedMimeTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ];
    }
}
