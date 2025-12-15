<?php

namespace App\Services;

use App\Models\AgencyProfile;
use App\Models\AgencyWorker;
use App\Models\ShiftPayment;
use App\Models\User;
use App\Notifications\StripeConnectRequiredNotification;
use App\Notifications\StripePayoutSuccessNotification;
use App\Notifications\StripePayoutFailedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AgencyCommissionService
 *
 * Handles agency commission calculations, deductions, and payout processing.
 *
 * TASK: AGY-003 Commission Automation
 *
 * Features:
 * - Commission rate configuration (5-20%, default 15%)
 * - Auto-deduction from worker earnings
 * - Agency payout processing (weekly)
 * - Commission reporting and analytics
 * - Per-worker commission overrides
 */
class AgencyCommissionService
{
    /**
     * Calculate commission for a shift payment.
     *
     * Determines the appropriate commission rate and calculates the amount
     * to be deducted from worker earnings.
     *
     * @param ShiftPayment $payment
     * @param int $agencyId
     * @param int $workerId
     * @return float Commission amount in cents
     */
    public function calculateCommissionForPayment(ShiftPayment $payment, $agencyId, $workerId)
    {
        // Get agency-worker relationship
        $agencyWorker = AgencyWorker::where('agency_id', $agencyId)
            ->where('worker_id', $workerId)
            ->where('status', 'active')
            ->first();

        if (!$agencyWorker) {
            Log::warning("No active agency-worker relationship found", [
                'agency_id' => $agencyId,
                'worker_id' => $workerId,
            ]);
            return 0;
        }

        // Get worker's net earnings (in cents)
        $workerEarningsInCents = $payment->worker_amount ?? 0;
        $workerEarningsInDollars = $workerEarningsInCents / 100;

        // Calculate commission using agency-worker logic
        $commissionInDollars = $agencyWorker->calculateCommission($workerEarningsInDollars);

        // Convert back to cents
        $commissionInCents = (int) round($commissionInDollars * 100);

        return $commissionInCents;
    }

    /**
     * Process commission deduction for a shift payment.
     *
     * Called when a payment is released from escrow.
     * Deducts commission from worker earnings and allocates to agency.
     *
     * @param ShiftPayment $payment
     * @param int $agencyId
     * @return bool Success status
     */
    public function processCommissionDeduction(ShiftPayment $payment, $agencyId)
    {
        DB::beginTransaction();
        try {
            // Calculate commission
            $commissionInCents = $this->calculateCommissionForPayment(
                $payment,
                $agencyId,
                $payment->worker_id
            );

            if ($commissionInCents <= 0) {
                DB::commit();
                return true; // No commission to process
            }

            // Deduct from worker amount
            $originalWorkerAmount = $payment->worker_amount;
            $newWorkerAmount = $originalWorkerAmount - $commissionInCents;

            // Update payment record
            $payment->update([
                'agency_commission' => $commissionInCents / 100, // Store as dollars
                'worker_amount' => $newWorkerAmount,
            ]);

            // Update agency pending commission
            $agencyProfile = AgencyProfile::where('user_id', $agencyId)->first();
            if ($agencyProfile) {
                $agencyProfile->increment('pending_commission', $commissionInCents / 100);
                $agencyProfile->increment('total_commission_earned', $commissionInCents / 100);
            }

            Log::info("Commission deducted successfully", [
                'payment_id' => $payment->id,
                'agency_id' => $agencyId,
                'worker_id' => $payment->worker_id,
                'commission_cents' => $commissionInCents,
                'original_worker_amount' => $originalWorkerAmount,
                'new_worker_amount' => $newWorkerAmount,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to process commission deduction", [
                'payment_id' => $payment->id,
                'agency_id' => $agencyId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Process weekly agency commission payouts.
     *
     * Called by scheduled job to pay agencies their pending commissions.
     *
     * @param int|null $agencyId Process specific agency or all agencies
     * @return array Summary of processed payouts
     */
    public function processWeeklyPayouts($agencyId = null)
    {
        $query = AgencyProfile::where('pending_commission', '>', 0);

        if ($agencyId) {
            $query->where('user_id', $agencyId);
        }

        $agencies = $query->get();
        $summary = [
            'total_agencies' => $agencies->count(),
            'total_amount' => 0,
            'successful' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($agencies as $agency) {
            $result = $this->processAgencyPayout($agency);

            if ($result['success']) {
                $summary['successful']++;
                $summary['total_amount'] += $result['amount'];
            } else {
                $summary['failed']++;
            }

            $summary['details'][] = $result;
        }

        return $summary;
    }

    /**
     * Process payout for a single agency.
     *
     * Uses Stripe Connect for actual payouts when available.
     *
     * @param AgencyProfile $agency
     * @return array Result details
     */
    protected function processAgencyPayout(AgencyProfile $agency)
    {
        DB::beginTransaction();
        try {
            $pendingAmount = $agency->pending_commission;

            if ($pendingAmount <= 0) {
                DB::rollBack();
                return [
                    'success' => false,
                    'agency_id' => $agency->user_id,
                    'agency_name' => $agency->agency_name,
                    'amount' => 0,
                    'error' => 'No pending commission',
                ];
            }

            // Check if agency has Stripe Connect enabled for payouts
            if (!$agency->stripe_payout_enabled || !$agency->canReceivePayouts()) {
                DB::rollBack();

                // Notify agency to complete Stripe Connect onboarding
                $this->notifyAgencyOnboardingRequired($agency);

                Log::warning("Agency cannot receive payouts - Stripe Connect not enabled", [
                    'agency_id' => $agency->user_id,
                    'agency_name' => $agency->agency_name,
                    'pending_amount' => $pendingAmount,
                    'stripe_payout_enabled' => $agency->stripe_payout_enabled,
                    'stripe_onboarding_complete' => $agency->stripe_onboarding_complete,
                ]);

                return [
                    'success' => false,
                    'agency_id' => $agency->user_id,
                    'agency_name' => $agency->agency_name,
                    'amount' => $pendingAmount,
                    'error' => 'Stripe Connect not enabled - agency notified to complete onboarding',
                    'requires_onboarding' => true,
                ];
            }

            // Process payout via Stripe Connect
            $stripeConnectService = app(StripeConnectService::class);
            $currency = $agency->stripe_default_currency ?? 'USD';
            $description = sprintf(
                'OvertimeStaff Commission Payout - Week of %s',
                now()->startOfWeek()->format('M j, Y')
            );

            $payoutResult = $stripeConnectService->createPayout(
                $agency,
                $pendingAmount,
                $currency,
                $description
            );

            if (!$payoutResult['success']) {
                DB::rollBack();

                // Notify agency of payout failure
                $this->notifyAgencyPayoutFailed($agency, $pendingAmount, $payoutResult['error'] ?? 'Unknown error');

                Log::error("Stripe Connect payout failed", [
                    'agency_id' => $agency->user_id,
                    'agency_name' => $agency->agency_name,
                    'amount' => $pendingAmount,
                    'error' => $payoutResult['error'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'agency_id' => $agency->user_id,
                    'agency_name' => $agency->agency_name,
                    'amount' => $pendingAmount,
                    'error' => $payoutResult['error'] ?? 'Stripe payout failed',
                ];
            }

            // Move from pending to paid
            $agency->decrement('pending_commission', $pendingAmount);
            $agency->increment('paid_commission', $pendingAmount);

            // Record payout details
            $agency->update([
                'last_payout_at' => now(),
                'last_payout_amount' => $pendingAmount,
                'last_payout_status' => 'paid',
            ]);

            Log::info("Agency commission payout processed via Stripe Connect", [
                'agency_id' => $agency->user_id,
                'agency_name' => $agency->agency_name,
                'amount' => $pendingAmount,
                'transfer_id' => $payoutResult['transfer_id'] ?? null,
            ]);

            DB::commit();

            // Notify agency of successful payout
            $this->notifyAgencyPayoutSuccess($agency, $pendingAmount, $currency, $payoutResult['transfer_id'] ?? '');

            return [
                'success' => true,
                'agency_id' => $agency->user_id,
                'agency_name' => $agency->agency_name,
                'amount' => $pendingAmount,
                'transfer_id' => $payoutResult['transfer_id'] ?? null,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to process agency payout", [
                'agency_id' => $agency->user_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'agency_id' => $agency->user_id,
                'agency_name' => $agency->agency_name,
                'amount' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Notify agency that Stripe Connect onboarding is required.
     *
     * @param AgencyProfile $agency
     * @return void
     */
    protected function notifyAgencyOnboardingRequired(AgencyProfile $agency): void
    {
        try {
            if ($agency->user) {
                $agency->user->notify(new StripeConnectRequiredNotification($agency));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to send Stripe onboarding notification", [
                'agency_id' => $agency->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify agency of successful payout.
     *
     * @param AgencyProfile $agency
     * @param float $amount
     * @param string $currency
     * @param string $transferId
     * @return void
     */
    protected function notifyAgencyPayoutSuccess(AgencyProfile $agency, float $amount, string $currency, string $transferId): void
    {
        try {
            if ($agency->user) {
                $agency->user->notify(new StripePayoutSuccessNotification($agency, $amount, $currency, $transferId));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to send payout success notification", [
                'agency_id' => $agency->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify agency of payout failure.
     *
     * @param AgencyProfile $agency
     * @param float $amount
     * @param string $error
     * @return void
     */
    protected function notifyAgencyPayoutFailed(AgencyProfile $agency, float $amount, string $error): void
    {
        try {
            if ($agency->user) {
                $agency->user->notify(new StripePayoutFailedNotification($agency, $amount, 'payout_failed', $error));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to send payout failure notification", [
                'agency_id' => $agency->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get commission breakdown for an agency.
     *
     * @param int $agencyId
     * @param \Carbon\Carbon|null $startDate
     * @param \Carbon\Carbon|null $endDate
     * @return array Commission details
     */
    public function getCommissionBreakdown($agencyId, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $payments = ShiftPayment::whereHas('assignment', function ($query) use ($agencyId) {
                $query->where('agency_id', $agencyId);
            })
            ->whereBetween('released_at', [$startDate, $endDate])
            ->whereNotNull('agency_commission')
            ->where('agency_commission', '>', 0)
            ->with(['worker', 'assignment.shift'])
            ->get();

        $breakdown = [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_commission' => $payments->sum('agency_commission'),
            'total_shifts' => $payments->count(),
            'by_worker' => [],
            'by_week' => [],
        ];

        // Group by worker
        $byWorker = $payments->groupBy('worker_id');
        foreach ($byWorker as $workerId => $workerPayments) {
            $worker = $workerPayments->first()->worker;
            $breakdown['by_worker'][] = [
                'worker_id' => $workerId,
                'worker_name' => $worker ? $worker->name : 'Unknown',
                'shifts' => $workerPayments->count(),
                'commission' => $workerPayments->sum('agency_commission'),
            ];
        }

        // Group by week
        $byWeek = $payments->groupBy(function ($payment) {
            return $payment->released_at->startOfWeek()->toDateString();
        });

        foreach ($byWeek as $week => $weekPayments) {
            $breakdown['by_week'][] = [
                'week_start' => $week,
                'shifts' => $weekPayments->count(),
                'commission' => $weekPayments->sum('agency_commission'),
            ];
        }

        return $breakdown;
    }

    /**
     * Update commission rate for an agency-worker relationship.
     *
     * @param int $agencyId
     * @param int $workerId
     * @param float $commissionRate Percentage (5-20)
     * @return bool Success status
     */
    public function updateWorkerCommissionRate($agencyId, $workerId, $commissionRate)
    {
        // Validate rate
        if ($commissionRate < 5.00 || $commissionRate > 20.00) {
            Log::warning("Invalid commission rate", [
                'agency_id' => $agencyId,
                'worker_id' => $workerId,
                'rate' => $commissionRate,
            ]);
            return false;
        }

        $agencyWorker = AgencyWorker::where('agency_id', $agencyId)
            ->where('worker_id', $workerId)
            ->first();

        if (!$agencyWorker) {
            Log::warning("Agency-worker relationship not found", [
                'agency_id' => $agencyId,
                'worker_id' => $workerId,
            ]);
            return false;
        }

        $agencyWorker->update([
            'commission_rate' => $commissionRate,
        ]);

        Log::info("Worker commission rate updated", [
            'agency_id' => $agencyId,
            'worker_id' => $workerId,
            'new_rate' => $commissionRate,
        ]);

        return true;
    }
}
