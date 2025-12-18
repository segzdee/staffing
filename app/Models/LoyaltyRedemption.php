<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $loyalty_reward_id
 * @property int $points_spent
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $fulfilled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\LoyaltyReward $reward
 */
class LoyaltyRedemption extends Model
{
    use HasFactory;

    protected $table = 'loyalty_redemptions';

    protected $fillable = [
        'user_id',
        'loyalty_reward_id',
        'points_spent',
        'status',
        'fulfilled_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_spent' => 'integer',
            'fulfilled_at' => 'datetime',
        ];
    }

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Reward relationship
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class, 'loyalty_reward_id');
    }

    /**
     * Mark as fulfilled
     */
    public function markFulfilled(): void
    {
        $this->update([
            'status' => self::STATUS_FULFILLED,
            'fulfilled_at' => now(),
        ]);
    }

    /**
     * Mark as cancelled
     */
    public function markCancelled(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Check if pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if fulfilled
     */
    public function isFulfilled(): bool
    {
        return $this->status === self::STATUS_FULFILLED;
    }

    /**
     * Check if cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Scope: Pending
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Fulfilled
     */
    public function scopeFulfilled($query)
    {
        return $query->where('status', self::STATUS_FULFILLED);
    }

    /**
     * Scope: Cancelled
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope: For user
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}
