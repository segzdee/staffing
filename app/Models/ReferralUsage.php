<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Model for tracking individual referral code usages.
 */
class ReferralUsage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'referral_code_id',
        'referrer_id',
        'referee_id',
        'status',
        'referee_shifts_completed',
        'qualification_met_at',
        'referrer_reward_paid',
        'referee_reward_paid',
        'referrer_reward_paid_at',
        'referee_reward_paid_at',
        'registration_ip',
        'registration_user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'referee_shifts_completed' => 'integer',
        'qualification_met_at' => 'datetime',
        'referrer_reward_paid' => 'decimal:2',
        'referee_reward_paid' => 'decimal:2',
        'referrer_reward_paid_at' => 'datetime',
        'referee_reward_paid_at' => 'datetime',
    ];

    // ===================== Relationships =====================

    /**
     * Get the referral code.
     */
    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    /**
     * Get the referrer (user who shared the code).
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the referee (user who used the code).
     */
    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    // ===================== Scopes =====================

    /**
     * Scope to pending usages.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to qualified usages.
     */
    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }

    /**
     * Scope to rewarded usages.
     */
    public function scopeRewarded($query)
    {
        return $query->where('status', 'rewarded');
    }

    // ===================== Methods =====================

    /**
     * Check if the referral has met qualification requirements.
     */
    public function hasMetQualification(): bool
    {
        $code = $this->referralCode;

        if ($code->referee_shifts_required > 0) {
            if ($this->referee_shifts_completed < $code->referee_shifts_required) {
                return false;
            }
        }

        if ($code->referee_days_required > 0) {
            $daysSinceRegistration = $this->referee->created_at->diffInDays(now());
            if ($daysSinceRegistration < $code->referee_days_required) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark referral as qualified.
     */
    public function markAsQualified(): void
    {
        $this->update([
            'status' => 'qualified',
            'qualification_met_at' => now(),
        ]);
    }

    /**
     * Mark referral as rewarded and record payouts.
     */
    public function markAsRewarded(float $referrerReward, float $refereeReward): void
    {
        $this->update([
            'status' => 'rewarded',
            'referrer_reward_paid' => $referrerReward,
            'referee_reward_paid' => $refereeReward,
            'referrer_reward_paid_at' => $referrerReward > 0 ? now() : null,
            'referee_reward_paid_at' => $refereeReward > 0 ? now() : null,
        ]);

        // Update the referral code statistics
        $this->referralCode->increment('successful_conversions');
        $this->referralCode->increment('total_rewards_paid', $referrerReward + $refereeReward);
    }

    /**
     * Increment referee's shift count and check qualification.
     */
    public function recordRefereeShiftCompleted(): bool
    {
        $this->increment('referee_shifts_completed');
        $this->refresh();

        if ($this->status === 'pending' && $this->hasMetQualification()) {
            $this->markAsQualified();
            return true;
        }

        return false;
    }

    /**
     * Expire the referral (e.g., if referee becomes inactive).
     */
    public function expire(string $reason = null): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Cancel the referral.
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }
}
