<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $onboarding_completed
 * @property int|null $onboarding_step
 * @property string|null $onboarding_completed_at
 * @property string $agency_name
 * @property string|null $license_number
 * @property bool $license_verified
 * @property string $verification_status
 * @property string|null $verification_notes
 * @property string $business_model
 * @property numeric $commission_rate
 * @property numeric|null $variable_commission_rate
 * @property numeric $total_commission_earned
 * @property numeric $pending_commission
 * @property numeric $paid_commission
 * @property int $urgent_fill_enabled
 * @property numeric $urgent_fill_commission_multiplier
 * @property int $urgent_fills_completed
 * @property numeric $average_urgent_fill_time_hours
 * @property numeric $fill_rate
 * @property int $shifts_declined
 * @property int $worker_dropouts
 * @property numeric $client_satisfaction_score
 * @property int $repeat_clients
 * @property array<array-key, mixed>|null $managed_workers
 * @property int $total_shifts_managed
 * @property int $total_workers_managed
 * @property int $active_workers
 * @property int $available_workers
 * @property numeric $average_worker_rating
 * @property string|null $worker_skill_distribution
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
 * @property string|null $specializations
 * @property int $total_workers
 * @property int $total_placements
 * @property numeric $rating
 * @property int $is_verified
 * @property string|null $verified_at
 * @property int $is_complete
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AgencyWorker> $agencyWorkers
 * @property-read int|null $agency_workers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AgencyClient> $clients
 * @property-read int|null $clients_count
 * @property-read mixed $active_workers_count
 * @property-read mixed $shifts_filled_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $shifts
 * @property-read int|null $shifts_count
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $workers
 * @property-read int|null $workers_count
 *
 * @method static \Database\Factories\AgencyProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereActiveWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereAgencyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereAvailableWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereAverageUrgentFillTimeHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereAverageWorkerRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereBusinessModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereBusinessRegistrationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereClientSatisfactionScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereCommissionRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereFillRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereIsComplete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereLicenseVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereManagedWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereOnboardingCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereOnboardingCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereOnboardingStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile wherePaidCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile wherePendingCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereRepeatClients($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereShiftsDeclined($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereSpecializations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereTotalCommissionEarned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereTotalPlacements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereTotalShiftsManaged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereTotalWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereTotalWorkersManaged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereUrgentFillCommissionMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereUrgentFillEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereUrgentFillsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereVariableCommissionRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereVerificationNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereVerificationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereWorkerDropouts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereWorkerSkillDistribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AgencyProfile whereZipCode($value)
 *
 * @mixin \Eloquent
 */
class AgencyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agency_name',
        'license_number',
        'license_verified',
        'business_model',
        'commission_rate',
        'managed_workers',
        'total_shifts_managed',
        'total_workers_managed',
        // Tier fields (AGY-001)
        'agency_tier_id',
        'tier_achieved_at',
        'tier_review_at',
        'tier_metrics_snapshot',
        // Contact & Location
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'description',
        'website',
        // Stripe Connect fields
        'stripe_connect_account_id',
        'stripe_onboarding_complete',
        'stripe_onboarded_at',
        'stripe_payout_enabled',
        'stripe_account_type',
        'stripe_charges_enabled',
        'stripe_details_submitted',
        'stripe_requirements',
        'stripe_default_currency',
        'last_payout_at',
        'last_payout_amount',
        'last_payout_status',
        'total_payouts_count',
        'total_payouts_amount',
        // AGY-REG-005: Go-Live & Compliance fields
        'is_live',
        'activated_at',
        'activated_by',
        'go_live_requested_at',
        'compliance_score',
        'compliance_grade',
        'compliance_last_checked',
        'license_verified_at',
        'license_expires_at',
        'tax_id',
        'tax_verified',
        'tax_verified_at',
        'background_check_status',
        'background_check_passed',
        'background_check_initiated_at',
        'background_check_completed_at',
        'references',
        'agreement_signed',
        'agreement_signed_at',
        'agreement_version',
        'agreement_signer_name',
        'agreement_signer_title',
        'agreement_signer_ip',
        'test_shift_completed',
        'test_shift_id',
        'manual_verifications',
        'is_complete',
    ];

    protected $casts = [
        'license_verified' => 'boolean',
        'commission_rate' => 'decimal:2',
        'managed_workers' => 'array',
        'total_shifts_managed' => 'integer',
        'total_workers_managed' => 'integer',
        // Stripe Connect casts
        'stripe_onboarding_complete' => 'boolean',
        'stripe_onboarded_at' => 'datetime',
        'stripe_payout_enabled' => 'boolean',
        'stripe_charges_enabled' => 'boolean',
        'stripe_details_submitted' => 'boolean',
        'stripe_requirements' => 'array',
        'last_payout_at' => 'datetime',
        'last_payout_amount' => 'decimal:2',
        'total_payouts_count' => 'integer',
        'total_payouts_amount' => 'decimal:2',
        // AGY-REG-005: Go-Live & Compliance casts
        'is_live' => 'boolean',
        'activated_at' => 'datetime',
        'go_live_requested_at' => 'datetime',
        'compliance_score' => 'decimal:2',
        'compliance_last_checked' => 'datetime',
        'license_verified_at' => 'datetime',
        'license_expires_at' => 'date',
        'tax_verified' => 'boolean',
        'tax_verified_at' => 'datetime',
        'background_check_passed' => 'boolean',
        'background_check_initiated_at' => 'datetime',
        'background_check_completed_at' => 'datetime',
        'references' => 'array',
        'agreement_signed' => 'boolean',
        'agreement_signed_at' => 'datetime',
        'test_shift_completed' => 'boolean',
        'manual_verifications' => 'array',
        'is_complete' => 'boolean',
        // Tier casts (AGY-001)
        'tier_achieved_at' => 'datetime',
        'tier_review_at' => 'datetime',
        'tier_metrics_snapshot' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * AGY-001: Get the current tier for this agency.
     */
    public function tier()
    {
        return $this->belongsTo(AgencyTier::class, 'agency_tier_id');
    }

    /**
     * AGY-001: Get tier history for this agency.
     */
    public function tierHistory()
    {
        return $this->hasMany(AgencyTierHistory::class, 'agency_id', 'user_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get all workers managed by this agency.
     */
    public function workers()
    {
        return $this->belongsToMany(User::class, 'agency_workers', 'agency_id', 'worker_id')
            ->withPivot('status', 'commission_rate', 'notes', 'added_at')
            ->withTimestamps();
    }

    /**
     * Get agency worker pivot records.
     */
    public function agencyWorkers()
    {
        return $this->hasMany(AgencyWorker::class, 'agency_id', 'user_id');
    }

    /**
     * Get shifts posted by this agency.
     */
    public function shifts()
    {
        return $this->hasMany(Shift::class, 'posted_by_agency_id', 'user_id');
    }

    /**
     * Get all agency clients.
     */
    public function clients()
    {
        return $this->hasMany(AgencyClient::class, 'agency_id', 'user_id');
    }

    public function isLicenseVerified()
    {
        return $this->license_verified;
    }

    /**
     * Get active workers count.
     */
    public function getActiveWorkersCountAttribute()
    {
        return $this->agencyWorkers()->where('status', 'active')->count();
    }

    /**
     * Get total shifts filled by agency workers.
     */
    public function getShiftsFilledCountAttribute()
    {
        $workerIds = $this->agencyWorkers()->pluck('worker_id');

        return ShiftAssignment::whereIn('worker_id', $workerIds)
            ->where('status', 'completed')
            ->count();
    }

    // =========================================================================
    // STRIPE CONNECT METHODS (AGY-003)
    // =========================================================================

    /**
     * Check if agency has a Stripe Connect account.
     */
    public function hasStripeConnectAccount(): bool
    {
        return ! empty($this->stripe_connect_account_id);
    }

    /**
     * Check if agency has completed Stripe onboarding.
     */
    public function hasCompletedStripeOnboarding(): bool
    {
        return $this->stripe_onboarding_complete && $this->stripe_payout_enabled;
    }

    /**
     * Check if agency can receive payouts.
     */
    public function canReceivePayouts(): bool
    {
        return $this->hasStripeConnectAccount()
            && $this->stripe_onboarding_complete
            && $this->stripe_payout_enabled
            && $this->stripe_charges_enabled;
    }

    /**
     * Check if agency needs to complete Stripe onboarding.
     */
    public function needsStripeOnboarding(): bool
    {
        return ! $this->hasCompletedStripeOnboarding();
    }

    /**
     * Check if agency has pending Stripe requirements.
     */
    public function hasPendingStripeRequirements(): bool
    {
        return ! empty($this->stripe_requirements);
    }

    /**
     * Get human-readable Stripe onboarding status.
     */
    public function getStripeStatusAttribute(): string
    {
        if (! $this->hasStripeConnectAccount()) {
            return 'not_started';
        }

        if (! $this->stripe_details_submitted) {
            return 'pending_details';
        }

        if ($this->hasPendingStripeRequirements()) {
            return 'pending_verification';
        }

        if (! $this->stripe_charges_enabled || ! $this->stripe_payout_enabled) {
            return 'restricted';
        }

        return 'active';
    }

    /**
     * Get human-readable Stripe status label.
     */
    public function getStripeStatusLabelAttribute(): string
    {
        return match ($this->stripe_status) {
            'not_started' => 'Not Connected',
            'pending_details' => 'Onboarding Incomplete',
            'pending_verification' => 'Pending Verification',
            'restricted' => 'Restricted',
            'active' => 'Active',
            default => 'Unknown',
        };
    }

    /**
     * Get CSS class for Stripe status badge.
     */
    public function getStripeStatusClassAttribute(): string
    {
        return match ($this->stripe_status) {
            'not_started' => 'bg-gray-100 text-gray-700',
            'pending_details' => 'bg-yellow-100 text-yellow-700',
            'pending_verification' => 'bg-orange-100 text-orange-700',
            'restricted' => 'bg-red-100 text-red-700',
            'active' => 'bg-green-100 text-green-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Mark Stripe onboarding as complete.
     */
    public function markStripeOnboardingComplete(): void
    {
        $this->update([
            'stripe_onboarding_complete' => true,
            'stripe_onboarded_at' => now(),
        ]);
    }

    /**
     * Update Stripe account status from webhook data.
     */
    public function updateStripeAccountStatus(array $accountData): void
    {
        $this->update([
            'stripe_charges_enabled' => $accountData['charges_enabled'] ?? false,
            'stripe_payout_enabled' => $accountData['payouts_enabled'] ?? false,
            'stripe_details_submitted' => $accountData['details_submitted'] ?? false,
            'stripe_requirements' => $accountData['requirements']['currently_due'] ?? [],
        ]);
    }

    /**
     * Record a successful payout.
     */
    public function recordPayout(float $amount, string $transactionId): void
    {
        $this->update([
            'last_payout_at' => now(),
            'last_payout_amount' => $amount,
            'last_payout_status' => 'paid',
            'total_payouts_count' => $this->total_payouts_count + 1,
            'total_payouts_amount' => $this->total_payouts_amount + $amount,
        ]);
    }

    /**
     * Record a failed payout.
     */
    public function recordPayoutFailure(): void
    {
        $this->update([
            'last_payout_status' => 'failed',
        ]);
    }

    // =========================================================================
    // AGY-REG-005: GO-LIVE CHECKLIST & COMPLIANCE METHODS
    // =========================================================================

    /**
     * Get agency documents (compliance documents).
     * Note: Uses AgencyDocument model if application_id relationship exists,
     * otherwise returns empty collection.
     */
    public function documents()
    {
        // Try to find documents via agency_application if it exists
        if (class_exists(\App\Models\AgencyDocument::class)) {
            // Check if there's an agency application for this user
            if (class_exists(\App\Models\AgencyApplication::class)) {
                $application = \App\Models\AgencyApplication::where('user_id', $this->user_id)->first();
                if ($application) {
                    return \App\Models\AgencyDocument::where('application_id', $application->id);
                }
            }
        }

        // Return an empty query builder if no documents model or relationship exists
        return \App\Models\AgencyDocument::whereRaw('1 = 0');
    }

    /**
     * Check if agency is live and active.
     */
    public function isLive(): bool
    {
        return $this->is_live ?? false;
    }

    /**
     * Check if agency has completed go-live process.
     */
    public function hasCompletedGoLive(): bool
    {
        return $this->is_live && $this->activated_at !== null;
    }

    /**
     * Check if agency is pending go-live review.
     */
    public function isPendingGoLiveReview(): bool
    {
        return $this->verification_status === 'pending_review';
    }

    /**
     * Get compliance grade badge class.
     */
    public function getComplianceGradeClassAttribute(): string
    {
        return match ($this->compliance_grade ?? 'F') {
            'A' => 'bg-green-100 text-green-800',
            'B' => 'bg-blue-100 text-blue-800',
            'C' => 'bg-yellow-100 text-yellow-800',
            'D' => 'bg-orange-100 text-orange-800',
            'F' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get compliance grade label.
     */
    public function getComplianceGradeLabelAttribute(): string
    {
        return match ($this->compliance_grade ?? 'F') {
            'A' => 'Excellent',
            'B' => 'Good',
            'C' => 'Acceptable',
            'D' => 'Poor',
            'F' => 'Non-Compliant',
            default => 'Unknown',
        };
    }

    /**
     * Check if background check is required.
     */
    public function requiresBackgroundCheck(): bool
    {
        return $this->background_check_status === 'pending' ||
               $this->background_check_status === null;
    }

    /**
     * Check if background check has passed.
     */
    public function hasPassedBackgroundCheck(): bool
    {
        return $this->background_check_status === 'completed' &&
               $this->background_check_passed === true;
    }

    /**
     * Check if agreement is signed.
     */
    public function hasSignedAgreement(): bool
    {
        return $this->agreement_signed === true;
    }

    /**
     * Get days until license expires.
     */
    public function getLicenseExpiresInDaysAttribute(): ?int
    {
        if (! $this->license_expires_at) {
            return null;
        }

        return now()->diffInDays($this->license_expires_at, false);
    }

    /**
     * Check if license is expiring soon (within 30 days).
     */
    public function isLicenseExpiringSoon(): bool
    {
        $daysUntilExpiry = $this->license_expires_in_days;

        return $daysUntilExpiry !== null && $daysUntilExpiry <= 30 && $daysUntilExpiry > 0;
    }

    /**
     * Check if license has expired.
     */
    public function isLicenseExpired(): bool
    {
        if (! $this->license_expires_at) {
            return false;
        }

        return now()->isAfter($this->license_expires_at);
    }

    /**
     * Get profile completeness percentage.
     */
    public function getProfileCompletenessAttribute(): int
    {
        $requiredFields = [
            'agency_name',
            'phone',
            'address',
            'city',
            'state',
            'zip_code',
            'country',
            'description',
        ];

        $completedCount = 0;
        foreach ($requiredFields as $field) {
            if (! empty($this->$field)) {
                $completedCount++;
            }
        }

        return count($requiredFields) > 0
            ? round(($completedCount / count($requiredFields)) * 100)
            : 0;
    }

    /**
     * Check if profile is complete.
     */
    public function isProfileComplete(): bool
    {
        return $this->profile_completeness >= 100;
    }
}
