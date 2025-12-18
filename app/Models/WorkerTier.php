<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WKR-007: Worker Career Tiers System
 *
 * Represents the career progression tiers available to workers.
 * Tiers provide various benefits like fee discounts, priority booking,
 * and access to premium shifts.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $level
 * @property int $min_shifts_completed
 * @property float $min_rating
 * @property int $min_hours_worked
 * @property int $min_months_active
 * @property float $fee_discount_percent
 * @property int $priority_booking_hours
 * @property bool $instant_payout
 * @property bool $premium_shifts_access
 * @property array|null $additional_benefits
 * @property string $badge_color
 * @property string|null $badge_icon
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkerProfile> $workerProfiles
 * @property-read int|null $worker_profiles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkerTierHistory> $tierHistory
 * @property-read int|null $tier_history_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTier active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTier whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerTier whereLevel($value)
 */
class WorkerTier extends Model
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
        'level',
        'min_shifts_completed',
        'min_rating',
        'min_hours_worked',
        'min_months_active',
        'fee_discount_percent',
        'priority_booking_hours',
        'instant_payout',
        'premium_shifts_access',
        'additional_benefits',
        'badge_color',
        'badge_icon',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level' => 'integer',
        'min_shifts_completed' => 'integer',
        'min_rating' => 'decimal:2',
        'min_hours_worked' => 'integer',
        'min_months_active' => 'integer',
        'fee_discount_percent' => 'decimal:2',
        'priority_booking_hours' => 'integer',
        'instant_payout' => 'boolean',
        'premium_shifts_access' => 'boolean',
        'additional_benefits' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Tier level constants.
     */
    public const LEVEL_ROOKIE = 1;

    public const LEVEL_REGULAR = 2;

    public const LEVEL_PRO = 3;

    public const LEVEL_ELITE = 4;

    public const LEVEL_LEGEND = 5;

    /**
     * Tier slug constants.
     */
    public const SLUG_ROOKIE = 'rookie';

    public const SLUG_REGULAR = 'regular';

    public const SLUG_PRO = 'pro';

    public const SLUG_ELITE = 'elite';

    public const SLUG_LEGEND = 'legend';

    /**
     * Get workers currently at this tier.
     */
    public function workerProfiles(): HasMany
    {
        return $this->hasMany(WorkerProfile::class, 'worker_tier_id');
    }

    /**
     * Get tier history records where workers transitioned to this tier.
     */
    public function tierHistory(): HasMany
    {
        return $this->hasMany(WorkerTierHistory::class, 'to_tier_id');
    }

    /**
     * Scope to only include active tiers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by level ascending.
     */
    public function scopeOrderByLevel($query)
    {
        return $query->orderBy('level', 'asc');
    }

    /**
     * Get the next tier above this one.
     */
    public function getNextTier(): ?self
    {
        return self::active()
            ->where('level', '>', $this->level)
            ->orderBy('level', 'asc')
            ->first();
    }

    /**
     * Get the previous tier below this one.
     */
    public function getPreviousTier(): ?self
    {
        return self::active()
            ->where('level', '<', $this->level)
            ->orderBy('level', 'desc')
            ->first();
    }

    /**
     * Get the starter/default tier.
     */
    public static function getDefaultTier(): ?self
    {
        return self::active()
            ->orderBy('level', 'asc')
            ->first();
    }

    /**
     * Find a tier by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->first();
    }

    /**
     * Find a tier by level.
     */
    public static function findByLevel(int $level): ?self
    {
        return self::where('level', $level)->first();
    }

    /**
     * Get all benefits as a formatted array.
     */
    public function getAllBenefits(): array
    {
        $benefits = [];

        if ($this->fee_discount_percent > 0) {
            $benefits[] = "{$this->fee_discount_percent}% fee discount";
        }

        if ($this->priority_booking_hours > 0) {
            $benefits[] = "{$this->priority_booking_hours}h early access to shifts";
        }

        if ($this->instant_payout) {
            $benefits[] = 'Instant payout available';
        }

        if ($this->premium_shifts_access) {
            $benefits[] = 'Access to premium shifts';
        }

        // Add any additional custom benefits
        if (! empty($this->additional_benefits)) {
            $benefits = array_merge($benefits, $this->additional_benefits);
        }

        return $benefits;
    }

    /**
     * Check if worker metrics meet this tier's requirements.
     */
    public function meetsRequirements(array $metrics): bool
    {
        return $metrics['shifts_completed'] >= $this->min_shifts_completed
            && $metrics['rating'] >= $this->min_rating
            && $metrics['hours_worked'] >= $this->min_hours_worked
            && $metrics['months_active'] >= $this->min_months_active;
    }

    /**
     * Get progress towards meeting this tier's requirements.
     */
    public function getProgressTowards(array $metrics): array
    {
        return [
            'shifts' => [
                'current' => $metrics['shifts_completed'],
                'required' => $this->min_shifts_completed,
                'remaining' => max(0, $this->min_shifts_completed - $metrics['shifts_completed']),
                'percent' => $this->min_shifts_completed > 0
                    ? min(100, round(($metrics['shifts_completed'] / $this->min_shifts_completed) * 100))
                    : 100,
            ],
            'rating' => [
                'current' => $metrics['rating'],
                'required' => $this->min_rating,
                'remaining' => max(0, $this->min_rating - $metrics['rating']),
                'percent' => $this->min_rating > 0
                    ? min(100, round(($metrics['rating'] / $this->min_rating) * 100))
                    : 100,
            ],
            'hours' => [
                'current' => $metrics['hours_worked'],
                'required' => $this->min_hours_worked,
                'remaining' => max(0, $this->min_hours_worked - $metrics['hours_worked']),
                'percent' => $this->min_hours_worked > 0
                    ? min(100, round(($metrics['hours_worked'] / $this->min_hours_worked) * 100))
                    : 100,
            ],
            'months' => [
                'current' => $metrics['months_active'],
                'required' => $this->min_months_active,
                'remaining' => max(0, $this->min_months_active - $metrics['months_active']),
                'percent' => $this->min_months_active > 0
                    ? min(100, round(($metrics['months_active'] / $this->min_months_active) * 100))
                    : 100,
            ],
        ];
    }

    /**
     * Get display attributes for the badge.
     */
    public function getBadgeAttributes(): array
    {
        return [
            'name' => $this->name,
            'color' => $this->badge_color,
            'icon' => $this->badge_icon ?? $this->getDefaultIcon(),
            'level' => $this->level,
        ];
    }

    /**
     * Get default icon based on tier level.
     */
    protected function getDefaultIcon(): string
    {
        return match ($this->level) {
            1 => 'seedling',          // Rookie
            2 => 'user-check',        // Regular
            3 => 'award',             // Pro
            4 => 'star',              // Elite
            5 => 'crown',             // Legend
            default => 'badge',
        };
    }

    /**
     * Get the number of workers currently at this tier.
     */
    public function getWorkerCount(): int
    {
        return $this->workerProfiles()->count();
    }

    /**
     * Check if this is the highest tier.
     */
    public function isHighestTier(): bool
    {
        return ! self::active()
            ->where('level', '>', $this->level)
            ->exists();
    }

    /**
     * Check if this is the lowest/default tier.
     */
    public function isLowestTier(): bool
    {
        return ! self::active()
            ->where('level', '<', $this->level)
            ->exists();
    }
}
