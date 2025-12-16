<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Model for tracking referral codes used during worker registration.
 */
class ReferralCode extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'code',
        'type',
        'is_active',
        'max_uses',
        'uses_count',
        'expires_at',
        'referrer_reward_amount',
        'referrer_reward_type',
        'referee_reward_amount',
        'referee_reward_type',
        'referee_shifts_required',
        'referee_days_required',
        'campaign_name',
        'campaign_source',
        'total_rewards_paid',
        'successful_conversions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'expires_at' => 'datetime',
        'referrer_reward_amount' => 'decimal:2',
        'referee_reward_amount' => 'decimal:2',
        'referee_shifts_required' => 'integer',
        'referee_days_required' => 'integer',
        'total_rewards_paid' => 'decimal:2',
        'successful_conversions' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = self::generateUniqueCode();
            }
        });
    }

    // ===================== Relationships =====================

    /**
     * Get the user who owns this referral code.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all referral usages for this code.
     */
    public function usages()
    {
        return $this->hasMany(ReferralUsage::class);
    }

    /**
     * Get successful referrals for this code.
     */
    public function successfulReferrals()
    {
        return $this->usages()->where('status', 'rewarded');
    }

    /**
     * Get pending referrals for this code.
     */
    public function pendingReferrals()
    {
        return $this->usages()->where('status', 'pending');
    }

    // ===================== Scopes =====================

    /**
     * Scope to only active codes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereColumn('uses_count', '<', 'max_uses');
            });
    }

    /**
     * Scope to find by code.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Scope to find by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ===================== Methods =====================

    /**
     * Generate a unique referral code.
     */
    public static function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Check if the code is valid for use.
     */
    public function isValid(): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check expiration
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Check max uses
        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Get the reason why code is invalid (if it is).
     */
    public function getInvalidReason(): ?string
    {
        if (!$this->is_active) {
            return 'This referral code has been deactivated.';
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'This referral code has expired.';
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return 'This referral code has reached its maximum uses.';
        }

        return null;
    }

    /**
     * Record a usage of this code.
     */
    public function recordUsage(User $referee, ?string $ip = null, ?string $userAgent = null): ReferralUsage
    {
        $usage = $this->usages()->create([
            'referrer_id' => $this->user_id,
            'referee_id' => $referee->id,
            'status' => 'pending',
            'registration_ip' => $ip,
            'registration_user_agent' => $userAgent,
        ]);

        $this->increment('uses_count');

        return $usage;
    }

    /**
     * Get statistics for this code.
     */
    public function getStatistics(): array
    {
        return [
            'total_uses' => $this->uses_count,
            'pending_referrals' => $this->usages()->where('status', 'pending')->count(),
            'qualified_referrals' => $this->usages()->where('status', 'qualified')->count(),
            'successful_referrals' => $this->successful_conversions,
            'total_rewards_paid' => $this->total_rewards_paid,
            'conversion_rate' => $this->uses_count > 0
                ? round(($this->successful_conversions / $this->uses_count) * 100, 2)
                : 0,
        ];
    }

    /**
     * Deactivate the code.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Extend expiration.
     */
    public function extendExpiration(int $days): void
    {
        $this->update([
            'expires_at' => ($this->expires_at ?? now())->addDays($days),
        ]);
    }
}
