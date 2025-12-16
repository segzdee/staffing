<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerActivationLog;
use App\Models\OnboardingStep;
use App\Models\OnboardingProgress;
use App\Notifications\WorkerActivatedNotification;
use App\Notifications\WelcomeToMarketplaceNotification;
use App\Notifications\FirstShiftGuidanceNotification;
use Illuminate\Support\Facades\DB;

/**
 * Worker Activation Service
 * STAFF-REG-010: Worker Activation Flow
 *
 * Handles worker activation eligibility checks and activation process.
 */
class WorkerActivationService
{
    protected OnboardingService $onboardingService;
    protected ProfileCompletionService $profileCompletionService;

    /**
     * Default initial reliability score for new workers.
     */
    public const INITIAL_RELIABILITY_SCORE = 80.0;

    /**
     * Default initial tier for new workers.
     */
    public const INITIAL_TIER = 'bronze';

    /**
     * Minimum profile completeness for activation.
     */
    public const MIN_PROFILE_COMPLETENESS = 80;

    /**
     * Referral bonus amount (in cents).
     */
    public const REFERRAL_BONUS_AMOUNT = 2500; // $25.00

    public function __construct(
        OnboardingService $onboardingService,
        ProfileCompletionService $profileCompletionService
    ) {
        $this->onboardingService = $onboardingService;
        $this->profileCompletionService = $profileCompletionService;
    }

    /**
     * Check if a worker is eligible for activation.
     */
    public function checkActivationEligibility(User $worker): array
    {
        if ($worker->user_type !== 'worker') {
            return [
                'eligible' => false,
                'error' => 'User is not a worker.',
            ];
        }

        $profile = $worker->workerProfile;

        if (!$profile) {
            return [
                'eligible' => false,
                'error' => 'Worker profile not found.',
            ];
        }

        $checks = [];
        $requiredPassed = true;
        $recommendedPassed = 0;
        $recommendedTotal = 6;

        // === REQUIRED CHECKS ===

        // 1. Email verified
        $emailVerified = $worker->email_verified_at !== null;
        $checks['email_verified'] = [
            'name' => 'Email Verified',
            'passed' => $emailVerified,
            'required' => true,
            'message' => $emailVerified ? 'Email verified' : 'Please verify your email address',
        ];
        if (!$emailVerified) $requiredPassed = false;

        // 2. Phone verified
        $phoneVerified = $profile->phone_verified ?? false;
        $checks['phone_verified'] = [
            'name' => 'Phone Verified',
            'passed' => $phoneVerified,
            'required' => true,
            'message' => $phoneVerified ? 'Phone verified' : 'Please verify your phone number',
        ];
        if (!$phoneVerified) $requiredPassed = false;

        // 3. Profile complete (80%+)
        $completion = $this->profileCompletionService->calculateCompletion($worker);
        $profileComplete = $completion['percentage'] >= self::MIN_PROFILE_COMPLETENESS;
        $checks['profile_complete'] = [
            'name' => 'Profile Complete',
            'passed' => $profileComplete,
            'required' => true,
            'value' => $completion['percentage'],
            'threshold' => self::MIN_PROFILE_COMPLETENESS,
            'message' => $profileComplete
                ? "Profile {$completion['percentage']}% complete"
                : "Profile is {$completion['percentage']}% complete (need {self::MIN_PROFILE_COMPLETENESS}%)",
        ];
        if (!$profileComplete) $requiredPassed = false;

        // 4. Identity verified
        $identityVerified = $profile->identity_verified ?? false;
        $checks['identity_verified'] = [
            'name' => 'Identity Verified',
            'passed' => $identityVerified,
            'required' => true,
            'message' => $identityVerified ? 'Identity verified' : 'Please complete identity verification',
        ];
        if (!$identityVerified) $requiredPassed = false;

        // 5. Right to Work verified
        $rtwVerified = $profile->rtw_verified ?? false;
        $checks['rtw_verified'] = [
            'name' => 'Right to Work Verified',
            'passed' => $rtwVerified,
            'required' => true,
            'message' => $rtwVerified ? 'Right to work verified' : 'Please complete right to work verification',
        ];
        if (!$rtwVerified) $requiredPassed = false;

        // 6. Background check (clear or pending is acceptable)
        $bgStatus = $profile->background_check_status ?? 'not_started';
        $bgAcceptable = in_array($bgStatus, ['approved', 'clear', 'pending']);
        $checks['background_check'] = [
            'name' => 'Background Check',
            'passed' => $bgAcceptable,
            'required' => true,
            'value' => $bgStatus,
            'message' => $bgAcceptable
                ? "Background check: {$bgStatus}"
                : 'Please initiate background check',
        ];
        if (!$bgAcceptable) $requiredPassed = false;

        // 7. Payment setup complete
        $paymentSetup = $profile->payment_setup_complete ?? false;
        $checks['payment_setup'] = [
            'name' => 'Payment Setup',
            'passed' => $paymentSetup,
            'required' => true,
            'message' => $paymentSetup ? 'Payment method configured' : 'Please set up your payment method',
        ];
        if (!$paymentSetup) $requiredPassed = false;

        // === RECOMMENDED CHECKS ===

        // 1. Skills added (3+)
        $skillsCount = $worker->skills()->count();
        $skillsAdded = $skillsCount >= 3;
        $checks['skills_added'] = [
            'name' => '3+ Skills Added',
            'passed' => $skillsAdded,
            'required' => false,
            'value' => $skillsCount,
            'target' => 3,
            'message' => $skillsAdded ? "{$skillsCount} skills added" : "Add more skills ({$skillsCount}/3)",
        ];
        if ($skillsAdded) $recommendedPassed++;

        // 2. Certifications uploaded
        $certsCount = $worker->certifications()->count();
        $certsUploaded = $certsCount >= 1;
        $checks['certifications_uploaded'] = [
            'name' => 'Certifications Uploaded',
            'passed' => $certsUploaded,
            'required' => false,
            'value' => $certsCount,
            'target' => 1,
            'message' => $certsUploaded ? "{$certsCount} certification(s) uploaded" : 'Upload at least 1 certification',
        ];
        if ($certsUploaded) $recommendedPassed++;

        // 3. Availability set
        $availabilitySet = $profile->hasAvailabilitySet();
        $checks['availability_set'] = [
            'name' => 'Availability Set',
            'passed' => $availabilitySet,
            'required' => false,
            'message' => $availabilitySet ? 'Availability configured' : 'Set your availability schedule',
        ];
        if ($availabilitySet) $recommendedPassed++;

        // 4. Bio written
        $bioWritten = $profile->bio && strlen($profile->bio) >= 50;
        $checks['bio_written'] = [
            'name' => 'Bio Written',
            'passed' => $bioWritten,
            'required' => false,
            'message' => $bioWritten ? 'Bio written' : 'Write a professional bio (50+ characters)',
        ];
        if ($bioWritten) $recommendedPassed++;

        // 5. Emergency contact added
        $emergencyContactAdded = $profile->emergency_contact_name && $profile->emergency_contact_phone;
        $checks['emergency_contact_added'] = [
            'name' => 'Emergency Contact',
            'passed' => $emergencyContactAdded,
            'required' => false,
            'message' => $emergencyContactAdded ? 'Emergency contact added' : 'Add an emergency contact',
        ];
        if ($emergencyContactAdded) $recommendedPassed++;

        // 6. Profile photo approved
        $photoApproved = $profile->profile_photo_status === 'approved';
        $checks['profile_photo_approved'] = [
            'name' => 'Profile Photo Approved',
            'passed' => $photoApproved,
            'required' => false,
            'message' => $photoApproved ? 'Profile photo approved' : 'Upload and get your profile photo approved',
        ];
        if ($photoApproved) $recommendedPassed++;

        // Calculate required counts
        $requiredChecks = array_filter($checks, fn($c) => $c['required']);
        $requiredComplete = count(array_filter($requiredChecks, fn($c) => $c['passed']));
        $requiredTotal = count($requiredChecks);

        // Update or create activation log
        $log = WorkerActivationLog::updateOrCreate(
            ['user_id' => $worker->id],
            [
                'eligibility_checks' => $checks,
                'all_required_complete' => $requiredPassed,
                'required_steps_complete' => $requiredComplete,
                'required_steps_total' => $requiredTotal,
                'recommended_steps_complete' => $recommendedPassed,
                'recommended_steps_total' => $recommendedTotal,
                'profile_completeness' => $completion['percentage'],
                'skills_count' => $skillsCount,
                'certifications_count' => $certsCount,
                'status' => $requiredPassed ? 'eligible' : 'pending',
            ]
        );

        return [
            'eligible' => $requiredPassed,
            'checks' => $checks,
            'summary' => [
                'required_complete' => $requiredComplete,
                'required_total' => $requiredTotal,
                'recommended_complete' => $recommendedPassed,
                'recommended_total' => $recommendedTotal,
                'profile_completeness' => $completion['percentage'],
            ],
            'activation_log' => $log,
        ];
    }

    /**
     * Check if a worker can be activated (simple boolean check).
     *
     * @param int|User $worker Worker ID or User instance
     * @return bool
     */
    public function canActivate(int|User $worker): bool
    {
        if (is_int($worker)) {
            $worker = User::findOrFail($worker);
        }

        $eligibility = $this->checkActivationEligibility($worker);
        return $eligibility['eligible'] ?? false;
    }

    /**
     * Activate a worker.
     */
    public function activateWorker(User $worker, ?int $adminId = null, string $source = 'self'): array
    {
        $eligibility = $this->checkActivationEligibility($worker);

        if (!$eligibility['eligible'] && $source !== 'admin') {
            return [
                'success' => false,
                'error' => 'Worker is not eligible for activation.',
                'eligibility' => $eligibility,
            ];
        }

        DB::beginTransaction();

        try {
            $profile = $worker->workerProfile;

            // Assign initial tier
            $this->assignInitialTier($worker);

            // Set initial reliability score
            $this->setInitialReliabilityScore($worker);

            // Mark as activated
            $profile->update([
                'is_activated' => true,
                'activated_at' => now(),
                'is_matching_eligible' => true,
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

            // Update user record
            $worker->update([
                'onboarding_completed' => true,
            ]);

            // Update activation log
            $log = WorkerActivationLog::getOrCreateForUser($worker->id);
            $log->markActivated($adminId, $source);

            // Process referral bonus if applicable
            if ($log->referral_code_used) {
                $this->processReferralBonus($worker);
            }

            // Create first shift guidance
            $this->createFirstShiftGuidance($worker);

            // Send notifications
            $this->sendActivationNotifications($worker);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Worker activated successfully!',
                'worker' => $worker->fresh(),
                'profile' => $profile->fresh(),
                'activation_log' => $log->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Assign initial tier to worker.
     */
    public function assignInitialTier(User $worker): void
    {
        $profile = $worker->workerProfile;

        if ($profile) {
            $profile->update([
                'subscription_tier' => self::INITIAL_TIER,
            ]);
        }

        // Update activation log
        $log = WorkerActivationLog::getOrCreateForUser($worker->id);
        $log->update(['initial_tier' => self::INITIAL_TIER]);
    }

    /**
     * Set initial reliability score.
     */
    public function setInitialReliabilityScore(User $worker): void
    {
        $profile = $worker->workerProfile;

        if ($profile) {
            $profile->update([
                'reliability_score' => self::INITIAL_RELIABILITY_SCORE,
            ]);
        }

        $worker->update([
            'reliability_score' => self::INITIAL_RELIABILITY_SCORE,
        ]);

        // Update activation log
        $log = WorkerActivationLog::getOrCreateForUser($worker->id);
        $log->update(['initial_reliability_score' => self::INITIAL_RELIABILITY_SCORE]);
    }

    /**
     * Create first shift guidance content.
     */
    public function createFirstShiftGuidance(User $worker): void
    {
        $profile = $worker->workerProfile;

        if ($profile && !$profile->first_shift_guidance_shown) {
            // Send first shift guidance notification
            try {
                $worker->notify(new FirstShiftGuidanceNotification());
            } catch (\Exception $e) {
                \Log::warning("Failed to send first shift guidance to user {$worker->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Process referral bonus for worker and referrer.
     */
    public function processReferralBonus(User $worker): array
    {
        $log = WorkerActivationLog::getOrCreateForUser($worker->id);

        if (!$log->hasUnprocessedReferralBonus()) {
            return [
                'success' => false,
                'message' => 'No referral bonus to process.',
            ];
        }

        DB::beginTransaction();

        try {
            $referrer = $log->referrer;

            if (!$referrer || !$referrer->workerProfile) {
                $log->processReferralBonus();
                DB::commit();
                return [
                    'success' => false,
                    'message' => 'Referrer not found.',
                ];
            }

            // Credit referrer's earnings
            $referrerProfile = $referrer->workerProfile;
            $referrerProfile->processReferralReward($log->referral_bonus_amount / 100); // Convert cents to dollars

            // Mark bonus as processed
            $log->processReferralBonus();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Referral bonus processed successfully.',
                'referrer_id' => $referrer->id,
                'bonus_amount' => $log->referral_bonus_amount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Apply a referral code to a worker.
     */
    public function applyReferralCode(User $worker, string $referralCode): array
    {
        // Find referrer by code
        $referrer = WorkerProfile::where('referral_code', strtoupper($referralCode))
            ->whereHas('user', function ($q) {
                $q->where('status', 'active');
            })
            ->first();

        if (!$referrer) {
            return [
                'success' => false,
                'error' => 'Invalid referral code.',
            ];
        }

        if ($referrer->user_id === $worker->id) {
            return [
                'success' => false,
                'error' => 'You cannot use your own referral code.',
            ];
        }

        $log = WorkerActivationLog::getOrCreateForUser($worker->id);

        if ($log->referral_code_used) {
            return [
                'success' => false,
                'error' => 'A referral code has already been applied.',
            ];
        }

        $log->update([
            'referral_code_used' => strtoupper($referralCode),
            'referred_by_user_id' => $referrer->user_id,
            'referral_bonus_amount' => self::REFERRAL_BONUS_AMOUNT,
        ]);

        return [
            'success' => true,
            'message' => 'Referral code applied successfully!',
            'referrer_name' => $referrer->user->name ?? 'Anonymous',
        ];
    }

    /**
     * Get activation status for a worker.
     */
    public function getActivationStatus(User $worker): array
    {
        $profile = $worker->workerProfile;
        $log = WorkerActivationLog::where('user_id', $worker->id)->first();

        $eligibility = $this->checkActivationEligibility($worker);

        return [
            'is_activated' => $profile?->is_activated ?? false,
            'activated_at' => $profile?->activated_at,
            'is_matching_eligible' => $profile?->is_matching_eligible ?? false,
            'eligibility' => $eligibility,
            'tier' => $profile?->subscription_tier ?? 'none',
            'reliability_score' => $profile?->reliability_score ?? 0,
            'days_since_registration' => $worker->created_at->diffInDays(now()),
            'days_to_activation' => $log?->days_to_activation,
            'activation_source' => $log?->activation_source,
        ];
    }

    /**
     * Send activation-related notifications.
     */
    protected function sendActivationNotifications(User $worker): void
    {
        try {
            // Send worker activated notification
            $worker->notify(new WorkerActivatedNotification());

            // Send welcome to marketplace notification
            $worker->notify(new WelcomeToMarketplaceNotification());
        } catch (\Exception $e) {
            \Log::warning("Failed to send activation notifications to user {$worker->id}: " . $e->getMessage());
        }
    }

    /**
     * Get analytics for activation tracking.
     */
    public function getActivationAnalytics(string $period = '30d'): array
    {
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30,
        };

        $startDate = now()->subDays($days);

        $totalActivations = WorkerActivationLog::activated()
            ->where('activated_at', '>=', $startDate)
            ->count();

        $avgDaysToActivation = WorkerActivationLog::activated()
            ->where('activated_at', '>=', $startDate)
            ->whereNotNull('days_to_activation')
            ->avg('days_to_activation');

        $activationsBySource = WorkerActivationLog::activated()
            ->where('activated_at', '>=', $startDate)
            ->selectRaw('activation_source, COUNT(*) as count')
            ->groupBy('activation_source')
            ->pluck('count', 'activation_source')
            ->toArray();

        $pendingActivations = WorkerActivationLog::pending()->count();
        $eligibleNotActivated = WorkerActivationLog::eligible()->count();

        // Step completion rates
        $stepCompletionRates = $this->getStepCompletionRates();

        // Drop-off analysis
        $dropOffPoints = $this->getDropOffPoints();

        return [
            'period' => $period,
            'total_activations' => $totalActivations,
            'avg_days_to_activation' => round($avgDaysToActivation ?? 0, 1),
            'activations_by_source' => $activationsBySource,
            'pending_activations' => $pendingActivations,
            'eligible_not_activated' => $eligibleNotActivated,
            'step_completion_rates' => $stepCompletionRates,
            'drop_off_points' => $dropOffPoints,
        ];
    }

    /**
     * Get step completion rates.
     */
    protected function getStepCompletionRates(): array
    {
        $steps = OnboardingStep::active()
            ->forUserType('worker')
            ->required()
            ->get();

        $totalWorkers = User::where('user_type', 'worker')->count();

        $rates = [];

        foreach ($steps as $step) {
            $completed = OnboardingProgress::where('onboarding_step_id', $step->id)
                ->completed()
                ->count();

            $rates[$step->step_id] = [
                'name' => $step->name,
                'completed' => $completed,
                'total' => $totalWorkers,
                'rate' => $totalWorkers > 0 ? round(($completed / $totalWorkers) * 100, 1) : 0,
            ];
        }

        return $rates;
    }

    /**
     * Get drop-off points analysis.
     */
    protected function getDropOffPoints(): array
    {
        $steps = OnboardingStep::active()
            ->forUserType('worker')
            ->required()
            ->ordered()
            ->get();

        $dropOffs = [];
        $previousRate = 100;

        foreach ($steps as $step) {
            $completed = OnboardingProgress::where('onboarding_step_id', $step->id)
                ->completed()
                ->count();

            $totalWorkers = User::where('user_type', 'worker')->count();
            $currentRate = $totalWorkers > 0 ? round(($completed / $totalWorkers) * 100, 1) : 0;

            $dropOff = $previousRate - $currentRate;

            $dropOffs[$step->step_id] = [
                'name' => $step->name,
                'completion_rate' => $currentRate,
                'drop_off_rate' => max(0, $dropOff),
            ];

            $previousRate = $currentRate;
        }

        // Sort by drop-off rate descending
        uasort($dropOffs, fn($a, $b) => $b['drop_off_rate'] <=> $a['drop_off_rate']);

        return $dropOffs;
    }
}
