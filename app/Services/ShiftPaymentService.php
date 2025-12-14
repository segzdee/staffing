<?php

namespace App\Services;

use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\Shift;
use App\Models\User;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class ShiftPaymentService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Hold payment in escrow when a shift is assigned to a worker.
     * Captures funds from business and holds in platform account.
     * SL-004: Booking confirmation & escrow capture
     *
     * @param ShiftAssignment $assignment
     * @return ShiftPayment|null
     */
    public function holdInEscrow(ShiftAssignment $assignment)
    {
        try {
            $shift = $assignment->shift;
            $business = $shift->business;
            $worker = $assignment->worker;

            // ===== SL-004: Use pre-calculated shift costs from SL-001 =====
            $escrowAmount = $shift->escrow_amount; // Total cost + 5% buffer
            $hoursEstimated = $shift->duration_hours;
            $hourlyRate = $shift->final_rate; // Includes surge pricing
            $workerPayPerHour = $hourlyRate;
            $workerPayEstimated = $hoursEstimated * $workerPayPerHour;
            $platformFeeAmount = $shift->platform_fee_amount; // Already calculated in shift
            $vatAmount = $shift->vat_amount; // Already calculated

            // Create Payment Intent to capture escrow amount from business
            // Validate amount to prevent -INF/INF casting errors
            $amountCents = $this->validateAndConvertToCents($escrowAmount);
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amountCents, // Convert to cents (includes 5% buffer)
                'currency' => 'usd',
                'customer' => $business->stripe_id,
                'payment_method' => $business->default_payment_method,
                'off_session' => true,
                'confirm' => true,
                'description' => "Escrow for shift: {$shift->title} (ID: {$shift->id})",
                'metadata' => [
                    'shift_id' => $shift->id,
                    'assignment_id' => $assignment->id,
                    'worker_id' => $worker->id,
                    'business_id' => $business->id,
                    'type' => 'shift_escrow',
                    'includes_buffer' => 'true',
                ],
            ]);

            // Create shift payment record with SL-001 calculated values
            $shiftPayment = ShiftPayment::create([
                'shift_id' => $shift->id,
                'assignment_id' => $assignment->id,
                'worker_id' => $worker->id,
                'business_id' => $business->id,
                'amount_gross' => $shift->total_business_cost, // Total before buffer
                'platform_fee' => $platformFeeAmount,
                'vat_amount' => $vatAmount,
                'amount_net' => $workerPayEstimated, // Worker's share
                'escrow_amount' => $escrowAmount, // Amount actually captured
                'hours_estimated' => $hoursEstimated,
                'hourly_rate' => $hourlyRate,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'status' => 'in_escrow',
                'escrow_held_at' => now(),
            ]);

            // Create transaction record
            Transaction::create([
                'user_id' => $business->id,
                'amount' => -$amountGross,
                'type' => 'shift_payment',
                'status' => 'completed',
                'description' => "Payment held in escrow for shift: {$shift->title}",
                'reference_id' => $shiftPayment->id,
            ]);

            // Update assignment status
            $assignment->update([
                'payment_status' => 'escrowed',
            ]);

            Log::info("Escrow held successfully", [
                'shift_id' => $shift->id,
                'assignment_id' => $assignment->id,
                'amount' => $amountGross,
                'payment_id' => $shiftPayment->id,
            ]);

            return $shiftPayment;

        } catch (ApiErrorException $e) {
            Log::error("Stripe escrow error", [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            // Mark assignment as payment failed
            $assignment->update([
                'payment_status' => 'failed',
            ]);

            return null;
        }
    }

    /**
     * Release payment from escrow after shift completion.
     * Called 15 minutes after shift check-out or manual completion.
     *
     * @param ShiftAssignment $assignment
     * @return bool
     */
    public function releaseFromEscrow(ShiftAssignment $assignment)
    {
        try {
            $shiftPayment = ShiftPayment::where('assignment_id', $assignment->id)
                ->where('status', 'in_escrow')
                ->first();

            if (!$shiftPayment) {
                Log::warning("No escrow payment found to release", [
                    'assignment_id' => $assignment->id,
                ]);
                return false;
            }

            // Recalculate based on actual hours worked
            $actualHours = $assignment->hours_worked ?? $shiftPayment->hours_estimated;
            $hourlyRate = $shiftPayment->hourly_rate;
            $amountGross = $actualHours * $hourlyRate;
            $platformFee = $amountGross * $this->platformFeePercentage;
            $amountNet = $amountGross - $platformFee;

            // Update payment record with actual amounts
            $shiftPayment->update([
                'hours_actual' => $actualHours,
                'amount_gross' => $amountGross,
                'platform_fee' => $platformFee,
                'amount_net' => $amountNet,
                'status' => 'released',
                'released_at' => now(),
            ]);

            // If actual amount is less than escrowed, refund difference to business
            $originalAmount = $shiftPayment->hours_estimated * $hourlyRate;
            if ($amountGross < $originalAmount) {
                $refundAmount = $originalAmount - $amountGross;
                $this->refundToBusiness($shiftPayment, $refundAmount);
            }

            // Update assignment status
            $assignment->update([
                'payment_status' => 'released',
            ]);

            Log::info("Escrow released successfully", [
                'assignment_id' => $assignment->id,
                'payment_id' => $shiftPayment->id,
                'amount' => $amountNet,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Escrow release error", [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Initiate instant payout to worker via Stripe Connect.
     * Called automatically 15 minutes after escrow release.
     *
     * @param ShiftPayment $shiftPayment
     * @return bool
     */
    public function instantPayout(ShiftPayment $shiftPayment)
    {
        try {
            $worker = $shiftPayment->worker;

            // Verify worker can receive instant payouts
            if (!$worker->canReceiveInstantPayouts()) {
                Log::warning("Worker cannot receive instant payouts", [
                    'worker_id' => $worker->id,
                    'payment_id' => $shiftPayment->id,
                ]);
                return false;
            }

            // Create instant payout via Stripe Connect
            // Validate amount to prevent -INF/INF casting errors
            $amountCents = $this->validateAndConvertToCents($shiftPayment->amount_net);
            $payout = $this->stripe->transfers->create([
                'amount' => $amountCents, // Convert to cents
                'currency' => 'usd',
                'destination' => $worker->stripe_connect_id,
                'description' => "Instant payout for shift: {$shiftPayment->shift->title}",
                'metadata' => [
                    'shift_id' => $shiftPayment->shift_id,
                    'payment_id' => $shiftPayment->id,
                    'worker_id' => $worker->id,
                    'type' => 'shift_payout',
                ],
            ]);

            // Update payment record
            $shiftPayment->update([
                'stripe_transfer_id' => $payout->id,
                'status' => 'paid_out',
                'payout_initiated_at' => now(),
                'payout_completed_at' => now(), // Instant payouts are immediate
            ]);

            // Create transaction record for worker
            Transaction::create([
                'user_id' => $worker->id,
                'amount' => $shiftPayment->amount_net,
                'type' => 'shift_earning',
                'status' => 'completed',
                'description' => "Instant payout for shift: {$shiftPayment->shift->title}",
                'reference_id' => $shiftPayment->id,
            ]);

            // Update worker profile statistics
            $workerProfile = $worker->workerProfile;
            if ($workerProfile) {
                $workerProfile->increment('total_shifts_completed');
                $workerProfile->increment('total_earnings', $shiftPayment->amount_net);
                $workerProfile->updateReliabilityScore();
            }

            // Update assignment status
            $shiftPayment->assignment->update([
                'payment_status' => 'completed',
            ]);

            Log::info("Instant payout completed successfully", [
                'worker_id' => $worker->id,
                'payment_id' => $shiftPayment->id,
                'amount' => $shiftPayment->amount_net,
                'transfer_id' => $payout->id,
            ]);

            // TODO: Send notification to worker
            // event(new InstantPayoutCompleted($shiftPayment));

            return true;

        } catch (ApiErrorException $e) {
            Log::error("Instant payout error", [
                'payment_id' => $shiftPayment->id,
                'worker_id' => $shiftPayment->worker_id,
                'error' => $e->getMessage(),
            ]);

            $shiftPayment->update([
                'status' => 'failed',
                'payout_error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process all payments that are ready for instant payout.
     * This should be called by a scheduled job every minute.
     */
    public function processReadyPayouts()
    {
        // Find all payments that were released 15+ minutes ago and not yet paid out
        $readyPayments = ShiftPayment::where('status', 'released')
            ->where('released_at', '<=', Carbon::now()->subMinutes(15))
            ->whereNull('disputed')
            ->get();

        $successCount = 0;
        $failureCount = 0;

        foreach ($readyPayments as $payment) {
            if ($this->instantPayout($payment)) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        Log::info("Processed ready payouts", [
            'total' => $readyPayments->count(),
            'successful' => $successCount,
            'failed' => $failureCount,
        ]);

        return [
            'total' => $readyPayments->count(),
            'successful' => $successCount,
            'failed' => $failureCount,
        ];
    }

    /**
     * Handle payment dispute.
     * Freezes payment until dispute is resolved.
     *
     * @param ShiftAssignment $assignment
     * @param string $reason
     * @return bool
     */
    public function handleDispute(ShiftAssignment $assignment, string $reason)
    {
        try {
            $shiftPayment = ShiftPayment::where('assignment_id', $assignment->id)->first();

            if (!$shiftPayment) {
                return false;
            }

            // Update payment status to disputed
            $shiftPayment->update([
                'disputed' => true,
                'dispute_reason' => $reason,
                'dispute_created_at' => now(),
                'status' => 'disputed',
            ]);

            // Update assignment
            $assignment->update([
                'payment_status' => 'disputed',
            ]);

            Log::info("Payment dispute created", [
                'assignment_id' => $assignment->id,
                'payment_id' => $shiftPayment->id,
                'reason' => $reason,
            ]);

            // TODO: Notify both parties
            // event(new PaymentDisputed($shiftPayment));

            return true;

        } catch (\Exception $e) {
            Log::error("Dispute handling error", [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Resolve a payment dispute.
     *
     * @param ShiftPayment $shiftPayment
     * @param string $resolution ('release_to_worker', 'refund_to_business', 'split')
     * @param float|null $workerAmount
     * @param float|null $businessRefund
     * @return bool
     */
    public function resolveDispute(ShiftPayment $shiftPayment, string $resolution, $workerAmount = null, $businessRefund = null)
    {
        try {
            DB::beginTransaction();

            switch ($resolution) {
                case 'release_to_worker':
                    // Release full amount to worker
                    $shiftPayment->update([
                        'disputed' => false,
                        'dispute_resolved_at' => now(),
                        'dispute_resolution' => $resolution,
                        'status' => 'released',
                        'released_at' => now(),
                    ]);
                    $this->instantPayout($shiftPayment);
                    break;

                case 'refund_to_business':
                    // Refund full amount to business
                    $this->refundToBusiness($shiftPayment, $shiftPayment->amount_gross);
                    $shiftPayment->update([
                        'disputed' => false,
                        'dispute_resolved_at' => now(),
                        'dispute_resolution' => $resolution,
                        'status' => 'refunded',
                    ]);
                    break;

                case 'split':
                    // Custom split between worker and business
                    if ($workerAmount && $businessRefund) {
                        $shiftPayment->update([
                            'amount_net' => $workerAmount,
                            'disputed' => false,
                            'dispute_resolved_at' => now(),
                            'dispute_resolution' => $resolution,
                            'status' => 'released',
                            'released_at' => now(),
                        ]);
                        $this->instantPayout($shiftPayment);
                        $this->refundToBusiness($shiftPayment, $businessRefund);
                    }
                    break;
            }

            DB::commit();

            Log::info("Dispute resolved successfully", [
                'payment_id' => $shiftPayment->id,
                'resolution' => $resolution,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Dispute resolution error", [
                'payment_id' => $shiftPayment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Refund amount to business (for overpayment or disputes).
     */
    protected function refundToBusiness(ShiftPayment $shiftPayment, float $amount)
    {
        try {
            $business = $shiftPayment->business;

            // Create refund in Stripe
            // Validate amount to prevent -INF/INF casting errors
            $amountCents = $this->validateAndConvertToCents($amount);
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $shiftPayment->stripe_payment_intent_id,
                'amount' => $amountCents, // Convert to cents
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'payment_id' => $shiftPayment->id,
                    'type' => 'shift_refund',
                ],
            ]);

            // Create transaction record
            Transaction::create([
                'user_id' => $business->id,
                'amount' => $amount,
                'type' => 'shift_refund',
                'status' => 'completed',
                'description' => "Refund for shift: {$shiftPayment->shift->title}",
                'reference_id' => $shiftPayment->id,
            ]);

            Log::info("Refund processed successfully", [
                'business_id' => $business->id,
                'payment_id' => $shiftPayment->id,
                'amount' => $amount,
            ]);

            return true;

        } catch (ApiErrorException $e) {
            Log::error("Refund error", [
                'payment_id' => $shiftPayment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Calculate platform revenue from completed shifts.
     */
    public function calculatePlatformRevenue($startDate = null, $endDate = null)
    {
        $query = ShiftPayment::where('status', 'paid_out');

        if ($startDate) {
            $query->where('payout_completed_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('payout_completed_at', '<=', $endDate);
        }

        return $query->sum('platform_fee');
    }

    /**
     * Get payment statistics for admin dashboard.
     */
    public function getPaymentStatistics()
    {
        return [
            'total_escrowed' => ShiftPayment::where('status', 'in_escrow')->sum('amount_gross'),
            'total_released' => ShiftPayment::where('status', 'released')->sum('amount_net'),
            'total_paid_out' => ShiftPayment::where('status', 'paid_out')->sum('amount_net'),
            'total_disputed' => ShiftPayment::where('disputed', true)->count(),
            'pending_payouts' => ShiftPayment::where('status', 'released')
                ->where('released_at', '<=', Carbon::now()->subMinutes(15))
                ->count(),
            'platform_revenue_today' => $this->calculatePlatformRevenue(
                Carbon::today(),
                Carbon::tomorrow()
            ),
            'platform_revenue_month' => $this->calculatePlatformRevenue(
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ),
        ];
    }

    /**
     * SL-004: Check and process worker acknowledgment requirements.
     * Workers must acknowledge shift within 2 hours of assignment.
     * Auto-cancel if no acknowledgment within 6 hours.
     *
     * This should be called by a scheduled job every 15 minutes.
     */
    public function processAcknowledgmentRequirements()
    {
        $now = now();

        // Find all assignments waiting for acknowledgment
        $waitingAcknowledgment = ShiftAssignment::whereNull('acknowledged_at')
            ->whereNotNull('acknowledgment_required_by')
            ->whereIn('status', ['assigned'])
            ->with(['shift', 'worker', 'application'])
            ->get();

        $remindersSent = 0;
        $autoCancelled = 0;

        foreach ($waitingAcknowledgment as $assignment) {
            $application = $assignment->application;
            $hoursElapsed = Carbon::parse($assignment->created_at)->diffInHours($now);

            // Send reminder if 2 hours passed and no reminder sent yet
            if ($hoursElapsed >= 2 && !$application->reminder_sent_at) {
                $application->update(['reminder_sent_at' => $now]);
                // TODO: Send reminder notification to worker
                // event(new ShiftAcknowledgmentReminder($assignment));
                $remindersSent++;
            }

            // Auto-cancel if 6 hours passed with no acknowledgment
            if ($hoursElapsed >= 6) {
                $assignment->update([
                    'status' => 'cancelled',
                    'cancelled_at' => $now,
                ]);

                $application->update([
                    'status' => 'auto_cancelled',
                    'auto_cancelled_at' => $now,
                    'acknowledgment_late' => true,
                ]);

                // Refund escrow to business
                $shiftPayment = ShiftPayment::where('assignment_id', $assignment->id)
                    ->where('status', 'in_escrow')
                    ->first();

                if ($shiftPayment) {
                    $this->refundToBusiness($shiftPayment, $shiftPayment->escrow_amount);
                }

                // Decrement shift filled workers count
                $assignment->shift->decrement('filled_workers');

                // Mark worker reliability (failure to acknowledge)
                $workerProfile = $assignment->worker->workerProfile;
                if ($workerProfile) {
                    $workerProfile->increment('total_no_acknowledgments');
                    $workerProfile->updateReliabilityScore();
                }

                // TODO: Notify worker and business
                // event(new ShiftAutoCancelled($assignment));

                $autoCancelled++;

                Log::info("Shift auto-cancelled due to no acknowledgment", [
                    'assignment_id' => $assignment->id,
                    'worker_id' => $assignment->worker_id,
                    'shift_id' => $assignment->shift_id,
                ]);
            }
        }

        return [
            'total_pending' => $waitingAcknowledgment->count(),
            'reminders_sent' => $remindersSent,
            'auto_cancelled' => $autoCancelled,
        ];
    }

    /**
     * Worker acknowledges shift assignment.
     * Must be done within 2-6 hours of assignment to avoid auto-cancellation.
     *
     * @param ShiftAssignment $assignment
     * @return bool
     */
    public function acknowledgeShift(ShiftAssignment $assignment)
    {
        if ($assignment->acknowledged_at) {
            return false; // Already acknowledged
        }

        $assignment->update(['acknowledged_at' => now()]);

        // Update application status
        if ($assignment->application) {
            $assignment->application->update(['acknowledged_at' => now()]);
        }

        // Check if acknowledgment was late (after 2 hours)
        $hoursElapsed = Carbon::parse($assignment->created_at)->diffInHours(now());
        if ($hoursElapsed > 2) {
            $assignment->application->update(['acknowledgment_late' => true]);

            // Minor reliability penalty for late acknowledgment
            $workerProfile = $assignment->worker->workerProfile;
            if ($workerProfile) {
                $workerProfile->decrement('reliability_score', 2); // -2 points
            }
        }

        Log::info("Shift acknowledged by worker", [
            'assignment_id' => $assignment->id,
            'worker_id' => $assignment->worker_id,
            'shift_id' => $assignment->shift_id,
            'late' => $hoursElapsed > 2,
        ]);

        return true;
    }

    /**
     * Validate amount and convert to cents, preventing -INF/INF casting errors.
     *
     * @param float|int|null $amount
     * @return int
     * @throws \InvalidArgumentException
     */
    protected function validateAndConvertToCents($amount)
    {
        // Check for null or invalid values
        if ($amount === null || $amount === '') {
            throw new \InvalidArgumentException('Amount cannot be null or empty');
        }

        // Check for INF/-INF values
        if (is_infinite($amount) || is_nan($amount)) {
            Log::error('Invalid amount value detected in payment service', [
                'amount' => $amount,
                'is_infinite' => is_infinite($amount),
                'is_nan' => is_nan($amount),
            ]);
            throw new \InvalidArgumentException('Amount cannot be infinite or NaN');
        }

        // Ensure amount is numeric
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }

        // Convert to float first, then to cents
        $amountFloat = (float) $amount;

        // Check for negative amounts (unless refunds are allowed)
        if ($amountFloat < 0) {
            Log::warning('Negative amount detected in payment service', [
                'amount' => $amountFloat,
            ]);
            // For refunds, we might allow negative, but for payments we should not
            // For now, we'll throw an error
            throw new \InvalidArgumentException('Amount cannot be negative');
        }

        // Convert to cents and ensure it's a valid integer
        $cents = (int) round($amountFloat * 100);

        // Final validation - ensure cents is within reasonable bounds
        if ($cents < 0 || $cents > 999999999) { // Max ~$9.9M
            throw new \InvalidArgumentException('Amount is out of valid range');
        }

        return $cents;
    }
}
