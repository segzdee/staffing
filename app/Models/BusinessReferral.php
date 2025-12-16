<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * BusinessReferral Model
 * BIZ-REG-002: Tracks business referrals
 *
 * @property int $id
 * @property int $referrer_business_id
 * @property int $referrer_user_id
 * @property int|null $referred_business_id
 * @property int|null $referred_user_id
 * @property string $referral_code
 * @property string $referred_email
 * @property string|null $referred_company_name
 * @property string $status
 * @property bool $reward_eligible
 * @property float|null $referrer_reward_amount
 * @property float|null $referred_reward_amount
 * @property string|null $reward_type
 * @property \Carbon\Carbon|null $reward_issued_at
 * @property string|null $reward_transaction_id
 * @property int $required_shifts_posted
 * @property int $actual_shifts_posted
 * @property float|null $required_spend_amount
 * @property float $actual_spend_amount
 * @property int $qualification_days
 * @property \Carbon\Carbon|null $qualification_deadline
 * @property \Carbon\Carbon|null $invitation_sent_at
 * @property \Carbon\Carbon|null $link_clicked_at
 * @property \Carbon\Carbon|null $registered_at
 * @property \Carbon\Carbon|null $activated_at
 * @property \Carbon\Carbon|null $first_shift_at
 * @property \Carbon\Carbon|null $qualified_at
 * @property int $reminder_count
 * @property \Carbon\Carbon|null $last_reminder_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read BusinessProfile $referrerBusiness
 * @property-read User $referrerUser
 * @property-read BusinessProfile|null $referredBusiness
 * @property-read User|null $referredUser
 */
class BusinessReferral extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'referrer_business_id',
        'referrer_user_id',
        'referred_business_id',
        'referred_user_id',
        'referral_code',
        'referred_email',
        'referred_company_name',
        'status',
        'reward_eligible',
        'referrer_reward_amount',
        'referred_reward_amount',
        'reward_type',
        'reward_issued_at',
        'reward_transaction_id',
        'required_shifts_posted',
        'actual_shifts_posted',
        'required_spend_amount',
        'actual_spend_amount',
        'qualification_days',
        'qualification_deadline',
        'invitation_sent_at',
        'link_clicked_at',
        'registered_at',
        'activated_at',
        'first_shift_at',
        'qualified_at',
        'reminder_count',
        'last_reminder_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'reward_eligible' => 'boolean',
        'referrer_reward_amount' => 'decimal:2',
        'referred_reward_amount' => 'decimal:2',
        'required_spend_amount' => 'decimal:2',
        'actual_spend_amount' => 'decimal:2',
        'reward_issued_at' => 'datetime',
        'qualification_deadline' => 'datetime',
        'invitation_sent_at' => 'datetime',
        'link_clicked_at' => 'datetime',
        'registered_at' => 'datetime',
        'activated_at' => 'datetime',
        'first_shift_at' => 'datetime',
        'qualified_at' => 'datetime',
        'last_reminder_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_CLICKED = 'clicked';
    const STATUS_REGISTERED = 'registered';
    const STATUS_ACTIVATED = 'activated';
    const STATUS_FIRST_SHIFT = 'first_shift';
    const STATUS_QUALIFIED = 'qualified';
    const STATUS_REWARDED = 'rewarded';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Reward type constants
     */
    const REWARD_CREDIT = 'credit';
    const REWARD_DISCOUNT = 'discount';
    const REWARD_CASH = 'cash';

    /**
     * Boot method to generate referral code.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->referral_code)) {
                $model->referral_code = self::generateUniqueCode();
            }
        });
    }

    /**
     * Generate a unique referral code.
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Get the referrer business.
     */
    public function referrerBusiness()
    {
        return $this->belongsTo(BusinessProfile::class, 'referrer_business_id');
    }

    /**
     * Get the referrer user.
     */
    public function referrerUser()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * Get the referred business.
     */
    public function referredBusiness()
    {
        return $this->belongsTo(BusinessProfile::class, 'referred_business_id');
    }

    /**
     * Get the referred user.
     */
    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    /**
     * Create a new referral invitation.
     */
    public static function createInvitation(
        BusinessProfile $referrerBusiness,
        User $referrerUser,
        string $email,
        ?string $companyName = null
    ): self {
        return self::create([
            'referrer_business_id' => $referrerBusiness->id,
            'referrer_user_id' => $referrerUser->id,
            'referred_email' => $email,
            'referred_company_name' => $companyName,
            'status' => self::STATUS_PENDING,
            'invitation_sent_at' => now(),
            'qualification_deadline' => now()->addDays(30),
        ]);
    }

    /**
     * Record that the referral link was clicked.
     */
    public function recordClick(?string $ipAddress = null, ?string $userAgent = null): void
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->update([
                'status' => self::STATUS_CLICKED,
                'link_clicked_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        }
    }

    /**
     * Record that the referred business registered.
     */
    public function recordRegistration(BusinessProfile $business, User $user): void
    {
        $this->update([
            'referred_business_id' => $business->id,
            'referred_user_id' => $user->id,
            'status' => self::STATUS_REGISTERED,
            'registered_at' => now(),
        ]);
    }

    /**
     * Record that the referred business activated.
     */
    public function recordActivation(): void
    {
        if (in_array($this->status, [self::STATUS_REGISTERED, self::STATUS_CLICKED])) {
            $this->update([
                'status' => self::STATUS_ACTIVATED,
                'activated_at' => now(),
            ]);
        }
    }

    /**
     * Record the first shift posted.
     */
    public function recordFirstShift(): void
    {
        if ($this->status === self::STATUS_ACTIVATED) {
            $this->update([
                'status' => self::STATUS_FIRST_SHIFT,
                'first_shift_at' => now(),
                'actual_shifts_posted' => 1,
            ]);

            $this->checkQualification();
        }
    }

    /**
     * Update shift count and check qualification.
     */
    public function incrementShiftCount(): void
    {
        $this->increment('actual_shifts_posted');
        $this->checkQualification();
    }

    /**
     * Update spend amount and check qualification.
     */
    public function addSpend(float $amount): void
    {
        $this->increment('actual_spend_amount', $amount);
        $this->checkQualification();
    }

    /**
     * Check if qualification requirements are met.
     */
    public function checkQualification(): bool
    {
        if ($this->status === self::STATUS_QUALIFIED || $this->status === self::STATUS_REWARDED) {
            return true;
        }

        if ($this->isExpired()) {
            $this->update(['status' => self::STATUS_EXPIRED]);
            return false;
        }

        $shiftsQualified = $this->actual_shifts_posted >= $this->required_shifts_posted;
        $spendQualified = is_null($this->required_spend_amount)
            || $this->actual_spend_amount >= $this->required_spend_amount;

        if ($shiftsQualified && $spendQualified) {
            $this->update([
                'status' => self::STATUS_QUALIFIED,
                'qualified_at' => now(),
                'reward_eligible' => true,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Check if referral has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->qualification_deadline) {
            return false;
        }

        return now()->isAfter($this->qualification_deadline);
    }

    /**
     * Issue rewards.
     */
    public function issueRewards(float $referrerAmount, float $referredAmount, string $type = self::REWARD_CREDIT, ?string $transactionId = null): void
    {
        $this->update([
            'status' => self::STATUS_REWARDED,
            'referrer_reward_amount' => $referrerAmount,
            'referred_reward_amount' => $referredAmount,
            'reward_type' => $type,
            'reward_issued_at' => now(),
            'reward_transaction_id' => $transactionId,
        ]);
    }

    /**
     * Cancel the referral.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Record a reminder sent.
     */
    public function recordReminderSent(): void
    {
        $this->update([
            'reminder_count' => $this->reminder_count + 1,
            'last_reminder_at' => now(),
        ]);
    }

    /**
     * Get referral URL.
     */
    public function getReferralUrl(): string
    {
        return url("/register/business?ref={$this->referral_code}");
    }

    /**
     * Get days remaining until deadline.
     */
    public function getDaysRemaining(): ?int
    {
        if (!$this->qualification_deadline) {
            return null;
        }

        $days = now()->diffInDays($this->qualification_deadline, false);
        return max(0, $days);
    }

    /**
     * Scope to get active referrals.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED,
            self::STATUS_REWARDED,
        ]);
    }

    /**
     * Scope to get qualified referrals.
     */
    public function scopeQualified($query)
    {
        return $query->where('status', self::STATUS_QUALIFIED);
    }

    /**
     * Scope to get referrals needing rewards.
     */
    public function scopeNeedsReward($query)
    {
        return $query->where('status', self::STATUS_QUALIFIED)
            ->where('reward_eligible', true);
    }

    /**
     * Scope to filter by referrer business.
     */
    public function scopeByReferrer($query, int $businessId)
    {
        return $query->where('referrer_business_id', $businessId);
    }

    /**
     * Scope to find by referral code.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('referral_code', strtoupper($code));
    }

    /**
     * Scope to find by referred email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('referred_email', strtolower($email));
    }
}
