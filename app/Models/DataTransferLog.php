<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GLO-010: Data Residency System - DataTransferLog Model
 *
 * Audit log for all cross-region data transfers for compliance purposes.
 *
 * @property int $id
 * @property int $user_id
 * @property string $from_region
 * @property string $to_region
 * @property string $transfer_type
 * @property string $status
 * @property array $data_types
 * @property string|null $legal_basis
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $error_message
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 */
class DataTransferLog extends Model
{
    use HasFactory;

    /**
     * Transfer type constants.
     */
    public const TYPE_MIGRATION = 'migration';

    public const TYPE_BACKUP = 'backup';

    public const TYPE_EXPORT = 'export';

    public const TYPE_PROCESSING = 'processing';

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * Data type constants.
     */
    public const DATA_TYPE_PROFILE = 'profile';

    public const DATA_TYPE_DOCUMENTS = 'documents';

    public const DATA_TYPE_MESSAGES = 'messages';

    public const DATA_TYPE_PAYMENTS = 'payments';

    public const DATA_TYPE_SHIFTS = 'shifts';

    public const DATA_TYPE_RATINGS = 'ratings';

    public const DATA_TYPE_ALL = 'all';

    /**
     * Legal basis constants.
     */
    public const LEGAL_BASIS_CONSENT = 'User consent';

    public const LEGAL_BASIS_CONTRACT = 'Contract performance';

    public const LEGAL_BASIS_LEGITIMATE_INTEREST = 'Legitimate interest';

    public const LEGAL_BASIS_LEGAL_OBLIGATION = 'Legal obligation';

    public const LEGAL_BASIS_ADEQUACY_DECISION = 'EU adequacy decision';

    public const LEGAL_BASIS_SCC = 'Standard contractual clauses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'from_region',
        'to_region',
        'transfer_type',
        'status',
        'data_types',
        'legal_basis',
        'completed_at',
        'error_message',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_types' => 'array',
            'metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the user associated with this transfer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Start the transfer (set status to in_progress).
     */
    public function start(): self
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);

        return $this;
    }

    /**
     * Mark the transfer as completed.
     */
    public function complete(?array $metadata = null): self
    {
        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ];

        if ($metadata) {
            $updateData['metadata'] = array_merge($this->metadata ?? [], $metadata);
        }

        $this->update($updateData);

        return $this;
    }

    /**
     * Mark the transfer as failed.
     */
    public function fail(string $errorMessage): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Check if the transfer is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the transfer is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if the transfer is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the transfer failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get the duration of the transfer in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (! $this->completed_at) {
            return null;
        }

        return $this->created_at->diffInSeconds($this->completed_at);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Only pending transfers.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Only in-progress transfers.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope: Only completed transfers.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Only failed transfers.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Filter by transfer type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('transfer_type', $type);
    }

    /**
     * Scope: Filter by from region.
     */
    public function scopeFromRegion($query, string $regionCode)
    {
        return $query->where('from_region', $regionCode);
    }

    /**
     * Scope: Filter by to region.
     */
    public function scopeToRegion($query, string $regionCode)
    {
        return $query->where('to_region', $regionCode);
    }

    /**
     * Scope: Filter transfers in a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get all transfer types as options array.
     */
    public static function getTransferTypeOptions(): array
    {
        return [
            self::TYPE_MIGRATION => 'Data Migration',
            self::TYPE_BACKUP => 'Backup',
            self::TYPE_EXPORT => 'Export',
            self::TYPE_PROCESSING => 'Processing',
        ];
    }

    /**
     * Get all status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    /**
     * Get all data type options.
     */
    public static function getDataTypeOptions(): array
    {
        return [
            self::DATA_TYPE_ALL => 'All Data',
            self::DATA_TYPE_PROFILE => 'Profile Data',
            self::DATA_TYPE_DOCUMENTS => 'Documents',
            self::DATA_TYPE_MESSAGES => 'Messages',
            self::DATA_TYPE_PAYMENTS => 'Payment Records',
            self::DATA_TYPE_SHIFTS => 'Shift Data',
            self::DATA_TYPE_RATINGS => 'Ratings & Reviews',
        ];
    }

    /**
     * Get all legal basis options.
     */
    public static function getLegalBasisOptions(): array
    {
        return [
            self::LEGAL_BASIS_CONSENT => 'User Consent',
            self::LEGAL_BASIS_CONTRACT => 'Contract Performance',
            self::LEGAL_BASIS_LEGITIMATE_INTEREST => 'Legitimate Interest',
            self::LEGAL_BASIS_LEGAL_OBLIGATION => 'Legal Obligation',
            self::LEGAL_BASIS_ADEQUACY_DECISION => 'EU Adequacy Decision',
            self::LEGAL_BASIS_SCC => 'Standard Contractual Clauses',
        ];
    }
}
