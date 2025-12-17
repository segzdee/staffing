<?php

namespace App\Services;

use App\Models\ShiftAssignment;
use App\Models\Shift;
use App\Models\ShiftPayment;
use App\Models\User;
use App\Models\Transaction;
use App\Models\EscrowRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * Escrow Management Service
 * 
 * Centralized escrow operations for OvertimeStaff platform
 * Handles all escrow capture, tracking, and release operations
 * 
 * SL-002: Escrow Management System
 * FIN-002: Escrow Management System
 */
class EscrowService
{
    protected $stripe;
    protected $masterEscrowAccountId;

    public function __construct()
    {
        $stripeSecret = config('services.stripe.secret');
        $this->stripe = $stripeSecret ? new StripeClient($stripeSecret) : null;
        $this->masterEscrowAccountId = config('services.stripe.escrow_account_id');
    }

    /**
     * Capture and hold funds in escrow for shift assignment
     * SL-004: Booking confirmation & escrow lock
     */
    public function captureEscrow(ShiftAssignment $assignment): ?ShiftPayment
    {
        try {
            DB::beginTransaction();

            $shift = $assignment->shift;
            $business = $shift->business;
            $worker = $assignment->worker;

            // Calculate escrow requirement
            $escrowCalculation = $this->calculateEscrowRequirement($shift);

            // Create payment intent for escrow capture
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $escrowCalculation['total_cents'],
                'currency' => $shift->currency ?? 'usd',
                'customer' => $business->stripe_customer_id,
                'payment_method_types' => ['card'],
                'confirmation_method' => 'manual',
                'confirm' => false,
                'metadata' => [
                    'shift_id' => $shift->id,
                    'assignment_id' => $assignment->id,
                    'worker_id' => $worker->id,
                    'business_id' => $business->id,
                    'type' => 'shift_escrow'
                ],
                'description' => "Shift #{$shift->id} - {$worker->name} at {$business->name}"
            ]);

            // Create shift payment record
            $shiftPayment = ShiftPayment::create([
                'shift_id' => $shift->id,
                'assignment_id' => $assignment->id,
                'worker_id' => $worker->id,
                'business_id' => $business->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount_cents' => $escrowCalculation['total_cents'],
                'currency' => $shift->currency ?? 'usd',
                'status' => 'PENDING_CAPTURE',
                'worker_pay_cents' => $escrowCalculation['worker_pay_cents'],
                'platform_fee_cents' => $escrowCalculation['platform_fee_cents'],
                'tax_cents' => $escrowCalculation['tax_cents'],
                'contingency_buffer_cents' => $escrowCalculation['contingency_buffer_cents'],
                'exchange_rate' => $escrowCalculation['exchange_rate'] ?? null,
                'rate_locked_at' => now(),
            ]);

            // Create escrow record
            EscrowRecord::create([
                'shift_payment_id' => $shiftPayment->id,
                'business_id' => $business->id,
                'worker_id' => $worker->id,
                'amount_cents' => $escrowCalculation['total_cents'],
                'currency' => $shift->currency ?? 'usd',
                'status' => 'PENDING',
                'stripe_transfer_id' => null,
                'captured_at' => null,
                'expires_at' => $shift->start_datetime->addHours(48), // 48-hour authorization
                'metadata' => [
                    'shift_id' => $shift->id,
                    'assignment_id' => $assignment->id,
                    'calculation_breakdown' => $escrowCalculation
                ]
            ]);

            DB::commit();

            Log::info('Escrow captured successfully', [
                'shift_id' => $shift->id,
                'worker_id' => $worker->id,
                'amount' => $escrowCalculation['total_cents'] / 100,
                'payment_intent_id' => $paymentIntent->id
            ]);

            return $shiftPayment;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to capture escrow', [
                'shift_id' => $assignment->shift_id,
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Confirm payment intent and transfer funds to escrow account
     */
    public function confirmEscrowCapture(ShiftPayment $payment): bool
    {
        try {
            if (!$payment->payment_intent_id) {
                Log::error('No payment intent ID for escrow confirmation', ['payment_id' => $payment->id]);
                return false;
            }

            // Confirm the payment intent (captures funds from customer)
            $confirmedIntent = $this->stripe->paymentIntents->confirm($payment->payment_intent_id);

            if ($confirmedIntent->status !== 'succeeded') {
                Log::error('Payment intent not succeeded', [
                    'payment_id' => $payment->id,
                    'intent_status' => $confirmedIntent->status
                ]);
                return false;
            }

            // Transfer funds to master escrow account
            $transfer = $this->stripe->transfers->create([
                'amount' => $payment->amount_cents,
                'currency' => $payment->currency,
                'destination' => $this->masterEscrowAccountId,
                'source_transaction' => $confirmedIntent->charges->data[0]->id,
                'metadata' => [
                    'shift_payment_id' => $payment->id,
                    'type' => 'escrow_transfer'
                ]
            ]);

            // Update records
            $payment->update([
                'status' => 'HELD',
                'captured_at' => now(),
                'stripe_transfer_id' => $transfer->id
            ]);

            $payment->escrowRecord->update([
                'status' => 'HELD',
                'stripe_transfer_id' => $transfer->id,
                'captured_at' => now()
            ]);

            Log::info('Escrow funds transferred successfully', [
                'payment_id' => $payment->id,
                'transfer_id' => $transfer->id,
                'amount' => $payment->amount_cents / 100
            ]);

            return true;

        } catch (ApiErrorException $e) {
            Log::error('Stripe error during escrow confirmation', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Release escrow funds to worker and platform
     * SL-007: Settlement Processing
     */
    public function releaseEscrow(ShiftPayment $payment, array $settlementDetails): bool
    {
        try {
            DB::beginTransaction();

            $worker = $payment->worker;
            $business = $payment->business;

            // Calculate final settlement amounts
            $settlement = $this->calculateFinalSettlement($payment, $settlementDetails);

            // Transfer to worker
            if ($settlement['worker_payout_cents'] > 0) {
                $workerTransfer = $this->stripe->transfers->create([
                    'amount' => $settlement['worker_payout_cents'],
                    'currency' => $payment->currency,
                    'destination' => $worker->stripe_account_id,
                    'metadata' => [
                        'shift_payment_id' => $payment->id,
                        'type' => 'worker_payout',
                        'net_hours' => $settlement['net_hours']
                    ]
                ]);

                // Create transaction record for worker
                Transaction::create([
                    'user_id' => $worker->id,
                    'type' => 'EARNING',
                    'amount_cents' => $settlement['worker_payout_cents'],
                    'currency' => $payment->currency,
                    'status' => 'COMPLETED',
                    'description' => "Shift #{$payment->shift_id} earnings",
                    'stripe_transfer_id' => $workerTransfer->id,
                    'shift_id' => $payment->shift_id,
                    'payment_id' => $payment->id
                ]);
            }

            // Refund excess to business (if any)
            if ($settlement['refund_to_business_cents'] > 0) {
                $refund = $this->stripe->refunds->create([
                    'payment_intent' => $payment->payment_intent_id,
                    'amount' => $settlement['refund_to_business_cents'],
                    'metadata' => [
                        'shift_payment_id' => $payment->id,
                        'type' => 'excess_refund'
                    ]
                ]);

                Transaction::create([
                    'user_id' => $business->id,
                    'type' => 'REFUND',
                    'amount_cents' => $settlement['refund_to_business_cents'],
                    'currency' => $payment->currency,
                    'status' => 'COMPLETED',
                    'description' => "Shift #{$payment->shift_id} excess refund",
                    'stripe_refund_id' => $refund->id,
                    'shift_id' => $payment->shift_id
                ]);
            }

            // Update payment record
            $payment->update([
                'status' => 'RELEASED',
                'released_at' => now(),
                'final_worker_payout_cents' => $settlement['worker_payout_cents'],
                'final_platform_fee_cents' => $settlement['platform_fee_cents'],
                'actual_hours_worked' => $settlement['actual_hours'],
                'net_hours_worked' => $settlement['net_hours']
            ]);

            // Update escrow record
            $payment->escrowRecord->update([
                'status' => 'RELEASED',
                'released_at' => now()
            ]);

            DB::commit();

            Log::info('Escrow released successfully', [
                'payment_id' => $payment->id,
                'worker_payout' => $settlement['worker_payout_cents'] / 100,
                'business_refund' => $settlement['refund_to_business_cents'] / 100
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to release escrow', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Handle escrow refund for cancelled shifts
     * SL-009: Shift Cancellation
     */
    public function refundEscrow(ShiftPayment $payment, array $penaltyCalculation): bool
    {
        try {
            $business = $payment->business;
            $worker = $payment->worker;

            // Calculate refund amount
            $refundAmountCents = $payment->amount_cents - $penaltyCalculation['penalty_cents'];

            if ($refundAmountCents > 0) {
                // Process refund to business
                $refund = $this->stripe->refunds->create([
                    'payment_intent' => $payment->payment_intent_id,
                    'amount' => $refundAmountCents,
                    'metadata' => [
                        'shift_payment_id' => $payment->id,
                        'type' => 'cancellation_refund',
                        'penalty_cents' => $penaltyCalculation['penalty_cents']
                    ]
                ]);

                // Create refund transaction
                Transaction::create([
                    'user_id' => $business->id,
                    'type' => 'REFUND',
                    'amount_cents' => $refundAmountCents,
                    'currency' => $payment->currency,
                    'status' => 'COMPLETED',
                    'description' => "Shift #{$payment->shift_id} cancellation refund",
                    'stripe_refund_id' => $refund->id,
                    'shift_id' => $payment->shift_id
                ]);
            }

            // Transfer worker penalty portion to worker (if any)
            if ($penaltyCalculation['worker_compensation_cents'] > 0) {
                $workerTransfer = $this->stripe->transfers->create([
                    'amount' => $penaltyCalculation['worker_compensation_cents'],
                    'currency' => $payment->currency,
                    'destination' => $worker->stripe_account_id,
                    'metadata' => [
                        'shift_payment_id' => $payment->id,
                        'type' => 'cancellation_compensation'
                    ]
                ]);

                Transaction::create([
                    'user_id' => $worker->id,
                    'type' => 'COMPENSATION',
                    'amount_cents' => $penaltyCalculation['worker_compensation_cents'],
                    'currency' => $payment->currency,
                    'status' => 'COMPLETED',
                    'description' => "Shift #{$payment->shift_id} cancellation compensation",
                    'stripe_transfer_id' => $workerTransfer->id,
                    'shift_id' => $payment->shift_id
                ]);
            }

            // Update payment status
            $payment->update([
                'status' => 'REFUNDED',
                'refunded_at' => now(),
                'refund_amount_cents' => $refundAmountCents,
                'penalty_cents' => $penaltyCalculation['penalty_cents']
            ]);

            // Update escrow record
            $payment->escrowRecord->update([
                'status' => 'REFUNDED',
                'refunded_at' => now()
            ]);

            Log::info('Escrow refund processed', [
                'payment_id' => $payment->id,
                'refund_amount' => $refundAmountCents / 100,
                'worker_compensation' => $penaltyCalculation['worker_compensation_cents'] / 100
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to refund escrow', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Calculate escrow requirement for a shift
     */
    private function calculateEscrowRequirement(Shift $shift): array
    {
        // Base worker pay
        $finalRate = $shift->final_rate;
        if (is_object($finalRate) && method_exists($finalRate, 'getAmount')) {
            $finalRate = ((float) $finalRate->getAmount()) / 100;
        }
        $workerPayCents = $finalRate * $shift->duration_hours * 100;

        // Add premiums
        $holidayPremiumCents = $workerPayCents * ($shift->is_public_holiday ? 0.50 : 0);
        $nightPremiumCents = $workerPayCents * ($shift->is_night_shift ? 0.15 : 0);
        $weekendPremiumCents = $workerPayCents * ($shift->is_weekend ? 0.10 : 0);

        $totalWorkerPayCents = $workerPayCents + $holidayPremiumCents + $nightPremiumCents + $weekendPremiumCents;

        // Platform fee (35% of worker pay)
        $platformFeeCents = $totalWorkerPayCents * 0.35;

        // Taxes (VAT/GST)
        $taxRate = config('platform.tax_rate', 0.18); // 18% default
        $taxCents = ($totalWorkerPayCents + $platformFeeCents) * $taxRate;

        // Contingency buffer (5%)
        $contingencyBufferCents = ($totalWorkerPayCents + $platformFeeCents + $taxCents) * 0.05;

        // Total escrow amount
        $totalCents = $totalWorkerPayCents + $platformFeeCents + $taxCents + $contingencyBufferCents;

        return [
            'worker_pay_cents' => $totalWorkerPayCents,
            'platform_fee_cents' => $platformFeeCents,
            'tax_cents' => $taxCents,
            'contingency_buffer_cents' => $contingencyBufferCents,
            'total_cents' => $totalCents,
            'exchange_rate' => $this->getExchangeRate($shift)
        ];
    }

    /**
     * Calculate final settlement amounts
     */
    private function calculateFinalSettlement(ShiftPayment $payment, array $details): array
    {
        $hourlyRateCents = $payment->worker_pay_cents / $payment->shift->duration_hours;

        // Calculate actual pay based on verified hours
        $actualWorkerPayCents = $hourlyRateCents * $details['verified_hours'];

        // Add overtime premium if applicable
        $overtimePremiumCents = 0;
        if ($details['overtime_hours'] > 0) {
            $overtimeRate = 1.5; // Default overtime multiplier
            $overtimePremiumCents = $hourlyRateCents * $details['overtime_hours'] * ($overtimeRate - 1);
        }

        $totalWorkerPayCents = $actualWorkerPayCents + $overtimePremiumCents;

        // Recalculate platform fee based on actual pay
        $platformFeeCents = $totalWorkerPayCents * 0.35;

        // Calculate refund to business
        $totalChargedCents = $totalWorkerPayCents + $platformFeeCents;
        $refundToBusinessCents = max(0, $payment->amount_cents - $totalChargedCents);

        return [
            'worker_payout_cents' => $totalWorkerPayCents,
            'platform_fee_cents' => $platformFeeCents,
            'overtime_premium_cents' => $overtimePremiumCents,
            'refund_to_business_cents' => $refundToBusinessCents,
            'actual_hours' => $details['verified_hours'],
            'net_hours' => $details['net_hours'] ?? $details['verified_hours'],
            'overtime_hours' => $details['overtime_hours'] ?? 0
        ];
    }

    /**
     * Get current exchange rate for shift currency conversion
     */
    private function getExchangeRate(Shift $shift): ?float
    {
        // In production, this would call a currency API
        // For now, return 1.0 for same currency transactions
        return 1.0;
    }

    /**
     * Reconcile escrow balances (run daily)
     */
    public function reconcileEscrowBalances(): array
    {
        $reconciliation = [];

        try {
            // Get Stripe escrow balance
            $stripeBalance = $this->stripe->balance->retrieve([
                'stripe_account' => $this->masterEscrowAccountId
            ]);

            // Sum of all held escrow records
            $expectedEscrow = EscrowRecord::where('status', 'HELD')->sum('amount_cents');

            $reconciliation = [
                'stripe_balance_cents' => $stripeBalance->available[0]->amount ?? 0,
                'expected_escrow_cents' => $expectedEscrow,
                'difference_cents' => ($stripeBalance->available[0]->amount ?? 0) - $expectedEscrow,
                'reconciled_at' => now(),
                'status' => 'RECONCILED'
            ];

            if (abs($reconciliation['difference_cents']) > 100) { // More than $1 difference
                $reconciliation['status'] = 'DISCREPENCY';
                Log::warning('Escrow reconciliation discrepancy detected', $reconciliation);
            }

            Log::info('Escrow reconciliation completed', $reconciliation);

        } catch (\Exception $e) {
            Log::error('Escrow reconciliation failed', ['error' => $e->getMessage()]);
            $reconciliation['status'] = 'FAILED';
        }

        return $reconciliation;
    }
}