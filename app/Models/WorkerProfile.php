<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'bio',
        'hourly_rate_min',
        'hourly_rate_max',
        'industries',
        'availability_schedule',
        'transportation',
        'max_commute_distance',
        'years_experience',
        'rating_average',
        'total_shifts_completed',
        'reliability_score',
        'total_no_shows',
        'total_cancellations',
        'background_check_status',
        'background_check_date',
        'background_check_notes',

        // WKR-001: Onboarding tracking
        'onboarding_completed',
        'onboarding_step',
        'onboarding_completed_at',
        'identity_verified',
        'identity_verified_at',
        'identity_verification_method',

        // WKR-004: Tier system
        'subscription_tier',
        'tier_expires_at',
        'tier_upgraded_at',

        // WKR-005: Enhanced reliability metrics
        'total_late_arrivals',
        'total_early_departures',
        'total_no_acknowledgments',
        'average_response_time_minutes',

        // WKR-009: Earnings tracking
        'total_earnings',
        'pending_earnings',
        'withdrawn_earnings',
        'average_hourly_earned',

        // WKR-010: Referral tracking
        'referral_code',
        'referred_by',
        'total_referrals',
        'referral_earnings',

        // Additional profile fields
        'location_lat',
        'location_lng',
        'location_city',
        'location_state',
        'location_country',
        'preferred_radius',
        'preferred_industries',
        'emergency_contact_name',
        'emergency_contact_phone',
        'profile_photo_url',
        'resume_url',
        'linkedin_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'hourly_rate_min' => 'decimal:2',
        'hourly_rate_max' => 'decimal:2',
        'industries' => 'array',
        'availability_schedule' => 'array',
        'preferred_industries' => 'array',
        'max_commute_distance' => 'integer',
        'years_experience' => 'integer',
        'rating_average' => 'decimal:2',
        'total_shifts_completed' => 'integer',
        'reliability_score' => 'decimal:2',
        'total_no_shows' => 'integer',
        'total_cancellations' => 'integer',
        'total_late_arrivals' => 'integer',
        'total_early_departures' => 'integer',
        'total_no_acknowledgments' => 'integer',
        'background_check_date' => 'date',
        'onboarding_completed' => 'boolean',
        'onboarding_completed_at' => 'datetime',
        'identity_verified' => 'boolean',
        'identity_verified_at' => 'datetime',
        'tier_expires_at' => 'datetime',
        'tier_upgraded_at' => 'datetime',
        'total_earnings' => 'decimal:2',
        'pending_earnings' => 'decimal:2',
        'withdrawn_earnings' => 'decimal:2',
        'average_hourly_earned' => 'decimal:2',
        'location_lat' => 'decimal:8',
        'location_lng' => 'decimal:8',
        'preferred_radius' => 'integer',
        'average_response_time_minutes' => 'integer',
        'total_referrals' => 'integer',
        'referral_earnings' => 'decimal:2',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the worker's skills.
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'worker_skills', 'worker_id', 'skill_id')
            ->withPivot('proficiency_level', 'years_experience', 'verified')
            ->withTimestamps();
    }

    /**
     * Get the worker's certifications.
     */
    public function certifications()
    {
        return $this->belongsToMany(Certification::class, 'worker_certifications', 'worker_id', 'certification_id')
            ->withPivot('certification_number', 'issue_date', 'expiry_date', 'document_url', 'verified', 'verified_at')
            ->withTimestamps();
    }

    /**
     * Check if worker has background check approved.
     */
    public function hasApprovedBackgroundCheck()
    {
        return $this->background_check_status === 'approved';
    }

    /**
     * Check if worker is reliable (low no-shows and cancellations).
     */
    public function isReliable()
    {
        return $this->reliability_score >= 0.80;
    }

    /**
     * Check if worker is available on a specific day/time.
     */
    public function isAvailable($dayOfWeek, $time)
    {
        if (!$this->availability_schedule) {
            return false;
        }

        $schedule = $this->availability_schedule;
        if (!isset($schedule[$dayOfWeek])) {
            return false;
        }

        $daySchedule = $schedule[$dayOfWeek];
        return $time >= $daySchedule['start'] && $time <= $daySchedule['end'];
    }

    /**
     * Update reliability score.
     * WKR-005: Enhanced reliability scoring with weighted factors
     */
    public function updateReliabilityScore()
    {
        $completionRate = 0;
        $punctualityScore = 0;
        $acknowledgmentScore = 0;
        $commitmentScore = 0;

        // 1. Completion Rate (40% weight)
        $totalAssigned = $this->total_shifts_completed + $this->total_no_shows + $this->total_cancellations;
        if ($totalAssigned > 0) {
            $completionRate = ($this->total_shifts_completed / $totalAssigned) * 40;
        } else {
            $completionRate = 40; // New workers start with perfect score
        }

        // 2. Punctuality Score (30% weight)
        if ($this->total_shifts_completed > 0) {
            $lateRate = $this->total_late_arrivals / $this->total_shifts_completed;
            $punctualityScore = (1 - $lateRate) * 30;
        } else {
            $punctualityScore = 30;
        }

        // 3. Acknowledgment Score (20% weight)
        if ($totalAssigned > 0) {
            $acknowledgmentRate = 1 - ($this->total_no_acknowledgments / $totalAssigned);
            $acknowledgmentScore = $acknowledgmentRate * 20;
        } else {
            $acknowledgmentScore = 20;
        }

        // 4. Commitment Score (10% weight)
        if ($this->total_shifts_completed > 0) {
            $earlyDepartureRate = $this->total_early_departures / $this->total_shifts_completed;
            $commitmentScore = (1 - $earlyDepartureRate) * 10;
        } else {
            $commitmentScore = 10;
        }

        // Calculate final score (0-100)
        $this->reliability_score = round($completionRate + $punctualityScore + $acknowledgmentScore + $commitmentScore, 2);
        $this->save();
    }

    // ===== WKR-001: Onboarding Methods =====

    /**
     * Check if worker has completed onboarding.
     */
    public function hasCompletedOnboarding()
    {
        return $this->onboarding_completed;
    }

    /**
     * Get current onboarding step.
     */
    public function getCurrentOnboardingStep()
    {
        return $this->onboarding_step ?? 1;
    }

    /**
     * Complete onboarding.
     */
    public function completeOnboarding()
    {
        $this->update([
            'onboarding_completed' => true,
            'onboarding_completed_at' => now(),
            'onboarding_step' => null,
        ]);
    }

    // ===== WKR-004: Tier System Methods =====

    /**
     * Get current tier (Bronze/Silver/Gold/Platinum).
     */
    public function getCurrentTier()
    {
        return $this->subscription_tier ?? 'bronze';
    }

    /**
     * Check if worker has active premium tier.
     */
    public function hasPremiumTier()
    {
        return in_array($this->getCurrentTier(), ['gold', 'platinum']) &&
               (!$this->tier_expires_at || $this->tier_expires_at->isFuture());
    }

    /**
     * Get tier benefits.
     */
    public function getTierBenefits()
    {
        $tier = $this->getCurrentTier();

        $benefits = [
            'bronze' => [
                'priority_level' => 4,
                'early_access_minutes' => 0,
                'platform_fee_discount' => 0,
                'support_level' => 'standard',
                'cancellation_penalty_reduction' => 0,
            ],
            'silver' => [
                'priority_level' => 3,
                'early_access_minutes' => 30,
                'platform_fee_discount' => 5,
                'support_level' => 'priority',
                'cancellation_penalty_reduction' => 10,
            ],
            'gold' => [
                'priority_level' => 2,
                'early_access_minutes' => 60,
                'platform_fee_discount' => 10,
                'support_level' => 'premium',
                'cancellation_penalty_reduction' => 20,
            ],
            'platinum' => [
                'priority_level' => 1,
                'early_access_minutes' => 120,
                'platform_fee_discount' => 15,
                'support_level' => 'vip',
                'cancellation_penalty_reduction' => 30,
            ],
        ];

        return $benefits[$tier] ?? $benefits['bronze'];
    }

    /**
     * Upgrade tier.
     */
    public function upgradeTier($newTier, $durationMonths = 1)
    {
        $this->update([
            'subscription_tier' => $newTier,
            'tier_upgraded_at' => now(),
            'tier_expires_at' => now()->addMonths($durationMonths),
        ]);
    }

    // ===== WKR-009: Earnings Methods =====

    /**
     * Add earnings from completed shift.
     */
    public function addEarnings($amount)
    {
        $this->increment('total_earnings', $amount);
        $this->increment('pending_earnings', $amount);

        // Update average hourly rate
        if ($this->total_shifts_completed > 0) {
            $this->average_hourly_earned = $this->total_earnings / $this->total_shifts_completed;
            $this->save();
        }
    }

    /**
     * Record withdrawal.
     */
    public function recordWithdrawal($amount)
    {
        $this->decrement('pending_earnings', $amount);
        $this->increment('withdrawn_earnings', $amount);
    }

    /**
     * Get earnings summary.
     */
    public function getEarningsSummary()
    {
        return [
            'lifetime_total' => $this->total_earnings,
            'pending' => $this->pending_earnings,
            'withdrawn' => $this->withdrawn_earnings,
            'available_for_withdrawal' => max(0, $this->pending_earnings),
            'average_per_shift' => $this->total_shifts_completed > 0
                ? $this->total_earnings / $this->total_shifts_completed
                : 0,
            'average_hourly' => $this->average_hourly_earned ?? 0,
        ];
    }

    // ===== WKR-010: Referral Methods =====

    /**
     * Generate unique referral code.
     */
    public function generateReferralCode()
    {
        if (!$this->referral_code) {
            $this->referral_code = strtoupper(substr(md5($this->user_id . time()), 0, 8));
            $this->save();
        }

        return $this->referral_code;
    }

    /**
     * Process referral reward.
     */
    public function processReferralReward($amount)
    {
        $this->increment('total_referrals');
        $this->increment('referral_earnings', $amount);
        $this->increment('total_earnings', $amount);
        $this->increment('pending_earnings', $amount);
    }

    // ===== WKR-003: Rating Methods =====

    /**
     * Add new rating.
     */
    public function addRating($rating)
    {
        // Recalculate average rating
        $totalRatings = $this->user->ratingsReceived()->count();
        $sumRatings = $this->user->ratingsReceived()->sum('rating');

        $this->rating_average = $totalRatings > 0 ? round($sumRatings / $totalRatings, 2) : 0;
        $this->save();
    }

    // ===== WKR-006: Availability Methods =====

    /**
     * Check if worker has availability set.
     */
    public function hasAvailabilitySet()
    {
        return !empty($this->availability_schedule);
    }

    /**
     * Get available days.
     */
    public function getAvailableDays()
    {
        if (!$this->availability_schedule) {
            return [];
        }

        return array_keys(array_filter($this->availability_schedule, function($day) {
            return isset($day['available']) && $day['available'];
        }));
    }
}
