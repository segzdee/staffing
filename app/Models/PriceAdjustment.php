<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GLO-009: Regional Pricing System - Price Adjustment Model
 *
 * Stores temporary or permanent price adjustments tied to regional pricing configurations.
 *
 * @property int $id
 * @property int $regional_pricing_id
 * @property string $adjustment_type
 * @property string|null $name
 * @property string|null $description
 * @property float $multiplier
 * @property float $fixed_adjustment
 * @property \Illuminate\Support\Carbon $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property array|null $conditions
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PriceAdjustment extends Model
{
    use HasFactory;

    /**
     * Adjustment type constants.
     */
    public const TYPE_SUBSCRIPTION = 'subscription';

    public const TYPE_SERVICE_FEE = 'service_fee';

    public const TYPE_SURGE = 'surge';

    public const TYPE_PROMOTIONAL = 'promotional';

    public const TYPE_SEASONAL = 'seasonal';

    public const TYPE_HOLIDAY = 'holiday';

    /**
     * All available adjustment types.
     */
    public const ADJUSTMENT_TYPES = [
        self::TYPE_SUBSCRIPTION => 'Subscription',
        self::TYPE_SERVICE_FEE => 'Service Fee',
        self::TYPE_SURGE => 'Surge Pricing',
        self::TYPE_PROMOTIONAL => 'Promotional',
        self::TYPE_SEASONAL => 'Seasonal',
        self::TYPE_HOLIDAY => 'Holiday',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'regional_pricing_id',
        'adjustment_type',
        'name',
        'description',
        'multiplier',
        'fixed_adjustment',
        'valid_from',
        'valid_until',
        'conditions',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'multiplier' => 'decimal:3',
            'fixed_adjustment' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'conditions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the regional pricing this adjustment belongs to.
     */
    public function regionalPricing(): BelongsTo
    {
        return $this->belongsTo(RegionalPricing::class);
    }

    /**
     * Get the user who created this adjustment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Only active adjustments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only currently valid adjustments.
     */
    public function scopeValid($query)
    {
        return $query->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    /**
     * Scope: Filter by adjustment type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('adjustment_type', $type);
    }

    /**
     * Check if this adjustment is currently valid.
     */
    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from > $now) {
            return false;
        }

        if ($this->valid_until && $this->valid_until < $now) {
            return false;
        }

        return true;
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::ADJUSTMENT_TYPES[$this->adjustment_type] ?? $this->adjustment_type;
    }

    /**
     * Get the status label.
     */
    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        $now = now();

        if ($this->valid_from > $now) {
            return 'Scheduled';
        }

        if ($this->valid_until && $this->valid_until < $now) {
            return 'Expired';
        }

        return 'Active';
    }

    /**
     * Apply this adjustment to a price.
     */
    public function applyToPrice(float $price): float
    {
        // First apply multiplier, then add/subtract fixed amount
        $adjustedPrice = $price * $this->multiplier;
        $adjustedPrice += $this->fixed_adjustment;

        return max(0, round($adjustedPrice, 2));
    }

    /**
     * Check if conditions are met for this adjustment.
     */
    public function conditionsMet(array $context = []): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        // Check time-based conditions
        if (isset($this->conditions['time_start']) && isset($this->conditions['time_end'])) {
            $currentTime = now()->format('H:i');
            if ($currentTime < $this->conditions['time_start'] || $currentTime > $this->conditions['time_end']) {
                return false;
            }
        }

        // Check day-of-week conditions
        if (isset($this->conditions['days_of_week'])) {
            $currentDay = now()->dayOfWeek;
            if (! in_array($currentDay, $this->conditions['days_of_week'])) {
                return false;
            }
        }

        // Check user type conditions
        if (isset($this->conditions['user_types']) && isset($context['user_type'])) {
            if (! in_array($context['user_type'], $this->conditions['user_types'])) {
                return false;
            }
        }

        // Check minimum amount conditions
        if (isset($this->conditions['min_amount']) && isset($context['amount'])) {
            if ($context['amount'] < $this->conditions['min_amount']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all adjustment types as options.
     */
    public static function getTypeOptions(): array
    {
        return self::ADJUSTMENT_TYPES;
    }

    /**
     * Find applicable adjustments for a regional pricing and type.
     */
    public static function findApplicable(
        int $regionalPricingId,
        string $type,
        array $context = []
    ): ?self {
        $adjustments = self::where('regional_pricing_id', $regionalPricingId)
            ->ofType($type)
            ->active()
            ->valid()
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($adjustments as $adjustment) {
            if ($adjustment->conditionsMet($context)) {
                return $adjustment;
            }
        }

        return null;
    }
}
