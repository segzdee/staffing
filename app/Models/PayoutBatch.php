<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payout Batch Model
 * 
 * Tracks batch processing for settlements and payouts
 * Records daily/weekly batch processing results
 * 
 * FIN-003: Payment Settlement Engine
 * FIN-005: Weekly Payout Cycle
 */
class PayoutBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'processed_count',
        'failed_count',
        'total_amount_cents',
        'currency',
        'processed_at',
        'status',
        'error_summary',
        'metadata'
    ];

    protected $casts = [
        'processed_count' => 'integer',
        'failed_count' => 'integer',
        'total_amount_cents' => 'integer',
        'processed_at' => 'datetime',
        'error_summary' => 'json',
        'metadata' => 'json'
    ];

    /**
     * Batch Type Constants
     */
    const TYPE_DAILY_SETTLEMENT = 'DAILY_SETTLEMENT';
    const TYPE_WEEKLY_PAYOUT = 'WEEKLY_PAYOUT';
    const TYPE_INSTAPAY = 'INSTAPAY';
    const TYPE_MONTHLY_RECONCILIATION = 'MONTHLY_RECONCILIATION';

    /**
     * Batch Status Constants
     */
    const STATUS_PENDING = 'PENDING';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_PARTIAL = 'PARTIAL';
    const STATUS_FAILED = 'FAILED';

    /**
     * Scope for completed batches
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed batches
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for daily settlements
     */
    public function scopeDailySettlements($query)
    {
        return $query->where('type', self::TYPE_DAILY_SETTLEMENT);
    }

    /**
     * Scope for weekly payouts
     */
    public function scopeWeeklyPayouts($query)
    {
        return $query->where('type', self::TYPE_WEEKLY_PAYOUT);
    }

    /**
     * Get success rate
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->processed_count + $this->failed_count;
        return $total > 0 ? ($this->processed_count / $total) * 100 : 0;
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->total_amount_cents / 100, 2);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_PARTIAL => 'Partial Success',
            self::STATUS_FAILED => 'Failed',
            default => 'Unknown'
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_DAILY_SETTLEMENT => 'Daily Settlement',
            self::TYPE_WEEKLY_PAYOUT => 'Weekly Payout',
            self::TYPE_INSTAPAY => 'InstaPay Batch',
            self::TYPE_MONTHLY_RECONCILIATION => 'Monthly Reconciliation',
            default => 'Unknown'
        };
    }

    /**
     * Check if batch had any failures
     */
    public function hasFailures(): bool
    {
        return $this->failed_count > 0;
    }

    /**
     * Check if batch was successful (all processed)
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED && $this->failed_count === 0;
    }

    /**
     * Get error count summary
     */
    public function getErrorCountAttribute(): int
    {
        return count($this->error_summary ?? []);
    }

    /**
     * Create daily settlement batch
     */
    public static function createDailySettlement(array $results): self
    {
        return static::create([
            'type' => self::TYPE_DAILY_SETTLEMENT,
            'processed_count' => $results['processed'],
            'failed_count' => $results['failed'],
            'total_amount_cents' => $results['total_amount_cents'],
            'currency' => 'usd',
            'processed_at' => now(),
            'status' => $results['failed'] > 0 ? self::STATUS_PARTIAL : self::STATUS_COMPLETED,
            'error_summary' => $results['errors'] ?? [],
            'metadata' => [
                'processing_time' => now()->toIso8601String(),
                'batch_id' => uniqid('daily_')
            ]
        ]);
    }

    /**
     * Create weekly payout batch
     */
    public static function createWeeklyPayout(array $results): self
    {
        return static::create([
            'type' => self::TYPE_WEEKLY_PAYOUT,
            'processed_count' => $results['workers_processed'],
            'failed_count' => $results['failed_workers'],
            'total_amount_cents' => $results['total_payout_cents'],
            'currency' => 'usd',
            'processed_at' => now(),
            'status' => $results['failed_workers'] > 0 ? self::STATUS_PARTIAL : self::STATUS_COMPLETED,
            'error_summary' => $results['errors'] ?? [],
            'metadata' => [
                'processing_time' => now()->toIso8601String(),
                'batch_id' => uniqid('weekly_')
            ]
        ]);
    }

    /**
     * Get batch statistics for date range
     */
    public static function getStatistics(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $batches = static::whereBetween('processed_at', [$startDate, $endDate])->get();

        return [
            'total_batches' => $batches->count(),
            'successful_batches' => $batches->where('status', self::STATUS_COMPLETED)->count(),
            'failed_batches' => $batches->where('status', self::STATUS_FAILED)->count(),
            'partial_batches' => $batches->where('status', self::STATUS_PARTIAL)->count(),
            'total_processed' => $batches->sum('processed_count'),
            'total_failed' => $batches->sum('failed_count'),
            'total_amount_cents' => $batches->sum('total_amount_cents'),
            'average_success_rate' => $batches->avg(function ($batch) {
                return $batch->success_rate;
            })
        ];
    }
}