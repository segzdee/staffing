<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AgencyTier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'level',
        'min_monthly_revenue',
        'min_active_workers',
        'min_fill_rate',
        'min_rating',
        'commission_rate',
        'priority_booking_hours',
        'dedicated_support',
        'custom_branding',
        'api_access',
        'additional_benefits',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_monthly_revenue' => 'decimal:2',
            'min_active_workers' => 'integer',
            'min_fill_rate' => 'decimal:2',
            'min_rating' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'priority_booking_hours' => 'integer',
            'dedicated_support' => 'boolean',
            'custom_branding' => 'boolean',
            'api_access' => 'boolean',
            'additional_benefits' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($tier) {
            if (empty($tier->slug)) {
                $tier->slug = Str::slug($tier->name);
            }
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get agencies with this tier.
     */
    public function agencyProfiles(): HasMany
    {
        return $this->hasMany(AgencyProfile::class, 'agency_tier_id');
    }

    /**
     * Get tier history entries where this was the source tier.
     */
    public function historyAsFromTier(): HasMany
    {
        return $this->hasMany(AgencyTierHistory::class, 'from_tier_id');
    }

    /**
     * Get tier history entries where this was the destination tier.
     */
    public function historyAsToTier(): HasMany
    {
        return $this->hasMany(AgencyTierHistory::class, 'to_tier_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to only active tiers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by level ascending.
     */
    public function scopeOrderByLevel($query, string $direction = 'asc')
    {
        return $query->orderBy('level', $direction);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get badge color class based on tier level.
     */
    public function getBadgeColorAttribute(): string
    {
        return match ($this->level) {
            1 => 'bg-amber-700 text-amber-100',      // Bronze
            2 => 'bg-gray-400 text-gray-900',        // Silver
            3 => 'bg-yellow-500 text-yellow-900',    // Gold
            4 => 'bg-purple-600 text-purple-100',    // Platinum
            5 => 'bg-cyan-400 text-cyan-900',        // Diamond
            default => 'bg-gray-200 text-gray-700',
        };
    }

    /**
     * Get tier icon based on level.
     */
    public function getIconAttribute(): string
    {
        return match ($this->level) {
            1 => 'heroicon-o-shield-check',
            2 => 'heroicon-o-star',
            3 => 'heroicon-o-trophy',
            4 => 'heroicon-o-sparkles',
            5 => 'heroicon-o-bolt',
            default => 'heroicon-o-bookmark',
        };
    }

    /**
     * Get formatted commission rate.
     */
    public function getFormattedCommissionRateAttribute(): string
    {
        return number_format($this->commission_rate, 1).'%';
    }

    /**
     * Get formatted minimum revenue.
     */
    public function getFormattedMinRevenueAttribute(): string
    {
        return '$'.number_format($this->min_monthly_revenue, 0);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get the next tier level.
     */
    public function getNextTier(): ?self
    {
        return static::active()
            ->where('level', '>', $this->level)
            ->orderBy('level')
            ->first();
    }

    /**
     * Get the previous tier level.
     */
    public function getPreviousTier(): ?self
    {
        return static::active()
            ->where('level', '<', $this->level)
            ->orderBy('level', 'desc')
            ->first();
    }

    /**
     * Check if this is the highest tier.
     */
    public function isHighestTier(): bool
    {
        return ! static::active()->where('level', '>', $this->level)->exists();
    }

    /**
     * Check if this is the lowest tier.
     */
    public function isLowestTier(): bool
    {
        return ! static::active()->where('level', '<', $this->level)->exists();
    }

    /**
     * Get all benefits as a formatted array.
     */
    public function getAllBenefits(): array
    {
        $benefits = [];

        if ($this->priority_booking_hours > 0) {
            $benefits[] = "{$this->priority_booking_hours}h priority booking window";
        }

        if ($this->dedicated_support) {
            $benefits[] = 'Dedicated support representative';
        }

        if ($this->custom_branding) {
            $benefits[] = 'Custom branding options';
        }

        if ($this->api_access) {
            $benefits[] = 'API access';
        }

        $benefits[] = "{$this->formatted_commission_rate} commission rate";

        // Add additional benefits from JSON
        if (! empty($this->additional_benefits)) {
            $benefits = array_merge($benefits, $this->additional_benefits);
        }

        return $benefits;
    }

    /**
     * Get requirements for this tier.
     */
    public function getRequirements(): array
    {
        $requirements = [];

        if ($this->min_monthly_revenue > 0) {
            $requirements['revenue'] = [
                'label' => 'Monthly Revenue',
                'value' => $this->min_monthly_revenue,
                'formatted' => $this->formatted_min_revenue,
            ];
        }

        if ($this->min_active_workers > 0) {
            $requirements['workers'] = [
                'label' => 'Active Workers',
                'value' => $this->min_active_workers,
                'formatted' => number_format($this->min_active_workers),
            ];
        }

        if ($this->min_fill_rate > 0) {
            $requirements['fill_rate'] = [
                'label' => 'Fill Rate',
                'value' => $this->min_fill_rate,
                'formatted' => number_format($this->min_fill_rate, 1).'%',
            ];
        }

        if ($this->min_rating > 0) {
            $requirements['rating'] = [
                'label' => 'Average Rating',
                'value' => $this->min_rating,
                'formatted' => number_format($this->min_rating, 2),
            ];
        }

        return $requirements;
    }

    /**
     * Check if agency metrics meet tier requirements.
     *
     * @param  array{monthly_revenue: float, active_workers: int, fill_rate: float, rating: float}  $metrics
     */
    public function meetsRequirements(array $metrics): bool
    {
        if ($metrics['monthly_revenue'] < $this->min_monthly_revenue) {
            return false;
        }

        if ($metrics['active_workers'] < $this->min_active_workers) {
            return false;
        }

        if ($this->min_fill_rate > 0 && $metrics['fill_rate'] < $this->min_fill_rate) {
            return false;
        }

        if ($this->min_rating > 0 && $metrics['rating'] < $this->min_rating) {
            return false;
        }

        return true;
    }
}
