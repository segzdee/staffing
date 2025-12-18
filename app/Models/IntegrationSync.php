<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BIZ-012: Integration APIs - Sync History Model
 *
 * @property int $id
 * @property int $integration_id
 * @property string $direction
 * @property string $entity_type
 * @property int $records_processed
 * @property int $records_created
 * @property int $records_updated
 * @property int $records_failed
 * @property array|null $errors
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Integration $integration
 */
class IntegrationSync extends Model
{
    use HasFactory;

    // Sync directions
    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    // Entity types
    public const ENTITY_SHIFTS = 'shifts';

    public const ENTITY_WORKERS = 'workers';

    public const ENTITY_TIMESHEETS = 'timesheets';

    public const ENTITY_PAYROLL = 'payroll';

    public const ENTITY_INVOICES = 'invoices';

    // Sync statuses
    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'integration_id',
        'direction',
        'entity_type',
        'records_processed',
        'records_created',
        'records_updated',
        'records_failed',
        'errors',
        'status',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'errors' => 'array',
        'records_processed' => 'integer',
        'records_created' => 'integer',
        'records_updated' => 'integer',
        'records_failed' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the integration that owns this sync.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Scope for syncs by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending syncs.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for running syncs.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope for completed syncs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed syncs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for inbound syncs.
     */
    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    /**
     * Scope for outbound syncs.
     */
    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    /**
     * Check if sync is in progress.
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if sync has completed successfully.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if sync has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if sync has any errors.
     */
    public function hasErrors(): bool
    {
        return $this->records_failed > 0 || ! empty($this->errors);
    }

    /**
     * Start the sync process.
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Complete the sync process.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        // Update the parent integration's last sync time
        $this->integration->resetSyncErrors();
    }

    /**
     * Mark the sync as failed.
     */
    public function fail(array $errors = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'errors' => array_merge($this->errors ?? [], $errors),
        ]);

        // Record error on parent integration
        $this->integration->recordSyncError();
    }

    /**
     * Increment processed records.
     */
    public function incrementProcessed(int $count = 1): void
    {
        $this->increment('records_processed', $count);
    }

    /**
     * Increment created records.
     */
    public function incrementCreated(int $count = 1): void
    {
        $this->increment('records_created', $count);
    }

    /**
     * Increment updated records.
     */
    public function incrementUpdated(int $count = 1): void
    {
        $this->increment('records_updated', $count);
    }

    /**
     * Increment failed records with error.
     */
    public function incrementFailed(?string $error = null): void
    {
        $this->increment('records_failed');

        if ($error) {
            $errors = $this->errors ?? [];
            $errors[] = [
                'message' => $error,
                'timestamp' => now()->toIso8601String(),
            ];
            $this->update(['errors' => $errors]);
        }
    }

    /**
     * Get duration of the sync in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get success rate as percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->records_processed === 0) {
            return 0;
        }

        return round((($this->records_processed - $this->records_failed) / $this->records_processed) * 100, 2);
    }

    /**
     * Get human-readable status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_RUNNING => 'Running',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            default => 'Unknown',
        };
    }
}
