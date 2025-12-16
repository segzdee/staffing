<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\BusinessOnboarding;
use App\Models\BusinessVerification;
use App\Models\InsuranceVerification;
use App\Models\BusinessPaymentMethod;
use App\Models\Venue;
use App\Services\BusinessPaymentService;
use App\Services\BusinessVerificationService;
use App\Services\InsuranceVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Business Activation Controller
 * BIZ-REG-011: Business Account Activation
 *
 * Ensures businesses complete all required steps before posting shifts:
 * - Email verification complete
 * - Company profile complete
 * - KYB verification approved
 * - Insurance uploaded and verified
 * - At least one venue created
 * - Payment method added and verified
 */
class ActivationController extends Controller
{
    protected BusinessPaymentService $paymentService;
    protected BusinessVerificationService $verificationService;
    protected InsuranceVerificationService $insuranceService;

    public function __construct(
        BusinessPaymentService $paymentService,
        BusinessVerificationService $verificationService,
        InsuranceVerificationService $insuranceService
    ) {
        $this->paymentService = $paymentService;
        $this->verificationService = $verificationService;
        $this->insuranceService = $insuranceService;
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Get current activation status with all requirements.
     *
     * GET /api/business/activation/status
     */
    public function getActivationStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isBusiness()) {
            return response()->json([
                'success' => false,
                'message' => 'Only business accounts can check activation status',
            ], 403);
        }

        $profile = $user->businessProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        $status = $this->checkActivationRequirements($profile);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Check all activation requirements and return detailed status.
     *
     * @param BusinessProfile $profile
     * @return array
     */
    public function checkActivationRequirements(BusinessProfile $profile): array
    {
        $user = $profile->user;
        $onboarding = $profile->onboarding;

        // Requirement 1: Email Verification
        $emailVerified = $this->checkEmailVerification($user, $profile);

        // Requirement 2: Company Profile Complete
        $profileComplete = $this->checkProfileCompletion($profile, $onboarding);

        // Requirement 3: KYB Verification Approved
        $kybVerified = $this->checkKYBVerification($profile);

        // Requirement 4: Insurance Uploaded and Verified
        $insuranceVerified = $this->checkInsuranceVerification($profile);

        // Requirement 5: At Least One Venue Created
        $venueCreated = $this->checkVenueExists($profile);

        // Requirement 6: Payment Method Added and Verified
        $paymentVerified = $this->checkPaymentMethod($profile);

        // Determine overall activation status
        $requirements = [
            'email_verified' => $emailVerified,
            'profile_complete' => $profileComplete,
            'kyb_verified' => $kybVerified,
            'insurance_verified' => $insuranceVerified,
            'venue_created' => $venueCreated,
            'payment_verified' => $paymentVerified,
        ];

        $allMet = collect($requirements)->every(fn($req) => $req['met']);
        $totalRequirements = count($requirements);
        $completedRequirements = collect($requirements)->filter(fn($req) => $req['met'])->count();
        $completionPercentage = round(($completedRequirements / $totalRequirements) * 100, 2);

        // Get current activation status from onboarding
        $isActivated = $onboarding?->is_activated ?? false;

        // Determine next required step
        $nextStep = null;
        foreach ($requirements as $key => $req) {
            if (!$req['met']) {
                $nextStep = [
                    'requirement' => $key,
                    'label' => $req['label'],
                    'description' => $req['description'],
                    'action_url' => $req['action_url'],
                    'action_text' => $req['action_text'],
                    'priority' => $req['priority'],
                ];
                break;
            }
        }

        return [
            'is_activated' => $isActivated,
            'can_activate' => $allMet && !$isActivated,
            'can_post_shifts' => $isActivated && $allMet && $profile->can_post_shifts,
            'activation_status' => $this->determineActivationStatus($allMet, $isActivated, $completedRequirements),
            'completion_percentage' => $completionPercentage,
            'completed_requirements' => $completedRequirements,
            'total_requirements' => $totalRequirements,
            'requirements' => $requirements,
            'next_step' => $nextStep,
            'activated_at' => $onboarding?->activated_at?->format('Y-m-d H:i:s'),
            'blocked_reasons' => $this->getBlockedReasons($profile),
        ];
    }

    /**
     * Check email verification status.
     */
    protected function checkEmailVerification($user, BusinessProfile $profile): array
    {
        // Check both user email verification and work email verification
        $userEmailVerified = $user->email_verified_at !== null;
        $workEmailVerified = $profile->work_email_verified ?? false;

        $met = $userEmailVerified && $workEmailVerified;

        return [
            'met' => $met,
            'label' => 'Email Verification',
            'description' => 'Both your account email and work email must be verified',
            'action_url' => $met ? null : route('business.profile.verify-email'),
            'action_text' => $met ? null : 'Verify Email',
            'priority' => 1,
            'details' => [
                'account_email_verified' => $userEmailVerified,
                'work_email_verified' => $workEmailVerified,
                'account_email' => $user->email,
                'work_email' => $profile->work_email,
            ],
        ];
    }

    /**
     * Check profile completion status.
     */
    protected function checkProfileCompletion(BusinessProfile $profile, ?BusinessOnboarding $onboarding): array
    {
        $completionPercentage = $profile->profile_completion_percentage ?? 0;
        $minimumRequired = 80;
        $met = $completionPercentage >= $minimumRequired && ($onboarding?->profile_minimum_met ?? false);

        return [
            'met' => $met,
            'label' => 'Company Profile',
            'description' => "Your profile must be at least {$minimumRequired}% complete with all required information",
            'action_url' => $met ? null : route('business.profile.edit'),
            'action_text' => $met ? null : 'Complete Profile',
            'priority' => 2,
            'details' => [
                'completion_percentage' => $completionPercentage,
                'minimum_required' => $minimumRequired,
                'missing_fields' => $onboarding?->missing_fields ?? [],
            ],
        ];
    }

    /**
     * Check KYB verification status.
     */
    protected function checkKYBVerification(BusinessProfile $profile): array
    {
        $verification = BusinessVerification::where('business_profile_id', $profile->id)
            ->where('verification_type', BusinessVerification::TYPE_KYB)
            ->latest()
            ->first();

        $met = $verification && $verification->status === BusinessVerification::STATUS_APPROVED;

        return [
            'met' => $met,
            'label' => 'Business Verification (KYB)',
            'description' => 'Your business must be verified through document submission and review',
            'action_url' => $met ? null : route('business.verification.index'),
            'action_text' => $met ? null : ($verification ? 'View Status' : 'Start Verification'),
            'priority' => 3,
            'details' => [
                'status' => $verification?->status ?? 'not_started',
                'submitted_at' => $verification?->submitted_at?->format('Y-m-d H:i:s'),
                'reviewed_at' => $verification?->reviewed_at?->format('Y-m-d H:i:s'),
                'rejection_reason' => $verification?->rejection_reason,
                'documents_submitted' => $verification?->documents()->count() ?? 0,
            ],
        ];
    }

    /**
     * Check insurance verification status.
     */
    protected function checkInsuranceVerification(BusinessProfile $profile): array
    {
        $verification = InsuranceVerification::where('business_profile_id', $profile->id)
            ->first();

        // Check if all required insurance types are verified
        $met = false;
        $certificatesCount = 0;
        $verifiedCount = 0;

        if ($verification) {
            $certificates = $verification->certificates()
                ->whereNull('deleted_at')
                ->get();

            $certificatesCount = $certificates->count();
            $verifiedCount = $certificates->where('status', 'verified')->count();

            // Check if at least general liability is verified (most common requirement)
            $hasGeneralLiability = $certificates
                ->where('insurance_type', 'general_liability')
                ->where('status', 'verified')
                ->isNotEmpty();

            $met = $hasGeneralLiability && $verification->status === InsuranceVerification::STATUS_COMPLETE;
        }

        return [
            'met' => $met,
            'label' => 'Insurance Certificate',
            'description' => 'Valid insurance certificate must be uploaded and verified',
            'action_url' => $met ? null : route('business.insurance.index'),
            'action_text' => $met ? null : ($verification ? 'Upload Certificate' : 'Get Started'),
            'priority' => 4,
            'details' => [
                'status' => $verification?->status ?? 'not_started',
                'certificates_count' => $certificatesCount,
                'verified_count' => $verifiedCount,
                'jurisdiction' => $verification?->jurisdiction,
            ],
        ];
    }

    /**
     * Check if at least one venue exists.
     */
    protected function checkVenueExists(BusinessProfile $profile): array
    {
        $venuesCount = Venue::where('business_profile_id', $profile->id)
            ->where('is_active', true)
            ->count();

        $met = $venuesCount > 0;

        return [
            'met' => $met,
            'label' => 'Venue/Location',
            'description' => 'At least one active venue or work location must be set up',
            'action_url' => $met ? null : route('business.venues.create'),
            'action_text' => $met ? null : 'Add Venue',
            'priority' => 5,
            'details' => [
                'venues_count' => $venuesCount,
                'active_venues' => $profile->active_venues ?? 0,
            ],
        ];
    }

    /**
     * Check payment method verification.
     */
    protected function checkPaymentMethod(BusinessProfile $profile): array
    {
        $hasPaymentMethod = $profile->payment_setup_complete ?? false;
        $canPostShifts = $this->paymentService->canBusinessPostShifts($profile);

        $paymentMethods = BusinessPaymentMethod::where('business_profile_id', $profile->id)
            ->usable()
            ->get();

        $met = $hasPaymentMethod && $canPostShifts && $paymentMethods->isNotEmpty();

        return [
            'met' => $met,
            'label' => 'Payment Method',
            'description' => 'A verified payment method must be added for worker payments',
            'action_url' => $met ? null : route('business.payment.setup'),
            'action_text' => $met ? null : 'Add Payment Method',
            'priority' => 6,
            'details' => [
                'payment_setup_complete' => $hasPaymentMethod,
                'payment_methods_count' => $paymentMethods->count(),
                'default_method' => $profile->default_payment_method,
                'stripe_customer_id' => $profile->stripe_customer_id ? 'Set' : 'Not set',
            ],
        ];
    }

    /**
     * Determine overall activation status message.
     */
    protected function determineActivationStatus(bool $allMet, bool $isActivated, int $completedCount): string
    {
        if ($isActivated) {
            return 'activated';
        }

        if ($allMet) {
            return 'ready_for_activation';
        }

        if ($completedCount === 0) {
            return 'not_started';
        }

        if ($completedCount < 3) {
            return 'early_stage';
        }

        return 'in_progress';
    }

    /**
     * Get reasons why account might be blocked from posting shifts.
     */
    protected function getBlockedReasons(BusinessProfile $profile): array
    {
        $reasons = [];

        if (!$profile->account_in_good_standing) {
            $reasons[] = [
                'type' => 'account_standing',
                'message' => $profile->account_warning_message ?? 'Account is not in good standing',
                'severity' => 'critical',
            ];
        }

        if (!$profile->can_post_shifts) {
            $reasons[] = [
                'type' => 'posting_suspended',
                'message' => 'Shift posting has been temporarily suspended',
                'severity' => 'critical',
            ];
        }

        if ($profile->hasExceededCreditLimit()) {
            $reasons[] = [
                'type' => 'credit_limit',
                'message' => 'Monthly credit limit has been exceeded',
                'severity' => 'warning',
            ];
        }

        return $reasons;
    }

    /**
     * Activate a business account after all requirements are met.
     *
     * POST /api/business/activation/activate
     */
    public function activateAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isBusiness()) {
            return response()->json([
                'success' => false,
                'message' => 'Only business accounts can be activated',
            ], 403);
        }

        $profile = $user->businessProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        // Check if already activated
        $onboarding = $profile->onboarding;
        if ($onboarding?->is_activated) {
            return response()->json([
                'success' => true,
                'message' => 'Account is already activated',
                'data' => [
                    'is_activated' => true,
                    'activated_at' => $onboarding->activated_at->format('Y-m-d H:i:s'),
                ],
            ]);
        }

        // Verify all requirements are met
        $status = $this->checkActivationRequirements($profile);

        if (!$status['can_activate']) {
            return response()->json([
                'success' => false,
                'message' => 'Not all activation requirements are met',
                'data' => [
                    'completion_percentage' => $status['completion_percentage'],
                    'completed_requirements' => $status['completed_requirements'],
                    'total_requirements' => $status['total_requirements'],
                    'next_step' => $status['next_step'],
                    'requirements' => $status['requirements'],
                ],
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create or update onboarding record
            if (!$onboarding) {
                $onboarding = BusinessOnboarding::create([
                    'business_profile_id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'status' => 'completed',
                ]);
            }

            // Mark as activated
            $onboarding->update([
                'is_activated' => true,
                'activated_at' => now(),
                'status' => 'completed',
                'completed_at' => $onboarding->completed_at ?? now(),
            ]);

            // Update business profile
            $profile->update([
                'can_post_shifts' => true,
                'account_in_good_standing' => true,
            ]);

            DB::commit();

            Log::info('Business account activated', [
                'business_profile_id' => $profile->id,
                'user_id' => $profile->user_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your business account has been activated! You can now post shifts.',
                'data' => [
                    'is_activated' => true,
                    'activated_at' => $onboarding->activated_at->format('Y-m-d H:i:s'),
                    'can_post_shifts' => true,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to activate business account', [
                'business_profile_id' => $profile->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate account. Please try again or contact support.',
            ], 500);
        }
    }

    /**
     * Check if business can post shifts (quick check for middleware).
     *
     * GET /api/business/activation/can-post-shifts
     */
    public function canPostShifts(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isBusiness()) {
            return response()->json([
                'success' => false,
                'can_post' => false,
                'reason' => 'Not a business account',
            ]);
        }

        $profile = $user->businessProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'can_post' => false,
                'reason' => 'Business profile not found',
            ]);
        }

        $onboarding = $profile->onboarding;
        $isActivated = $onboarding?->is_activated ?? false;
        $canPost = $profile->can_post_shifts && $profile->account_in_good_standing;

        $status = $this->checkActivationRequirements($profile);
        $allRequirementsMet = $status['completion_percentage'] >= 100;

        return response()->json([
            'success' => true,
            'can_post' => $isActivated && $canPost && $allRequirementsMet,
            'is_activated' => $isActivated,
            'account_in_good_standing' => $profile->account_in_good_standing,
            'requirements_met' => $allRequirementsMet,
            'completion_percentage' => $status['completion_percentage'],
            'blocked_reasons' => $status['blocked_reasons'],
        ]);
    }
}
