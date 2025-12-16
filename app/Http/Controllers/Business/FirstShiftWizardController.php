<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\FirstShiftWizardService;
use App\Http\Requests\Business\WizardStepRequest;
use App\Http\Requests\Business\CreateShiftFromWizardRequest;
use App\Notifications\FirstShiftPostedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Business\ActivationController;

/**
 * FirstShiftWizardController
 *
 * BIZ-REG-009: First Shift Wizard
 *
 * Handles the 6-step first shift posting wizard:
 * 1. Select Venue
 * 2. Choose Role
 * 3. Set Schedule
 * 4. Set Pay Rate
 * 5. Add Details
 * 6. Review & Post
 */
class FirstShiftWizardController extends Controller
{
    protected FirstShiftWizardService $wizardService;
    protected ActivationController $activationController;

    public function __construct(
        FirstShiftWizardService $wizardService,
        ActivationController $activationController
    ) {
        $this->middleware(['auth', 'business']);
        $this->wizardService = $wizardService;
        $this->activationController = $activationController;
    }

    /**
     * Show the first shift wizard.
     *
     * GET /business/first-shift
     */
    public function index()
    {
        $user = Auth::user();
        $business = $user->businessProfile;

        if (!$business) {
            return redirect()->route('business.profile.complete')
                ->with('error', 'Please complete your business profile first.');
        }

        // BIZ-REG-011: Check activation status first
        $activationStatus = $this->activationController->checkActivationRequirements($business);

        if (!$activationStatus['can_post_shifts']) {
            return view('business.first-shift.activation-required', [
                'user' => $user,
                'business' => $business,
                'activationStatus' => $activationStatus,
            ]);
        }

        // Check prerequisites
        $prerequisites = $this->wizardService->checkPrerequisites($business);

        if (!$prerequisites['ready']) {
            return view('business.first-shift.prerequisites', [
                'user' => $user,
                'business' => $business,
                'prerequisites' => $prerequisites['prerequisites'],
                'next_step' => $prerequisites['next_step'],
                'activationStatus' => $activationStatus,
            ]);
        }

        // Get wizard status
        $wizardStatus = $this->wizardService->getWizardStatus($business);

        // If wizard is already complete, show success page or redirect
        if ($wizardStatus['wizard_completed']) {
            return redirect()->route('business.shifts.index')
                ->with('success', 'Your first shift was already posted!');
        }

        // Get data for current step
        $stepData = $this->getStepData($business, $wizardStatus['current_step']);

        return view('business.first-shift.wizard', [
            'user' => $user,
            'business' => $business,
            'wizardStatus' => $wizardStatus,
            'stepData' => $stepData,
        ]);
    }

    /**
     * Get prerequisites status.
     *
     * GET /business/first-shift/prerequisites
     */
    public function getPrerequisites()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $prerequisites = $this->wizardService->checkPrerequisites($business);

        return response()->json([
            'success' => true,
            'prerequisites' => $prerequisites,
        ]);
    }

    /**
     * Get wizard status.
     *
     * GET /business/first-shift/wizard-status
     */
    public function getWizardStatus()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $status = $this->wizardService->getWizardStatus($business);

        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }

    /**
     * Get venues for step 1.
     *
     * GET /business/first-shift/venues
     */
    public function getVenues()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $venues = $this->wizardService->getVenues($business);

        return response()->json([
            'success' => true,
            'data' => $venues,
        ]);
    }

    /**
     * Get suggested roles for step 2.
     *
     * GET /business/first-shift/roles
     */
    public function getSuggestedRoles()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $roles = $this->wizardService->getSuggestedRoles($business);

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Get minimum wage for a jurisdiction.
     *
     * GET /business/first-shift/minimum-wage
     */
    public function getMinimumWage(Request $request)
    {
        $request->validate([
            'country_code' => 'required|string|size:2',
            'state_code' => 'sometimes|nullable|string|max:10',
            'city' => 'sometimes|nullable|string|max:255',
        ]);

        $minimumWage = $this->wizardService->getMinimumWage(
            $request->input('country_code'),
            $request->input('state_code'),
            $request->input('city')
        );

        return response()->json([
            'success' => true,
            'data' => $minimumWage,
        ]);
    }

    /**
     * Get suggested rate for step 4.
     *
     * GET /business/first-shift/suggested-rate
     */
    public function getSuggestedRate(Request $request)
    {
        $request->validate([
            'role' => 'required|string|max:255',
            'country_code' => 'sometimes|string|size:2',
            'state_code' => 'sometimes|nullable|string|max:10',
            'city' => 'sometimes|nullable|string|max:255',
            'date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
        ]);

        $shiftData = $request->only([
            'role', 'country_code', 'state_code', 'city', 'date', 'start_time'
        ]);

        // Add defaults
        $shiftData['country_code'] = $shiftData['country_code'] ?? 'US';

        $suggestion = $this->wizardService->getSuggestedRate($shiftData);

        return response()->json([
            'success' => true,
            'data' => $suggestion,
        ]);
    }

    /**
     * Get competitive rating for a rate.
     *
     * GET /business/first-shift/competitive-rating
     */
    public function getCompetitiveRating(Request $request)
    {
        $request->validate([
            'rate_cents' => 'required|integer|min:1',
            'role' => 'required|string|max:255',
            'country_code' => 'sometimes|string|size:2',
            'state_code' => 'sometimes|nullable|string|max:10',
            'city' => 'sometimes|nullable|string|max:255',
        ]);

        $rating = $this->wizardService->getCompetitiveRating(
            $request->input('rate_cents'),
            $request->input('role'),
            [
                'country_code' => $request->input('country_code', 'US'),
                'state_code' => $request->input('state_code'),
                'city' => $request->input('city'),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $rating,
        ]);
    }

    /**
     * Validate shift data (schedule, rate, etc.).
     *
     * POST /business/first-shift/validate
     */
    public function validateShiftData(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'hourly_rate' => 'sometimes|integer|min:1',
            'country_code' => 'sometimes|string|size:2',
            'state_code' => 'sometimes|nullable|string|max:10',
            'city' => 'sometimes|nullable|string|max:255',
        ]);

        $validation = [
            'timing' => $this->wizardService->validateShiftTiming($request->only(['date', 'start_time', 'end_time'])),
        ];

        // Validate rate against minimum wage if provided
        if ($request->has('hourly_rate')) {
            $validation['rate'] = $this->wizardService->validateRate(
                $request->input('hourly_rate'),
                $request->input('country_code', 'US'),
                $request->input('state_code'),
                $request->input('city')
            );
        }

        $isValid = $validation['timing']['valid'] && (!isset($validation['rate']) || $validation['rate']['valid']);

        return response()->json([
            'success' => true,
            'valid' => $isValid,
            'validation' => $validation,
        ]);
    }

    /**
     * Update wizard step data.
     *
     * PUT /business/first-shift/step/{step}
     */
    public function updateStep(WizardStepRequest $request, int $step)
    {
        if ($step < 1 || $step > 6) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid step number.',
            ], 400);
        }

        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->wizardService->updateStep($business, $step, $request->validated());

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            'progress' => $this->wizardService->getWizardStatus($business),
            'next_step_data' => $step < 6 ? $this->getStepData($business, $step + 1) : null,
        ]);
    }

    /**
     * Navigate to a specific step.
     *
     * POST /business/first-shift/go-to-step/{step}
     */
    public function goToStep(int $step)
    {
        if ($step < 1 || $step > 6) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid step number.',
            ], 400);
        }

        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $progress = $this->wizardService->getWizardProgress($business);

        if (!$progress->canNavigateToStep($step)) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot navigate to this step. Please complete previous steps first.',
            ], 400);
        }

        $progress->goToStep($step);

        return response()->json([
            'success' => true,
            'progress' => $this->wizardService->getWizardStatus($business),
            'step_data' => $this->getStepData($business, $step),
        ]);
    }

    /**
     * Create the first shift.
     *
     * POST /business/first-shift/create
     */
    public function createShift(CreateShiftFromWizardRequest $request)
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        // Check prerequisites one more time
        $prerequisites = $this->wizardService->checkPrerequisites($business);
        if (!$prerequisites['ready']) {
            return response()->json([
                'success' => false,
                'error' => 'Prerequisites not met.',
                'prerequisites' => $prerequisites,
            ], 400);
        }

        $result = $this->wizardService->createFirstShift($business);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        // Send notification
        try {
            // Auth::user()->notify(new FirstShiftPostedNotification($result['shift']));
        } catch (\Exception $e) {
            \Log::warning('Failed to send first shift notification', ['error' => $e->getMessage()]);
        }

        // Track analytics
        $this->wizardService->trackWizardProgress($business, 'shift_created', [
            'shift_id' => $result['shift']->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'shift' => $result['shift'],
                'message' => $result['message'],
                'redirect_url' => route('business.shifts.show', $result['shift']->id),
            ]);
        }

        return redirect()->route('business.shifts.show', $result['shift']->id)
            ->with('success', $result['message']);
    }

    /**
     * Save wizard data as template without creating shift.
     *
     * POST /business/first-shift/save-template
     */
    public function saveTemplate(Request $request)
    {
        $request->validate([
            'template_name' => 'required|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'dress_code' => 'sometimes|nullable|string|max:500',
            'special_instructions' => 'sometimes|nullable|string|max:1000',
        ]);

        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->wizardService->saveAsTemplate($business, $request->all());

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Apply promotional code.
     *
     * POST /business/first-shift/apply-promo
     */
    public function applyPromoCode(Request $request)
    {
        $request->validate([
            'promo_code' => 'required|string|max:50',
        ]);

        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $result = $this->wizardService->applyPromoCode($business, $request->input('promo_code'));

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Remove promotional code.
     *
     * DELETE /business/first-shift/promo
     */
    public function removePromoCode()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $progress = $this->wizardService->getWizardProgress($business);
        $progress->removePromoCode();

        return response()->json([
            'success' => true,
            'message' => 'Promotional code removed.',
        ]);
    }

    /**
     * Get review summary for step 6.
     *
     * GET /business/first-shift/review-summary
     */
    public function getReviewSummary()
    {
        $business = Auth::user()->businessProfile;

        if (!$business) {
            return response()->json([
                'success' => false,
                'error' => 'Business profile not found.',
            ], 404);
        }

        $progress = $this->wizardService->getWizardProgress($business);

        // Get venue details
        $venue = $progress->selectedVenue;

        // Calculate timing
        $timing = $this->wizardService->validateShiftTiming([
            'date' => $progress->selected_date?->format('Y-m-d'),
            'start_time' => $progress->selected_start_time,
            'end_time' => $progress->selected_end_time,
        ]);

        // Get rate suggestion for comparison
        $rateSuggestion = $this->wizardService->getSuggestedRate([
            'role' => $progress->selected_role,
            'country_code' => $venue?->country ?? 'US',
            'state_code' => $venue?->state,
            'city' => $venue?->city,
            'date' => $progress->selected_date?->format('Y-m-d'),
            'start_time' => $progress->selected_start_time,
        ]);

        // Get competitive rating
        $competitiveRating = $progress->selected_hourly_rate
            ? $this->wizardService->getCompetitiveRating(
                $progress->selected_hourly_rate,
                $progress->selected_role,
                ['country_code' => $venue?->country ?? 'US', 'state_code' => $venue?->state, 'city' => $venue?->city]
            )
            : null;

        // Calculate estimated cost
        $estimatedCost = $this->calculateEstimatedCost($progress, $timing);

        return response()->json([
            'success' => true,
            'summary' => [
                'venue' => $venue ? [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'address' => $venue->full_address,
                ] : null,
                'role' => $progress->selected_role,
                'schedule' => [
                    'date' => $progress->selected_date?->format('l, F j, Y'),
                    'date_raw' => $progress->selected_date?->format('Y-m-d'),
                    'start_time' => $progress->selected_start_time,
                    'end_time' => $progress->selected_end_time,
                    'duration_hours' => $timing['duration_hours'] ?? null,
                    'is_weekend' => $timing['is_weekend'] ?? false,
                    'is_night_shift' => $timing['is_night_shift'] ?? false,
                ],
                'rate' => [
                    'hourly_cents' => $progress->selected_hourly_rate,
                    'hourly_dollars' => $progress->selected_hourly_rate ? $progress->selected_hourly_rate / 100 : null,
                    'suggested' => $rateSuggestion,
                    'competitive_rating' => $competitiveRating,
                ],
                'workers_needed' => $progress->selected_workers_needed,
                'details' => $progress->getDraftDataForStep(5),
                'estimated_cost' => $estimatedCost,
                'promo' => [
                    'applied' => $progress->promo_applied,
                    'code' => $progress->promo_code,
                    'discount_dollars' => $progress->promo_discount_cents / 100,
                ],
                'save_as_template' => $progress->save_as_template,
                'template_name' => $progress->template_name,
            ],
        ]);
    }

    /**
     * Get data for a specific step.
     */
    protected function getStepData($business, int $step): array
    {
        return match($step) {
            1 => $this->wizardService->getVenues($business),
            2 => $this->wizardService->getSuggestedRoles($business),
            3 => ['current_date' => now()->format('Y-m-d')],
            4 => [
                'default_rate' => 1500, // $15.00
            ],
            5 => [
                'dress_codes' => [
                    'casual' => 'Casual',
                    'business_casual' => 'Business Casual',
                    'uniform' => 'Uniform Provided',
                    'all_black' => 'All Black',
                    'formal' => 'Formal',
                ],
            ],
            6 => [],
            default => [],
        };
    }

    /**
     * Calculate estimated cost for review.
     */
    protected function calculateEstimatedCost($progress, array $timing): array
    {
        $hourlyRate = $progress->selected_hourly_rate ?? 0;
        $duration = $timing['duration_hours'] ?? 0;
        $workers = $progress->selected_workers_needed ?? 1;

        $basePayCents = $hourlyRate * $duration * $workers;
        $platformFeeCents = (int) round($basePayCents * 0.15); // 15% platform fee estimate
        $totalCents = $basePayCents + $platformFeeCents;

        // Apply promo discount
        $discountCents = $progress->promo_applied ? $progress->promo_discount_cents : 0;
        $finalTotalCents = max(0, $totalCents - $discountCents);

        return [
            'base_pay_cents' => $basePayCents,
            'base_pay_dollars' => $basePayCents / 100,
            'platform_fee_cents' => $platformFeeCents,
            'platform_fee_dollars' => $platformFeeCents / 100,
            'discount_cents' => $discountCents,
            'discount_dollars' => $discountCents / 100,
            'total_cents' => $finalTotalCents,
            'total_dollars' => $finalTotalCents / 100,
        ];
    }
}
