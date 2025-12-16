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

        // STAFF-REG-003: Profile Creation Fields
        'first_name',
        'last_name',
        'middle_name',
        'preferred_name',
        'gender',
        'profile_photo_verified',
        'profile_photo_face_detected',
        'profile_photo_face_confidence',
        'profile_photo_updated_at',

        // STAFF-REG-004: KYC Status Fields
        'kyc_status',
        'kyc_level',
        'kyc_expires_at',
        'kyc_verification_id',
        'verified_first_name',
        'verified_last_name',
        'verified_date_of_birth',
        'verified_nationality',
        'age_verified',
        'age_verified_at',
        'minimum_working_age_met',

        // Location Geocoding
        'geocoded_address',
        'geocoded_at',
        'timezone',

        // Profile Completion Tracking
        'profile_completion_percentage',
        'profile_sections_completed',
        'profile_last_updated_at',

        // Work Eligibility
        'work_eligibility_status',
        'work_eligibility_countries',

        // STAFF-REG-011: Activation Fields
        'is_activated',
        'activated_at',
        'is_matching_eligible',
        'matching_eligibility_reason',
        'phone_verified',
        'phone_verified_at',
        'rtw_verified',
        'rtw_verified_at',
        'rtw_document_type',
        'rtw_document_url',
        'rtw_expiry_date',
        'payment_setup_complete',
        'payment_setup_at',
        'first_shift_guidance_shown',
        'first_shift_completed_at',
        'profile_photo_status',
        'profile_photo_rejected_reason',
        'onboarding_started_at',
        'onboarding_last_step_at',
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
        // STAFF-REG-003 & 004: New casts
        'date_of_birth' => 'date',
        'profile_photo_verified' => 'boolean',
        'profile_photo_face_detected' => 'boolean',
        'profile_photo_face_confidence' => 'decimal:4',
        'profile_photo_updated_at' => 'datetime',
        'kyc_expires_at' => 'datetime',
        'verified_date_of_birth' => 'date',
        'age_verified' => 'boolean',
        'age_verified_at' => 'datetime',
        'minimum_working_age_met' => 'boolean',
        'geocoded_at' => 'datetime',
        'profile_completion_percentage' => 'integer',
        'profile_sections_completed' => 'array',
        'profile_last_updated_at' => 'datetime',
        'work_eligibility_countries' => 'array',
        // STAFF-REG-011: Activation casts
        'is_activated' => 'boolean',
        'activated_at' => 'datetime',
        'is_matching_eligible' => 'boolean',
        'phone_verified' => 'boolean',
        'phone_verified_at' => 'datetime',
        'rtw_verified' => 'boolean',
        'rtw_verified_at' => 'datetime',
        'rtw_expiry_date' => 'date',
        'payment_setup_complete' => 'boolean',
        'payment_setup_at' => 'datetime',
        'first_shift_guidance_shown' => 'boolean',
        'first_shift_completed_at' => 'datetime',
        'onboarding_started_at' => 'datetime',
        'onboarding_last_step_at' => 'datetime',
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

    // ===== STAFF-REG-004: Identity Verification Methods =====

    /**
     * Get all identity verifications for this worker.
     */
    public function identityVerifications()
    {
        return $this->hasMany(IdentityVerification::class, 'user_id', 'user_id');
    }

    /**
     * Get the current/active identity verification.
     */
    public function currentIdentityVerification()
    {
        return $this->hasOne(IdentityVerification::class, 'user_id', 'user_id')
            ->latestOfMany();
    }

    /**
     * Get the approved identity verification.
     */
    public function approvedIdentityVerification()
    {
        return $this->hasOne(IdentityVerification::class, 'user_id', 'user_id')
            ->where('status', 'approved')
            ->latestOfMany();
    }

    /**
     * Check if worker has completed KYC.
     */
    public function hasCompletedKyc(): bool
    {
        return $this->kyc_status === 'approved' && !$this->isKycExpired();
    }

    /**
     * Check if KYC has expired.
     */
    public function isKycExpired(): bool
    {
        return $this->kyc_expires_at && $this->kyc_expires_at->isPast();
    }

    /**
     * Check if KYC is expiring soon.
     */
    public function isKycExpiringSoon(int $days = 30): bool
    {
        if (!$this->kyc_expires_at) {
            return false;
        }

        return $this->kyc_expires_at->isBetween(now(), now()->addDays($days));
    }

    /**
     * Get KYC status label.
     */
    public function getKycStatusLabel(): string
    {
        $labels = [
            'not_started' => 'Not Started',
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'manual_review' => 'Under Review',
            'approved' => 'Verified',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
        ];

        return $labels[$this->kyc_status] ?? 'Unknown';
    }

    // ===== STAFF-REG-003: Age Verification Methods =====

    /**
     * Get minimum working age for a jurisdiction.
     */
    public static function getMinimumWorkingAge(string $countryCode): int
    {
        $ages = [
            'US' => 14, // With restrictions, 16 for general work
            'GB' => 13, // Part-time, 16 for full-time
            'CA' => 14, // Varies by province
            'AU' => 14, // With restrictions
            'DE' => 15,
            'FR' => 16,
            'ES' => 16,
            'IT' => 16,
            'NL' => 15,
            'BE' => 15,
            'IN' => 14, // With restrictions
            'BR' => 14, // Apprentice, 16 for general
            'MX' => 15,
            'ZA' => 15,
            'NG' => 15,
            'KE' => 16,
        ];

        return $ages[$countryCode] ?? 16; // Default to 16
    }

    /**
     * Verify worker meets minimum age requirement.
     */
    public function verifyMinimumAge(string $countryCode = null): bool
    {
        if (!$this->date_of_birth) {
            return false;
        }

        $country = $countryCode ?? $this->country ?? 'US';
        $minimumAge = self::getMinimumWorkingAge($country);
        $age = $this->date_of_birth->age;

        $meetsRequirement = $age >= $minimumAge;

        $this->update([
            'age_verified' => true,
            'age_verified_at' => now(),
            'minimum_working_age_met' => $meetsRequirement,
        ]);

        return $meetsRequirement;
    }

    /**
     * Get worker's current age.
     */
    public function getAge(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    // ===== STAFF-REG-003: Profile Photo Methods =====

    /**
     * Check if profile photo has valid face detection.
     */
    public function hasValidProfilePhoto(): bool
    {
        return $this->profile_photo_url
            && $this->profile_photo_verified
            && $this->profile_photo_face_detected;
    }

    /**
     * Update profile photo verification status.
     */
    public function updatePhotoVerification(bool $faceDetected, float $confidence = null): self
    {
        $this->update([
            'profile_photo_face_detected' => $faceDetected,
            'profile_photo_face_confidence' => $confidence,
            'profile_photo_verified' => $faceDetected && ($confidence ?? 0) >= 0.90,
            'profile_photo_updated_at' => now(),
        ]);

        return $this;
    }

    // ===== STAFF-REG-003: Profile Completion Methods =====

    /**
     * Get full name.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ]);

        if (empty($parts)) {
            return $this->user->name ?? '';
        }

        return implode(' ', $parts);
    }

    /**
     * Get display name (preferred name or first name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->preferred_name ?? $this->first_name ?? $this->user->first_name ?? '';
    }

    /**
     * Check if profile is complete.
     */
    public function isProfileComplete(): bool
    {
        return $this->profile_completion_percentage >= 100;
    }

    /**
     * Get required fields for profile completion.
     */
    public static function getRequiredFields(): array
    {
        return [
            'first_name',
            'last_name',
            'date_of_birth',
            'city',
            'phone',
            'profile_photo_url',
        ];
    }

    /**
     * Get optional fields for profile enhancement.
     */
    public static function getOptionalFields(): array
    {
        return [
            'middle_name',
            'preferred_name',
            'gender',
            'bio',
            'linkedin_url',
            'emergency_contact_name',
            'emergency_contact_phone',
        ];
    }

    /**
     * Check which required fields are missing.
     */
    public function getMissingRequiredFields(): array
    {
        $missing = [];

        foreach (self::getRequiredFields() as $field) {
            if (empty($this->{$field})) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Calculate and update profile completion percentage.
     */
    public function recalculateProfileCompletion(): int
    {
        $requiredFields = self::getRequiredFields();
        $optionalFields = self::getOptionalFields();

        $requiredCount = count($requiredFields);
        $optionalCount = count($optionalFields);

        $requiredCompleted = 0;
        $optionalCompleted = 0;

        foreach ($requiredFields as $field) {
            if (!empty($this->{$field})) {
                $requiredCompleted++;
            }
        }

        foreach ($optionalFields as $field) {
            if (!empty($this->{$field})) {
                $optionalCompleted++;
            }
        }

        // Required fields are worth 70%, optional fields are worth 30%
        $requiredPercentage = ($requiredCompleted / $requiredCount) * 70;
        $optionalPercentage = ($optionalCompleted / $optionalCount) * 30;

        $totalPercentage = (int) round($requiredPercentage + $optionalPercentage);

        $this->update([
            'profile_completion_percentage' => $totalPercentage,
            'profile_last_updated_at' => now(),
        ]);

        return $totalPercentage;
    }

    // ===== STAFF-REG-011: Activation Methods =====

    /**
     * Check if worker is activated and can access shift marketplace.
     */
    public function isActivated(): bool
    {
        return $this->is_activated && $this->is_matching_eligible;
    }

    /**
     * Check if worker has completed Right to Work verification.
     */
    public function hasCompletedRTW(): bool
    {
        return $this->rtw_verified;
    }

    /**
     * Check if RTW documentation is expired or expiring soon.
     */
    public function isRTWExpiring(int $days = 30): bool
    {
        if (!$this->rtw_expiry_date) {
            return false;
        }

        return $this->rtw_expiry_date->isBetween(now(), now()->addDays($days));
    }

    /**
     * Check if RTW documentation has expired.
     */
    public function isRTWExpired(): bool
    {
        return $this->rtw_expiry_date && $this->rtw_expiry_date->isPast();
    }

    /**
     * Check if phone is verified.
     */
    public function isPhoneVerified(): bool
    {
        return $this->phone_verified;
    }

    /**
     * Check if payment setup is complete.
     */
    public function hasPaymentSetup(): bool
    {
        return $this->payment_setup_complete;
    }

    /**
     * Get profile photo status label.
     */
    public function getProfilePhotoStatusLabel(): string
    {
        $labels = [
            'none' => 'No Photo',
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];

        return $labels[$this->profile_photo_status] ?? 'Unknown';
    }

    /**
     * Check if ready for activation (meets all requirements).
     */
    public function isReadyForActivation(): bool
    {
        return $this->identity_verified
            && $this->rtw_verified
            && $this->phone_verified
            && $this->payment_setup_complete
            && $this->profile_completion_percentage >= 80
            && !$this->is_activated;
    }

    /**
     * Mark first shift guidance as shown.
     */
    public function markFirstShiftGuidanceShown(): self
    {
        $this->update([
            'first_shift_guidance_shown' => true,
        ]);

        return $this;
    }

    /**
     * Mark first shift as completed.
     */
    public function markFirstShiftCompleted(): self
    {
        $this->update([
            'first_shift_completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Disable matching eligibility.
     */
    public function disableMatching(string $reason): self
    {
        $this->update([
            'is_matching_eligible' => false,
            'matching_eligibility_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Enable matching eligibility.
     */
    public function enableMatching(): self
    {
        $this->update([
            'is_matching_eligible' => true,
            'matching_eligibility_reason' => null,
        ]);

        return $this;
    }
}
