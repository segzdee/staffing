<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InstapayRequest Model
 *
 * FIN-004: InstaPay (Same-Day Payout) Feature
 *
 * Represents a worker's request for instant/same-day payout of their earnings.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $shift_assignment_id
 * @property float $gross_amount
 * @property float $instapay_fee
 * @property float $platform_fee
 * @property float $net_amount
 * @property string $status
 * @property string $payout_method
 * @property string|null $payout_reference
 * @property \Illuminate\Support\Carbon $requested_at
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $failure_reason
 * @property string $currency
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read ShiftAssignment|null $shiftAssignment
 */
class InstapayRequest extends Model
{
    use HasFactory;

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Payout method constants
     */
    public const METHOD_STRIPE = 'stripe';

    public const METHOD_PAYPAL = 'paypal';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'shift_assignment_id',
        'gross_amount',
        'instapay_fee',
        'platform_fee',
        'net_amount',
        'status',
        'payout_method',
        'payout_reference',
        'requested_at',
        'processed_at',
        'completed_at',
        'failure_reason',
        'currency',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gross_amount' => 'decimal:2',
        'instapay_fee' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift assignment associated with this payout.
     */
    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if request failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if request can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark request as processing.
     */
    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark request as completed.
     */
    public function markAsCompleted(string $payoutReference): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'payout_reference' => $payoutReference,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark request as failed.
     */
    public function markAsFailed(string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mark request as cancelled.
     */
    public function markAsCancelled(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for processing requests.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope for completed requests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed requests.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for requests made today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('requested_at', today());
    }

    /**
     * Scope for requests by payout method.
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('payout_method', $method);
    }

    /**
     * Get available payout methods.
     */
    public static function getPayoutMethods(): array
    {
        return [
            self::METHOD_STRIPE => 'Stripe (Instant)',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get formatted gross amount.
     */
    public function getFormattedGrossAmountAttribute(): string
    {
        return number_format($this->gross_amount, 2).' '.$this->currency;
    }

    /**
     * Get formatted net amount.
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount, 2).' '.$this->currency;
    }

    /**
     * Get formatted fee.
     */
    public function getFormattedFeeAttribute(): string
    {
        return number_format($this->instapay_fee, 2).' '.$this->currency;
    }
}
