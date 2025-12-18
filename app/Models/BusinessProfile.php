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
 * @property string $business_name
 * @property string $business_type
 * @property string|null $industry
 * @property string|null $business_address
 * @property string|null $business_city
 * @property string|null $business_state
 * @property string|null $business_country
 * @property string|null $business_phone
 * @property string|null $ein_tax_id
 * @property numeric $rating_average
 * @property int $total_reviews
 * @property numeric $communication_rating
 * @property numeric $punctuality_rating
 * @property numeric $professionalism_rating
 * @property int $total_shifts_posted
 * @property int $total_shifts_completed
 * @property int $total_shifts_cancelled
 * @property numeric $average_shift_cost
 * @property numeric $total_spent
 * @property numeric $pending_payment
 * @property int $unique_workers_hired
 * @property int $repeat_workers
 * @property string $subscription_plan
 * @property \Illuminate\Support\Carbon|null $subscription_expires_at
 * @property numeric|null $monthly_credit_limit
 * @property numeric $monthly_credit_used
 * @property numeric $fill_rate
 * @property numeric $cancellation_rate
 * @property int $late_cancellations
 * @property numeric $total_cancellation_penalties
 * @property int $open_support_tickets
 * @property \Illuminate\Support\Carbon|null $last_support_contact
 * @property bool $priority_support
 * @property bool $account_in_good_standing
 * @property string|null $account_warning_message
 * @property \Illuminate\Support\Carbon|null $last_shift_posted_at
 * @property bool $can_post_shifts
 * @property bool $is_verified
 * @property string $verification_status
 * @property string|null $verification_notes
 * @property string|null $business_license_url
 * @property string|null $insurance_certificate_url
 * @property string|null $tax_document_url
 * @property \Illuminate\Support\Carbon|null $documents_submitted_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $business_registration_number
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string|null $country
 * @property string|null $description
 * @property int $total_locations
 * @property bool $multi_location_enabled
 * @property int $active_venues
 * @property int $total_templates
 * @property int $active_templates
 * @property string|null $employee_count
 * @property numeric $rating
 * @property int $has_payment_method
 * @property bool $autopay_enabled
 * @property string|null $default_payment_method_id
 * @property array<array-key, mixed>|null $preferred_worker_ids
 * @property array<array-key, mixed>|null $blacklisted_worker_ids
 * @property bool $allow_new_workers
 * @property numeric $minimum_worker_rating
 * @property int $minimum_shifts_completed
 * @property int $is_complete
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rating> $ratingsReceived
 * @property-read int|null $ratings_received_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftPayment> $shiftPayments
 * @property-read int|null $shift_payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $shifts
 * @property-read int|null $shifts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftTemplate> $templates
 * @property-read int|null $templates_count
 * @property-read \App\Models\User $user
 *
 * @method static \Database\Factories\BusinessProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereAccountInGoodStanding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereAccountWarningMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereActiveTemplates($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereActiveVenues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereAllowNewWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereAutopayEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereAverageShiftCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereBlacklistedWorkerIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereBusinessAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereBusinessCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereBusinessCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereBusinessLicenseUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereBusinessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereBusinessPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereBusinessRegistrationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereBusinessState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereBusinessType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereCanPostShifts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereCancellationRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereCommunicationRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereDefaultPaymentMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereDocumentsSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereEinTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereEmployeeCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereFillRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereHasPaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereIndustry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereInsuranceCertificateUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereIsComplete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereLastShiftPostedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereLastSupportContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereLateCancellations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereMinimumShiftsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereMinimumWorkerRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereMonthlyCreditLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereMonthlyCreditUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereMultiLocationEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereOnboardingCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereOnboardingCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereOnboardingStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereOpenSupportTickets($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile wherePendingPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile wherePreferredWorkerIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile wherePrioritySupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereProfessionalismRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile wherePunctualityRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereRatingAverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereRepeatWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereSubscriptionExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereSubscriptionPlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereTaxDocumentUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereTotalCancellationPenalties($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereTotalLocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereTotalReviews($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereTotalShiftsCancelled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereTotalShiftsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereTotalShiftsPosted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereTotalSpent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereTotalTemplates($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereUniqueWorkersHired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereVerificationNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereVerificationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BusinessProfile whereZipCode($value)
 *
 * @mixin \Eloquent
 */
class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'primary_admin_user_id',
        'business_name',
        'legal_business_name',
        'trading_name',
        'business_type',
        'business_category',
        'industry',
        'business_address',
        'business_city',
        'business_state',
        'business_country',
        'business_phone',
        'ein_tax_id',
        'rating_average',
        'total_shifts_posted',
        'total_shifts_completed',
        'total_shifts_cancelled',
        'fill_rate',
        'is_verified',
        'verified_at',

        // BIZ-001: Onboarding
        'onboarding_completed',
        'onboarding_step',
        'onboarding_completed_at',
        'verification_status',
        'verification_notes',
        'business_license_url',
        'insurance_certificate_url',
        'tax_document_url',
        'documents_submitted_at',

        // BIZ-002: Venues
        'multi_location_enabled',
        'active_venues',

        // BIZ-003: Templates
        'total_templates',
        'active_templates',

        // BIZ-004: Ratings
        'total_reviews',
        'communication_rating',
        'punctuality_rating',
        'professionalism_rating',

        // BIZ-005: Analytics
        'average_shift_cost',
        'total_spent',
        'pending_payment',
        'unique_workers_hired',
        'repeat_workers',

        // BIZ-006: Billing
        'subscription_plan',
        'subscription_expires_at',
        'monthly_credit_limit',
        'monthly_credit_used',
        'autopay_enabled',
        'default_payment_method_id',

        // BIZ-007: Worker Preferences
        'preferred_worker_ids',
        'blacklisted_worker_ids',
        'allow_new_workers',
        'minimum_worker_rating',
        'minimum_shifts_completed',

        // BIZ-008: Cancellation
        'cancellation_rate',
        'late_cancellations',
        'total_cancellation_penalties',

        // BIZ-009: Support
        'open_support_tickets',
        'last_support_contact',
        'priority_support',

        // BIZ-010: Compliance
        'account_in_good_standing',
        'account_warning_message',
        'last_shift_posted_at',
        'can_post_shifts',

        // BIZ-REG-002 & 003: Registration & Profile fields
        'company_size',
        'logo_url',
        'logo_public_id',
        'default_currency',
        'default_timezone',
        'jurisdiction_country',
        'jurisdiction_state',
        'tax_jurisdiction',
        'work_email',
        'work_email_domain',
        'work_email_verified',
        'work_email_verified_at',
        'email_verification_token',
        'email_verification_sent_at',
        'registration_source',
        'sales_rep_name',
        'sales_rep_email',
        'referral_code_used',
        'profile_completion_percentage',
        'profile_completion_details',

        // FIN-001: Volume Discount Tiers
        'current_volume_tier_id',
        'lifetime_shifts',
        'lifetime_spend',
        'lifetime_savings',
        'custom_pricing',
        'custom_fee_percent',
        'custom_pricing_notes',
        'custom_pricing_expires_at',
        'tier_upgraded_at',
        'tier_downgraded_at',
        'months_at_current_tier',
    ];

    protected $casts = [
        'rating_average' => 'decimal:2',
        'total_shifts_posted' => 'integer',
        'total_shifts_completed' => 'integer',
        'total_shifts_cancelled' => 'integer',
        'fill_rate' => 'decimal:2',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'onboarding_completed' => 'boolean',
        'onboarding_completed_at' => 'datetime',
        'documents_submitted_at' => 'datetime',
        'multi_location_enabled' => 'boolean',
        'total_reviews' => 'integer',
        'communication_rating' => 'decimal:2',
        'punctuality_rating' => 'decimal:2',
        'professionalism_rating' => 'decimal:2',
        'average_shift_cost' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'pending_payment' => 'decimal:2',
        'unique_workers_hired' => 'integer',
        'repeat_workers' => 'integer',
        'subscription_expires_at' => 'datetime',
        'monthly_credit_limit' => 'decimal:2',
        'monthly_credit_used' => 'decimal:2',
        'autopay_enabled' => 'boolean',
        'preferred_worker_ids' => 'array',
        'blacklisted_worker_ids' => 'array',
        'allow_new_workers' => 'boolean',
        'minimum_worker_rating' => 'decimal:2',
        'minimum_shifts_completed' => 'integer',
        'cancellation_rate' => 'decimal:2',
        'late_cancellations' => 'integer',
        'total_cancellation_penalties' => 'decimal:2',
        'open_support_tickets' => 'integer',
        'last_support_contact' => 'datetime',
        'priority_support' => 'boolean',
        'account_in_good_standing' => 'boolean',
        'last_shift_posted_at' => 'datetime',
        'can_post_shifts' => 'boolean',
        // BIZ-REG-002 & 003 casts
        'work_email_verified' => 'boolean',
        'work_email_verified_at' => 'datetime',
        'email_verification_sent_at' => 'datetime',
        'profile_completion_percentage' => 'decimal:2',
        'profile_completion_details' => 'array',
        // BIZ-REG-011: Activation tracking
        'activation_checked' => 'boolean',
        'activation_checked_at' => 'datetime',
        'last_activation_check' => 'datetime',
        'activation_requirements_status' => 'array',
        'activation_completion_percentage' => 'integer',
        'activation_requirements_met' => 'integer',
        'activation_requirements_total' => 'integer',
        'activation_blocked_reasons' => 'array',
        // FIN-001: Volume Discount Tiers
        'current_volume_tier_id' => 'integer',
        'lifetime_shifts' => 'integer',
        'lifetime_spend' => 'decimal:2',
        'lifetime_savings' => 'decimal:2',
        'custom_pricing' => 'boolean',
        'custom_fee_percent' => 'decimal:2',
        'custom_pricing_expires_at' => 'date',
        'tier_upgraded_at' => 'datetime',
        'tier_downgraded_at' => 'datetime',
        'months_at_current_tier' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all shifts posted by this business.
     */
    public function shifts()
    {
        return $this->hasMany(Shift::class, 'business_id', 'user_id');
    }

    /**
     * Get all shift payments made by this business.
     */
    public function shiftPayments()
    {
        return $this->hasMany(ShiftPayment::class, 'business_id', 'user_id');
    }

    /**
     * Get all ratings received by this business.
     */
    public function ratingsReceived()
    {
        return $this->hasMany(Rating::class, 'rated_id', 'user_id')
            ->where('rater_type', 'worker');
    }

    // ===== FIN-001: Volume Discount Tier Relationships =====

    /**
     * Get the current volume discount tier.
     */
    public function currentVolumeTier()
    {
        return $this->belongsTo(VolumeDiscountTier::class, 'current_volume_tier_id');
    }

    /**
     * Get all volume tracking records for this business.
     */
    public function volumeTrackings()
    {
        return $this->hasMany(BusinessVolumeTracking::class, 'business_id', 'user_id');
    }

    /**
     * Get the current month's volume tracking.
     */
    public function currentMonthTracking()
    {
        return $this->hasOne(BusinessVolumeTracking::class, 'business_id', 'user_id')
            ->where('month', now()->startOfMonth()->toDateString());
    }

    /**
     * Check if business has custom pricing.
     */
    public function hasCustomPricing(): bool
    {
        if (! $this->custom_pricing) {
            return false;
        }

        if ($this->custom_fee_percent === null) {
            return false;
        }

        if ($this->custom_pricing_expires_at && $this->custom_pricing_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get the effective platform fee percent for this business.
     */
    public function getEffectiveFeePercent(): float
    {
        if ($this->hasCustomPricing()) {
            return (float) $this->custom_fee_percent;
        }

        if ($this->currentVolumeTier) {
            return (float) $this->currentVolumeTier->platform_fee_percent;
        }

        return config('overtimestaff.financial.platform_fee_rate', 35.00);
    }

    public function isVerified()
    {
        return $this->is_verified;
    }

    public function updateFillRate()
    {
        $total = $this->total_shifts_posted;
        if ($total === 0) {
            $this->fill_rate = 0.00;
        } else {
            $this->fill_rate = round($this->total_shifts_completed / $total, 2);
        }
        $this->save();
    }

    // ===== BIZ-001: Onboarding Methods =====

    /**
     * Check if business has completed onboarding.
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

    /**
     * Submit verification documents.
     */
    public function submitVerificationDocuments($licenseUrl, $insuranceUrl, $taxDocUrl)
    {
        $this->update([
            'business_license_url' => $licenseUrl,
            'insurance_certificate_url' => $insuranceUrl,
            'tax_document_url' => $taxDocUrl,
            'documents_submitted_at' => now(),
            'verification_status' => 'in_review',
        ]);
    }

    /**
     * Check if all verification documents are submitted.
     */
    public function hasSubmittedAllDocuments()
    {
        return ! empty($this->business_license_url) &&
               ! empty($this->insurance_certificate_url) &&
               ! empty($this->tax_document_url);
    }

    // ===== BIZ-002: Venue Management Methods =====

    /**
     * Check if multi-location is enabled.
     */
    public function hasMultiLocationEnabled()
    {
        return $this->multi_location_enabled;
    }

    /**
     * Get venues relationship.
     */
    public function venues()
    {
        return $this->hasMany(BusinessVenue::class, 'business_id', 'user_id');
    }

    // ===== BIZ-003: Template Methods =====

    /**
     * Get templates relationship.
     */
    public function templates()
    {
        return $this->hasMany(ShiftTemplate::class, 'business_id', 'user_id');
    }

    // ===== BIZ-004: Rating Methods =====

    /**
     * Update overall rating from individual component ratings.
     */
    public function updateOverallRating()
    {
        if ($this->total_reviews > 0) {
            $this->rating_average = round(
                ($this->communication_rating + $this->punctuality_rating + $this->professionalism_rating) / 3,
                2
            );
            $this->save();
        }
    }

    /**
     * Add new rating.
     */
    public function addRating($communication, $punctuality, $professionalism)
    {
        $currentTotal = $this->total_reviews;

        // Calculate weighted average
        $this->communication_rating = round(
            (($this->communication_rating * $currentTotal) + $communication) / ($currentTotal + 1),
            2
        );
        $this->punctuality_rating = round(
            (($this->punctuality_rating * $currentTotal) + $punctuality) / ($currentTotal + 1),
            2
        );
        $this->professionalism_rating = round(
            (($this->professionalism_rating * $currentTotal) + $professionalism) / ($currentTotal + 1),
            2
        );

        $this->increment('total_reviews');
        $this->updateOverallRating();
    }

    // ===== BIZ-005: Analytics Methods =====

    /**
     * Update average shift cost.
     */
    public function updateAverageShiftCost()
    {
        if ($this->total_shifts_posted > 0) {
            $this->average_shift_cost = round($this->total_spent / $this->total_shifts_posted, 2);
            $this->save();
        }
    }

    /**
     * Record shift payment.
     */
    public function recordShiftPayment($amount)
    {
        $this->increment('total_spent', $amount);
        $this->increment('pending_payment', $amount);
        $this->updateAverageShiftCost();
    }

    /**
     * Complete shift payment (move from pending to completed).
     */
    public function completeShiftPayment($amount)
    {
        $this->decrement('pending_payment', $amount);
    }

    /**
     * Track unique worker.
     */
    public function trackWorkerHire($workerId)
    {
        // Check if worker has worked before
        $existingWorker = \DB::table('shift_assignments')
            ->where('shift_id', 'IN', function ($query) {
                $query->select('id')
                    ->from('shifts')
                    ->where('business_id', $this->user_id);
            })
            ->where('worker_id', $workerId)
            ->where('status', 'completed')
            ->count();

        if ($existingWorker === 0) {
            $this->increment('unique_workers_hired');
        } else {
            $this->increment('repeat_workers');
        }
    }

    /**
     * Get analytics summary.
     */
    public function getAnalyticsSummary()
    {
        return [
            'total_shifts_posted' => $this->total_shifts_posted,
            'total_shifts_completed' => $this->total_shifts_completed,
            'fill_rate' => $this->fill_rate,
            'cancellation_rate' => $this->cancellation_rate,
            'average_shift_cost' => $this->average_shift_cost,
            'total_spent' => $this->total_spent,
            'pending_payment' => $this->pending_payment,
            'unique_workers' => $this->unique_workers_hired,
            'repeat_workers' => $this->repeat_workers,
            'worker_retention_rate' => $this->unique_workers_hired > 0
                ? round(($this->repeat_workers / $this->unique_workers_hired) * 100, 2)
                : 0,
        ];
    }

    // ===== BIZ-006: Billing & Subscription Methods =====

    /**
     * Get subscription status.
     */
    public function getSubscriptionStatus()
    {
        if ($this->subscription_plan === 'free') {
            return 'free';
        }

        if ($this->subscription_expires_at && $this->subscription_expires_at->isFuture()) {
            return 'active';
        }

        return 'expired';
    }

    /**
     * Check if subscription is active.
     */
    public function hasActiveSubscription()
    {
        return $this->getSubscriptionStatus() === 'active' || $this->subscription_plan === 'free';
    }

    /**
     * Get subscription benefits.
     */
    public function getSubscriptionBenefits()
    {
        $benefits = [
            'free' => [
                'monthly_shifts' => 5,
                'platform_fee_discount' => 0,
                'priority_support' => false,
                'analytics_access' => 'basic',
                'template_limit' => 2,
            ],
            'basic' => [
                'monthly_shifts' => 20,
                'platform_fee_discount' => 5,
                'priority_support' => false,
                'analytics_access' => 'standard',
                'template_limit' => 10,
            ],
            'professional' => [
                'monthly_shifts' => 100,
                'platform_fee_discount' => 10,
                'priority_support' => true,
                'analytics_access' => 'advanced',
                'template_limit' => 50,
            ],
            'enterprise' => [
                'monthly_shifts' => 999,
                'platform_fee_discount' => 15,
                'priority_support' => true,
                'analytics_access' => 'full',
                'template_limit' => 999,
            ],
        ];

        return $benefits[$this->subscription_plan] ?? $benefits['free'];
    }

    /**
     * Check if monthly credit limit is exceeded.
     */
    public function hasExceededCreditLimit()
    {
        if (! $this->monthly_credit_limit) {
            return false;
        }

        return $this->monthly_credit_used >= $this->monthly_credit_limit;
    }

    // ===== BIZ-007: Worker Preference Methods =====

    /**
     * Add worker to preferred list.
     */
    public function addPreferredWorker($workerId)
    {
        $preferred = $this->preferred_worker_ids ?? [];
        if (! in_array($workerId, $preferred)) {
            $preferred[] = $workerId;
            $this->preferred_worker_ids = $preferred;
            $this->save();
        }
    }

    /**
     * Add worker to blacklist.
     */
    public function blacklistWorker($workerId)
    {
        $blacklisted = $this->blacklisted_worker_ids ?? [];
        if (! in_array($workerId, $blacklisted)) {
            $blacklisted[] = $workerId;
            $this->blacklisted_worker_ids = $blacklisted;
            $this->save();
        }

        // Remove from preferred if exists
        $this->removePreferredWorker($workerId);
    }

    /**
     * Remove worker from preferred list.
     */
    public function removePreferredWorker($workerId)
    {
        $preferred = $this->preferred_worker_ids ?? [];
        $preferred = array_values(array_filter($preferred, fn ($id) => $id != $workerId));
        $this->preferred_worker_ids = $preferred;
        $this->save();
    }

    /**
     * Check if worker is blacklisted.
     */
    public function isWorkerBlacklisted($workerId)
    {
        $blacklisted = $this->blacklisted_worker_ids ?? [];

        return in_array($workerId, $blacklisted);
    }

    /**
     * Check if worker meets minimum requirements.
     */
    public function doesWorkerMeetRequirements($worker)
    {
        if ($this->isWorkerBlacklisted($worker->id)) {
            return false;
        }

        if (! $this->allow_new_workers && $worker->workerProfile->total_shifts_completed === 0) {
            return false;
        }

        if ($worker->workerProfile->rating_average < $this->minimum_worker_rating) {
            return false;
        }

        if ($worker->workerProfile->total_shifts_completed < $this->minimum_shifts_completed) {
            return false;
        }

        return true;
    }

    // ===== BIZ-008: Cancellation Methods =====

    /**
     * Update cancellation rate.
     */
    public function updateCancellationRate()
    {
        $total = $this->total_shifts_posted;
        if ($total > 0) {
            $this->cancellation_rate = round(($this->total_shifts_cancelled / $total) * 100, 2);
            $this->save();
        }
    }

    /**
     * Record cancellation.
     */
    public function recordCancellation($isLate, $penaltyAmount = 0)
    {
        $this->increment('total_shifts_cancelled');

        if ($isLate) {
            $this->increment('late_cancellations');
        }

        if ($penaltyAmount > 0) {
            $this->increment('total_cancellation_penalties', $penaltyAmount);
        }

        $this->updateCancellationRate();
    }

    // ===== BIZ-009: Support Methods =====

    /**
     * Create support ticket.
     */
    public function createSupportTicket()
    {
        $this->increment('open_support_tickets');
        $this->last_support_contact = now();
        $this->save();
    }

    /**
     * Close support ticket.
     */
    public function closeSupportTicket()
    {
        if ($this->open_support_tickets > 0) {
            $this->decrement('open_support_tickets');
        }
    }

    // ===== BIZ-010: Compliance Methods =====

    /**
     * Check if account is in good standing.
     */
    public function isInGoodStanding()
    {
        return $this->account_in_good_standing && $this->can_post_shifts;
    }

    /**
     * Flag account for review.
     */
    public function flagAccountForReview($warningMessage)
    {
        $this->update([
            'account_in_good_standing' => false,
            'account_warning_message' => $warningMessage,
            'can_post_shifts' => false,
        ]);
    }

    /**
     * Restore account to good standing.
     */
    public function restoreGoodStanding()
    {
        $this->update([
            'account_in_good_standing' => true,
            'account_warning_message' => null,
            'can_post_shifts' => true,
        ]);
    }

    /**
     * Update last shift posted timestamp.
     */
    public function recordShiftPosted()
    {
        $this->last_shift_posted_at = now();
        $this->save();
    }

    // ===== BIZ-REG-002 & 003: Registration & Profile Methods =====

    /**
     * Get business contacts.
     */
    public function contacts()
    {
        return $this->hasMany(BusinessContact::class);
    }

    /**
     * Get primary contact.
     */
    public function primaryContact()
    {
        return $this->hasOne(BusinessContact::class)
            ->where('contact_type', 'primary')
            ->where('is_primary', true);
    }

    /**
     * Get billing contact.
     */
    public function billingContact()
    {
        return $this->hasOne(BusinessContact::class)
            ->where('contact_type', 'billing')
            ->where('is_primary', true);
    }

    /**
     * Get business addresses.
     */
    public function addresses()
    {
        return $this->hasMany(BusinessAddress::class);
    }

    /**
     * Get registered address.
     */
    public function registeredAddress()
    {
        return $this->hasOne(BusinessAddress::class)
            ->where('address_type', 'registered')
            ->where('is_primary', true);
    }

    /**
     * Get billing address.
     */
    public function billingAddress()
    {
        return $this->hasOne(BusinessAddress::class)
            ->where('address_type', 'billing')
            ->where('is_primary', true);
    }

    /**
     * Get operating addresses.
     */
    public function operatingAddresses()
    {
        return $this->hasMany(BusinessAddress::class)
            ->where('address_type', 'operating');
    }

    /**
     * Get onboarding record.
     */
    public function onboarding()
    {
        return $this->hasOne(BusinessOnboarding::class);
    }

    /**
     * Get referrals made by this business.
     */
    public function referralsMade()
    {
        return $this->hasMany(BusinessReferral::class, 'referrer_business_id');
    }

    /**
     * Get referral that brought this business.
     */
    public function referredBy()
    {
        return $this->hasOne(BusinessReferral::class, 'referred_business_id');
    }

    /**
     * Get the primary admin user.
     */
    public function primaryAdmin()
    {
        return $this->belongsTo(User::class, 'primary_admin_user_id');
    }

    /**
     * Get the business type details.
     */
    public function businessTypeDetails()
    {
        return BusinessType::findByCode($this->business_category);
    }

    /**
     * Get the industry details.
     */
    public function industryDetails()
    {
        return Industry::findByCode($this->industry);
    }

    /**
     * Check if work email is verified.
     */
    public function hasVerifiedWorkEmail(): bool
    {
        return $this->work_email_verified;
    }

    /**
     * Generate email verification token.
     */
    public function generateEmailVerificationToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update([
            'email_verification_token' => $token,
            'email_verification_sent_at' => now(),
        ]);

        return $token;
    }

    /**
     * Verify email with token.
     */
    public function verifyWorkEmail(string $token): bool
    {
        if ($this->email_verification_token === $token) {
            $this->update([
                'work_email_verified' => true,
                'work_email_verified_at' => now(),
                'email_verification_token' => null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get display name (trading name or business name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->trading_name ?: $this->business_name;
    }

    /**
     * Get logo URL or default.
     */
    public function getLogoUrlOrDefaultAttribute(): string
    {
        return $this->logo_url ?: asset('images/default-business-logo.png');
    }

    /**
     * Check if profile meets minimum completion threshold.
     */
    public function meetsMinimumCompletion(): bool
    {
        return $this->profile_completion_percentage >= 80;
    }

    /**
     * Check if business can be activated.
     */
    public function canBeActivated(): bool
    {
        return $this->hasVerifiedWorkEmail()
            && $this->meetsMinimumCompletion()
            && $this->onboarding?->terms_accepted;
    }

    /**
     * Scope to get businesses by email domain.
     */
    public function scopeByEmailDomain($query, string $domain)
    {
        return $query->where('work_email_domain', strtolower($domain));
    }

    /**
     * Scope to get businesses by registration source.
     */
    public function scopeByRegistrationSource($query, string $source)
    {
        return $query->where('registration_source', $source);
    }

    /**
     * Scope to get verified businesses.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get businesses with verified work email.
     */
    public function scopeWithVerifiedEmail($query)
    {
        return $query->where('work_email_verified', true);
    }

    /**
     * Scope to get activated businesses.
     */
    public function scopeActivated($query)
    {
        return $query->whereHas('onboarding', function ($q) {
            $q->where('is_activated', true);
        });
    }

    // ===== BIZ-REG-011: Activation Methods =====

    /**
     * Check if business is fully activated.
     */
    public function isActivated(): bool
    {
        return $this->onboarding?->is_activated ?? false;
    }

    /**
     * Check if business can post shifts.
     */
    public function canPostShifts(): bool
    {
        return $this->isActivated()
            && $this->can_post_shifts
            && $this->account_in_good_standing;
    }

    /**
     * Update activation requirements status cache.
     */
    public function updateActivationStatus(array $requirementsStatus): void
    {
        $metCount = collect($requirementsStatus)->filter(fn ($req) => $req['met'])->count();
        $totalCount = count($requirementsStatus);
        $percentage = $totalCount > 0 ? round(($metCount / $totalCount) * 100, 2) : 0;

        $this->update([
            'activation_requirements_status' => $requirementsStatus,
            'activation_requirements_met' => $metCount,
            'activation_requirements_total' => $totalCount,
            'activation_completion_percentage' => $percentage,
            'activation_checked' => true,
            'activation_checked_at' => now(),
            'last_activation_check' => now(),
        ]);
    }

    /**
     * Get activation completion percentage.
     */
    public function getActivationCompletionPercentage(): int
    {
        return $this->activation_completion_percentage ?? 0;
    }

    /**
     * Check if all activation requirements are met.
     */
    public function hasMetAllActivationRequirements(): bool
    {
        return $this->activation_completion_percentage >= 100;
    }

    /**
     * Add activation blocked reason.
     */
    public function addActivationBlockedReason(string $type, string $message, string $severity = 'warning'): void
    {
        $reasons = $this->activation_blocked_reasons ?? [];

        $reasons[] = [
            'type' => $type,
            'message' => $message,
            'severity' => $severity,
            'added_at' => now()->toIso8601String(),
        ];

        $this->update(['activation_blocked_reasons' => $reasons]);
    }

    /**
     * Clear activation blocked reasons.
     */
    public function clearActivationBlockedReasons(): void
    {
        $this->update(['activation_blocked_reasons' => []]);
    }

    /**
     * Get cached activation status or null if needs refresh.
     */
    public function getCachedActivationStatus(): ?array
    {
        // Cache is valid for 1 hour
        if (! $this->activation_checked || ! $this->last_activation_check) {
            return null;
        }

        if ($this->last_activation_check->diffInHours(now()) > 1) {
            return null;
        }

        return [
            'requirements_status' => $this->activation_requirements_status,
            'completion_percentage' => $this->activation_completion_percentage,
            'requirements_met' => $this->activation_requirements_met,
            'requirements_total' => $this->activation_requirements_total,
            'blocked_reasons' => $this->activation_blocked_reasons,
            'checked_at' => $this->last_activation_check->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Set activation notes.
     */
    public function setActivationNotes(string $notes): void
    {
        $this->update(['activation_notes' => $notes]);
    }
}
