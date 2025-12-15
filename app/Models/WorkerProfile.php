<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $onboarding_completed
 * @property int|null $onboarding_step
 * @property \Illuminate\Support\Carbon|null $onboarding_completed_at
 * @property bool $identity_verified
 * @property \Illuminate\Support\Carbon|null $identity_verified_at
 * @property string|null $identity_verification_method
 * @property string $subscription_tier
 * @property \Illuminate\Support\Carbon|null $tier_expires_at
 * @property \Illuminate\Support\Carbon|null $tier_upgraded_at
 * @property string|null $bio
 * @property numeric|null $hourly_rate_min
 * @property numeric|null $hourly_rate_max
 * @property array<array-key, mixed>|null $industries
 * @property array<array-key, mixed>|null $preferred_industries
 * @property string|null $profile_photo_url
 * @property string|null $resume_url
 * @property string|null $linkedin_url
 * @property array<array-key, mixed>|null $availability_schedule
 * @property string $transportation
 * @property int $max_commute_distance
 * @property int $years_experience
 * @property numeric $rating_average
 * @property int $total_shifts_completed
 * @property numeric $reliability_score
 * @property int $total_no_shows
 * @property int $total_cancellations
 * @property int $total_late_arrivals
 * @property int $total_early_departures
 * @property int $total_no_acknowledgments
 * @property int $average_response_time_minutes
 * @property numeric $total_earnings
 * @property numeric $pending_earnings
 * @property numeric $withdrawn_earnings
 * @property numeric|null $average_hourly_earned
 * @property string|null $referral_code
 * @property string|null $referred_by
 * @property int $total_referrals
 * @property numeric $referral_earnings
 * @property string|null $location_city
 * @property string|null $location_state
 * @property string|null $location_country
 * @property string $background_check_status
 * @property \Illuminate\Support\Carbon|null $background_check_date
 * @property string|null $background_check_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $phone
 * @property string|null $date_of_birth
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string|null $country
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Skill> $skills
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Certification> $certifications
 * @property numeric|null $hourly_rate
 * @property numeric $rating
 * @property int $completed_shifts
 * @property int $is_available
 * @property int $is_complete
 * @property numeric|null $location_lat
 * @property numeric|null $location_lng
 * @property int $preferred_radius
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkerBadge> $badges
 * @property-read int|null $badges_count
 * @property-read int|null $certifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rating> $ratingsReceived
 * @property-read int|null $ratings_received_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftApplication> $shiftApplications
 * @property-read int|null $shift_applications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftAssignment> $shiftAssignments
 * @property-read int|null $shift_assignments_count
 * @property-read int|null $skills_count
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\WorkerProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereAvailabilitySchedule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereAverageHourlyEarned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereAverageResponseTimeMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereBackgroundCheckDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereBackgroundCheckNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereBackgroundCheckStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereCertifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereCompletedShifts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereEmergencyContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereEmergencyContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereHourlyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereHourlyRateMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereHourlyRateMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereIdentityVerificationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereIdentityVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereIdentityVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereIndustries($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereIsComplete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereLinkedinUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereLocationCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereLocationCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereLocationLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereLocationLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereLocationState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereMaxCommuteDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereOnboardingCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereOnboardingCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereOnboardingStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile wherePendingEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile wherePreferredIndustries($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile wherePreferredRadius($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereProfilePhotoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereRatingAverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereReferralCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereReferralEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereReferredBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereReliabilityScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereResumeUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereSubscriptionTier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTierExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTierUpgradedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTotalCancellations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTotalEarlyDepartures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTotalEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTotalLateArrivals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTotalNoAcknowledgments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTotalNoShows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTotalReferrals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTotalShiftsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereTransportation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereWithdrawnEarnings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereYearsExperience($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerProfile whereZipCode($value)
 * @mixin \Eloquent
 */
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
     * Get the worker's shift assignments.
     */
    public function shiftAssignments()
    {
        return $this->hasMany(ShiftAssignment::class, 'worker_id', 'user_id');
    }

    /**
     * Get the worker's shift applications.
     */
    public function shiftApplications()
    {
        return $this->hasMany(ShiftApplication::class, 'worker_id', 'user_id');
    }

    /**
     * Get the worker's badges.
     */
    public function badges()
    {
        return $this->hasMany(WorkerBadge::class, 'worker_id', 'user_id');
    }

    /**
     * Get ratings received by this worker.
     */
    public function ratingsReceived()
    {
        return $this->hasMany(Rating::class, 'rated_id', 'user_id')
            ->where('rater_type', 'business');
    }

    /**
     * Get the worker's payments received.
     */
    public function payments()
    {
        return $this->hasMany(ShiftPayment::class, 'worker_id', 'user_id');
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

    // ===== WKR-010: Portfolio & Showcase Methods =====

    /**
     * Get the worker's portfolio items.
     */
    public function portfolioItems()
    {
        return $this->hasMany(WorkerPortfolioItem::class, 'worker_id', 'user_id');
    }

    /**
     * Get the worker's featured statuses.
     */
    public function featuredStatuses()
    {
        return $this->hasMany(WorkerFeaturedStatus::class, 'worker_id', 'user_id');
    }

    /**
     * Get active featured status.
     */
    public function activeFeaturedStatus()
    {
        return $this->hasOne(WorkerFeaturedStatus::class, 'worker_id', 'user_id')
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Get profile views.
     */
    public function profileViews()
    {
        return $this->hasMany(WorkerProfileView::class, 'worker_id', 'user_id');
    }

    /**
     * Check if worker has public profile enabled.
     */
    public function hasPublicProfile(): bool
    {
        return $this->public_profile_enabled && $this->public_profile_slug;
    }

    /**
     * Get public profile URL.
     */
    public function getPublicProfileUrlAttribute(): ?string
    {
        if (!$this->hasPublicProfile()) {
            return null;
        }

        return route('profile.public', $this->public_profile_slug);
    }

    /**
     * Check if worker is currently featured.
     */
    public function isFeatured(): bool
    {
        return $this->activeFeaturedStatus()->exists();
    }

    /**
     * Get featured search boost multiplier.
     */
    public function getFeaturedBoost(): float
    {
        $status = $this->activeFeaturedStatus;

        if (!$status) {
            return 1.0;
        }

        return $status->search_boost ?? 1.0;
    }
}
