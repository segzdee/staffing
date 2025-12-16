<?php

namespace App\Services;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class ShiftPricingService
{
    /**
     * Calculate and update all financial fields for the shift.
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

        // Step 4: Calculate platform fee
        $platformFeeRate = Config::get('overtimestaff.financial.platform_fee_rate', 35.00);
        $shift->platform_fee_rate = $platformFeeRate;
        $shift->platform_fee_amount = ($totalWorkerPay * $platformFeeRate) / 100;

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
     * Calculate and apply surge pricing multipliers.
     */
    public function calculateSurge(Shift $shift): Shift
    {
        $shift->time_surge = $this->calculateTimeSurge($shift);
        // Demand and Event surge logic can be injected here
        $shift->demand_surge = $shift->demand_surge ?? 0.0;
        $shift->event_surge = $shift->event_surge ?? 0.0;

        // Total surge multiplier
        $shift->surge_multiplier = 1.0 + $shift->time_surge + $shift->demand_surge + $shift->event_surge;

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

        // Urgent shifts
        if ($shift->urgency_level === 'urgent' || $shift->isUrgent()) {
            $surge += $config['urgent_shift'];
        }

        // Night shifts
        if ($shift->is_night_shift) {
            $surge += $config['night_shift'];
        }

        // Weekends
        if ($shift->is_weekend) {
            $surge += $config['weekend'];
        }

        // Public holidays
        if ($shift->is_public_holiday) {
            $surge += $config['public_holiday'];
        }

        return $surge;
    }

    /**
     * Check if a date is a public holiday.
     */
    public function isPublicHoliday(Carbon $date): bool
    {
        $holidays = Config::get('overtimestaff.holidays', []);
        return in_array($date->toDateString(), $holidays);
    }
}
