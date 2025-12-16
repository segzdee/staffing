<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * MarketRate Model
 *
 * BIZ-REG-009: Rate Suggestions
 *
 * Stores market rate data for different roles/positions by location.
 */
class MarketRate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'country_code',
        'state_code',
        'city',
        'metro_area',
        'role_category',
        'role_name',
        'industry',
        'rate_low_cents',
        'rate_median_cents',
        'rate_high_cents',
        'rate_premium_cents',
        'night_shift_multiplier',
        'weekend_multiplier',
        'holiday_multiplier',
        'urgent_multiplier',
        'currency',
        'entry_level_adjustment_cents',
        'experienced_adjustment_cents',
        'data_source',
        'sample_size',
        'data_collected_at',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'rate_low_cents' => 'integer',
        'rate_median_cents' => 'integer',
        'rate_high_cents' => 'integer',
        'rate_premium_cents' => 'integer',
        'night_shift_multiplier' => 'decimal:2',
        'weekend_multiplier' => 'decimal:2',
        'holiday_multiplier' => 'decimal:2',
        'urgent_multiplier' => 'decimal:2',
        'entry_level_adjustment_cents' => 'integer',
        'experienced_adjustment_cents' => 'integer',
        'sample_size' => 'integer',
        'data_collected_at' => 'date',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Role category constants.
     */
    const CATEGORY_HOSPITALITY = 'hospitality';
    const CATEGORY_RETAIL = 'retail';
    const CATEGORY_WAREHOUSE = 'warehouse';
    const CATEGORY_EVENTS = 'events';
    const CATEGORY_HEALTHCARE = 'healthcare';
    const CATEGORY_OFFICE = 'office';
    const CATEGORY_CLEANING = 'cleaning';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_DELIVERY = 'delivery';

    // =========================================
    // Rate Accessors (Dollars)
    // =========================================

    /**
     * Get low rate in dollars.
     */
    public function getRateLowDollarsAttribute(): float
    {
        return $this->rate_low_cents / 100;
    }

    /**
     * Get median rate in dollars.
     */
    public function getRateMedianDollarsAttribute(): float
    {
        return $this->rate_median_cents / 100;
    }

    /**
     * Get high rate in dollars.
     */
    public function getRateHighDollarsAttribute(): float
    {
        return $this->rate_high_cents / 100;
    }

    /**
     * Get premium rate in dollars.
     */
    public function getRatePremiumDollarsAttribute(): ?float
    {
        return $this->rate_premium_cents ? $this->rate_premium_cents / 100 : null;
    }

    // =========================================
    // Rate Calculation Methods
    // =========================================

    /**
     * Get suggested rate based on conditions.
     */
    public function getSuggestedRate(array $conditions = []): array
    {
        $baseRate = $this->rate_median_cents;

        // Apply experience adjustment
        if (isset($conditions['experience_level'])) {
            $baseRate += match($conditions['experience_level']) {
                'entry' => $this->entry_level_adjustment_cents,
                'experienced' => $this->experienced_adjustment_cents,
                'expert' => $this->experienced_adjustment_cents * 2,
                default => 0,
            };
        }

        // Calculate modifiers
        $multiplier = 1.0;
        $modifiersApplied = [];

        // Time-based modifiers
        if (!empty($conditions['is_night_shift'])) {
            $multiplier *= $this->night_shift_multiplier;
            $modifiersApplied[] = 'Night shift (+' . (($this->night_shift_multiplier - 1) * 100) . '%)';
        }

        if (!empty($conditions['is_weekend'])) {
            $multiplier *= $this->weekend_multiplier;
            $modifiersApplied[] = 'Weekend (+' . (($this->weekend_multiplier - 1) * 100) . '%)';
        }

        if (!empty($conditions['is_holiday'])) {
            $multiplier *= $this->holiday_multiplier;
            $modifiersApplied[] = 'Holiday (+' . (($this->holiday_multiplier - 1) * 100) . '%)';
        }

        if (!empty($conditions['is_urgent'])) {
            $multiplier *= $this->urgent_multiplier;
            $modifiersApplied[] = 'Urgent (+' . (($this->urgent_multiplier - 1) * 100) . '%)';
        }

        $suggestedRate = (int) round($baseRate * $multiplier);

        return [
            'suggested_rate_cents' => $suggestedRate,
            'suggested_rate_dollars' => $suggestedRate / 100,
            'base_rate_cents' => $this->rate_median_cents,
            'rate_range' => [
                'low_cents' => (int) round($this->rate_low_cents * $multiplier),
                'median_cents' => $suggestedRate,
                'high_cents' => (int) round($this->rate_high_cents * $multiplier),
                'low_dollars' => round($this->rate_low_cents * $multiplier / 100, 2),
                'median_dollars' => $suggestedRate / 100,
                'high_dollars' => round($this->rate_high_cents * $multiplier / 100, 2),
            ],
            'multiplier' => $multiplier,
            'modifiers_applied' => $modifiersApplied,
            'currency' => $this->currency,
            'data_source' => $this->data_source,
            'sample_size' => $this->sample_size,
        ];
    }

    /**
     * Get competitive rating for a given rate.
     */
    public function getCompetitiveRating(int $rateCents): array
    {
        if ($rateCents >= $this->rate_premium_cents) {
            $rating = 'premium';
            $percentile = 90;
            $description = 'Premium rate - will attract top talent quickly';
        } elseif ($rateCents >= $this->rate_high_cents) {
            $rating = 'competitive';
            $percentile = 75;
            $description = 'Competitive rate - above market average';
        } elseif ($rateCents >= $this->rate_median_cents) {
            $rating = 'average';
            $percentile = 50;
            $description = 'Average rate - matches market expectations';
        } elseif ($rateCents >= $this->rate_low_cents) {
            $rating = 'below_average';
            $percentile = 25;
            $description = 'Below average - may take longer to fill';
        } else {
            $rating = 'low';
            $percentile = 10;
            $description = 'Below market range - may struggle to attract workers';
        }

        return [
            'rating' => $rating,
            'percentile' => $percentile,
            'description' => $description,
            'comparison' => [
                'vs_low' => round(($rateCents - $this->rate_low_cents) / $this->rate_low_cents * 100, 1),
                'vs_median' => round(($rateCents - $this->rate_median_cents) / $this->rate_median_cents * 100, 1),
                'vs_high' => round(($rateCents - $this->rate_high_cents) / $this->rate_high_cents * 100, 1),
            ],
        ];
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to active rates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to currently valid rates.
     */
    public function scopeCurrentlyValid($query)
    {
        $now = now()->startOfDay();

        return $query->active()
            ->where('valid_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', $now);
            });
    }

    /**
     * Scope by location.
     */
    public function scopeForLocation($query, string $countryCode, ?string $stateCode = null, ?string $city = null)
    {
        $query->where('country_code', strtoupper($countryCode));

        if ($stateCode) {
            $query->where('state_code', $stateCode);
        }

        if ($city) {
            $query->where(function ($q) use ($city) {
                $q->where('city', $city)
                  ->orWhereNull('city');
            });
        }

        return $query;
    }

    /**
     * Scope by role category.
     */
    public function scopeForCategory($query, string $category)
    {
        return $query->where('role_category', $category);
    }

    /**
     * Scope by role name.
     */
    public function scopeForRole($query, string $roleName)
    {
        return $query->where('role_name', $roleName);
    }

    /**
     * Scope by industry.
     */
    public function scopeForIndustry($query, string $industry)
    {
        return $query->where(function ($q) use ($industry) {
            $q->where('industry', $industry)
              ->orWhereNull('industry');
        });
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * Get market rate for a specific role and location.
     */
    public static function getForRoleAndLocation(
        string $roleName,
        string $countryCode,
        ?string $stateCode = null,
        ?string $city = null
    ): ?self {
        $cacheKey = "market_rate_{$countryCode}_{$stateCode}_{$city}_{$roleName}";

        return Cache::remember($cacheKey, 3600, function () use ($roleName, $countryCode, $stateCode, $city) {
            // Try exact match first
            $rate = self::currentlyValid()
                ->forLocation($countryCode, $stateCode, $city)
                ->forRole($roleName)
                ->orderBy('city', 'desc') // Prefer city-specific
                ->first();

            if ($rate) {
                return $rate;
            }

            // Fall back to state level
            if ($stateCode) {
                $rate = self::currentlyValid()
                    ->forLocation($countryCode, $stateCode)
                    ->whereNull('city')
                    ->forRole($roleName)
                    ->first();

                if ($rate) {
                    return $rate;
                }
            }

            // Fall back to country level
            return self::currentlyValid()
                ->forLocation($countryCode)
                ->whereNull('state_code')
                ->whereNull('city')
                ->forRole($roleName)
                ->first();
        });
    }

    /**
     * Get suggested rate for shift.
     */
    public static function getSuggestedRateForShift(array $shiftData): array
    {
        $role = $shiftData['role'] ?? $shiftData['role_name'] ?? null;
        $countryCode = $shiftData['country_code'] ?? 'US';
        $stateCode = $shiftData['state_code'] ?? $shiftData['state'] ?? null;
        $city = $shiftData['city'] ?? null;

        $marketRate = self::getForRoleAndLocation($role, $countryCode, $stateCode, $city);

        if (!$marketRate) {
            // Return default suggestion if no market data
            return [
                'suggested_rate_cents' => 1500, // $15.00 default
                'suggested_rate_dollars' => 15.00,
                'has_market_data' => false,
                'message' => 'No market data available for this role. Showing default rate.',
            ];
        }

        // Determine conditions
        $conditions = [];

        if (!empty($shiftData['start_time'])) {
            $startHour = (int) date('H', strtotime($shiftData['start_time']));
            if ($startHour >= 22 || $startHour < 6) {
                $conditions['is_night_shift'] = true;
            }
        }

        if (!empty($shiftData['shift_date'])) {
            $dayOfWeek = date('w', strtotime($shiftData['shift_date']));
            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                $conditions['is_weekend'] = true;
            }
        }

        if (!empty($shiftData['is_urgent']) || !empty($shiftData['urgency_level']) && $shiftData['urgency_level'] === 'urgent') {
            $conditions['is_urgent'] = true;
        }

        if (!empty($shiftData['experience_level'])) {
            $conditions['experience_level'] = $shiftData['experience_level'];
        }

        $suggestion = $marketRate->getSuggestedRate($conditions);
        $suggestion['has_market_data'] = true;
        $suggestion['market_rate_id'] = $marketRate->id;
        $suggestion['role_name'] = $marketRate->role_name;
        $suggestion['jurisdiction'] = implode(', ', array_filter([
            $marketRate->city,
            $marketRate->state_code,
            $marketRate->country_code,
        ]));

        return $suggestion;
    }

    /**
     * Get all available roles for a category.
     */
    public static function getRolesForCategory(string $category): array
    {
        return Cache::remember("market_rate_roles_{$category}", 3600, function () use ($category) {
            return self::active()
                ->forCategory($category)
                ->select('role_name')
                ->distinct()
                ->orderBy('role_name')
                ->pluck('role_name')
                ->toArray();
        });
    }

    /**
     * Get all available categories.
     */
    public static function getAllCategories(): array
    {
        return Cache::remember('market_rate_categories', 3600, function () {
            return self::active()
                ->select('role_category')
                ->distinct()
                ->orderBy('role_category')
                ->pluck('role_category')
                ->toArray();
        });
    }

    /**
     * Get suggested roles by business type.
     */
    public static function getSuggestedRolesByBusinessType(string $businessType): array
    {
        $mapping = [
            'restaurant' => ['Server', 'Bartender', 'Host/Hostess', 'Busser', 'Line Cook', 'Prep Cook', 'Dishwasher'],
            'bar' => ['Bartender', 'Server', 'Barback', 'Security Guard', 'Host/Hostess'],
            'cafe' => ['Barista', 'Server', 'Cashier', 'Kitchen Staff'],
            'hotel' => ['Housekeeper', 'Front Desk', 'Bellhop', 'Server', 'Bartender', 'Event Server'],
            'retail' => ['Sales Associate', 'Cashier', 'Stock Associate', 'Customer Service Rep', 'Visual Merchandiser'],
            'warehouse' => ['Picker/Packer', 'Forklift Operator', 'Warehouse Associate', 'Loader/Unloader', 'Inventory Clerk'],
            'events' => ['Event Server', 'Event Bartender', 'Catering Staff', 'Event Setup', 'Brand Ambassador'],
            'catering' => ['Catering Staff', 'Event Server', 'Event Bartender', 'Kitchen Staff'],
            'healthcare' => ['CNA', 'Medical Assistant', 'Patient Transporter', 'Dietary Aide'],
            'office' => ['Receptionist', 'Data Entry', 'Administrative Assistant'],
            'cleaning' => ['Janitor', 'Housekeeper', 'Commercial Cleaner'],
            'security' => ['Security Guard', 'Event Security'],
            'delivery' => ['Delivery Driver', 'Courier'],
            'general' => ['General Labor', 'Helper', 'Assistant'],
        ];

        $normalizedType = strtolower($businessType);

        foreach ($mapping as $key => $roles) {
            if (str_contains($normalizedType, $key)) {
                return $roles;
            }
        }

        // Return a mix of common roles if no match
        return [
            'General Labor',
            'Helper',
            'Customer Service',
            'Cashier',
            'Stock Associate',
        ];
    }
}
