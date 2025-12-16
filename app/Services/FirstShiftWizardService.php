<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\FirstShiftProgress;
use App\Models\Venue;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use App\Models\MinimumWage;
use App\Models\MarketRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * FirstShiftWizardService
 *
 * BIZ-REG-009: First Shift Wizard
 *
 * Handles all first shift wizard logic:
 * - Prerequisites checking
 * - Suggested roles by business type
 * - Minimum wage validation
 * - Market rate suggestions
 * - Shift creation and template saving
 */
class FirstShiftWizardService
{
    protected BusinessPaymentService $paymentService;
    protected ShiftMatchingService $matchingService;

    public function __construct(
        BusinessPaymentService $paymentService,
        ShiftMatchingService $matchingService
    ) {
        $this->paymentService = $paymentService;
        $this->matchingService = $matchingService;
    }

    // =========================================
    // Prerequisites Checking
    // =========================================

    /**
     * Check all prerequisites for posting first shift.
     */
    public function checkPrerequisites(BusinessProfile $business): array
    {
        $prerequisites = [
            'business_verified' => [
                'met' => $this->isBusinessVerified($business),
                'label' => 'Business Verified',
                'description' => 'Your business profile must be verified',
                'action_url' => route('business.profile'),
                'action_text' => 'Complete Verification',
            ],
            'payment_method_verified' => [
                'met' => $this->hasVerifiedPaymentMethod($business),
                'label' => 'Payment Method Added',
                'description' => 'A verified payment method is required',
                'action_url' => route('business.payment.setup'),
                'action_text' => 'Add Payment Method',
            ],
            'venue_exists' => [
                'met' => $this->hasAtLeastOneVenue($business),
                'label' => 'Venue Created',
                'description' => 'At least one venue/location must be set up',
                'action_url' => route('business.profile'),
                'action_text' => 'Add Venue',
            ],
        ];

        $allMet = collect($prerequisites)->every(fn($p) => $p['met']);

        return [
            'ready' => $allMet,
            'prerequisites' => $prerequisites,
            'next_step' => $allMet ? null : collect($prerequisites)->first(fn($p) => !$p['met']),
        ];
    }

    /**
     * Check if business is verified.
     */
    protected function isBusinessVerified(BusinessProfile $business): bool
    {
        // For now, check if basic profile is complete
        // Can be enhanced with formal verification process
        return $business->is_verified ||
               ($business->business_name && $business->business_type);
    }

    /**
     * Check if business has verified payment method.
     */
    protected function hasVerifiedPaymentMethod(BusinessProfile $business): bool
    {
        return $this->paymentService->canBusinessPostShifts($business);
    }

    /**
     * Check if business has at least one venue.
     */
    protected function hasAtLeastOneVenue(BusinessProfile $business): bool
    {
        return Venue::where('business_profile_id', $business->id)
            ->where('is_active', true)
            ->exists();
    }

    // =========================================
    // Wizard Progress Management
    // =========================================

    /**
     * Get or create wizard progress for business.
     */
    public function getWizardProgress(BusinessProfile $business): FirstShiftProgress
    {
        return FirstShiftProgress::getOrCreateForBusiness($business->id);
    }

    /**
     * Get wizard status summary.
     */
    public function getWizardStatus(BusinessProfile $business): array
    {
        $progress = $this->getWizardProgress($business);

        return [
            'wizard_completed' => $progress->wizard_completed,
            'current_step' => $progress->current_step,
            'highest_step_reached' => $progress->highest_step_reached,
            'completion_percentage' => $progress->completion_percentage,
            'steps_status' => $progress->steps_status,
            'posting_mode' => $progress->posting_mode,
            'can_continue' => !$progress->wizard_completed,
            'first_shift_id' => $progress->first_shift_id,
            'draft_data' => $progress->draft_data,
            'time_spent' => $progress->formatted_time_spent,
        ];
    }

    /**
     * Update wizard step.
     */
    public function updateStep(BusinessProfile $business, int $step, array $data): array
    {
        $progress = $this->getWizardProgress($business);

        if (!$progress->canNavigateToStep($step)) {
            return [
                'success' => false,
                'error' => 'Cannot navigate to this step. Please complete previous steps first.',
            ];
        }

        // Save step data
        $progress->saveDraftData($step, $data);

        // Update selected values based on step
        $this->updateSelectedValues($progress, $step, $data);

        // Mark step complete if valid
        if ($this->validateStepData($step, $data)) {
            $progress->completeStep($step, $data);
        }

        return [
            'success' => true,
            'progress' => $progress->fresh(),
        ];
    }

    /**
     * Update selected values in progress.
     */
    protected function updateSelectedValues(FirstShiftProgress $progress, int $step, array $data): void
    {
        $updates = [];

        switch ($step) {
            case FirstShiftProgress::STEP_VENUE:
                if (isset($data['venue_id'])) {
                    $updates['selected_venue_id'] = $data['venue_id'];
                }
                break;

            case FirstShiftProgress::STEP_ROLE:
                if (isset($data['role'])) {
                    $updates['selected_role'] = $data['role'];
                }
                break;

            case FirstShiftProgress::STEP_SCHEDULE:
                if (isset($data['date'])) {
                    $updates['selected_date'] = $data['date'];
                }
                if (isset($data['start_time'])) {
                    $updates['selected_start_time'] = $data['start_time'];
                }
                if (isset($data['end_time'])) {
                    $updates['selected_end_time'] = $data['end_time'];
                }
                if (isset($data['workers_needed'])) {
                    $updates['selected_workers_needed'] = $data['workers_needed'];
                }
                break;

            case FirstShiftProgress::STEP_RATE:
                if (isset($data['hourly_rate'])) {
                    $updates['selected_hourly_rate'] = $data['hourly_rate'];
                }
                break;
        }

        if (!empty($updates)) {
            $progress->update($updates);
        }
    }

    /**
     * Validate step data.
     */
    protected function validateStepData(int $step, array $data): bool
    {
        return match($step) {
            FirstShiftProgress::STEP_VENUE => isset($data['venue_id']),
            FirstShiftProgress::STEP_ROLE => isset($data['role']) && !empty($data['role']),
            FirstShiftProgress::STEP_SCHEDULE => isset($data['date']) && isset($data['start_time']) && isset($data['end_time']),
            FirstShiftProgress::STEP_RATE => isset($data['hourly_rate']) && $data['hourly_rate'] > 0,
            FirstShiftProgress::STEP_DETAILS => true, // Optional step
            FirstShiftProgress::STEP_REVIEW => true, // Just confirmation
            default => false,
        };
    }

    // =========================================
    // Venue Methods
    // =========================================

    /**
     * Get venues for business.
     */
    public function getVenues(BusinessProfile $business): array
    {
        $venues = Venue::where('business_profile_id', $business->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($venue) {
                return [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'address' => $venue->full_address,
                    'city' => $venue->city,
                    'state' => $venue->state,
                    'country' => $venue->country,
                    'has_coordinates' => $venue->latitude && $venue->longitude,
                ];
            });

        return [
            'venues' => $venues,
            'count' => $venues->count(),
        ];
    }

    // =========================================
    // Role Suggestions
    // =========================================

    /**
     * Get suggested roles based on business type.
     */
    public function getSuggestedRoles(BusinessProfile $business): array
    {
        $businessType = $business->business_type ?? $business->industry ?? 'general';

        $suggestedRoles = MarketRate::getSuggestedRolesByBusinessType($businessType);

        // Get all available categories for browsing
        $allCategories = MarketRate::getAllCategories();

        $rolesByCategory = [];
        foreach ($allCategories as $category) {
            $rolesByCategory[$category] = MarketRate::getRolesForCategory($category);
        }

        return [
            'suggested_roles' => $suggestedRoles,
            'business_type' => $businessType,
            'all_categories' => $allCategories,
            'roles_by_category' => $rolesByCategory,
        ];
    }

    // =========================================
    // Minimum Wage Methods
    // =========================================

    /**
     * Get minimum wage for a jurisdiction.
     */
    public function getMinimumWage(
        string $countryCode,
        ?string $stateCode = null,
        ?string $city = null
    ): array {
        $minimumWage = MinimumWage::getForJurisdiction($countryCode, $stateCode, $city);

        if (!$minimumWage) {
            return [
                'found' => false,
                'jurisdiction' => null,
                'minimum_rate_cents' => null,
                'minimum_rate_dollars' => null,
                'message' => 'No minimum wage data available for this jurisdiction.',
            ];
        }

        return [
            'found' => true,
            'jurisdiction' => $minimumWage->jurisdiction_name,
            'minimum_rate_cents' => $minimumWage->hourly_rate_cents,
            'minimum_rate_dollars' => $minimumWage->hourly_rate_dollars,
            'tipped_rate_dollars' => $minimumWage->tipped_rate_dollars,
            'currency' => $minimumWage->currency,
            'currency_symbol' => $minimumWage->getCurrencySymbol(),
            'formatted_rate' => $minimumWage->formatted_rate,
            'effective_date' => $minimumWage->effective_date->format('Y-m-d'),
            'overtime_multiplier' => $minimumWage->overtime_multiplier,
            'overtime_threshold_weekly' => $minimumWage->overtime_threshold_weekly,
        ];
    }

    /**
     * Validate a rate against minimum wage.
     */
    public function validateRate(
        int $rateCents,
        string $countryCode,
        ?string $stateCode = null,
        ?string $city = null
    ): array {
        return MinimumWage::validateRate($rateCents, $countryCode, $stateCode, $city);
    }

    // =========================================
    // Rate Suggestion Methods
    // =========================================

    /**
     * Get suggested rate for a shift.
     */
    public function getSuggestedRate(array $shiftData): array
    {
        // Get market rate suggestion
        $suggestion = MarketRate::getSuggestedRateForShift($shiftData);

        // Get minimum wage for validation
        $countryCode = $shiftData['country_code'] ?? 'US';
        $stateCode = $shiftData['state_code'] ?? $shiftData['state'] ?? null;
        $city = $shiftData['city'] ?? null;

        $minimumWage = $this->getMinimumWage($countryCode, $stateCode, $city);

        // Ensure suggestion meets minimum wage
        if ($minimumWage['found'] && $suggestion['suggested_rate_cents'] < $minimumWage['minimum_rate_cents']) {
            $suggestion['suggested_rate_cents'] = $minimumWage['minimum_rate_cents'];
            $suggestion['suggested_rate_dollars'] = $minimumWage['minimum_rate_dollars'];
            $suggestion['adjusted_to_minimum'] = true;
            $suggestion['adjustment_reason'] = 'Rate adjusted to meet minimum wage requirements';
        }

        $suggestion['minimum_wage'] = $minimumWage;

        return $suggestion;
    }

    /**
     * Get competitive rating for a rate.
     */
    public function getCompetitiveRating(int $rateCents, string $role, array $location): array
    {
        $marketRate = MarketRate::getForRoleAndLocation(
            $role,
            $location['country_code'] ?? 'US',
            $location['state_code'] ?? null,
            $location['city'] ?? null
        );

        if (!$marketRate) {
            return [
                'rating' => 'unknown',
                'percentile' => null,
                'description' => 'No market data available for comparison.',
            ];
        }

        return $marketRate->getCompetitiveRating($rateCents);
    }

    // =========================================
    // Shift Timing Validation
    // =========================================

    /**
     * Validate shift timing.
     */
    public function validateShiftTiming(array $data): array
    {
        $errors = [];

        // Parse times
        $date = Carbon::parse($data['date']);
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        // Check date is in future
        if ($date->isPast() && !$date->isToday()) {
            $errors[] = 'Shift date must be today or in the future.';
        }

        // If today, check start time is in future
        if ($date->isToday()) {
            $startDateTime = Carbon::parse($data['date'] . ' ' . $startTime);
            if ($startDateTime->isPast()) {
                $errors[] = 'Shift start time must be in the future.';
            }
        }

        // Calculate duration
        $start = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        // Handle overnight shifts
        if ($end->lte($start)) {
            $end->addDay();
        }

        $durationHours = $start->diffInMinutes($end) / 60;

        // Minimum duration (e.g., 2 hours)
        if ($durationHours < 2) {
            $errors[] = 'Shift must be at least 2 hours long.';
        }

        // Maximum duration (e.g., 12 hours)
        if ($durationHours > 12) {
            $errors[] = 'Shift cannot exceed 12 hours.';
        }

        // Determine shift characteristics
        $startHour = (int) explode(':', $startTime)[0];
        $isNightShift = $startHour >= 22 || $startHour < 6;
        $isWeekend = $date->isWeekend();
        $isOvernight = $end->gt($start->copy()->addDay()->startOfDay());

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'duration_hours' => round($durationHours, 2),
            'is_night_shift' => $isNightShift,
            'is_weekend' => $isWeekend,
            'is_overnight' => $isOvernight,
            'start_datetime' => $date->format('Y-m-d') . ' ' . $startTime,
            'end_datetime' => $isOvernight
                ? $date->copy()->addDay()->format('Y-m-d') . ' ' . $endTime
                : $date->format('Y-m-d') . ' ' . $endTime,
        ];
    }

    // =========================================
    // Shift Creation
    // =========================================

    /**
     * Create the first shift from wizard data.
     */
    public function createFirstShift(BusinessProfile $business): array
    {
        $progress = $this->getWizardProgress($business);

        // Validate all steps are complete
        if (!$progress->step_1_venue_complete ||
            !$progress->step_2_role_complete ||
            !$progress->step_3_schedule_complete ||
            !$progress->step_4_rate_complete) {
            return [
                'success' => false,
                'error' => 'Please complete all required steps before posting.',
            ];
        }

        try {
            $shift = DB::transaction(function () use ($business, $progress) {
                // Get venue details
                $venue = Venue::find($progress->selected_venue_id);

                if (!$venue) {
                    throw new \Exception('Selected venue not found.');
                }

                // Get all draft data
                $draftData = $progress->all_draft_data;

                // Validate timing
                $timing = $this->validateShiftTiming([
                    'date' => $progress->selected_date,
                    'start_time' => $progress->selected_start_time,
                    'end_time' => $progress->selected_end_time,
                ]);

                if (!$timing['valid']) {
                    throw new \Exception(implode(' ', $timing['errors']));
                }

                // Build shift data
                $shiftData = [
                    'business_id' => $business->user_id,
                    'venue_id' => $venue->id,
                    'title' => $draftData['title'] ?? $progress->selected_role,
                    'description' => $draftData['description'] ?? "Looking for {$progress->selected_role}",
                    'role_type' => $progress->selected_role,
                    'industry' => $business->industry ?? $business->business_type,
                    'location_address' => $venue->address,
                    'location_city' => $venue->city,
                    'location_state' => $venue->state,
                    'location_country' => $venue->country ?? 'US',
                    'location_lat' => $venue->latitude,
                    'location_lng' => $venue->longitude,
                    'shift_date' => $progress->selected_date,
                    'start_time' => $progress->selected_start_time,
                    'end_time' => $progress->selected_end_time,
                    'start_datetime' => $timing['start_datetime'],
                    'end_datetime' => $timing['end_datetime'],
                    'duration_hours' => $timing['duration_hours'],
                    'base_rate' => $progress->selected_hourly_rate,
                    'required_workers' => $progress->selected_workers_needed,
                    'filled_workers' => 0,
                    'status' => 'open',
                    'urgency_level' => $this->determineUrgencyLevel($progress->selected_date),
                    'is_night_shift' => $timing['is_night_shift'],
                    'is_weekend' => $timing['is_weekend'],
                    'in_market' => true,
                    'market_posted_at' => now(),

                    // Optional details from step 5
                    'required_skills' => $draftData['required_skills'] ?? null,
                    'dress_code' => $draftData['dress_code'] ?? null,
                    'special_instructions' => $draftData['special_instructions'] ?? null,
                    'parking_info' => $draftData['parking_info'] ?? null,
                    'break_info' => $draftData['break_info'] ?? null,
                ];

                // Calculate costs
                $shift = Shift::create($shiftData);
                $shift->calculateCosts();

                // Save as template if requested
                if ($progress->save_as_template && $progress->template_name) {
                    $this->createShiftTemplate($business, $shift, $progress->template_name);
                }

                // Mark wizard complete
                $progress->completeWizard($shift->id);

                // Update business profile
                $business->update([
                    'first_shift_posted' => true,
                    'first_shift_posted_at' => now(),
                    'last_shift_posted_at' => now(),
                ]);
                $business->increment('total_shifts_posted');

                return $shift;
            });

            Log::info('First shift created via wizard', [
                'business_id' => $business->id,
                'shift_id' => $shift->id,
            ]);

            return [
                'success' => true,
                'shift' => $shift,
                'message' => 'Your first shift has been posted! Workers will start applying soon.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create first shift', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Determine urgency level based on shift date.
     */
    protected function determineUrgencyLevel($date): string
    {
        $shiftDate = Carbon::parse($date);
        $hoursUntil = now()->diffInHours($shiftDate, false);

        if ($hoursUntil < 0) {
            return 'open';
        }

        if ($hoursUntil <= 4) {
            return 'urgent';
        }

        if ($hoursUntil <= 24) {
            return 'high';
        }

        if ($hoursUntil <= 72) {
            return 'normal';
        }

        return 'low';
    }

    // =========================================
    // Template Methods
    // =========================================

    /**
     * Create a shift template from a shift.
     */
    public function createShiftTemplate(
        BusinessProfile $business,
        Shift $shift,
        string $templateName
    ): ShiftTemplate {
        return ShiftTemplate::create([
            'business_id' => $business->user_id,
            'venue_id' => $shift->venue_id,
            'template_name' => $templateName,
            'title' => $shift->title,
            'shift_description' => $shift->description,
            'description' => "Template created from first shift",
            'industry' => $shift->industry,
            'location_address' => $shift->location_address,
            'location_city' => $shift->location_city,
            'location_state' => $shift->location_state,
            'location_country' => $shift->location_country,
            'location_lat' => $shift->location_lat,
            'location_lng' => $shift->location_lng,
            'start_time' => $shift->start_time?->format('H:i'),
            'end_time' => $shift->end_time?->format('H:i'),
            'duration_hours' => $shift->duration_hours,
            'base_rate' => $shift->base_rate,
            'urgency_level' => 'normal',
            'required_workers' => $shift->required_workers,
            'requirements' => $shift->requirements,
            'required_skills' => $shift->required_skills,
            'dress_code' => $shift->dress_code,
            'parking_info' => $shift->parking_info,
            'break_info' => $shift->break_info,
            'special_instructions' => $shift->special_instructions,
            'created_via' => 'wizard',
            'is_from_first_shift' => true,
            'times_used' => 1,
            'last_used_at' => now(),
        ]);
    }

    /**
     * Save wizard data as template without creating shift.
     */
    public function saveAsTemplate(BusinessProfile $business, array $data): array
    {
        try {
            $progress = $this->getWizardProgress($business);
            $venue = Venue::find($progress->selected_venue_id);

            if (!$venue) {
                return [
                    'success' => false,
                    'error' => 'Please select a venue first.',
                ];
            }

            // Calculate duration
            $timing = $this->validateShiftTiming([
                'date' => $progress->selected_date ?? now()->addDay()->format('Y-m-d'),
                'start_time' => $progress->selected_start_time ?? '09:00',
                'end_time' => $progress->selected_end_time ?? '17:00',
            ]);

            $template = ShiftTemplate::create([
                'business_id' => $business->user_id,
                'venue_id' => $venue->id,
                'template_name' => $data['template_name'] ?? 'My Template',
                'title' => $progress->selected_role ?? 'Shift',
                'shift_description' => $data['description'] ?? '',
                'industry' => $business->industry ?? $business->business_type,
                'location_address' => $venue->address,
                'location_city' => $venue->city,
                'location_state' => $venue->state,
                'location_country' => $venue->country ?? 'US',
                'location_lat' => $venue->latitude,
                'location_lng' => $venue->longitude,
                'start_time' => $progress->selected_start_time ?? '09:00',
                'end_time' => $progress->selected_end_time ?? '17:00',
                'duration_hours' => $timing['duration_hours'] ?? 8,
                'base_rate' => $progress->selected_hourly_rate ?? 1500,
                'required_workers' => $progress->selected_workers_needed ?? 1,
                'dress_code' => $data['dress_code'] ?? null,
                'special_instructions' => $data['special_instructions'] ?? null,
                'created_via' => 'wizard',
            ]);

            return [
                'success' => true,
                'template' => $template,
                'message' => 'Template saved successfully!',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to save template', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to save template.',
            ];
        }
    }

    // =========================================
    // Promotional Credits
    // =========================================

    /**
     * Apply promotional code to first shift.
     */
    public function applyPromoCode(BusinessProfile $business, string $code): array
    {
        // Validate promo code (simplified - would integrate with promo system)
        $validCodes = [
            'FIRSTSHIFT' => 5000, // $50 off
            'WELCOME' => 2500, // $25 off
            'NEWBIZ' => 10000, // $100 off
        ];

        $upperCode = strtoupper(trim($code));

        if (!isset($validCodes[$upperCode])) {
            return [
                'success' => false,
                'error' => 'Invalid promotional code.',
            ];
        }

        // Check if already used
        $progress = $this->getWizardProgress($business);
        if ($progress->promo_applied) {
            return [
                'success' => false,
                'error' => 'A promotional code has already been applied.',
            ];
        }

        $discountCents = $validCodes[$upperCode];
        $progress->applyPromoCode($upperCode, $discountCents);

        return [
            'success' => true,
            'code' => $upperCode,
            'discount_cents' => $discountCents,
            'discount_dollars' => $discountCents / 100,
            'message' => 'Promotional code applied! You\'ll receive $' . ($discountCents / 100) . ' off.',
        ];
    }

    /**
     * Track wizard progress for analytics.
     */
    public function trackWizardProgress(BusinessProfile $business, string $action, array $data = []): void
    {
        $progress = $this->getWizardProgress($business);

        Log::info('Wizard progress tracked', [
            'business_id' => $business->id,
            'action' => $action,
            'step' => $progress->current_step,
            'data' => $data,
        ]);

        // Could integrate with analytics service here
    }
}
