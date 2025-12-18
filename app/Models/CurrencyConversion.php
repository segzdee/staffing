<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * GLO-001: Multi-Currency Support - Currency Conversion Model
 *
 * Audit trail for all currency conversions performed on the platform.
 * Links to reference entities (shift payments, withdrawals, etc.)
 *
 * @property int $id
 * @property int $user_id
 * @property string $from_currency
 * @property string $to_currency
 * @property float $from_amount
 * @property float $to_amount
 * @property float $exchange_rate
 * @property float $fee_amount
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read string $pair
 * @property-read string $formatted_from_amount
 * @property-read string $formatted_to_amount
 * @property-read float $effective_rate
 */
class CurrencyConversion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'from_currency',
        'to_currency',
        'from_amount',
        'to_amount',
        'exchange_rate',
        'fee_amount',
        'reference_type',
        'reference_id',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_amount' => 'decimal:2',
            'to_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:8',
            'fee_amount' => 'decimal:2',
        ];
    }

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REVERSED = 'reversed';

    /**
     * Get the user who performed the conversion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reference entity (polymorphic).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Get the currency pair string (e.g., "EUR/USD").
     */
    public function getPairAttribute(): string
    {
        return "{$this->from_currency}/{$this->to_currency}";
    }

    /**
     * Get the effective exchange rate (including fees).
     */
    public function getEffectiveRateAttribute(): float
    {
        if ($this->from_amount <= 0) {
            return 0;
        }

        return $this->to_amount / $this->from_amount;
    }

    /**
     * Get formatted from amount with currency symbol.
     */
    public function getFormattedFromAmountAttribute(): string
    {
        return $this->formatAmount($this->from_amount, $this->from_currency);
    }

    /**
     * Get formatted to amount with currency symbol.
     */
    public function getFormattedToAmountAttribute(): string
    {
        return $this->formatAmount($this->to_amount, $this->to_currency);
    }

    /**
     * Get formatted fee amount.
     */
    public function getFormattedFeeAmountAttribute(): string
    {
        return $this->formatAmount($this->fee_amount, $this->from_currency);
    }

    /**
     * Get the fee percentage.
     */
    public function getFeePercentageAttribute(): float
    {
        if ($this->from_amount <= 0) {
            return 0;
        }

        return ($this->fee_amount / $this->from_amount) * 100;
    }

    /**
     * Format an amount with currency symbol.
     */
    protected function formatAmount(float $amount, string $currency): string
    {
        $symbol = config("currencies.symbols.{$currency}", $currency);
        $decimals = config("currencies.rounding.{$currency}", 2);
        $symbolBefore = config("currencies.symbol_before.{$currency}", true);

        $formatted = number_format($amount, $decimals);

        return $symbolBefore
            ? "{$symbol}{$formatted}"
            : "{$formatted} {$symbol}";
    }

    /**
     * Check if conversion is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if conversion is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if conversion failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark conversion as completed.
     */
    public function markAsCompleted(): self
    {
        $this->update(['status' => self::STATUS_COMPLETED]);

        return $this;
    }

    /**
     * Mark conversion as failed.
     */
    public function markAsFailed(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason,
        ]);

        return $this;
    }

    /**
     * Mark conversion as reversed.
     */
    public function markAsReversed(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_REVERSED,
            'notes' => $reason,
        ]);

        return $this;
    }

    /**
     * Scope: Completed conversions only.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Pending conversions only.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Failed conversions only.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Filter by currency pair.
     */
    public function scopeForPair($query, string $fromCurrency, string $toCurrency)
    {
        return $query->where('from_currency', strtoupper($fromCurrency))
            ->where('to_currency', strtoupper($toCurrency));
    }

    /**
     * Scope: Filter by reference.
     */
    public function scopeForReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)
            ->where('reference_id', $id);
    }

    /**
     * Scope: Conversions within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Order by most recent first.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
