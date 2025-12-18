<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FIN-001: Volume Discount Tier Model
 *
 * Represents a volume-based discount tier that businesses can qualify for
 * based on their monthly shift posting volume.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $min_shifts_monthly
 * @property int|null $max_shifts_monthly
 * @property float $platform_fee_percent
 * @property float|null $min_monthly_spend
 * @property float|null $max_monthly_spend
 * @property array|null $benefits
 * @property string|null $badge_color
 * @property string|null $badge_icon
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_default
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class VolumeDiscountTier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'min_shifts_monthly',
        'max_shifts_monthly',
        'platform_fee_percent',
        'min_monthly_spend',
        'max_monthly_spend',
        'benefits',
        'badge_color',
        'badge_icon',
        'description',
        'is_active',
        'is_default',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_shifts_monthly' => 'integer',
        'max_shifts_monthly' => 'integer',
        'platform_fee_percent' => 'decimal:2',
        'min_monthly_spend' => 'decimal:2',
        'max_monthly_spend' => 'decimal:2',
        'benefits' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get all businesses currently at this tier.
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(BusinessProfile::class, 'current_volume_tier_id');
    }

    /**
     * Get all volume tracking records for this tier.
     */
    public function volumeTrackings(): HasMany
    {
        return $this->hasMany(BusinessVolumeTracking::class, 'applied_tier_id');
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get only active tiers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get tiers ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope to get the default tier.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to find tier for a given shift count.
     */
    public function scopeForShiftCount($query, int $shiftCount)
    {
        return $query->where('is_active', true)
            ->where('min_shifts_monthly', '<=', $shiftCount)
            ->where(function ($q) use ($shiftCount) {
                $q->whereNull('max_shifts_monthly')
                    ->orWhere('max_shifts_monthly', '>=', $shiftCount);
            })
            ->orderBy('min_shifts_monthly', 'desc');
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * Get the default tier for new businesses.
     */
    public static function getDefaultTier(): ?self
    {
        return static::query()
            ->active()
            ->default()
            ->first() ?? static::query()
            ->active()
            ->ordered()
            ->first();
    }

    /**
     * Get the appropriate tier for a given shift count.
     */
    public static function getTierForShiftCount(int $shiftCount): ?self
    {
        return static::query()
            ->forShiftCount($shiftCount)
            ->first();
    }

    /**
     * Get all active tiers in order.
     */
    public static function getActiveTiers()
    {
        return static::query()
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Find a tier by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::query()
            ->where('slug', $slug)
            ->first();
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the shift range display string.
     */
    public function getShiftRangeAttribute(): string
    {
        if ($this->max_shifts_monthly === null) {
            return "{$this->min_shifts_monthly}+ shifts/mo";
        }

        if ($this->min_shifts_monthly === 0) {
            return "0-{$this->max_shifts_monthly} shifts/mo";
        }

        return "{$this->min_shifts_monthly}-{$this->max_shifts_monthly} shifts/mo";
    }

    /**
     * Get the fee percent display string.
     */
    public function getFeeDisplayAttribute(): string
    {
        return number_format($this->platform_fee_percent, 1).'%';
    }

    /**
     * Get discount percentage compared to default rate.
     */
    public function getDiscountPercentageAttribute(): float
    {
        $defaultRate = config('overtimestaff.financial.platform_fee_rate', 35.00);

        if ($defaultRate <= 0) {
            return 0;
        }

        return round((($defaultRate - $this->platform_fee_percent) / $defaultRate) * 100, 1);
    }

    /**
     * Get savings description.
     */
    public function getSavingsDescriptionAttribute(): string
    {
        $discount = $this->discount_percentage;

        if ($discount <= 0) {
            return 'Standard pricing';
        }

        return "Save {$discount}% on platform fees";
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * Check if a shift count qualifies for this tier.
     */
    public function qualifiesForShiftCount(int $shiftCount): bool
    {
        if ($shiftCount < $this->min_shifts_monthly) {
            return false;
        }

        if ($this->max_shifts_monthly !== null && $shiftCount > $this->max_shifts_monthly) {
            return false;
        }

        return true;
    }

    /**
     * Check if a spend amount qualifies for this tier.
     */
    public function qualifiesForSpend(float $spend): bool
    {
        if ($this->min_monthly_spend !== null && $spend < $this->min_monthly_spend) {
            return false;
        }

        if ($this->max_monthly_spend !== null && $spend > $this->max_monthly_spend) {
            return false;
        }

        return true;
    }

    /**
     * Get the next tier (higher level).
     */
    public function getNextTier(): ?self
    {
        return static::query()
            ->active()
            ->where('min_shifts_monthly', '>', $this->min_shifts_monthly)
            ->orderBy('min_shifts_monthly')
            ->first();
    }

    /**
     * Get the previous tier (lower level).
     */
    public function getPreviousTier(): ?self
    {
        return static::query()
            ->active()
            ->where('min_shifts_monthly', '<', $this->min_shifts_monthly)
            ->orderBy('min_shifts_monthly', 'desc')
            ->first();
    }

    /**
     * Calculate shifts needed to reach this tier from a given count.
     */
    public function shiftsNeededFrom(int $currentShifts): int
    {
        return max(0, $this->min_shifts_monthly - $currentShifts);
    }

    /**
     * Get a specific benefit from the benefits array.
     */
    public function getBenefit(string $key, $default = null)
    {
        return $this->benefits[$key] ?? $default;
    }

    /**
     * Check if this tier has a specific benefit.
     */
    public function hasBenefit(string $key): bool
    {
        return isset($this->benefits[$key]) && $this->benefits[$key];
    }
}
