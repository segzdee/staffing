<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-013: Availability Forecasting - Demand Forecast Model
 *
 * Stores predicted demand (worker needs) for future dates.
 * Includes supply predictions and gap analysis.
 *
 * @property int $id
 * @property \Carbon\Carbon $forecast_date
 * @property int|null $venue_id
 * @property string|null $skill_category
 * @property string|null $region
 * @property int $predicted_demand
 * @property int $predicted_supply
 * @property float $supply_demand_ratio
 * @property string $demand_level low|normal|high|critical
 * @property array|null $factors
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Venue|null $venue
 */
class DemandForecast extends Model
{
    use HasFactory;

    /**
     * Demand level constants.
     */
    public const LEVEL_LOW = 'low';

    public const LEVEL_NORMAL = 'normal';

    public const LEVEL_HIGH = 'high';

    public const LEVEL_CRITICAL = 'critical';

    /**
     * Demand level labels.
     */
    public const LEVEL_LABELS = [
        self::LEVEL_LOW => 'Low Demand',
        self::LEVEL_NORMAL => 'Normal Demand',
        self::LEVEL_HIGH => 'High Demand',
        self::LEVEL_CRITICAL => 'Critical - Worker Shortage',
    ];

    /**
     * Supply-demand ratio thresholds.
     */
    public const RATIO_CRITICAL = 0.5; // Supply < 50% of demand

    public const RATIO_HIGH = 0.8;     // Supply < 80% of demand

    public const RATIO_NORMAL = 1.2;   // Supply within 80-120% of demand

    protected $fillable = [
        'forecast_date',
        'venue_id',
        'skill_category',
        'region',
        'predicted_demand',
        'predicted_supply',
        'supply_demand_ratio',
        'demand_level',
        'factors',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_demand' => 'integer',
        'predicted_supply' => 'integer',
        'supply_demand_ratio' => 'decimal:2',
        'factors' => 'array',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the venue this forecast is for (optional).
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the demand level label.
     */
    public function getLevelLabelAttribute(): string
    {
        return self::LEVEL_LABELS[$this->demand_level] ?? 'Unknown';
    }

    /**
     * Get the supply gap (negative means shortage).
     */
    public function getSupplyGapAttribute(): int
    {
        return $this->predicted_supply - $this->predicted_demand;
    }

    /**
     * Check if there's a worker shortage.
     */
    public function getHasShortageAttribute(): bool
    {
        return $this->predicted_supply < $this->predicted_demand;
    }

    /**
     * Get shortage percentage.
     */
    public function getShortagePercentAttribute(): float
    {
        if ($this->predicted_demand == 0) {
            return 0;
        }

        $gap = $this->supply_gap;
        if ($gap >= 0) {
            return 0;
        }

        return round(abs($gap) / $this->predicted_demand * 100, 1);
    }

    /**
     * Get surplus percentage.
     */
    public function getSurplusPercentAttribute(): float
    {
        if ($this->predicted_demand == 0) {
            return $this->predicted_supply > 0 ? 100 : 0;
        }

        $gap = $this->supply_gap;
        if ($gap <= 0) {
            return 0;
        }

        return round($gap / $this->predicted_demand * 100, 1);
    }

    /**
     * Get demand level color for UI.
     */
    public function getLevelColorAttribute(): string
    {
        return match ($this->demand_level) {
            self::LEVEL_LOW => 'gray',
            self::LEVEL_NORMAL => 'green',
            self::LEVEL_HIGH => 'yellow',
            self::LEVEL_CRITICAL => 'red',
            default => 'gray',
        };
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get forecasts for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : $date;

        return $query->where('forecast_date', $dateString);
    }

    /**
     * Scope to get forecasts for a date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('forecast_date', [
            $startDate instanceof Carbon ? $startDate->toDateString() : $startDate,
            $endDate instanceof Carbon ? $endDate->toDateString() : $endDate,
        ]);
    }

    /**
     * Scope to get forecasts for a specific region.
     */
    public function scopeForRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope to get forecasts for a specific skill.
     */
    public function scopeForSkill($query, string $skill)
    {
        return $query->where('skill_category', $skill);
    }

    /**
     * Scope to get forecasts for a specific venue.
     */
    public function scopeForVenue($query, int $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    /**
     * Scope to get only critical forecasts.
     */
    public function scopeCritical($query)
    {
        return $query->where('demand_level', self::LEVEL_CRITICAL);
    }

    /**
     * Scope to get high and critical forecasts.
     */
    public function scopeHighDemand($query)
    {
        return $query->whereIn('demand_level', [self::LEVEL_HIGH, self::LEVEL_CRITICAL]);
    }

    /**
     * Scope to get forecasts with worker shortage.
     */
    public function scopeWithShortage($query)
    {
        return $query->whereColumn('predicted_supply', '<', 'predicted_demand');
    }

    /**
     * Scope to get future forecasts.
     */
    public function scopeFuture($query)
    {
        return $query->where('forecast_date', '>=', Carbon::today());
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * Calculate demand level from ratio.
     */
    public static function calculateDemandLevel(float $ratio): string
    {
        if ($ratio < self::RATIO_CRITICAL) {
            return self::LEVEL_CRITICAL;
        }
        if ($ratio < self::RATIO_HIGH) {
            return self::LEVEL_HIGH;
        }
        if ($ratio <= self::RATIO_NORMAL) {
            return self::LEVEL_NORMAL;
        }

        return self::LEVEL_LOW;
    }

    /**
     * Update forecast with new supply/demand values.
     */
    public function updateForecast(int $demand, int $supply): void
    {
        $this->predicted_demand = $demand;
        $this->predicted_supply = $supply;
        $this->supply_demand_ratio = $demand > 0 ? $supply / $demand : ($supply > 0 ? 999.99 : 0);
        $this->demand_level = self::calculateDemandLevel($this->supply_demand_ratio);
        $this->save();
    }

    /**
     * Add a factor that influenced this forecast.
     */
    public function addFactor(string $name, $value, ?string $impact = null): void
    {
        $factors = $this->factors ?? [];
        $factors[$name] = [
            'value' => $value,
            'impact' => $impact,
        ];
        $this->factors = $factors;
        $this->save();
    }

    /**
     * Get recommendations based on forecast.
     */
    public function getRecommendations(): array
    {
        $recommendations = [];

        if ($this->has_shortage) {
            $recommendations[] = [
                'type' => 'urgent',
                'action' => 'recruit',
                'message' => "Need {$this->shortage_percent}% more workers for {$this->forecast_date->format('M j')}",
            ];

            if ($this->demand_level === self::LEVEL_CRITICAL) {
                $recommendations[] = [
                    'type' => 'critical',
                    'action' => 'surge_pricing',
                    'message' => 'Consider surge pricing to attract more workers',
                ];
            }
        } elseif ($this->surplus_percent > 30) {
            $recommendations[] = [
                'type' => 'info',
                'action' => 'reduce_outreach',
                'message' => 'Worker surplus expected - reduce recruitment efforts',
            ];
        }

        return $recommendations;
    }

    /**
     * Get unique regions from forecasts.
     */
    public static function getDistinctRegions(): array
    {
        return self::query()
            ->whereNotNull('region')
            ->distinct()
            ->pluck('region')
            ->toArray();
    }

    /**
     * Get unique skill categories from forecasts.
     */
    public static function getDistinctSkills(): array
    {
        return self::query()
            ->whereNotNull('skill_category')
            ->distinct()
            ->pluck('skill_category')
            ->toArray();
    }
}
