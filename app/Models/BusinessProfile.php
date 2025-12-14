<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'business_type',
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
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
        return !empty($this->business_license_url) &&
               !empty($this->insurance_certificate_url) &&
               !empty($this->tax_document_url);
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
            ->where('shift_id', 'IN', function($query) {
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
        if (!$this->monthly_credit_limit) {
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
        if (!in_array($workerId, $preferred)) {
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
        if (!in_array($workerId, $blacklisted)) {
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
        $preferred = array_values(array_filter($preferred, fn($id) => $id != $workerId));
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

        if (!$this->allow_new_workers && $worker->workerProfile->total_shifts_completed === 0) {
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
}
