<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GLO-001: Multi-Currency Support - Currency Wallet Model
 *
 * Represents a user's wallet in a specific currency.
 * Users can have multiple wallets, one per currency.
 *
 * @property int $id
 * @property int $user_id
 * @property string $currency_code
 * @property float $balance
 * @property float $pending_balance
 * @property bool $is_primary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read string $formatted_balance
 * @property-read string $currency_symbol
 * @property-read string $currency_name
 * @property-read float $total_balance
 */
class CurrencyWallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'currency_code',
        'balance',
        'pending_balance',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get conversions from this wallet.
     */
    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(CurrencyConversion::class, 'user_id', 'user_id')
            ->where('from_currency', $this->currency_code);
    }

    /**
     * Get conversions to this wallet.
     */
    public function conversionsTo(): HasMany
    {
        return $this->hasMany(CurrencyConversion::class, 'user_id', 'user_id')
            ->where('to_currency', $this->currency_code);
    }

    /**
     * Get the currency symbol.
     */
    public function getCurrencySymbolAttribute(): string
    {
        return config("currencies.symbols.{$this->currency_code}", $this->currency_code);
    }

    /**
     * Get the currency name.
     */
    public function getCurrencyNameAttribute(): string
    {
        return config("currencies.names.{$this->currency_code}", $this->currency_code);
    }

    /**
     * Get the decimal places for this currency.
     */
    public function getDecimalPlacesAttribute(): int
    {
        return config("currencies.rounding.{$this->currency_code}", 2);
    }

    /**
     * Get formatted balance with currency symbol.
     */
    public function getFormattedBalanceAttribute(): string
    {
        return $this->formatAmount($this->balance);
    }

    /**
     * Get formatted pending balance with currency symbol.
     */
    public function getFormattedPendingBalanceAttribute(): string
    {
        return $this->formatAmount($this->pending_balance);
    }

    /**
     * Get total balance (available + pending).
     */
    public function getTotalBalanceAttribute(): float
    {
        return $this->balance + $this->pending_balance;
    }

    /**
     * Get formatted total balance.
     */
    public function getFormattedTotalBalanceAttribute(): string
    {
        return $this->formatAmount($this->total_balance);
    }

    /**
     * Format an amount with this wallet's currency.
     */
    public function formatAmount(float $amount): string
    {
        $symbol = $this->currency_symbol;
        $decimals = $this->decimal_places;
        $symbolBefore = config("currencies.symbol_before.{$this->currency_code}", true);

        $formatted = number_format($amount, $decimals);

        return $symbolBefore
            ? "{$symbol}{$formatted}"
            : "{$formatted} {$symbol}";
    }

    /**
     * Check if wallet has sufficient balance.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Credit the wallet (add funds).
     */
    public function credit(float $amount): self
    {
        $this->increment('balance', $amount);

        return $this;
    }

    /**
     * Debit the wallet (remove funds).
     *
     * @throws \InvalidArgumentException
     */
    public function debit(float $amount): self
    {
        if (! $this->hasSufficientBalance($amount)) {
            throw new \InvalidArgumentException(
                "Insufficient balance. Available: {$this->formatted_balance}, Required: ".$this->formatAmount($amount)
            );
        }

        $this->decrement('balance', $amount);

        return $this;
    }

    /**
     * Add to pending balance.
     */
    public function addPending(float $amount): self
    {
        $this->increment('pending_balance', $amount);

        return $this;
    }

    /**
     * Release from pending to available balance.
     */
    public function releasePending(float $amount): self
    {
        if ($this->pending_balance < $amount) {
            $amount = $this->pending_balance;
        }

        $this->decrement('pending_balance', $amount);
        $this->increment('balance', $amount);

        return $this;
    }

    /**
     * Cancel pending amount (remove without adding to available).
     */
    public function cancelPending(float $amount): self
    {
        if ($this->pending_balance < $amount) {
            $amount = $this->pending_balance;
        }

        $this->decrement('pending_balance', $amount);

        return $this;
    }

    /**
     * Set this wallet as the user's primary wallet.
     */
    public function setAsPrimary(): self
    {
        // Remove primary status from other wallets
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);

        return $this;
    }

    /**
     * Scope: Primary wallets only.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Wallets with balance.
     */
    public function scopeWithBalance($query)
    {
        return $query->where('balance', '>', 0);
    }

    /**
     * Scope: Wallets with pending balance.
     */
    public function scopeWithPendingBalance($query)
    {
        return $query->where('pending_balance', '>', 0);
    }

    /**
     * Scope: Filter by currency.
     */
    public function scopeForCurrency($query, string $currencyCode)
    {
        return $query->where('currency_code', strtoupper($currencyCode));
    }
}
