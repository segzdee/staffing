<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $points
 * @property int $lifetime_points
 * @property string $tier
 * @property \Illuminate\Support\Carbon|null $tier_expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoyaltyTransaction> $transactions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoyaltyRedemption> $redemptions
 */
class LoyaltyPoints extends Model
{
    use HasFactory;

    protected $table = 'loyalty_points';

    protected $fillable = [
        'user_id',
        'points',
        'lifetime_points',
        'tier',
        'tier_expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'lifetime_points' => 'integer',
            'tier_expires_at' => 'datetime',
        ];
    }

    /**
     * Tier hierarchy for comparison
     */
    public const TIER_ORDER = [
        'bronze' => 0,
        'silver' => 1,
        'gold' => 2,
        'platinum' => 3,
    ];

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All transactions for this account
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'user_id', 'user_id');
    }

    /**
     * All redemptions for this account
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRedemption::class, 'user_id', 'user_id');
    }

    /**
     * Check if tier is expired
     */
    public function isTierExpired(): bool
    {
        if ($this->tier === 'bronze') {
            return false; // Bronze never expires
        }

        return $this->tier_expires_at && $this->tier_expires_at->isPast();
    }

    /**
     * Check if user meets tier threshold
     */
    public function meetsTierThreshold(string $tier): bool
    {
        $thresholds = config('loyalty.tier_thresholds', [
            'bronze' => 0,
            'silver' => 500,
            'gold' => 2000,
            'platinum' => 5000,
        ]);

        return $this->lifetime_points >= ($thresholds[$tier] ?? PHP_INT_MAX);
    }

    /**
     * Get progress to next tier
     *
     * @return array{current_tier: string, next_tier: string|null, points_needed: int, progress_percent: float}
     */
    public function getNextTierProgress(): array
    {
        $thresholds = config('loyalty.tier_thresholds', [
            'bronze' => 0,
            'silver' => 500,
            'gold' => 2000,
            'platinum' => 5000,
        ]);

        $tiers = array_keys($thresholds);
        $currentIndex = array_search($this->tier, $tiers);
        $nextIndex = $currentIndex + 1;

        if ($nextIndex >= count($tiers)) {
            return [
                'current_tier' => $this->tier,
                'next_tier' => null,
                'points_needed' => 0,
                'progress_percent' => 100.0,
            ];
        }

        $nextTier = $tiers[$nextIndex];
        $currentThreshold = $thresholds[$this->tier];
        $nextThreshold = $thresholds[$nextTier];
        $pointsNeeded = max(0, $nextThreshold - $this->lifetime_points);
        $progressPercent = min(100, (($this->lifetime_points - $currentThreshold) / ($nextThreshold - $currentThreshold)) * 100);

        return [
            'current_tier' => $this->tier,
            'next_tier' => $nextTier,
            'points_needed' => $pointsNeeded,
            'progress_percent' => round($progressPercent, 1),
        ];
    }

    /**
     * Compare tier level
     */
    public function tierIsAtLeast(string $tier): bool
    {
        return self::TIER_ORDER[$this->tier] >= (self::TIER_ORDER[$tier] ?? 0);
    }

    /**
     * Scope: By tier
     */
    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    /**
     * Scope: With points at least
     */
    public function scopeWithMinPoints($query, int $points)
    {
        return $query->where('points', '>=', $points);
    }
}
