<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MinimumWage Model
 *
 * BIZ-REG-007/009: Minimum Wage Enforcement
 *
 * Stores minimum wage data by jurisdiction for real-time validation.
 */
class MinimumWage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'country_code',
        'state_code',
        'city',
        'jurisdiction_name',
        'hourly_rate_cents',
        'tipped_rate_cents',
        'youth_rate_cents',
        'overtime_rate_cents',
        'currency',
        'effective_date',
        'expiry_date',
        'rate_type',
        'conditions',
        'overtime_multiplier',
        'overtime_threshold_daily',
        'overtime_threshold_weekly',
        'source',
        'source_url',
        'last_verified_at',
        'is_active',
        'is_federal',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'hourly_rate_cents' => 'integer',
        'tipped_rate_cents' => 'integer',
        'youth_rate_cents' => 'integer',
        'overtime_rate_cents' => 'integer',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'conditions' => 'array',
        'overtime_multiplier' => 'decimal:2',
        'overtime_threshold_daily' => 'integer',
        'overtime_threshold_weekly' => 'integer',
        'last_verified_at' => 'date',
        'is_active' => 'boolean',
        'is_federal' => 'boolean',
    ];

    /**
     * Rate type constants.
     */
    const RATE_STANDARD = 'standard';
    const RATE_TIPPED = 'tipped';
    const RATE_YOUTH = 'youth';
    const RATE_TRAINING = 'training';

    // =========================================
    // Rate Accessors (Dollars)
    // =========================================

    /**
     * Get hourly rate in dollars.
     */
    public function getHourlyRateDollarsAttribute(): float
    {
        return $this->hourly_rate_cents / 100;
    }

    /**
     * Get tipped rate in dollars.
     */
    public function getTippedRateDollarsAttribute(): ?float
    {
        return $this->tipped_rate_cents ? $this->tipped_rate_cents / 100 : null;
    }

    /**
     * Get youth rate in dollars.
     */
    public function getYouthRateDollarsAttribute(): ?float
    {
        return $this->youth_rate_cents ? $this->youth_rate_cents / 100 : null;
    }

    /**
     * Get overtime rate in dollars.
     */
    public function getOvertimeRateDollarsAttribute(): ?float
    {
        if ($this->overtime_rate_cents) {
            return $this->overtime_rate_cents / 100;
        }

        // Calculate from multiplier
        return $this->hourly_rate_dollars * $this->overtime_multiplier;
    }

    // =========================================
    // Display Methods
    // =========================================

    /**
     * Get formatted rate for display.
     */
    public function getFormattedRateAttribute(): string
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($this->hourly_rate_dollars, 2);
    }

    /**
     * Get currency symbol.
     */
    public function getCurrencySymbol(): string
    {
        return match($this->currency) {
            'USD' => '$',
            'EUR' => "\u{20AC}",
            'GBP' => "\u{00A3}",
            'CAD' => 'C$',
            'AUD' => 'A$',
            default => $this->currency . ' ',
        };
    }

    /**
     * Get jurisdiction display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->jurisdiction_name;
    }

    // =========================================
    // Status Methods
    // =========================================

    /**
     * Check if currently effective.
     */
    public function isCurrentlyEffective(): bool
    {
        $now = now()->startOfDay();

        if ($now->lt($this->effective_date)) {
            return false;
        }

        if ($this->expiry_date && $now->gt($this->expiry_date)) {
            return false;
        }

        return $this->is_active;
    }

    /**
     * Check if rate is compliant.
     */
    public function isRateCompliant(int $rateCents): bool
    {
        return $rateCents >= $this->hourly_rate_cents;
    }

    /**
     * Get the minimum compliant rate.
     */
    public function getMinimumCompliantRate(string $rateType = 'standard'): int
    {
        return match($rateType) {
            self::RATE_TIPPED => $this->tipped_rate_cents ?? $this->hourly_rate_cents,
            self::RATE_YOUTH => $this->youth_rate_cents ?? $this->hourly_rate_cents,
            self::RATE_TRAINING => $this->youth_rate_cents ?? $this->hourly_rate_cents,
            default => $this->hourly_rate_cents,
        };
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get active rates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get currently effective rates.
     */
    public function scopeCurrentlyEffective($query)
    {
        $now = now()->startOfDay();

        return $query->active()
            ->where('effective_date', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', $now);
            });
    }

    /**
     * Scope by country.
     */
    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    /**
     * Scope by state.
     */
    public function scopeForState($query, string $countryCode, ?string $stateCode)
    {
        return $query->forCountry($countryCode)
            ->where('state_code', $stateCode);
    }

    /**
     * Scope by city.
     */
    public function scopeForCity($query, string $countryCode, ?string $stateCode, ?string $city)
    {
        return $query->forState($countryCode, $stateCode)
            ->where('city', $city);
    }

    /**
     * Scope for federal rates only.
     */
    public function scopeFederal($query)
    {
        return $query->where('is_federal', true);
    }

    /**
     * Scope for non-federal rates only.
     */
    public function scopeLocal($query)
    {
        return $query->where('is_federal', false);
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * Get minimum wage for a jurisdiction (most specific available).
     *
     * Precedence: City > State > Federal
     */
    public static function getForJurisdiction(
        string $countryCode,
        ?string $stateCode = null,
        ?string $city = null
    ): ?self {
        $countryCode = strtoupper($countryCode);

        // Try city-specific rate first
        if ($city && $stateCode) {
            $cityRate = self::currentlyEffective()
                ->forCity($countryCode, $stateCode, $city)
                ->orderBy('effective_date', 'desc')
                ->first();

            if ($cityRate) {
                return $cityRate;
            }
        }

        // Try state-specific rate
        if ($stateCode) {
            $stateRate = self::currentlyEffective()
                ->forState($countryCode, $stateCode)
                ->whereNull('city')
                ->orderBy('effective_date', 'desc')
                ->first();

            if ($stateRate) {
                return $stateRate;
            }
        }

        // Fall back to federal/country rate
        return self::currentlyEffective()
            ->forCountry($countryCode)
            ->whereNull('state_code')
            ->whereNull('city')
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * Validate a rate against minimum wage requirements.
     */
    public static function validateRate(
        int $rateCents,
        string $countryCode,
        ?string $stateCode = null,
        ?string $city = null,
        string $rateType = 'standard'
    ): array {
        $minimumWage = self::getForJurisdiction($countryCode, $stateCode, $city);

        if (!$minimumWage) {
            return [
                'valid' => true,
                'minimum_wage' => null,
                'jurisdiction' => null,
                'message' => 'No minimum wage data available for this jurisdiction.',
            ];
        }

        $minimumRate = $minimumWage->getMinimumCompliantRate($rateType);
        $isCompliant = $rateCents >= $minimumRate;

        return [
            'valid' => $isCompliant,
            'minimum_wage' => $minimumWage,
            'minimum_rate_cents' => $minimumRate,
            'minimum_rate_dollars' => $minimumRate / 100,
            'your_rate_dollars' => $rateCents / 100,
            'difference_cents' => $rateCents - $minimumRate,
            'jurisdiction' => $minimumWage->jurisdiction_name,
            'message' => $isCompliant
                ? "Rate meets minimum wage requirements."
                : "Rate is below the minimum wage of {$minimumWage->getCurrencySymbol()}" .
                  number_format($minimumRate / 100, 2) . "/hour for {$minimumWage->jurisdiction_name}.",
        ];
    }

    /**
     * Get all rates for a country (for admin/display).
     */
    public static function getAllForCountry(string $countryCode): array
    {
        $countryCode = strtoupper($countryCode);

        $rates = self::currentlyEffective()
            ->forCountry($countryCode)
            ->orderBy('is_federal', 'desc')
            ->orderBy('state_code')
            ->orderBy('city')
            ->get();

        $grouped = [
            'federal' => null,
            'states' => [],
            'cities' => [],
        ];

        foreach ($rates as $rate) {
            if ($rate->is_federal && !$rate->state_code && !$rate->city) {
                $grouped['federal'] = $rate;
            } elseif ($rate->state_code && !$rate->city) {
                $grouped['states'][$rate->state_code] = $rate;
            } elseif ($rate->city) {
                $key = $rate->state_code . '_' . $rate->city;
                $grouped['cities'][$key] = $rate;
            }
        }

        return $grouped;
    }
}
