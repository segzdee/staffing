<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GLO-009: Regional Pricing System - Regional Pricing Model
 *
 * Stores regional pricing configurations based on location and purchasing power parity.
 *
 * @property int $id
 * @property string $country_code
 * @property string|null $region_code
 * @property string $currency_code
 * @property float $ppp_factor
 * @property float $min_hourly_rate
 * @property float $max_hourly_rate
 * @property float $platform_fee_rate
 * @property float $worker_fee_rate
 * @property array|null $tier_adjustments
 * @property string|null $country_name
 * @property string|null $region_name
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RegionalPricing extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'regional_pricing';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'country_code',
        'region_code',
        'currency_code',
        'ppp_factor',
        'min_hourly_rate',
        'max_hourly_rate',
        'platform_fee_rate',
        'worker_fee_rate',
        'tier_adjustments',
        'country_name',
        'region_name',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'ppp_factor' => 'decimal:3',
            'min_hourly_rate' => 'decimal:2',
            'max_hourly_rate' => 'decimal:2',
            'platform_fee_rate' => 'decimal:2',
            'worker_fee_rate' => 'decimal:2',
            'tier_adjustments' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Default tier adjustments template.
     */
    public const DEFAULT_TIER_ADJUSTMENTS = [
        'free' => ['platform_fee_modifier' => 1.0, 'worker_fee_modifier' => 1.0],
        'basic' => ['platform_fee_modifier' => 0.9, 'worker_fee_modifier' => 0.95],
        'professional' => ['platform_fee_modifier' => 0.8, 'worker_fee_modifier' => 0.9],
        'enterprise' => ['platform_fee_modifier' => 0.7, 'worker_fee_modifier' => 0.85],
    ];

    /**
     * Get price adjustments for this regional pricing.
     */
    public function priceAdjustments(): HasMany
    {
        return $this->hasMany(PriceAdjustment::class);
    }

    /**
     * Get active price adjustments.
     */
    public function activeAdjustments(): HasMany
    {
        return $this->hasMany(PriceAdjustment::class)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    /**
     * Scope: Only active regional pricing.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by country code.
     */
    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    /**
     * Scope: Filter by region code.
     */
    public function scopeByRegion($query, string $countryCode, ?string $regionCode = null)
    {
        return $query->where('country_code', strtoupper($countryCode))
            ->where('region_code', $regionCode);
    }

    /**
     * Get the full location identifier.
     */
    public function getLocationIdentifierAttribute(): string
    {
        if ($this->region_code) {
            return "{$this->country_code}-{$this->region_code}";
        }

        return $this->country_code;
    }

    /**
     * Get the display name for this region.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->region_name && $this->country_name) {
            return "{$this->region_name}, {$this->country_name}";
        }

        return $this->country_name ?? $this->country_code;
    }

    /**
     * Get the formatted rate range.
     */
    public function getFormattedRateRangeAttribute(): string
    {
        $symbol = config("currencies.symbols.{$this->currency_code}", $this->currency_code);

        return "{$symbol}{$this->min_hourly_rate} - {$symbol}{$this->max_hourly_rate}";
    }

    /**
     * Check if a rate is within the allowed range.
     */
    public function isRateValid(float $rate): bool
    {
        return $rate >= $this->min_hourly_rate && $rate <= $this->max_hourly_rate;
    }

    /**
     * Apply PPP adjustment to a price.
     */
    public function applyPPPAdjustment(float $price): float
    {
        return round($price * $this->ppp_factor, 2);
    }

    /**
     * Get platform fee for a given amount.
     */
    public function calculatePlatformFee(float $amount, ?string $tier = null): float
    {
        $feeRate = $this->platform_fee_rate;

        if ($tier && $this->tier_adjustments) {
            $tierAdjustment = $this->tier_adjustments[$tier] ?? null;
            if ($tierAdjustment && isset($tierAdjustment['platform_fee_modifier'])) {
                $feeRate *= $tierAdjustment['platform_fee_modifier'];
            }
        }

        return round($amount * ($feeRate / 100), 2);
    }

    /**
     * Get worker fee for a given amount.
     */
    public function calculateWorkerFee(float $amount, ?string $tier = null): float
    {
        $feeRate = $this->worker_fee_rate;

        if ($tier && $this->tier_adjustments) {
            $tierAdjustment = $this->tier_adjustments[$tier] ?? null;
            if ($tierAdjustment && isset($tierAdjustment['worker_fee_modifier'])) {
                $feeRate *= $tierAdjustment['worker_fee_modifier'];
            }
        }

        return round($amount * ($feeRate / 100), 2);
    }

    /**
     * Clamp a rate to the allowed range.
     */
    public function clampRate(float $rate): float
    {
        return max($this->min_hourly_rate, min($this->max_hourly_rate, $rate));
    }

    /**
     * Find regional pricing for a given country and optional region.
     */
    public static function findForLocation(string $countryCode, ?string $regionCode = null): ?self
    {
        $countryCode = strtoupper($countryCode);

        // First try to find specific region pricing
        if ($regionCode) {
            $regional = self::active()
                ->byRegion($countryCode, $regionCode)
                ->first();

            if ($regional) {
                return $regional;
            }
        }

        // Fall back to country-level pricing
        return self::active()
            ->byCountry($countryCode)
            ->whereNull('region_code')
            ->first();
    }

    /**
     * Get all countries with regional pricing.
     */
    public static function getActiveCountries(): array
    {
        return self::active()
            ->select('country_code', 'country_name')
            ->distinct()
            ->orderBy('country_name')
            ->pluck('country_name', 'country_code')
            ->toArray();
    }

    /**
     * Get all regions for a specific country.
     */
    public static function getRegionsForCountry(string $countryCode): array
    {
        return self::active()
            ->byCountry($countryCode)
            ->whereNotNull('region_code')
            ->orderBy('region_name')
            ->pluck('region_name', 'region_code')
            ->toArray();
    }
}
