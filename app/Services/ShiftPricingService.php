<?php

namespace App\Services;

use App\Models\DemandMetric;
use App\Models\Shift;
use App\Models\SurgeEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * SL-008: Surge Pricing Service
 * FIN-001: Volume Discount Integration
 *
 * Handles all surge pricing calculations including:
 * - Time-based surge (night shifts, weekends, holidays, urgent shifts)
 * - Demand-based surge (based on supply/demand metrics)
 * - Event-based surge (special events like concerts, festivals, etc.)
 * - Holiday surge (GLO-007: Multi-jurisdiction holiday calendar)
 * - Volume-based discounts (FIN-001: Platform fee discounts based on monthly volume)
 */
class ShiftPricingService
{
    protected ?HolidayService $holidayService = null;

    protected ?VolumeDiscountService $volumeDiscountService = null;

    /**
     * Get the HolidayService instance (lazy loaded)
     */
    protected function getHolidayService(): HolidayService
    {
        if ($this->holidayService === null) {
            $this->holidayService = app(HolidayService::class);
        }

        return $this->holidayService;
    }

    /**
     * FIN-001: Get the VolumeDiscountService instance (lazy loaded)
     */
    protected function getVolumeDiscountService(): VolumeDiscountService
    {
        if ($this->volumeDiscountService === null) {
            $this->volumeDiscountService = app(VolumeDiscountService::class);
        }

        return $this->volumeDiscountService;
    }

    /**
     * Calculate and update all financial fields for the shift.
     * FIN-001: Now includes volume-based discount calculation for platform fees.
     */
    public function calculateCosts(Shift $shift): Shift
    {
        // Step 1: Calculate base worker pay (before surge)
        $baseHourlyRate = $shift->base_rate;
        $hours = $shift->duration_hours;
        $workers = $shift->required_workers;

        $shift->base_worker_pay = $baseHourlyRate * $hours * $workers;

        // Step 2: Apply surge pricing to get final rate
        $this->calculateSurge($shift);
        $finalHourlyRate = $shift->final_rate;

        // Step 3: Calculate total worker pay with surge
        $totalWorkerPay = $finalHourlyRate * $hours * $workers;

        // Step 4: Calculate platform fee with volume discount (FIN-001)
        $feeCalculation = $this->calculatePlatformFee($shift, $totalWorkerPay);
        $shift->platform_fee_rate = $feeCalculation['fee_percent'];
        $shift->platform_fee_amount = $feeCalculation['fee_amount'];

        // Store volume discount info in shift metadata if not already set
        if (! isset($shift->volume_discount_applied)) {
            $shift->volume_discount_applied = $feeCalculation['discount_amount'] > 0;
            $shift->volume_discount_amount = $feeCalculation['discount_amount'];
            $shift->volume_tier_name = $feeCalculation['tier_name'];
        }

        // Step 5: Calculate subtotal (worker pay + platform fee)
        $subtotal = $totalWorkerPay + $shift->platform_fee_amount;

        // Step 6: Calculate VAT
        $vatRate = Config::get('overtimestaff.financial.vat_rate', 18.00);
        $shift->vat_rate = $vatRate;
        $shift->vat_amount = ($subtotal * $vatRate) / 100;

        // Step 7: Calculate total business cost
        $shift->total_business_cost = $subtotal + $shift->vat_amount;

        // Step 8: Add contingency buffer for escrow
        $bufferRate = Config::get('overtimestaff.financial.contingency_buffer_rate', 5.00);
        $shift->contingency_buffer_rate = $bufferRate;
        $shift->escrow_amount = $shift->total_business_cost * (1 + $bufferRate / 100);

        return $shift;
    }

    /**
     * FIN-001: Calculate platform fee with volume discount applied.
     *
     * @return array{
     *   fee_percent: float,
     *   fee_amount: float,
     *   fee_without_discount: float,
     *   discount_amount: float,
     *   discount_percent: float,
     *   tier_name: string|null,
     *   custom_pricing: bool
     * }
     */
    public function calculatePlatformFee(Shift $shift, ?float $baseAmount = null): array
    {
        // Get the business user
        $business = $shift->business;

        // Calculate base amount if not provided
        if ($baseAmount === null) {
            $finalHourlyRate = $shift->final_rate ?? $shift->base_rate;
            $hours = $shift->duration_hours ?? 1;
            $workers = $shift->required_workers ?? 1;
            $baseAmount = $finalHourlyRate * $hours * $workers;
        }

        // If no business found or volume discounts are disabled, use default rate
        if (! $business || ! Config::get('overtimestaff.financial.volume_discounts_enabled', true)) {
            $defaultFeeRate = Config::get('overtimestaff.financial.platform_fee_rate', 35.00);
            $feeAmount = ($baseAmount * $defaultFeeRate) / 100;

            return [
                'fee_percent' => $defaultFeeRate,
                'fee_amount' => round($feeAmount, 2),
                'fee_without_discount' => round($feeAmount, 2),
                'discount_amount' => 0,
                'discount_percent' => 0,
                'tier_name' => null,
                'tier_id' => null,
                'custom_pricing' => false,
            ];
        }

        // Use VolumeDiscountService to calculate fee with discount
        return $this->getVolumeDiscountService()->calculateFeeWithDiscount($business, $baseAmount);
    }

    /**
     * FIN-001: Get the effective platform fee percent for a business.
     */
    public function getEffectiveFeePercent(User $business): float
    {
        if (! Config::get('overtimestaff.financial.volume_discounts_enabled', true)) {
            return Config::get('overtimestaff.financial.platform_fee_rate', 35.00);
        }

        return $this->getVolumeDiscountService()->getEffectiveFeePercent($business);
    }

    /**
     * FIN-001: Get platform fee preview for shift creation.
     *
     * Shows the business their current tier discount before posting.
     *
     * @return array{
     *   base_fee_percent: float,
     *   discounted_fee_percent: float,
     *   discount_percentage: float,
     *   tier_name: string|null,
     *   estimated_savings: float,
     *   next_tier_info: array|null
     * }
     */
    public function getPlatformFeePreview(User $business, float $estimatedCost): array
    {
        $defaultFeeRate = Config::get('overtimestaff.financial.platform_fee_rate', 35.00);

        if (! Config::get('overtimestaff.financial.volume_discounts_enabled', true)) {
            return [
                'base_fee_percent' => $defaultFeeRate,
                'discounted_fee_percent' => $defaultFeeRate,
                'discount_percentage' => 0,
                'tier_name' => null,
                'estimated_fee' => round($estimatedCost * ($defaultFeeRate / 100), 2),
                'estimated_savings' => 0,
                'next_tier_info' => null,
                'volume_discounts_enabled' => false,
            ];
        }

        $volumeService = $this->getVolumeDiscountService();
        $feeCalculation = $volumeService->calculateFeeWithDiscount($business, $estimatedCost);
        $nextTierProgress = $volumeService->getNextTierProgress($business);

        return [
            'base_fee_percent' => $defaultFeeRate,
            'discounted_fee_percent' => $feeCalculation['fee_percent'],
            'discount_percentage' => $feeCalculation['discount_percent'],
            'tier_name' => $feeCalculation['tier_name'],
            'estimated_fee' => $feeCalculation['fee_amount'],
            'estimated_savings' => $feeCalculation['discount_amount'],
            'next_tier_info' => $nextTierProgress['has_next_tier'] ? [
                'name' => $nextTierProgress['next_tier_name'],
                'fee_percent' => $nextTierProgress['next_tier_fee'],
                'shifts_needed' => $nextTierProgress['shifts_needed'],
                'potential_savings' => $nextTierProgress['potential_savings'],
            ] : null,
            'volume_discounts_enabled' => true,
            'custom_pricing' => $feeCalculation['custom_pricing'],
        ];
    }

    /**
     * Calculate and apply surge pricing multipliers.
     */
    public function calculateSurge(Shift $shift): Shift
    {
        // Calculate individual surge components
        $shift->time_surge = $this->calculateTimeSurge($shift);

        // Get the shift date for demand/event calculations
        $shiftDate = $shift->shift_date instanceof Carbon ? $shift->shift_date : Carbon::parse($shift->shift_date);
        $region = $shift->location_city ?? $shift->location_state ?? $shift->location_country;
        $skill = $shift->role_type ?? ($shift->required_skills[0] ?? null);

        // Calculate demand and event surges
        $shift->demand_surge = $this->calculateDemandSurge($shiftDate, $region, $skill) - 1.0;
        $shift->event_surge = $this->getEventSurge($shiftDate, $region) - 1.0;

        // Calculate total surge multiplier based on configuration
        $surgeBreakdown = $this->getTotalSurgeMultiplier($shift);
        $shift->surge_multiplier = $surgeBreakdown['total'];

        // Apply surge to base rate
        $shift->final_rate = $shift->base_rate * $shift->surge_multiplier;

        return $shift;
    }

    /**
     * Calculate time-based surge.
     */
    protected function calculateTimeSurge(Shift $shift): float
    {
        $surge = 0.0;
        $config = Config::get('overtimestaff.surge');

        // Check if time-based surge is enabled
        if (! Config::get('overtimestaff.surge.time_based.enabled', true)) {
            return $surge;
        }

        // Urgent shifts
        if ($shift->urgency_level === 'urgent' || $shift->isUrgent()) {
            $surge += $config['urgent_shift'] ?? 0.50;
        }

        // Night shifts
        if ($shift->is_night_shift) {
            $surge += $config['night_shift'] ?? Config::get('overtimestaff.surge.time_based.night_multiplier', 0.25) - 1.0;
        }

        // Weekends
        if ($shift->is_weekend) {
            $surge += $config['weekend'] ?? Config::get('overtimestaff.surge.time_based.weekend_multiplier', 0.15) - 1.0;
        }

        // Public holidays
        if ($shift->is_public_holiday) {
            $surge += $config['public_holiday'] ?? 0.50;
        }

        return $surge;
    }

    /**
     * Calculate demand-based surge multiplier.
     *
     * Based on supply/demand ratio for the date/region/skill:
     * - If demand > supply by 20%, apply 1.2x
     * - If demand > supply by 50%, apply 1.5x
     * - If demand > supply by 100%, apply 2.0x
     */
    public function calculateDemandSurge(Carbon $date, ?string $region, ?string $skill): float
    {
        // Check if demand-based surge is enabled
        if (! Config::get('overtimestaff.surge.demand_based.enabled', true)) {
            return 1.0;
        }

        // Get demand metric for this date/region/skill combination
        return DemandMetric::getSurgeFor($date, $region, $skill);
    }

    /**
     * Get event-based surge multiplier.
     *
     * Checks for active surge events on this date/region and returns
     * the highest applicable multiplier.
     */
    public function getEventSurge(Carbon $date, ?string $region): float
    {
        // Check if event-based surge is enabled
        if (! Config::get('overtimestaff.surge.event_based.enabled', true)) {
            return 1.0;
        }

        $maxMultiplier = Config::get('overtimestaff.surge.event_based.max_multiplier', 2.5);
        $eventMultiplier = SurgeEvent::getHighestMultiplierFor($date, $region);

        // Cap the event surge at the configured maximum
        return min($eventMultiplier, $maxMultiplier);
    }

    /**
     * Get the total surge multiplier with breakdown.
     *
     * Combines time-based, demand-based, event-based, and holiday surges
     * using either 'highest' or 'multiplicative' combination method.
     *
     * @return array{
     *   time_surge: float,
     *   demand_surge: float,
     *   event_surge: float,
     *   holiday_surge: float,
     *   total: float,
     *   breakdown: array<string, float>,
     *   method: string
     * }
     */
    public function getTotalSurgeMultiplier(Shift $shift): array
    {
        // Get individual surge values (as multipliers, not deltas)
        $timeSurge = 1.0 + ($shift->time_surge ?? 0.0);
        $demandSurge = 1.0 + ($shift->demand_surge ?? 0.0);
        $eventSurge = 1.0 + ($shift->event_surge ?? 0.0);
        $holidaySurge = $shift->is_public_holiday
            ? 1.0 + (Config::get('overtimestaff.surge.public_holiday', 0.50))
            : 1.0;

        // Get combination method from config
        $method = Config::get('overtimestaff.surge.combination_method', 'highest');
        $cap = Config::get('overtimestaff.surge.cap', 3.0);

        // Calculate total based on method
        if ($method === 'multiplicative') {
            // Multiply all surges together
            $total = $timeSurge * $demandSurge * $eventSurge;
            // Avoid double-counting holiday if it's already in time_surge
            if (! $shift->is_public_holiday || $shift->time_surge === 0.0) {
                $total *= $holidaySurge;
            }
        } else {
            // 'highest' - use the highest individual multiplier
            $total = max($timeSurge, $demandSurge, $eventSurge, $holidaySurge);
        }

        // Apply cap
        $total = min($total, $cap);

        return [
            'time_surge' => round($timeSurge, 2),
            'demand_surge' => round($demandSurge, 2),
            'event_surge' => round($eventSurge, 2),
            'holiday_surge' => round($holidaySurge, 2),
            'total' => round($total, 2),
            'breakdown' => [
                'time' => $timeSurge > 1.0 ? round(($timeSurge - 1.0) * 100, 1).'%' : '0%',
                'demand' => $demandSurge > 1.0 ? round(($demandSurge - 1.0) * 100, 1).'%' : '0%',
                'event' => $eventSurge > 1.0 ? round(($eventSurge - 1.0) * 100, 1).'%' : '0%',
                'holiday' => $holidaySurge > 1.0 ? round(($holidaySurge - 1.0) * 100, 1).'%' : '0%',
            ],
            'method' => $method,
            'cap_applied' => $total >= $cap,
        ];
    }

    /**
     * Check if a date is a public holiday.
     *
     * GLO-007: Now uses the multi-jurisdiction HolidayService for proper
     * country-specific holiday detection instead of hardcoded Malta dates.
     */
    public function isPublicHoliday(Carbon $date, ?string $countryCode = null, ?string $region = null): bool
    {
        // If no country specified, fall back to legacy config
        if (! $countryCode) {
            $holidays = Config::get('overtimestaff.holidays', []);

            return in_array($date->toDateString(), $holidays);
        }

        return $this->getHolidayService()->isHoliday($date, $countryCode, $region);
    }

    /**
     * Check if a date is a public holiday for a specific shift.
     *
     * GLO-007: Determines the country from the shift's location data.
     */
    public function isPublicHolidayForShift(Carbon $date, Shift $shift): bool
    {
        $countryCode = $this->getCountryCodeFromShift($shift);
        $region = $shift->location_state ?? null;

        return $this->isPublicHoliday($date, $countryCode, $region);
    }

    /**
     * Get holiday surge multiplier for a specific date and location.
     *
     * GLO-007: Returns the holiday-specific surge multiplier from the
     * public_holidays table, which can vary by holiday type and country.
     */
    public function getHolidaySurgeMultiplier(Carbon $date, ?string $countryCode = null, ?string $region = null): float
    {
        if (! $countryCode) {
            // Legacy behavior: return config default if holiday
            $holidays = Config::get('overtimestaff.holidays', []);
            if (in_array($date->toDateString(), $holidays)) {
                return 1.0 + Config::get('overtimestaff.surge.public_holiday', 0.50);
            }

            return 1.0;
        }

        return $this->getHolidayService()->getSurgeMultiplier($date, $countryCode, $region);
    }

    /**
     * Get holiday info for display purposes.
     *
     * GLO-007: Returns full holiday details including name, type, and surge.
     *
     * @return array<string, mixed>|null
     */
    public function getHolidayInfo(Carbon $date, ?string $countryCode = null, ?string $region = null): ?array
    {
        if (! $countryCode) {
            $holidays = Config::get('overtimestaff.holidays', []);
            if (in_array($date->toDateString(), $holidays)) {
                return [
                    'name' => 'Public Holiday',
                    'type' => 'public',
                    'surge_multiplier' => 1.0 + Config::get('overtimestaff.surge.public_holiday', 0.50),
                    'is_legacy' => true,
                ];
            }

            return null;
        }

        $holiday = $this->getHolidayService()->getHolidayInfo($date, $countryCode, $region);

        if (! $holiday) {
            return null;
        }

        return [
            'id' => $holiday->id,
            'name' => $holiday->display_name,
            'local_name' => $holiday->local_name,
            'type' => $holiday->type,
            'type_label' => $holiday->type_label,
            'surge_multiplier' => (float) $holiday->surge_multiplier,
            'surge_percentage' => $holiday->surge_percentage,
            'is_national' => $holiday->is_national,
            'shifts_restricted' => $holiday->shifts_restricted,
        ];
    }

    /**
     * Get country code from shift location data.
     */
    protected function getCountryCodeFromShift(Shift $shift): ?string
    {
        // Try location_country first
        if (! empty($shift->location_country)) {
            return strtoupper($shift->location_country);
        }

        // Try to get from business profile
        if ($shift->business_id) {
            $business = $shift->business;
            if ($business && $business->businessProfile?->country) {
                return strtoupper($business->businessProfile->country);
            }
        }

        // Default to US if not specified
        return 'US';
    }

    /**
     * Get surge preview for a shift before creation.
     *
     * Useful for showing businesses the expected cost breakdown before posting.
     *
     * GLO-007: Now accepts country_code parameter for multi-jurisdiction holiday support.
     *
     * @return array{
     *   base_rate: float,
     *   surge_multiplier: float,
     *   final_rate: float,
     *   surge_breakdown: array<string, mixed>,
     *   active_events: array<int, array<string, mixed>>,
     *   demand_status: array<string, mixed>,
     *   holiday_info: array<string, mixed>|null
     * }
     */
    public function getSurgePreview(
        Carbon $date,
        ?string $region,
        ?string $skill,
        float $baseRate,
        bool $isNightShift = false,
        bool $isWeekend = false,
        bool $isUrgent = false,
        ?string $countryCode = null
    ): array {
        // Calculate time surge components
        $timeSurge = 0.0;
        $config = Config::get('overtimestaff.surge');
        $holidayInfo = null;
        $holidaySurgeMultiplier = 1.0;

        if (Config::get('overtimestaff.surge.time_based.enabled', true)) {
            if ($isUrgent) {
                $timeSurge += $config['urgent_shift'] ?? 0.50;
            }
            if ($isNightShift) {
                $timeSurge += $config['night_shift'] ?? 0.25;
            }
            if ($isWeekend) {
                $timeSurge += $config['weekend'] ?? 0.15;
            }

            // GLO-007: Use multi-jurisdiction holiday detection
            $holidayInfo = $this->getHolidayInfo($date, $countryCode, $region);
            if ($holidayInfo) {
                $holidaySurgeMultiplier = $holidayInfo['surge_multiplier'];
                // Add the delta to time surge (multiplier - 1.0)
                $timeSurge += $holidaySurgeMultiplier - 1.0;
            }
        }

        // Get demand and event surges
        $demandSurge = $this->calculateDemandSurge($date, $region, $skill);
        $eventSurge = $this->getEventSurge($date, $region);

        // Get active events for display
        $activeEvents = SurgeEvent::getActiveEventsFor($date, $region)
            ->map(function ($event) {
                return [
                    'name' => $event->name,
                    'type' => $event->event_type_label,
                    'multiplier' => $event->surge_multiplier,
                    'description' => $event->description,
                ];
            })
            ->toArray();

        // Get demand status
        $demandMetric = DemandMetric::query()
            ->forDate($date)
            ->forRegion($region)
            ->forSkill($skill)
            ->first();

        $demandStatus = [
            'fill_rate' => $demandMetric?->fill_rate ?? null,
            'workers_available' => $demandMetric?->workers_available ?? null,
            'shifts_posted' => $demandMetric?->shifts_posted ?? null,
            'surge_applied' => $demandSurge > 1.0,
        ];

        // Calculate total
        $method = Config::get('overtimestaff.surge.combination_method', 'highest');
        $cap = Config::get('overtimestaff.surge.cap', 3.0);

        $timeSurgeMultiplier = 1.0 + $timeSurge;

        if ($method === 'multiplicative') {
            $totalMultiplier = $timeSurgeMultiplier * $demandSurge * $eventSurge;
        } else {
            $totalMultiplier = max($timeSurgeMultiplier, $demandSurge, $eventSurge);
        }

        $totalMultiplier = min($totalMultiplier, $cap);

        return [
            'base_rate' => $baseRate,
            'surge_multiplier' => round($totalMultiplier, 2),
            'final_rate' => round($baseRate * $totalMultiplier, 2),
            'surge_breakdown' => [
                'time' => round($timeSurgeMultiplier, 2),
                'demand' => round($demandSurge, 2),
                'event' => round($eventSurge, 2),
                'holiday' => round($holidaySurgeMultiplier, 2),
            ],
            'active_events' => $activeEvents,
            'demand_status' => $demandStatus,
            'holiday_info' => $holidayInfo, // GLO-007: Include holiday details
            'combination_method' => $method,
            'cap' => $cap,
            'cap_applied' => $totalMultiplier >= $cap,
        ];
    }

    /**
     * Recalculate surge for all shifts on a specific date.
     *
     * Useful when demand metrics or events are updated.
     */
    public function recalculateSurgeForDate(Carbon $date): int
    {
        $shifts = Shift::query()
            ->whereDate('shift_date', $date)
            ->whereIn('status', ['draft', 'open', 'pending'])
            ->get();

        $updated = 0;
        foreach ($shifts as $shift) {
            $this->calculateSurge($shift);
            $shift->save();
            $updated++;
        }

        return $updated;
    }
}
