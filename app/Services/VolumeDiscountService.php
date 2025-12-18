<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\BusinessVolumeTracking;
use App\Models\Shift;
use App\Models\User;
use App\Models\VolumeDiscountTier;
use App\Notifications\TierProgressNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * FIN-001: Volume Discount Service
 *
 * Handles all volume-based discount tier logic including:
 * - Tier qualification and calculation
 * - Fee calculations with discounts
 * - Monthly volume tracking
 * - Tier change notifications
 */
class VolumeDiscountService
{
    /**
     * Get the current volume tier for a business.
     */
    public function getCurrentTier(User $business): ?VolumeDiscountTier
    {
        $profile = $business->businessProfile;

        if (! $profile) {
            return VolumeDiscountTier::getDefaultTier();
        }

        // Check for custom pricing first
        if ($this->hasValidCustomPricing($profile)) {
            return null; // Custom pricing overrides tiers
        }

        // Return cached tier if available
        if ($profile->current_volume_tier_id) {
            return VolumeDiscountTier::find($profile->current_volume_tier_id);
        }

        // Calculate and cache the tier
        return $this->recalculateTier($business);
    }

    /**
     * Calculate the platform fee with volume discount applied.
     *
     * @return array{
     *   base_amount: float,
     *   fee_percent: float,
     *   fee_amount: float,
     *   fee_without_discount: float,
     *   discount_amount: float,
     *   discount_percent: float,
     *   tier_name: string|null,
     *   custom_pricing: bool
     * }
     */
    public function calculateFeeWithDiscount(User $business, float $amount): array
    {
        $profile = $business->businessProfile;
        $defaultFeeRate = Config::get('overtimestaff.financial.platform_fee_rate', 35.00);

        // Get the effective fee percent
        $effectiveFeePercent = $this->getEffectiveFeePercent($business);
        $feeWithoutDiscount = $amount * ($defaultFeeRate / 100);
        $feeAmount = $amount * ($effectiveFeePercent / 100);
        $discountAmount = $feeWithoutDiscount - $feeAmount;
        $discountPercent = $defaultFeeRate > 0
            ? (($defaultFeeRate - $effectiveFeePercent) / $defaultFeeRate) * 100
            : 0;

        // Determine tier info
        $tier = $this->getCurrentTier($business);
        $isCustomPricing = $profile && $this->hasValidCustomPricing($profile);

        return [
            'base_amount' => $amount,
            'fee_percent' => $effectiveFeePercent,
            'fee_amount' => round($feeAmount, 2),
            'fee_without_discount' => round($feeWithoutDiscount, 2),
            'discount_amount' => round($discountAmount, 2),
            'discount_percent' => round($discountPercent, 1),
            'tier_name' => $tier?->name,
            'tier_id' => $tier?->id,
            'custom_pricing' => $isCustomPricing,
        ];
    }

    /**
     * Get the effective platform fee percent for a business.
     * Considers custom pricing, volume tiers, and default rate.
     */
    public function getEffectiveFeePercent(User $business): float
    {
        $profile = $business->businessProfile;
        $defaultFeeRate = Config::get('overtimestaff.financial.platform_fee_rate', 35.00);

        // Check for custom pricing first
        if ($profile && $this->hasValidCustomPricing($profile)) {
            return (float) $profile->custom_fee_percent;
        }

        // Get the current tier
        $tier = $this->getCurrentTier($business);

        if ($tier) {
            return (float) $tier->platform_fee_percent;
        }

        // Fall back to default rate
        return $defaultFeeRate;
    }

    /**
     * Update monthly volume tracking when a shift is posted/completed.
     */
    public function updateMonthlyVolume(User $business, Shift $shift, string $event = 'posted'): BusinessVolumeTracking
    {
        $shiftMonth = $shift->shift_date
            ? Carbon::parse($shift->shift_date)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $tracking = BusinessVolumeTracking::getOrCreateForMonth($business->id, $shiftMonth);

        switch ($event) {
            case 'posted':
                $tracking->incrementShiftsPosted();
                break;

            case 'filled':
                $tracking->incrementShiftsFilled();
                break;

            case 'completed':
                $tracking->incrementShiftsCompleted();
                // Add spend tracking
                if ($shift->total_business_cost) {
                    $cost = is_object($shift->total_business_cost)
                        ? $shift->total_business_cost->getAmount() / 100
                        : (float) $shift->total_business_cost;
                    $tracking->addSpend($cost);
                }
                break;

            case 'cancelled':
                $tracking->incrementShiftsCancelled();
                break;
        }

        // Recalculate tier after updating volume
        $this->recalculateTier($business);

        return $tracking;
    }

    /**
     * Recalculate and update the tier for a business.
     */
    public function recalculateTier(User $business): ?VolumeDiscountTier
    {
        $profile = $business->businessProfile;

        if (! $profile) {
            return VolumeDiscountTier::getDefaultTier();
        }

        // Skip if custom pricing is active
        if ($this->hasValidCustomPricing($profile)) {
            return null;
        }

        // Get current month's shift count
        $currentMonthTracking = BusinessVolumeTracking::getOrCreateForMonth($business->id);
        $shiftCount = $currentMonthTracking->shifts_posted;

        // Find the appropriate tier
        $newTier = VolumeDiscountTier::getTierForShiftCount($shiftCount);
        $previousTierId = $profile->current_volume_tier_id;

        // Update profile with new tier
        if ($newTier && $newTier->id !== $previousTierId) {
            $isUpgrade = ! $previousTierId ||
                ($newTier->min_shifts_monthly > (VolumeDiscountTier::find($previousTierId)?->min_shifts_monthly ?? 0));

            $profile->update([
                'current_volume_tier_id' => $newTier->id,
                'tier_upgraded_at' => $isUpgrade ? now() : $profile->tier_upgraded_at,
                'tier_downgraded_at' => ! $isUpgrade ? now() : $profile->tier_downgraded_at,
            ]);

            // Update tracking record
            $currentMonthTracking->updateAppliedTier($newTier);

            // Send notification for tier change
            $this->notifyTierChange($business, $newTier, $isUpgrade);

            Log::info('FIN-001: Business tier changed', [
                'business_id' => $business->id,
                'previous_tier_id' => $previousTierId,
                'new_tier_id' => $newTier->id,
                'new_tier_name' => $newTier->name,
                'is_upgrade' => $isUpgrade,
                'shift_count' => $shiftCount,
            ]);
        } elseif (! $newTier && $previousTierId) {
            // No tier qualifies anymore, use default
            $defaultTier = VolumeDiscountTier::getDefaultTier();
            $profile->update([
                'current_volume_tier_id' => $defaultTier?->id,
                'tier_downgraded_at' => now(),
            ]);
            $currentMonthTracking->updateAppliedTier($defaultTier);
        }

        return $newTier ?? VolumeDiscountTier::getDefaultTier();
    }

    /**
     * Get comprehensive volume statistics for a business.
     *
     * @return array{
     *   current_tier: array|null,
     *   current_month: array,
     *   next_tier: array|null,
     *   lifetime: array,
     *   history: array
     * }
     */
    public function getVolumeStats(User $business): array
    {
        $profile = $business->businessProfile;
        $currentTier = $this->getCurrentTier($business);
        $currentMonthTracking = BusinessVolumeTracking::getOrCreateForMonth($business->id);

        // Get next tier info
        $nextTierInfo = null;
        if ($currentTier) {
            $nextTier = $currentTier->getNextTier();
            if ($nextTier) {
                $shiftsNeeded = $nextTier->shiftsNeededFrom($currentMonthTracking->shifts_posted);
                $nextTierInfo = [
                    'id' => $nextTier->id,
                    'name' => $nextTier->name,
                    'platform_fee_percent' => $nextTier->platform_fee_percent,
                    'min_shifts_required' => $nextTier->min_shifts_monthly,
                    'shifts_needed' => $shiftsNeeded,
                    'potential_savings_percent' => $currentTier->platform_fee_percent - $nextTier->platform_fee_percent,
                ];
            }
        }

        // Get historical data
        $history = BusinessVolumeTracking::getHistory($business->id, 12);

        return [
            'current_tier' => $currentTier ? [
                'id' => $currentTier->id,
                'name' => $currentTier->name,
                'slug' => $currentTier->slug,
                'platform_fee_percent' => $currentTier->platform_fee_percent,
                'shift_range' => $currentTier->shift_range,
                'benefits' => $currentTier->benefits,
                'badge_color' => $currentTier->badge_color,
                'badge_icon' => $currentTier->badge_icon,
                'discount_percentage' => $currentTier->discount_percentage,
            ] : null,
            'custom_pricing' => $profile ? $this->hasValidCustomPricing($profile) : false,
            'custom_fee_percent' => $profile?->custom_fee_percent,
            'current_month' => [
                'month' => $currentMonthTracking->month_name,
                'shifts_posted' => $currentMonthTracking->shifts_posted,
                'shifts_completed' => $currentMonthTracking->shifts_completed,
                'total_spend' => $currentMonthTracking->total_spend,
                'platform_fees_paid' => $currentMonthTracking->platform_fees_paid,
                'savings' => $currentMonthTracking->discount_amount,
                'fill_rate' => $currentMonthTracking->fill_rate,
            ],
            'next_tier' => $nextTierInfo,
            'lifetime' => [
                'total_shifts' => $profile?->lifetime_shifts ?? 0,
                'total_spend' => $profile?->lifetime_spend ?? 0,
                'total_savings' => $profile?->lifetime_savings ?? 0,
            ],
            'history' => $history->map(fn ($h) => $h->getSummary())->toArray(),
        ];
    }

    /**
     * Get progress information toward the next tier.
     *
     * @return array{
     *   has_next_tier: bool,
     *   current_shifts: int,
     *   shifts_needed: int,
     *   progress_percent: float,
     *   next_tier_name: string|null,
     *   next_tier_fee: float|null,
     *   potential_savings: float|null
     * }
     */
    public function getNextTierProgress(User $business): array
    {
        $currentTier = $this->getCurrentTier($business);
        $tracking = BusinessVolumeTracking::getOrCreateForMonth($business->id);
        $currentShifts = $tracking->shifts_posted;

        if (! $currentTier) {
            $nextTier = VolumeDiscountTier::getDefaultTier();

            return [
                'has_next_tier' => (bool) $nextTier,
                'current_shifts' => $currentShifts,
                'shifts_needed' => $nextTier ? $nextTier->min_shifts_monthly : 0,
                'progress_percent' => 0,
                'next_tier_name' => $nextTier?->name,
                'next_tier_fee' => $nextTier?->platform_fee_percent,
                'potential_savings' => null,
            ];
        }

        $nextTier = $currentTier->getNextTier();

        if (! $nextTier) {
            return [
                'has_next_tier' => false,
                'current_shifts' => $currentShifts,
                'shifts_needed' => 0,
                'progress_percent' => 100,
                'next_tier_name' => null,
                'next_tier_fee' => null,
                'potential_savings' => null,
                'at_max_tier' => true,
                'current_tier_name' => $currentTier->name,
            ];
        }

        $shiftsNeeded = $nextTier->shiftsNeededFrom($currentShifts);
        $shiftsInCurrentTierRange = $nextTier->min_shifts_monthly - $currentTier->min_shifts_monthly;
        $shiftsCompleted = $currentShifts - $currentTier->min_shifts_monthly;
        $progressPercent = $shiftsInCurrentTierRange > 0
            ? min(100, ($shiftsCompleted / $shiftsInCurrentTierRange) * 100)
            : 100;

        // Calculate potential monthly savings at next tier
        $avgSpend = $tracking->average_shift_value * $currentShifts;
        $currentFee = $avgSpend * ($currentTier->platform_fee_percent / 100);
        $nextTierFee = $avgSpend * ($nextTier->platform_fee_percent / 100);
        $potentialSavings = $currentFee - $nextTierFee;

        return [
            'has_next_tier' => true,
            'current_shifts' => $currentShifts,
            'shifts_needed' => $shiftsNeeded,
            'progress_percent' => round($progressPercent, 1),
            'next_tier_name' => $nextTier->name,
            'next_tier_fee' => $nextTier->platform_fee_percent,
            'potential_savings' => round($potentialSavings, 2),
            'current_tier_name' => $currentTier->name,
            'min_shifts_for_next' => $nextTier->min_shifts_monthly,
        ];
    }

    /**
     * Apply custom pricing to a business (for enterprise contracts).
     */
    public function applyCustomPricing(
        User $business,
        float $feePercent,
        ?string $notes = null,
        ?Carbon $expiresAt = null
    ): bool {
        $profile = $business->businessProfile;

        if (! $profile) {
            Log::warning('FIN-001: Cannot apply custom pricing - no business profile', [
                'business_id' => $business->id,
            ]);

            return false;
        }

        $profile->update([
            'custom_pricing' => true,
            'custom_fee_percent' => $feePercent,
            'custom_pricing_notes' => $notes,
            'custom_pricing_expires_at' => $expiresAt,
        ]);

        Log::info('FIN-001: Custom pricing applied', [
            'business_id' => $business->id,
            'fee_percent' => $feePercent,
            'expires_at' => $expiresAt?->toDateString(),
        ]);

        return true;
    }

    /**
     * Remove custom pricing from a business.
     */
    public function removeCustomPricing(User $business): bool
    {
        $profile = $business->businessProfile;

        if (! $profile) {
            return false;
        }

        $profile->update([
            'custom_pricing' => false,
            'custom_fee_percent' => null,
            'custom_pricing_notes' => null,
            'custom_pricing_expires_at' => null,
        ]);

        // Recalculate tier
        $this->recalculateTier($business);

        Log::info('FIN-001: Custom pricing removed', [
            'business_id' => $business->id,
        ]);

        return true;
    }

    /**
     * Get monthly report for a business.
     *
     * @return array{
     *   month: string,
     *   summary: array,
     *   tier_info: array|null,
     *   comparison: array|null
     * }
     */
    public function getMonthlyReport(User $business, Carbon $month): array
    {
        $tracking = BusinessVolumeTracking::query()
            ->forBusiness($business->id)
            ->forMonth($month)
            ->with('appliedTier')
            ->first();

        if (! $tracking) {
            return [
                'month' => $month->format('F Y'),
                'summary' => null,
                'tier_info' => null,
                'comparison' => null,
                'message' => 'No data available for this month',
            ];
        }

        // Get previous month for comparison
        $previousMonth = $month->copy()->subMonth();
        $previousTracking = BusinessVolumeTracking::query()
            ->forBusiness($business->id)
            ->forMonth($previousMonth)
            ->first();

        $comparison = null;
        if ($previousTracking) {
            $comparison = [
                'shifts_change' => $tracking->shifts_posted - $previousTracking->shifts_posted,
                'shifts_change_percent' => $previousTracking->shifts_posted > 0
                    ? round((($tracking->shifts_posted - $previousTracking->shifts_posted) / $previousTracking->shifts_posted) * 100, 1)
                    : null,
                'spend_change' => $tracking->total_spend - $previousTracking->total_spend,
                'spend_change_percent' => $previousTracking->total_spend > 0
                    ? round((($tracking->total_spend - $previousTracking->total_spend) / $previousTracking->total_spend) * 100, 1)
                    : null,
                'savings_change' => $tracking->discount_amount - $previousTracking->discount_amount,
            ];
        }

        return [
            'month' => $month->format('F Y'),
            'summary' => $tracking->getSummary(),
            'tier_info' => $tracking->appliedTier ? [
                'name' => $tracking->appliedTier->name,
                'fee_percent' => $tracking->appliedTier->platform_fee_percent,
                'discount_percentage' => $tracking->appliedTier->discount_percentage,
            ] : null,
            'comparison' => $comparison,
            'daily_breakdown' => $tracking->daily_breakdown,
        ];
    }

    /**
     * Get all available tiers with business's qualification status.
     */
    public function getAvailableTiers(User $business): array
    {
        $tiers = VolumeDiscountTier::getActiveTiers();
        $tracking = BusinessVolumeTracking::getOrCreateForMonth($business->id);
        $currentTier = $this->getCurrentTier($business);

        return $tiers->map(function ($tier) use ($tracking, $currentTier) {
            $qualifies = $tier->qualifiesForShiftCount($tracking->shifts_posted);
            $shiftsNeeded = $tier->shiftsNeededFrom($tracking->shifts_posted);

            return [
                'id' => $tier->id,
                'name' => $tier->name,
                'slug' => $tier->slug,
                'platform_fee_percent' => $tier->platform_fee_percent,
                'shift_range' => $tier->shift_range,
                'min_shifts' => $tier->min_shifts_monthly,
                'max_shifts' => $tier->max_shifts_monthly,
                'benefits' => $tier->benefits,
                'badge_color' => $tier->badge_color,
                'badge_icon' => $tier->badge_icon,
                'description' => $tier->description,
                'discount_percentage' => $tier->discount_percentage,
                'savings_description' => $tier->savings_description,
                'is_current' => $currentTier && $currentTier->id === $tier->id,
                'qualifies' => $qualifies,
                'shifts_needed' => $shiftsNeeded,
            ];
        })->toArray();
    }

    /**
     * Record platform fee payment and update tracking.
     */
    public function recordFeePayment(User $business, Shift $shift, float $discountedFee, float $fullFee): void
    {
        $shiftMonth = $shift->shift_date
            ? Carbon::parse($shift->shift_date)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $tracking = BusinessVolumeTracking::getOrCreateForMonth($business->id, $shiftMonth);
        $tracking->addPlatformFees($discountedFee, $fullFee);

        // Update lifetime savings in profile
        $profile = $business->businessProfile;
        if ($profile) {
            $savings = $fullFee - $discountedFee;
            $profile->increment('lifetime_savings', $savings);
        }
    }

    /**
     * Update lifetime statistics after shift completion.
     */
    public function updateLifetimeStats(User $business, Shift $shift): void
    {
        $profile = $business->businessProfile;

        if (! $profile) {
            return;
        }

        $profile->increment('lifetime_shifts');

        if ($shift->total_business_cost) {
            $cost = is_object($shift->total_business_cost)
                ? $shift->total_business_cost->getAmount() / 100
                : (float) $shift->total_business_cost;
            $profile->increment('lifetime_spend', $cost);
        }
    }

    // =========================================
    // Protected Methods
    // =========================================

    /**
     * Check if business has valid custom pricing.
     */
    protected function hasValidCustomPricing(BusinessProfile $profile): bool
    {
        if (! $profile->custom_pricing) {
            return false;
        }

        if ($profile->custom_fee_percent === null) {
            return false;
        }

        // Check if custom pricing has expired
        if ($profile->custom_pricing_expires_at && Carbon::parse($profile->custom_pricing_expires_at)->isPast()) {
            // Auto-remove expired custom pricing
            $profile->update([
                'custom_pricing' => false,
                'custom_fee_percent' => null,
                'custom_pricing_notes' => null,
                'custom_pricing_expires_at' => null,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Send notification about tier change.
     */
    protected function notifyTierChange(User $business, VolumeDiscountTier $newTier, bool $isUpgrade): void
    {
        try {
            $business->notify(new TierProgressNotification(
                $newTier,
                $isUpgrade ? 'upgraded' : 'downgraded',
                $this->getNextTierProgress($business)
            ));

            // Mark tracking as notified
            $tracking = BusinessVolumeTracking::getOrCreateForMonth($business->id);
            $tracking->markTierNotified();
        } catch (\Exception $e) {
            Log::error('FIN-001: Failed to send tier change notification', [
                'business_id' => $business->id,
                'tier_id' => $newTier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
