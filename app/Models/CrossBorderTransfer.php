<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * GLO-008: Cross-Border Payments - Cross Border Transfer
 *
 * Tracks international money transfers between currencies.
 *
 * @property int $id
 * @property string $transfer_reference
 * @property int $user_id
 * @property int $bank_account_id
 * @property string $source_currency
 * @property string $destination_currency
 * @property float $source_amount
 * @property float $destination_amount
 * @property float $exchange_rate
 * @property float $fee_amount
 * @property string $payment_method
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $estimated_arrival_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $provider_reference
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\BankAccount $bankAccount
 */
class CrossBorderTransfer extends Model
{
    use HasFactory;

    /**
     * Transfer status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETURNED = 'returned';

    /**
     * Available statuses.
     */
    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_SENT,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_RETURNED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'transfer_reference',
        'user_id',
        'bank_account_id',
        'source_currency',
        'destination_currency',
        'source_amount',
        'destination_amount',
        'exchange_rate',
        'fee_amount',
        'payment_method',
        'status',
        'estimated_arrival_at',
        'sent_at',
        'completed_at',
        'provider_reference',
        'failure_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'source_amount' => 'decimal:2',
        'destination_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
        'fee_amount' => 'decimal:2',
        'estimated_arrival_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate transfer reference.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $transfer) {
            if (empty($transfer->transfer_reference)) {
                $transfer->transfer_reference = self::generateReference();
            }
        });
    }

    /**
     * Generate a unique transfer reference.
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'CBT-'.strtoupper(Str::random(12));
        } while (self::query()->where('transfer_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Get the user that initiated the transfer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the destination bank account.
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Scope for transfers by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending transfers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for processing transfers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope for completed transfers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed transfers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for active transfers (not completed or failed).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_RETURNED,
        ]);
    }

    /**
     * Check if transfer is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transfer is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if transfer is sent.
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if transfer is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transfer has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if transfer was returned.
     */
    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    /**
     * Check if this is a cross-currency transfer.
     */
    public function isCrossCurrency(): bool
    {
        return $this->source_currency !== $this->destination_currency;
    }

    /**
     * Get the total deduction (source amount + fees).
     */
    public function getTotalDeduction(): float
    {
        return round($this->source_amount + $this->fee_amount, 2);
    }

    /**
     * Mark transfer as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Mark transfer as sent.
     */
    public function markAsSent(?string $providerReference = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'provider_reference' => $providerReference,
        ]);
    }

    /**
     * Mark transfer as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark transfer as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mark transfer as returned.
     */
    public function markAsReturned(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_RETURNED,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SENT => 'Sent',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_RETURNED => 'Returned',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the status color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_SENT => 'indigo',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_RETURNED => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            PaymentCorridor::METHOD_SEPA => 'SEPA Transfer',
            PaymentCorridor::METHOD_SWIFT => 'SWIFT/Wire Transfer',
            PaymentCorridor::METHOD_ACH => 'ACH Transfer',
            PaymentCorridor::METHOD_FASTER_PAYMENTS => 'Faster Payments',
            PaymentCorridor::METHOD_LOCAL => 'Local Bank Transfer',
            default => ucfirst($this->payment_method),
        };
    }

    /**
     * Get formatted source amount with currency.
     */
    public function getFormattedSourceAmount(): string
    {
        return number_format($this->source_amount, 2).' '.$this->source_currency;
    }

    /**
     * Get formatted destination amount with currency.
     */
    public function getFormattedDestinationAmount(): string
    {
        return number_format($this->destination_amount, 2).' '.$this->destination_currency;
    }

    /**
     * Get formatted fee amount with currency.
     */
    public function getFormattedFeeAmount(): string
    {
        return number_format($this->fee_amount, 2).' '.$this->source_currency;
    }

    /**
     * Get the exchange rate display.
     */
    public function getExchangeRateDisplay(): string
    {
        if (! $this->isCrossCurrency()) {
            return '1:1';
        }

        return sprintf(
            '1 %s = %.4f %s',
            $this->source_currency,
            $this->exchange_rate,
            $this->destination_currency
        );
    }

    /**
     * Calculate the estimated arrival date based on corridor timing.
     */
    public function calculateEstimatedArrival(int $daysMin, int $daysMax): array
    {
        $now = now();

        // Skip weekends for business day calculation
        $minDate = $now->copy();
        $maxDate = $now->copy();

        $addedDaysMin = 0;
        while ($addedDaysMin < $daysMin) {
            $minDate->addDay();
            if (! $minDate->isWeekend()) {
                $addedDaysMin++;
            }
        }

        $addedDaysMax = 0;
        while ($addedDaysMax < $daysMax) {
            $maxDate->addDay();
            if (! $maxDate->isWeekend()) {
                $addedDaysMax++;
            }
        }

        return [
            'min' => $minDate,
            'max' => $maxDate,
        ];
    }
}
