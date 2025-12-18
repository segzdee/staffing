<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property int $points
 * @property int $balance_after
 * @property string $description
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Model|null $reference
 */
class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $table = 'loyalty_transactions';

    protected $fillable = [
        'user_id',
        'type',
        'points',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'expires_at',
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
            'balance_after' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Transaction types
     */
    public const TYPE_EARNED = 'earned';

    public const TYPE_REDEEMED = 'redeemed';

    public const TYPE_EXPIRED = 'expired';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_ADJUSTMENT = 'adjustment';

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic reference relationship
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if points are expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if this is a credit (positive points)
     */
    public function isCredit(): bool
    {
        return $this->points > 0;
    }

    /**
     * Check if this is a debit (negative points)
     */
    public function isDebit(): bool
    {
        return $this->points < 0;
    }

    /**
     * Scope: Earned transactions
     */
    public function scopeEarned($query)
    {
        return $query->where('type', self::TYPE_EARNED);
    }

    /**
     * Scope: Redeemed transactions
     */
    public function scopeRedeemed($query)
    {
        return $query->where('type', self::TYPE_REDEEMED);
    }

    /**
     * Scope: Bonus transactions
     */
    public function scopeBonus($query)
    {
        return $query->where('type', self::TYPE_BONUS);
    }

    /**
     * Scope: Not expired
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: Expired
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope: By reference
     */
    public function scopeForReference($query, Model $reference)
    {
        return $query->where('reference_type', get_class($reference))
            ->where('reference_id', $reference->getKey());
    }
}
