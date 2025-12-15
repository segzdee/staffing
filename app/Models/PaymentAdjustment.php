<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PaymentAdjustment Model
 *
 * Tracks financial adjustments from dispute resolutions.
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 *
 * @property int $id
 * @property int|null $dispute_id
 * @property int|null $shift_payment_id
 * @property string $adjustment_type
 * @property numeric $amount
 * @property string $currency
 * @property string $reason
 * @property string|null $applied_to
 * @property int|null $worker_id
 * @property int|null $business_id
 * @property int|null $created_by_admin_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $applied_at
 * @property \Illuminate\Support\Carbon|null $reversed_at
 * @property string|null $reversal_reason
 * @property string|null $stripe_transfer_id
 * @property string|null $stripe_refund_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PaymentAdjustment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_adjustments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dispute_id',
        'shift_payment_id',
        'adjustment_type',
        'amount',
        'currency',
        'reason',
        'applied_to',
        'worker_id',
        'business_id',
        'created_by_admin_id',
        'status',
        'applied_at',
        'reversed_at',
        'reversal_reason',
        'stripe_transfer_id',
        'stripe_refund_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'applied_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    /**
     * Adjustment types.
     */
    public const TYPE_WORKER_PAYOUT = 'worker_payout';
    public const TYPE_BUSINESS_REFUND = 'business_refund';
    public const TYPE_SPLIT_RESOLUTION = 'split_resolution';
    public const TYPE_NO_ADJUSTMENT = 'no_adjustment';
    public const TYPE_BONUS = 'bonus';
    public const TYPE_PENALTY = 'penalty';
    public const TYPE_OTHER = 'other';

    /**
     * Statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_FAILED = 'failed';

    /**
     * Get the dispute this adjustment relates to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dispute()
    {
        return $this->belongsTo(AdminDisputeQueue::class, 'dispute_id');
    }

    /**
     * Get the shift payment this adjustment relates to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shiftPayment()
    {
        return $this->belongsTo(ShiftPayment::class);
    }

    /**
     * Get the worker this adjustment is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the business this adjustment is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the admin who created this adjustment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdByAdmin()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    /**
     * Check if adjustment is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if adjustment has been applied.
     *
     * @return bool
     */
    public function isApplied(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    /**
     * Check if adjustment has been reversed.
     *
     * @return bool
     */
    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    /**
     * Apply this adjustment.
     *
     * @return bool
     */
    public function apply(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_APPLIED,
            'applied_at' => now(),
        ]);
    }

    /**
     * Reverse this adjustment.
     *
     * @param string $reason
     * @return bool
     */
    public function reverse(string $reason): bool
    {
        if (!$this->isApplied()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_REVERSED,
            'reversed_at' => now(),
            'reversal_reason' => $reason,
        ]);
    }

    /**
     * Scope: Pending adjustments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Applied adjustments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApplied($query)
    {
        return $query->where('status', self::STATUS_APPLIED);
    }

    /**
     * Scope: For a specific worker.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $workerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForWorker($query, int $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    /**
     * Scope: For a specific business.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $businessId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Get human-readable adjustment type.
     *
     * @return string
     */
    public function getTypeLabel(): string
    {
        return match ($this->adjustment_type) {
            self::TYPE_WORKER_PAYOUT => 'Worker Payout',
            self::TYPE_BUSINESS_REFUND => 'Business Refund',
            self::TYPE_SPLIT_RESOLUTION => 'Split Resolution',
            self::TYPE_NO_ADJUSTMENT => 'No Adjustment',
            self::TYPE_BONUS => 'Bonus',
            self::TYPE_PENALTY => 'Penalty',
            self::TYPE_OTHER => 'Other',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted amount.
     *
     * @return string
     */
    public function getFormattedAmount(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'EUR' => "\u{20AC}",
            'GBP' => "\u{00A3}",
            default => $this->currency . ' ',
        };

        return $symbol . number_format($this->amount, 2);
    }
}
