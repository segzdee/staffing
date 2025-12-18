<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * GLO-001: Multi-Currency Support - Exchange Rate Model
 *
 * Stores historical exchange rates between currency pairs.
 * Rates are fetched from ECB or OpenExchangeRates APIs.
 *
 * @property int $id
 * @property string $base_currency
 * @property string $target_currency
 * @property float $rate
 * @property float $inverse_rate
 * @property string $source
 * @property \Illuminate\Support\Carbon $rate_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $pair
 * @property-read bool $is_stale
 */
class ExchangeRate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'inverse_rate',
        'source',
        'rate_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'inverse_rate' => 'decimal:8',
            'rate_date' => 'datetime',
        ];
    }

    /**
     * Get the currency pair string (e.g., "EUR/USD").
     */
    public function getPairAttribute(): string
    {
        return "{$this->base_currency}/{$this->target_currency}";
    }

    /**
     * Check if the rate is stale (older than threshold).
     */
    public function getIsStaleAttribute(): bool
    {
        $thresholdHours = config('currencies.stale_rate_threshold_hours', 48);

        return $this->rate_date->diffInHours(now()) > $thresholdHours;
    }

    /**
     * Get the age of this rate in hours.
     */
    public function getAgeInHoursAttribute(): int
    {
        return $this->rate_date->diffInHours(now());
    }

    /**
     * Convert an amount using this exchange rate.
     */
    public function convert(float $amount): float
    {
        return $amount * $this->rate;
    }

    /**
     * Convert an amount using the inverse rate.
     */
    public function convertInverse(float $amount): float
    {
        return $amount * $this->inverse_rate;
    }

    /**
     * Get the latest rate for a currency pair.
     */
    public static function getLatestRate(string $baseCurrency, string $targetCurrency): ?self
    {
        return static::where('base_currency', strtoupper($baseCurrency))
            ->where('target_currency', strtoupper($targetCurrency))
            ->orderBy('rate_date', 'desc')
            ->first();
    }

    /**
     * Get the rate for a specific date.
     */
    public static function getRateForDate(string $baseCurrency, string $targetCurrency, Carbon $date): ?self
    {
        return static::where('base_currency', strtoupper($baseCurrency))
            ->where('target_currency', strtoupper($targetCurrency))
            ->whereDate('rate_date', $date->toDateString())
            ->first();
    }

    /**
     * Get rate history for a currency pair.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getHistory(string $baseCurrency, string $targetCurrency, int $days = 30)
    {
        return static::where('base_currency', strtoupper($baseCurrency))
            ->where('target_currency', strtoupper($targetCurrency))
            ->where('rate_date', '>=', now()->subDays($days))
            ->orderBy('rate_date', 'desc')
            ->get();
    }

    /**
     * Create or update an exchange rate.
     */
    public static function updateOrCreateRate(
        string $baseCurrency,
        string $targetCurrency,
        float $rate,
        string $source = 'ecb',
        ?Carbon $rateDate = null
    ): self {
        $rateDate = $rateDate ?? now();

        return static::updateOrCreate(
            [
                'base_currency' => strtoupper($baseCurrency),
                'target_currency' => strtoupper($targetCurrency),
                'rate_date' => $rateDate->startOfDay(),
            ],
            [
                'rate' => $rate,
                'inverse_rate' => $rate > 0 ? (1 / $rate) : 0,
                'source' => $source,
            ]
        );
    }

    /**
     * Scope: Latest rates only (most recent for each pair).
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('rate_date', 'desc');
    }

    /**
     * Scope: Filter by source.
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: Stale rates (older than threshold).
     */
    public function scopeStale($query)
    {
        $thresholdHours = config('currencies.stale_rate_threshold_hours', 48);

        return $query->where('rate_date', '<', now()->subHours($thresholdHours));
    }

    /**
     * Scope: Fresh rates (within threshold).
     */
    public function scopeFresh($query)
    {
        $thresholdHours = config('currencies.stale_rate_threshold_hours', 48);

        return $query->where('rate_date', '>=', now()->subHours($thresholdHours));
    }

    /**
     * Scope: For a specific currency pair.
     */
    public function scopeForPair($query, string $baseCurrency, string $targetCurrency)
    {
        return $query->where('base_currency', strtoupper($baseCurrency))
            ->where('target_currency', strtoupper($targetCurrency));
    }

    /**
     * Scope: Rates within a date range.
     */
    public function scopeBetweenDates($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('rate_date', [$startDate, $endDate]);
    }
}
