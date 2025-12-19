<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\OnboardingProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Business Onboarding Controller
 * BIZ-REG-010: Enhanced Business Onboarding Progress Tracking
 *
 * Handles business onboarding flow, progress tracking, and step completion.
 * Uses OnboardingProgressService for sophisticated weighted progress calculation.
 */
class OnboardingController extends Controller
{
    protected OnboardingProgressService $onboardingProgressService;

    public function __construct(OnboardingProgressService $onboardingProgressService)
    {
        $this->middleware(['auth', 'business']);
        $this->onboardingProgressService = $onboardingProgressService;
    }

    /**
     * Show the profile completion page for businesses.
     * Guides businesses through completing their profile after registration.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function completeProfile()
    {
        $user = Auth::user();
        $user->load('businessProfile');

        // Get detailed onboarding progress using service
        $progress = $this->onboardingProgressService->getProgressData($user);

        // If profile is already complete (>=80%), redirect to dashboard
        if ($progress['overall_progress'] >= 80) {
            return redirect()->route('business.dashboard')
                ->with('success', 'Your profile is complete! Start posting shifts.');
        }

        // Get missing fields for backwards compatibility
        $missingFields = $this->getMissingFields($user);

        return view('business.onboarding.complete-profile', [
            'user' => $user,
            'completeness' => $progress['overall_progress'],
            'progress' => $progress,
            'missingFields' => $missingFields,
            'nextStep' => $progress['next_step'],
        ]);
    }

    /**
     * Show payment setup page.
     * Guides businesses through setting up payment methods.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function setupPayment()
    {
        $user = Auth::user();

        // Check if payment method is already configured
        $hasPaymentMethod = $this->hasValidPaymentMethod($user);

        if ($hasPaymentMethod) {
            return redirect()->route('business.dashboard')
                ->with('success', 'Payment method already configured.');
        }

        return view('business.onboarding.setup-payment', compact('user'));
    }

    /**
     * Check if the business has a valid payment method configured.
     *
     * Checks multiple payment gateways in order of priority:
     * 1. Stripe (via Laravel Cashier)
     * 2. PayPal
     * 3. Other regional gateways (Paystack, Razorpay, Mollie, etc.)
     *
     * @param  \App\Models\User  $user
     */
    protected function hasValidPaymentMethod($user): bool
    {
        $profile = $user->businessProfile;

        // Check profile flag first (fastest check)
        if ($profile && $profile->has_payment_method) {
            return true;
        }

        // Check Stripe via Laravel Cashier (if Billable trait is used)
        if ($this->hasStripePaymentMethod($user)) {
            // Update profile flag for future fast checks
            $this->updatePaymentMethodFlag($profile, true);

            return true;
        }

        // Check PayPal stored credentials
        if ($this->hasPayPalPaymentMethod($profile)) {
            $this->updatePaymentMethodFlag($profile, true);

            return true;
        }

        // Check other regional payment gateways
        if ($this->hasRegionalPaymentMethod($profile)) {
            $this->updatePaymentMethodFlag($profile, true);

            return true;
        }

        return false;
    }

    /**
     * Check if user has a Stripe payment method via Laravel Cashier.
     *
     * @param  \App\Models\User  $user
     */
    protected function hasStripePaymentMethod($user): bool
    {
        // Check if the user model uses Laravel Cashier's Billable trait
        if (! method_exists($user, 'hasDefaultPaymentMethod')) {
            return false;
        }

        try {
            // Check for default payment method (card, bank account, etc.)
            if ($user->hasDefaultPaymentMethod()) {
                return true;
            }

            // Also check for any stored payment methods
            $paymentMethods = $user->paymentMethods();

            return $paymentMethods->isNotEmpty();

        } catch (\Exception $e) {
            // Stripe API might be unavailable or credentials misconfigured
            \Log::warning('Stripe payment method check failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if business has PayPal payment method configured.
     *
     * @param  \App\Models\BusinessProfile|null  $profile
     */
    protected function hasPayPalPaymentMethod($profile): bool
    {
        if (! $profile) {
            return false;
        }

        // Check for stored PayPal billing agreement or payer ID
        return ! empty($profile->paypal_payer_id)
            || ! empty($profile->paypal_billing_agreement_id);
    }

    /**
     * Check if business has regional payment gateway configured.
     *
     * Supports: Paystack (Africa), Razorpay (India), Mollie (Europe),
     * Flutterwave (Africa), MercadoPago (Latin America)
     *
     * @param  \App\Models\BusinessProfile|null  $profile
     */
    protected function hasRegionalPaymentMethod($profile): bool
    {
        if (! $profile) {
            return false;
        }

        // Check for any regional payment gateway authorization
        $regionalGateways = [
            'paystack_authorization_code',
            'paystack_customer_code',
            'razorpay_customer_id',
            'razorpay_token_id',
            'mollie_customer_id',
            'mollie_mandate_id',
            'flutterwave_customer_id',
            'flutterwave_token',
            'mercadopago_customer_id',
            'mercadopago_card_id',
        ];

        foreach ($regionalGateways as $field) {
            if (! empty($profile->$field)) {
                return true;
            }
        }

        // Check payment_methods JSON field if it exists
        if (! empty($profile->payment_methods) && is_array($profile->payment_methods)) {
            return count($profile->payment_methods) > 0;
        }

        return false;
    }

    /**
     * Update the has_payment_method flag on the business profile.
     *
     * @param  \App\Models\BusinessProfile|null  $profile
     */
    protected function updatePaymentMethodFlag($profile, bool $hasMethod): void
    {
        if ($profile && $profile->has_payment_method !== $hasMethod) {
            $profile->update(['has_payment_method' => $hasMethod]);
        }
    }

    /**
     * Get current onboarding progress.
     */
    public function getProgress(): JsonResponse
    {
        $business = Auth::user();

        // Auto-validate and complete steps based on current data
        $this->autoValidateSteps($business);

        $progress = $this->onboardingProgressService->getProgressData($business);

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    /**
     * Get the next step to complete.
     */
    public function getNextStep(): JsonResponse
    {
        $business = Auth::user();
        $nextStep = $this->onboardingProgressService->getNextRequiredStep($business);

        if (! $nextStep) {
            return response()->json([
                'success' => true,
                'all_complete' => true,
                'message' => 'All required steps are complete!',
            ]);
        }

        return response()->json([
            'success' => true,
            'all_complete' => false,
            'next_step' => [
                'id' => $nextStep->id,
                'step_id' => $nextStep->step_id,
                'name' => $nextStep->name,
                'description' => $nextStep->description,
                'help_text' => $nextStep->help_text,
                'route_url' => $nextStep->getRouteUrl(),
                'estimated_time' => $nextStep->getEstimatedTimeString(),
            ],
        ]);
    }

    /**
     * Complete a specific step.
     */
    public function completeStep(Request $request): JsonResponse
    {
        $request->validate([
            'step_id' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $business = Auth::user();

        $result = $this->onboardingProgressService->updateProgress(
            $business,
            $request->step_id,
            'completed',
            [
                'notes' => $request->notes,
            ]
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['message'] ?? 'Failed to complete step.',
            ], 422);
        }

        $nextStep = $this->onboardingProgressService->getNextRequiredStep($business);

        return response()->json([
            'success' => true,
            'message' => 'Step completed successfully!',
            'overall_progress' => $result['overall_progress'],
            'can_activate' => $result['can_activate'],
            'next_step' => $nextStep ? [
                'step_id' => $nextStep->step_id,
                'name' => $nextStep->name,
                'route_url' => $nextStep->getRouteUrl(),
            ] : null,
        ]);
    }

    /**
     * Skip an optional step.
     */
    public function skipOptionalStep(Request $request): JsonResponse
    {
        $request->validate([
            'step_id' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $business = Auth::user();

        $result = $this->onboardingProgressService->updateProgress(
            $business,
            $request->step_id,
            'skipped',
            [
                'reason' => $request->reason,
            ]
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['message'] ?? 'Failed to skip step.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Step skipped successfully.',
            'overall_progress' => $result['overall_progress'],
        ]);
    }

    /**
     * Show the onboarding dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $business = Auth::user();
        $business->load('businessProfile');

        // Auto-validate steps
        $this->autoValidateSteps($business);

        $progress = $this->onboardingProgressService->getProgressData($business);
        $nextStep = $this->onboardingProgressService->getNextRequiredStep($business);

        return view('business.onboarding.dashboard', [
            'user' => $business,
            'progress' => $progress,
            'nextStep' => $nextStep ? [
                'step' => $nextStep,
                'route_url' => $nextStep->getRouteUrl(),
            ] : null,
        ]);
    }

    /**
     * Initialize onboarding for a new business.
     */
    public function initialize(): JsonResponse
    {
        $business = Auth::user();
        $result = $this->onboardingProgressService->initializeOnboarding($business);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['message'] ?? 'Failed to initialize onboarding.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Onboarding initialized successfully.',
            'total_steps' => $result['total_steps'] ?? 0,
        ]);
    }

    /**
     * Auto-validate and complete steps based on current business data.
     *
     * @param  \App\Models\User  $business
     */
    protected function autoValidateSteps($business): void
    {
        $profile = $business->businessProfile;

        if (! $profile) {
            return;
        }

        // Email verified
        if ($business->email_verified_at) {
            $this->onboardingProgressService->autoCompleteStep($business, 'email_verified');
        }

        // Business profile complete
        if ($this->calculateProfileCompleteness($business) >= 80) {
            $this->onboardingProgressService->autoCompleteStep($business, 'profile_complete');
        }

        // Business information complete
        if ($profile->business_name && $profile->business_type && $profile->industry) {
            $this->onboardingProgressService->autoCompleteStep($business, 'business_info_complete');
        }

        // Business address complete
        if ($profile->address && $profile->city && $profile->state && $profile->country) {
            $this->onboardingProgressService->autoCompleteStep($business, 'business_address_complete');
        }

        // Contact information complete
        if ($profile->phone && $profile->work_email) {
            $this->onboardingProgressService->autoCompleteStep($business, 'contact_info_complete');
        }

        // Work email verified
        if ($profile->work_email_verified) {
            $this->onboardingProgressService->autoCompleteStep($business, 'work_email_verified');
        }

        // Business verification
        if ($profile->is_verified) {
            $this->onboardingProgressService->autoCompleteStep($business, 'business_verified');
        }

        // Documents submitted
        if ($profile->hasSubmittedAllDocuments()) {
            $this->onboardingProgressService->autoCompleteStep($business, 'documents_submitted');
        }

        // Payment method setup
        if ($profile->has_payment_method) {
            $this->onboardingProgressService->autoCompleteStep($business, 'payment_setup');
        }

        // First shift template created (recommended)
        if ($profile->total_templates > 0) {
            $this->onboardingProgressService->autoCompleteStep($business, 'first_template_created');
        }

        // Logo uploaded (recommended)
        if ($profile->logo_url) {
            $this->onboardingProgressService->autoCompleteStep($business, 'logo_uploaded');
        }

        // Description added (recommended)
        if ($profile->description && strlen($profile->description) >= 50) {
            $this->onboardingProgressService->autoCompleteStep($business, 'description_added');
        }
    }

    /**
     * Calculate profile completeness percentage (legacy method for auto-validation).
     *
     * @param  \App\Models\User  $user
     */
    protected function calculateProfileCompleteness($user): int
    {
        $completeness = 0;

        // Base user fields (30%)
        if ($user->name) {
            $completeness += 10;
        }
        if ($user->email) {
            $completeness += 10;
        }
        if ($user->avatar && $user->avatar != 'avatar.jpg') {
            $completeness += 10;
        }

        // Business profile fields (70%)
        if ($user->businessProfile) {
            $profile = $user->businessProfile;

            if ($profile->business_name) {
                $completeness += 15;
            }
            if ($profile->business_type) {
                $completeness += 10;
            }
            if ($profile->address) {
                $completeness += 10;
            }
            if ($profile->city && $profile->state) {
                $completeness += 15;
            }
            if ($profile->phone) {
                $completeness += 10;
            }
            if ($profile->description) {
                $completeness += 10;
            }
        }

        return min($completeness, 100);
    }

    /**
     * Get list of missing profile fields (legacy method for backwards compatibility).
     *
     * @param  \App\Models\User  $user
     */
    protected function getMissingFields($user): array
    {
        $missing = [];

        // Check base user fields
        if (! $user->avatar || $user->avatar == 'avatar.jpg') {
            $missing[] = [
                'field' => 'avatar',
                'label' => 'Company Logo',
                'description' => 'A logo helps workers recognize your business',
                'priority' => 'medium',
            ];
        }

        // Check business profile fields
        $profile = $user->businessProfile;

        if (! $profile || ! $profile->business_name) {
            $missing[] = [
                'field' => 'business_name',
                'label' => 'Business Name',
                'description' => 'Your official business name',
                'priority' => 'high',
            ];
        }

        if (! $profile || ! $profile->business_type) {
            $missing[] = [
                'field' => 'business_type',
                'label' => 'Business Type',
                'description' => 'What industry is your business in?',
                'priority' => 'high',
            ];
        }

        if (! $profile || ! $profile->address) {
            $missing[] = [
                'field' => 'address',
                'label' => 'Business Address',
                'description' => 'Where workers will report for shifts',
                'priority' => 'high',
            ];
        }

        if (! $profile || ! $profile->city || ! $profile->state) {
            $missing[] = [
                'field' => 'location',
                'label' => 'City and State',
                'description' => 'Your business location',
                'priority' => 'high',
            ];
        }

        if (! $profile || ! $profile->phone) {
            $missing[] = [
                'field' => 'phone',
                'label' => 'Business Phone',
                'description' => 'Contact number for workers',
                'priority' => 'high',
            ];
        }

        if (! $profile || ! $profile->description) {
            $missing[] = [
                'field' => 'description',
                'label' => 'Business Description',
                'description' => 'Tell workers about your company',
                'priority' => 'medium',
            ];
        }

        return $missing;
    }
}
