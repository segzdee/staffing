<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $points_required
 * @property string $type
 * @property array<string, mixed>|null $reward_data
 * @property int|null $quantity_available
 * @property int $quantity_redeemed
 * @property bool $is_active
 * @property string $min_tier
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LoyaltyRedemption> $redemptions
 * @property-read int $quantity_remaining
 */
class LoyaltyReward extends Model
{
    use HasFactory;

    protected $table = 'loyalty_rewards';

    protected $fillable = [
        'name',
        'description',
        'points_required',
        'type',
        'reward_data',
        'quantity_available',
        'quantity_redeemed',
        'is_active',
        'min_tier',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_required' => 'integer',
            'reward_data' => 'array',
            'quantity_available' => 'integer',
            'quantity_redeemed' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Reward types
     */
    public const TYPE_CASH_BONUS = 'cash_bonus';

    public const TYPE_FEE_DISCOUNT = 'fee_discount';

    public const TYPE_PRIORITY_MATCHING = 'priority_matching';

    public const TYPE_BADGE = 'badge';

    public const TYPE_MERCH = 'merch';

    /**
     * Redemptions relationship
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRedemption::class);
    }

    /**
     * Check if reward is available
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->quantity_available === null) {
            return true; // Unlimited
        }

        return $this->quantity_available > $this->quantity_redeemed;
    }

    /**
     * Get remaining quantity
     */
    public function getQuantityRemainingAttribute(): ?int
    {
        if ($this->quantity_available === null) {
            return null; // Unlimited
        }

        return max(0, $this->quantity_available - $this->quantity_redeemed);
    }

    /**
     * Check if user meets tier requirement
     */
    public function userMeetsTierRequirement(User $user): bool
    {
        $loyaltyPoints = $user->loyaltyPoints;

        if (! $loyaltyPoints) {
            return $this->min_tier === 'bronze';
        }

        return $loyaltyPoints->tierIsAtLeast($this->min_tier);
    }

    /**
     * Check if user can redeem this reward
     */
    public function canBeRedeemedBy(User $user): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        if (! $this->userMeetsTierRequirement($user)) {
            return false;
        }

        $loyaltyPoints = $user->loyaltyPoints;

        if (! $loyaltyPoints || $loyaltyPoints->points < $this->points_required) {
            return false;
        }

        return true;
    }

    /**
     * Get reward value (cash amount, discount percent, etc.)
     */
    public function getRewardValue(string $key, $default = null)
    {
        return $this->reward_data[$key] ?? $default;
    }

    /**
     * Increment redeemed count
     */
    public function incrementRedeemed(): void
    {
        $this->increment('quantity_redeemed');
    }

    /**
     * Scope: Active rewards
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Available (active and in stock)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('quantity_available')
                    ->orWhereColumn('quantity_available', '>', 'quantity_redeemed');
            });
    }

    /**
     * Scope: By type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: For tier
     */
    public function scopeForTier($query, string $tier)
    {
        $tierOrder = LoyaltyPoints::TIER_ORDER;
        $userTierLevel = $tierOrder[$tier] ?? 0;

        return $query->where(function ($q) use ($tierOrder, $userTierLevel) {
            foreach ($tierOrder as $tierName => $level) {
                if ($level <= $userTierLevel) {
                    $q->orWhere('min_tier', $tierName);
                }
            }
        });
    }

    /**
     * Scope: Affordable by points
     */
    public function scopeAffordable($query, int $points)
    {
        return $query->where('points_required', '<=', $points);
    }
}
