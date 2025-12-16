<?php

namespace App\Services;

use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\User;
use App\Models\Transaction;
use App\Models\PayoutBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * Settlement Service
 * 
 * Processes batch settlements for worker earnings
 * Handles daily/weekly payout cycles and InstaPay
 * 
 * FIN-003: Payment Settlement Engine
 * FIN-005: Weekly Payout Cycle
 * FIN-004: InstaPay System
 */
class SettlementService
{
    protected $stripe;
    protected $escrowService;

    public function __construct(EscrowService $escrowService)
    {
        $stripeSecret = config('services.stripe.secret');
        $this->stripe = $stripeSecret ? new StripeClient($stripeSecret) : null;
        $this->escrowService = $escrowService;
    }

    /**
     * Process daily settlement batch
     * SL-007: Settlement Processing
     */
    public function processDailySettlement(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'total_amount_cents' => 0,
            'errors' => []
        ];

        try {
            // Get completed shifts ready for settlement
            $readyPayments = ShiftPayment::with(['shift', 'worker', 'business'])
                ->where('status', 'HELD')
                ->whereHas('shift', function ($query) {
                    $query->where('status', 'COMPLETED')
                          ->where('verified_at', '<=', now()->subHours(72)); // Auto-approve after 72 hours
                })
                ->where('created_at', '<=', now()->subHours(1)) // Allow 1 hour for verification
                ->get();

            DB::transaction(function () use ($readyPayments, &$results) {
                foreach ($readyPayments as $payment) {
                    try {
                        $settlementResult = $this->settlePayment($payment);
                        
                        if ($settlementResult['success']) {
                            $results['processed']++;
                            $results['total_amount_cents'] += $settlementResult['amount_cents'];
                        } else {
                            $results['failed']++;
                            $results['errors'][] = [
                                'payment_id' => $payment->id,
                                'error' => $settlementResult['error']
                            ];
                        }
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage()
                        ];
                        Log::error('Settlement failed for payment', [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            // Create settlement batch record
            PayoutBatch::create([
                'type' => 'DAILY_SETTLEMENT',
                'processed_count' => $results['processed'],
                'failed_count' => $results['failed'],
                'total_amount_cents' => $results['total_amount_cents'],
                'processed_at' => now(),
                'status' => $results['failed'] > 0 ? 'PARTIAL' : 'COMPLETED',
                'error_summary' => $results['errors']
            ]);

            Log::info('Daily settlement completed', $results);

        } catch (\Exception $e) {
            Log::error('Daily settlement batch failed', [
                'error' => $e->getMessage(),
                'results' => $results
            ]);
        }

        return $results;
    }

    /**
     * Process weekly payout batch
     * FIN-005: Weekly Payout Cycle
     */
    public function processWeeklyPayouts(): array
    {
        $results = [
            'workers_processed' => 0,
            'total_payout_cents' => 0,
            'failed_workers' => 0,
            'errors' => []
        ];

        try {
            // Get workers with positive available balances
            $workersWithBalance = User::where('type', 'worker')
                ->whereHas('transactions', function ($query) {
                    $query->whereIn('type', [
                        Transaction::TYPE_EARNING,
                        Transaction::TYPE_REFUND,
                        Transaction::TYPE_COMPENSATION
                    ])
                    ->where('status', Transaction::STATUS_COMPLETED);
                })
                ->get()
                ->filter(function ($worker) {
                    return Transaction::calculateBalance($worker) > 1000; // Minimum $10
                });

            DB::transaction(function () use ($workersWithBalance, &$results) {
                foreach ($workersWithBalance as $worker) {
                    try {
                        $payoutResult = $this->processWorkerPayout($worker);
                        
                        if ($payoutResult['success']) {
                            $results['workers_processed']++;
                            $results['total_payout_cents'] += $payoutResult['amount_cents'];
                        } else {
                            $results['failed_workers']++;
                            $results['errors'][] = [
                                'worker_id' => $worker->id,
                                'error' => $payoutResult['error']
                            ];
                        }
                    } catch (\Exception $e) {
                        $results['failed_workers']++;
                        $results['errors'][] = [
                            'worker_id' => $worker->id,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            });

            // Create weekly payout batch record
            PayoutBatch::create([
                'type' => 'WEEKLY_PAYOUT',
                'processed_count' => $results['workers_processed'],
                'failed_count' => $results['failed_workers'],
                'total_amount_cents' => $results['total_payout_cents'],
                'processed_at' => now(),
                'status' => $results['failed_workers'] > 0 ? 'PARTIAL' : 'COMPLETED',
                'error_summary' => $results['errors']
            ]);

            Log::info('Weekly payouts completed', $results);

        } catch (\Exception $e) {
            Log::error('Weekly payout batch failed', [
                'error' => $e->getMessage(),
                'results' => $results
            ]);
        }

        return $results;
    }

    /**
     * Process InstaPay request
     * FIN-004: InstaPay System
     */
    public function processInstaPay(User $worker, int $amountCents): array
    {
        try {
            // Validate eligibility
            $eligibility = $this->validateInstaPayEligibility($worker, $amountCents);
            if (!$eligibility['eligible']) {
                return [
                    'success' => false,
                    'error' => $eligibility['reason']
                ];
            }

            // Calculate fees
            $feeRate = $this->getInstaPayFeeRate($worker);
            $feeAmountCents = (int) ($amountCents * $feeRate);
            $netAmountCents = $amountCents - $feeAmountCents;

            // Create InstaPay transaction
            $instaPayTransaction = Transaction::create([
                'user_id' => $worker->id,
                'type' => Transaction::TYPE_PAYOUT,
                'amount_cents' => $amountCents,
                'currency' => 'usd',
                'status' => Transaction::STATUS_PROCESSING,
                'description' => 'InstaPay payout',
                'metadata' => [
                    'type' => 'instapay',
                    'fee_amount_cents' => $feeAmountCents,
                    'net_amount_cents' => $netAmountCents
                ]
            ]);

            // Create fee transaction
            if ($feeAmountCents > 0) {
                Transaction::create([
                    'user_id' => $worker->id,
                    'type' => Transaction::TYPE_INSTAPAY_FEE,
                    'amount_cents' => $feeAmountCents,
                    'currency' => 'usd',
                    'status' => Transaction::STATUS_COMPLETED,
                    'description' => 'InstaPay processing fee',
                    'related_transaction_id' => $instaPayTransaction->id
                ]);
            }

            // Process instant payout via Stripe
            $payout = $this->stripe->payouts->create([
                'amount' => $netAmountCents,
                'currency' => 'usd',
                'method' => 'instant',
                'destination' => $worker->stripe_account_id,
                'metadata' => [
                    'type' => 'instapay',
                    'transaction_id' => $instaPayTransaction->id
                ]
            ]);

            // Update transaction
            $instaPayTransaction->update([
                'stripe_transfer_id' => $payout->id,
                'status' => Transaction::STATUS_PROCESSING,
                'metadata' => array_merge($instaPayTransaction->metadata, [
                    'stripe_payout_id' => $payout->id,
                    'expected_arrival' => now()->addMinutes(30)->toIso8601String()
                ])
            ]);

            // Update worker balance
            $newBalance = Transaction::calculateBalance($worker);

            Log::info('InstaPay processed', [
                'worker_id' => $worker->id,
                'amount_cents' => $amountCents,
                'fee_cents' => $feeAmountCents,
                'net_cents' => $netAmountCents,
                'payout_id' => $payout->id
            ]);

            return [
                'success' => true,
                'amount_cents' => $amountCents,
                'fee_cents' => $feeAmountCents,
                'net_cents' => $netAmountCents,
                'payout_id' => $payout->id,
                'expected_arrival' => now()->addMinutes(30)->toIso8601String(),
                'new_balance_cents' => $newBalance
            ];

        } catch (ApiErrorException $e) {
            Log::error('InstaPay Stripe error', [
                'worker_id' => $worker->id,
                'amount_cents' => $amountCents,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Payment processing failed: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('InstaPay processing error', [
                'worker_id' => $worker->id,
                'amount_cents' => $amountCents,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Processing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Settle individual payment
     */
    private function settlePayment(ShiftPayment $payment): array
    {
        try {
            $shift = $payment->shift;
            $worker = $payment->worker;

            // Calculate final settlement
            $settlementDetails = $this->calculatePaymentSettlement($payment);

            // Release escrow
            $releaseResult = $this->escrowService->releaseEscrow($payment, $settlementDetails);

            if (!$releaseResult) {
                return [
                    'success' => false,
                    'error' => 'Failed to release escrow',
                    'amount_cents' => 0
                ];
            }

            // Create earnings transaction
            Transaction::createEarning(
                $worker,
                $shift,
                $settlementDetails['worker_payout_cents'],
                "Shift #{$shift->id} earnings ({$settlementDetails['actual_hours']}h)"
            );

            return [
                'success' => true,
                'amount_cents' => $settlementDetails['worker_payout_cents'],
                'worker_id' => $worker->id
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'amount_cents' => 0
            ];
        }
    }

    /**
     * Process payout for individual worker
     */
    private function processWorkerPayout(User $worker): array
    {
        try {
            $balanceCents = Transaction::calculateBalance($worker);

            if ($balanceCents < 1000) { // Minimum $10
                return [
                    'success' => false,
                    'error' => 'Insufficient balance for payout',
                    'amount_cents' => 0
                ];
            }

            // Check for pending transactions
            $pendingDebits = Transaction::where('user_id', $worker->id)
                ->whereIn('type', [Transaction::TYPE_PAYOUT, Transaction::TYPE_PENALTY])
                ->where('status', Transaction::STATUS_PENDING)
                ->sum('amount_cents');

            $payoutAmountCents = $balanceCents - $pendingDebits;

            if ($payoutAmountCents < 1000) {
                return [
                    'success' => false,
                    'error' => 'Insufficient available balance after pending debits',
                    'amount_cents' => 0
                ];
            }

            // Create payout transaction
            $payoutTransaction = Transaction::create([
                'user_id' => $worker->id,
                'type' => Transaction::TYPE_PAYOUT,
                'amount_cents' => $payoutAmountCents,
                'currency' => 'usd',
                'status' => Transaction::STATUS_PROCESSING,
                'description' => 'Weekly payout',
                'processed_at' => now()
            ]);

            // Process payout via Stripe
            $payout = $this->stripe->payouts->create([
                'amount' => $payoutAmountCents,
                'currency' => 'usd',
                'destination' => $worker->stripe_account_id,
                'metadata' => [
                    'type' => 'weekly_payout',
                    'transaction_id' => $payoutTransaction->id
                ]
            ]);

            // Update transaction
            $payoutTransaction->update([
                'stripe_transfer_id' => $payout->id,
                'status' => Transaction::STATUS_PROCESSING
            ]);

            // Calculate expected arrival based on region
            $expectedArrival = $this->calculateExpectedArrival('usd');

            return [
                'success' => true,
                'amount_cents' => $payoutAmountCents,
                'payout_id' => $payout->id,
                'expected_arrival' => $expectedArrival,
                'new_balance_cents' => 0
            ];

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => 'Payment processing failed: ' . $e->getMessage(),
                'amount_cents' => 0
            ];
        }
    }

    /**
     * Calculate payment settlement details
     */
    private function calculatePaymentSettlement(ShiftPayment $payment): array
    {
        $shift = $payment->shift;

        // Get verified hours (business approved or auto-approved)
        $verifiedHours = $shift->verified_hours ?? $shift->actual_hours ?? $shift->duration_hours;
        $netHours = $verifiedHours; // Would subtract break hours if tracked

        // Base hourly rate
        $hourlyRateCents = $payment->worker_pay_cents / $shift->duration_hours;

        // Calculate overtime
        $overtimeHours = 0;
        $overtimePremiumCents = 0;

        if ($verifiedHours > 8) { // Overtime after 8 hours (example)
            $overtimeHours = $verifiedHours - 8;
            $overtimeRate = 1.5; // 1.5x overtime rate
            $overtimePremiumCents = $hourlyRateCents * $overtimeHours * ($overtimeRate - 1);
        }

        // Total worker payout
        $workerPayoutCents = ($hourlyRateCents * $verifiedHours) + $overtimePremiumCents;

        // Recalculate platform fee (35% of actual pay)
        $platformFeeCents = $workerPayoutCents * 0.35;

        return [
            'worker_payout_cents' => $workerPayoutCents,
            'platform_fee_cents' => $platformFeeCents,
            'overtime_premium_cents' => $overtimePremiumCents,
            'actual_hours' => $verifiedHours,
            'net_hours' => $netHours,
            'overtime_hours' => $overtimeHours
        ];
    }

    /**
     * Validate InstaPay eligibility
     */
    private function validateInstaPayEligibility(User $worker, int $amountCents): array
    {
        // Check tier eligibility
        $tier = $worker->tier ?? 'bronze';
        if ($tier === 'bronze') {
            return [
                'eligible' => false,
                'reason' => 'InstaPay requires Silver tier or higher'
            ];
        }

        // Check minimum shifts completed
        $completedShifts = $worker->shiftAssignments()->where('status', 'completed')->count();
        if ($completedShifts < 5) {
            return [
                'eligible' => false,
                'reason' => 'Minimum 5 completed shifts required'
            ];
        }

        // Check minimum balance
        $balance = Transaction::calculateBalance($worker);
        if ($balance < $amountCents) {
            return [
                'eligible' => false,
                'reason' => 'Insufficient balance'
            ];
        }

        // Check daily limit
        $dailyLimit = $this->getInstaPayDailyLimit($tier);
        $todayPayouts = Transaction::where('user_id', $worker->id)
            ->where('type', Transaction::TYPE_PAYOUT)
            ->where('metadata->type', 'instapay')
            ->whereDate('created_at', today())
            ->sum('amount_cents');

        if (($todayPayouts + $amountCents) > $dailyLimit) {
            return [
                'eligible' => false,
                'reason' => "Daily limit of $" . ($dailyLimit / 100) . " exceeded"
            ];
        }

        return ['eligible' => true];
    }

    /**
     * Get InstaPay fee rate by tier
     */
    private function getInstaPayFeeRate(User $worker): float
    {
        $tier = $worker->tier ?? 'bronze';

        return match($tier) {
            'platinum' => 0.01, // 1%
            'gold' => 0.03, // 3%
            'silver' => 0.05, // 5%
            default => 0.05 // 5%
        };
    }

    /**
     * Get InstaPay daily limit by tier
     */
    private function getInstaPayDailyLimit(string $tier): int
    {
        return match($tier) {
            'platinum' => 100000, // $1,000
            'gold' => 50000, // $500
            'silver' => 20000, // $200
            default => 0 // Not available
        };
    }

    /**
     * Calculate expected payout arrival by currency
     */
    private function calculateExpectedArrival(string $currency): string
    {
        $businessDays = match($currency) {
            'usd' => 3, // ACH: 2-3 business days
            'gbp' => 1, // FPS: same day
            'eur' => 2, // SEPA: 1-2 business days
            'aud' => 2, // NPP: same day
            'cad' => 3, // EFT: 2-3 business days
            default => 3
        };

        return now()->addWeekdays($businessDays)->toIso8601String();
    }

    /**
     * Monitor and update payout statuses
     */
    public function updatePayoutStatuses(): array
    {
        $results = [
            'updated' => 0,
            'failed' => 0
        ];

        // Get processing payouts
        $processingPayouts = Transaction::where('type', Transaction::TYPE_PAYOUT)
            ->where('status', Transaction::STATUS_PROCESSING)
            ->whereNotNull('stripe_transfer_id')
            ->get();

        foreach ($processingPayouts as $transaction) {
            try {
                $payout = $this->stripe->payouts->retrieve($transaction->stripe_transfer_id);

                if ($payout->status === 'paid') {
                    $transaction->update([
                        'status' => Transaction::STATUS_COMPLETED,
                        'processed_at' => now()
                    ]);
                    $results['updated']++;
                } elseif ($payout->status === 'failed') {
                    $transaction->markAsFailed($payout->failure_message ?? 'Payout failed');
                    $results['failed']++;
                }

            } catch (\Exception $e) {
                Log::error('Failed to update payout status', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }
}