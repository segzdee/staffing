<?php

namespace App\Services;

use App\Models\InstapayRequest;
use App\Models\InstapaySettings;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * InstaPayService
 *
 * FIN-004: InstaPay (Same-Day Payout) Feature
 *
 * Handles all InstaPay functionality including:
 * - Eligibility checking
 * - Balance calculations
 * - Request creation and processing
 * - Stripe Connect instant payouts
 * - Fee calculations
 * - Daily limits
 */
class InstaPayService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient([
            'api_key' => config('services.stripe.secret'),
            'stripe_version' => '2023-10-16',
        ]);
    }

    /**
     * Check if a user is eligible for InstaPay.
     */
    public function isEligible(User $user): bool
    {
        // Global feature check
        if (! config('instapay.enabled', true)) {
            return false;
        }

        return $user->isEligibleForInstapay();
    }

    /**
     * Get detailed eligibility status with reasons.
     *
     * @return array{eligible: bool, reasons: array}
     */
    public function getEligibilityStatus(User $user): array
    {
        $reasons = [];

        if (! config('instapay.enabled', true)) {
            $reasons[] = 'InstaPay is currently disabled.';
        }

        if (! $user->isWorker()) {
            $reasons[] = 'Only workers can use InstaPay.';
        }

        $minShifts = config('instapay.eligibility.min_completed_shifts', 3);
        if ($user->total_shifts_completed < $minShifts) {
            $remaining = $minShifts - $user->total_shifts_completed;
            $reasons[] = "Complete {$remaining} more shift(s) to unlock InstaPay.";
        }

        $minReliability = config('instapay.eligibility.min_reliability_score', 70);
        if ($user->reliability_score < $minReliability) {
            $reasons[] = "Reliability score must be at least {$minReliability}% (current: {$user->reliability_score}%).";
        }

        if (config('instapay.eligibility.require_verified', true) && ! $user->is_verified_worker) {
            $reasons[] = 'Account must be verified.';
        }

        if (config('instapay.eligibility.require_payment_method', true) && ! $user->hasValidPayoutMethod()) {
            $reasons[] = 'Complete Stripe Connect setup to receive payouts.';
        }

        return [
            'eligible' => empty($reasons),
            'reasons' => $reasons,
        ];
    }

    /**
     * Get available balance for instant payout.
     * Returns the sum of completed shift payments that are ready for payout.
     */
    public function getAvailableBalance(User $user): float
    {
        return ShiftPayment::query()
            ->where('worker_id', $user->id)
            ->readyForPayout()
            ->sum(DB::raw('CAST(amount_net AS DECIMAL(10,2)) / 100'));
    }

    /**
     * Get earnings awaiting payout (not yet ready for instant payout).
     */
    public function getEarningsAwaitingPayout(User $user): Collection
    {
        return ShiftPayment::query()
            ->where('worker_id', $user->id)
            ->where('status', 'released')
            ->whereNull('payout_completed_at')
            ->with(['assignment.shift'])
            ->orderBy('released_at', 'desc')
            ->get();
    }

    /**
     * Get pending (in escrow) earnings.
     */
    public function getPendingEarnings(User $user): Collection
    {
        return ShiftPayment::query()
            ->where('worker_id', $user->id)
            ->where('status', 'in_escrow')
            ->with(['assignment.shift'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Request instant payout.
     *
     * @throws \Exception
     */
    public function requestInstaPay(User $user, float $amount, ?string $method = null): InstapayRequest
    {
        // Validate eligibility
        if (! $this->isEligible($user)) {
            throw new \Exception('You are not eligible for InstaPay.');
        }

        // Get user settings
        $settings = $user->getOrCreateInstapaySettings();
        $method = $method ?? $settings->preferred_method;

        // Validate amount
        $this->validateAmount($user, $amount, $settings);

        // Validate method
        $this->validatePayoutMethod($user, $method);

        // Check fraud prevention limits
        $this->checkFraudLimits($user);

        // Calculate fees
        $fees = $this->calculateFees($amount);

        return DB::transaction(function () use ($user, $amount, $method, $fees) {
            // Create the request
            $request = InstapayRequest::create([
                'user_id' => $user->id,
                'gross_amount' => $amount,
                'instapay_fee' => $fees['instapay_fee'],
                'platform_fee' => $fees['platform_fee'],
                'net_amount' => $fees['net_amount'],
                'status' => InstapayRequest::STATUS_PENDING,
                'payout_method' => $method,
                'requested_at' => now(),
                'currency' => config('instapay.default_currency', 'EUR'),
                'metadata' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            Log::info('InstaPay request created', [
                'request_id' => $request->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'method' => $method,
            ]);

            return $request;
        });
    }

    /**
     * Process an InstaPay request.
     */
    public function processRequest(InstapayRequest $request): void
    {
        if (! $request->isPending()) {
            throw new \Exception('Request is not in pending status.');
        }

        $request->markAsProcessing();

        try {
            $result = match ($request->payout_method) {
                InstapayRequest::METHOD_STRIPE => $this->processViaStripe($request),
                InstapayRequest::METHOD_PAYPAL => $this->processViaPayPal($request),
                InstapayRequest::METHOD_BANK_TRANSFER => $this->processViaBankTransfer($request),
                default => throw new \Exception("Unknown payout method: {$request->payout_method}"),
            };

            $request->markAsCompleted($result['reference']);

            Log::info('InstaPay request completed', [
                'request_id' => $request->id,
                'reference' => $result['reference'],
            ]);
        } catch (\Exception $e) {
            $request->markAsFailed($e->getMessage());

            Log::error('InstaPay request failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process payout via Stripe Connect instant payout.
     *
     * @return array{reference: string, status: string}
     */
    public function processViaStripe(InstapayRequest $request): array
    {
        $user = $request->user;

        if (! $user->stripe_connect_id) {
            throw new \Exception('Stripe Connect account not found.');
        }

        try {
            // Amount in cents
            $amountInCents = (int) round($request->net_amount * 100);

            // Create instant payout
            $payout = $this->stripe->payouts->create([
                'amount' => $amountInCents,
                'currency' => strtolower($request->currency),
                'method' => 'instant',
                'metadata' => [
                    'instapay_request_id' => $request->id,
                    'user_id' => $user->id,
                    'platform' => 'overtimestaff',
                ],
            ], ['stripe_account' => $user->stripe_connect_id]);

            return [
                'reference' => $payout->id,
                'status' => $payout->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe instant payout failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode(),
            ]);

            // Check if instant payout is not supported, fall back to standard
            if ($e->getStripeCode() === 'instant_payouts_unsupported') {
                return $this->processViaStripeStandard($request);
            }

            throw new \Exception('Stripe payout failed: '.$e->getMessage());
        }
    }

    /**
     * Process standard (non-instant) Stripe payout.
     *
     * @return array{reference: string, status: string}
     */
    protected function processViaStripeStandard(InstapayRequest $request): array
    {
        $user = $request->user;
        $amountInCents = (int) round($request->net_amount * 100);

        $payout = $this->stripe->payouts->create([
            'amount' => $amountInCents,
            'currency' => strtolower($request->currency),
            'method' => 'standard',
            'metadata' => [
                'instapay_request_id' => $request->id,
                'user_id' => $user->id,
                'platform' => 'overtimestaff',
                'fallback' => true,
            ],
        ], ['stripe_account' => $user->stripe_connect_id]);

        return [
            'reference' => $payout->id,
            'status' => $payout->status,
        ];
    }

    /**
     * Process payout via PayPal.
     *
     * @return array{reference: string, status: string}
     */
    public function processViaPayPal(InstapayRequest $request): array
    {
        // PayPal implementation placeholder
        // In production, integrate with PayPal Payouts API

        throw new \Exception('PayPal payouts are not yet implemented.');
    }

    /**
     * Process payout via bank transfer.
     *
     * @return array{reference: string, status: string}
     */
    public function processViaBankTransfer(InstapayRequest $request): array
    {
        $user = $request->user;

        if (! $user->stripe_connect_id) {
            throw new \Exception('Bank transfer setup not found.');
        }

        try {
            $amountInCents = (int) round($request->net_amount * 100);

            // Standard bank transfer via Stripe
            $payout = $this->stripe->payouts->create([
                'amount' => $amountInCents,
                'currency' => strtolower($request->currency),
                'method' => 'standard',
                'metadata' => [
                    'instapay_request_id' => $request->id,
                    'user_id' => $user->id,
                    'platform' => 'overtimestaff',
                    'type' => 'bank_transfer',
                ],
            ], ['stripe_account' => $user->stripe_connect_id]);

            return [
                'reference' => $payout->id,
                'status' => $payout->status,
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Bank transfer failed: '.$e->getMessage());
        }
    }

    /**
     * Calculate fees for an InstaPay request.
     *
     * @return array{instapay_fee: float, platform_fee: float, net_amount: float}
     */
    public function calculateFees(float $amount): array
    {
        $feePercent = config('instapay.fee_percent', 1.5);
        $feeMinimum = config('instapay.fee_minimum', 0.50);
        $feeMaximum = config('instapay.fee_maximum', 10.00);

        // Calculate percentage fee
        $percentageFee = $amount * ($feePercent / 100);

        // Apply min/max bounds
        $instapayFee = max($feeMinimum, min($feeMaximum, $percentageFee));

        // Round to 2 decimal places
        $instapayFee = round($instapayFee, 2);

        // Platform fee (already deducted in ShiftPayment)
        $platformFee = 0;

        // Net amount after InstaPay fee
        $netAmount = round($amount - $instapayFee, 2);

        return [
            'instapay_fee' => $instapayFee,
            'platform_fee' => $platformFee,
            'net_amount' => $netAmount,
        ];
    }

    /**
     * Calculate fee for a given amount (simple version).
     */
    public function calculateFee(float $amount): float
    {
        return $this->calculateFees($amount)['instapay_fee'];
    }

    /**
     * Cancel an InstaPay request.
     */
    public function cancelRequest(InstapayRequest $request): void
    {
        if (! $request->canBeCancelled()) {
            throw new \Exception('This request cannot be cancelled.');
        }

        $request->markAsCancelled();

        Log::info('InstaPay request cancelled', [
            'request_id' => $request->id,
            'user_id' => $request->user_id,
        ]);
    }

    /**
     * Get daily limit for a user.
     */
    public function getDailyLimit(User $user): float
    {
        $settings = $user->instapaySettings;

        return $settings
            ? $settings->getEffectiveDailyLimit()
            : config('instapay.daily_limit', 500.00);
    }

    /**
     * Get remaining daily limit for a user.
     */
    public function getRemainingDailyLimit(User $user): float
    {
        return $user->getRemainingInstapayLimit();
    }

    /**
     * Process all pending InstaPay requests.
     *
     * @return int Number of requests processed
     */
    public function processAllPendingRequests(): int
    {
        $batchSize = config('instapay.batch.size', 50);

        $requests = InstapayRequest::query()
            ->pending()
            ->orderBy('requested_at')
            ->limit($batchSize)
            ->get();

        $processed = 0;

        foreach ($requests as $request) {
            try {
                $this->processRequest($request);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to process InstaPay request in batch', [
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('InstaPay batch processing completed', [
            'total' => $requests->count(),
            'processed' => $processed,
            'failed' => $requests->count() - $processed,
        ]);

        return $processed;
    }

    /**
     * Get InstaPay request history for a user.
     */
    public function getRequestHistory(User $user, int $limit = 20): Collection
    {
        return $user->instapayRequests()
            ->orderBy('requested_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get InstaPay statistics for a user.
     *
     * @return array{total_requests: int, total_amount: float, completed_requests: int, ...}
     */
    public function getUserStatistics(User $user): array
    {
        $requests = $user->instapayRequests();

        return [
            'total_requests' => $requests->count(),
            'total_amount' => $requests->completed()->sum('gross_amount'),
            'total_fees_paid' => $requests->completed()->sum('instapay_fee'),
            'completed_requests' => $requests->completed()->count(),
            'pending_requests' => $requests->pending()->count(),
            'failed_requests' => $requests->failed()->count(),
            'today_total' => $user->getTodayInstapayTotal(),
            'remaining_limit' => $user->getRemainingInstapayLimit(),
        ];
    }

    /**
     * Validate payout amount.
     *
     * @throws \Exception
     */
    protected function validateAmount(User $user, float $amount, InstapaySettings $settings): void
    {
        $minimumAmount = $settings->getEffectiveMinimumAmount();
        $maximumSingle = config('instapay.maximum_single_request', 500.00);
        $remainingLimit = $user->getRemainingInstapayLimit();
        $availableBalance = $this->getAvailableBalance($user);

        if ($amount < $minimumAmount) {
            throw new \Exception("Minimum amount is {$minimumAmount}.");
        }

        if ($amount > $maximumSingle) {
            throw new \Exception("Maximum single request is {$maximumSingle}.");
        }

        if ($amount > $remainingLimit) {
            throw new \Exception("Amount exceeds daily limit. Remaining: {$remainingLimit}.");
        }

        if ($amount > $availableBalance) {
            throw new \Exception("Insufficient balance. Available: {$availableBalance}.");
        }
    }

    /**
     * Validate payout method.
     *
     * @throws \Exception
     */
    protected function validatePayoutMethod(User $user, string $method): void
    {
        $methodConfig = config("instapay.payout_methods.{$method}");

        if (! $methodConfig || ! $methodConfig['enabled']) {
            throw new \Exception("Payout method '{$method}' is not available.");
        }

        if ($method === InstapayRequest::METHOD_STRIPE && ! $user->canReceiveInstantPayouts()) {
            throw new \Exception('Stripe Connect setup is incomplete.');
        }
    }

    /**
     * Check fraud prevention limits.
     *
     * @throws \Exception
     */
    protected function checkFraudLimits(User $user): void
    {
        $maxDailyRequests = config('instapay.fraud_prevention.max_daily_requests', 5);
        $cooldownMinutes = config('instapay.fraud_prevention.cooldown_minutes', 30);

        // Check max daily requests
        $todayRequests = $user->instapayRequests()
            ->today()
            ->count();

        if ($todayRequests >= $maxDailyRequests) {
            throw new \Exception("Maximum {$maxDailyRequests} requests per day.");
        }

        // Check cooldown period
        $lastRequest = $user->instapayRequests()
            ->latest('requested_at')
            ->first();

        if ($lastRequest && $lastRequest->requested_at->diffInMinutes(now()) < $cooldownMinutes) {
            $remaining = $cooldownMinutes - $lastRequest->requested_at->diffInMinutes(now());
            throw new \Exception("Please wait {$remaining} minutes before next request.");
        }
    }

    /**
     * Auto-request InstaPay after shift completion (if enabled).
     */
    public function autoRequestAfterShift(ShiftAssignment $assignment): ?InstapayRequest
    {
        if (! config('instapay.auto_request.enabled', true)) {
            return null;
        }

        $user = $assignment->worker;
        $settings = $user->instapaySettings;

        // Check if auto-request is enabled for this user
        if (! $settings || ! $settings->auto_request) {
            return null;
        }

        // Check eligibility
        if (! $this->isEligible($user)) {
            return null;
        }

        // Get payment for this assignment
        $payment = $assignment->payment;
        if (! $payment || ! $payment->isReadyForPayout()) {
            return null;
        }

        // Get payment amount
        $amount = $payment->amount_net->getAmount() / 100; // Convert from cents

        // Check minimum amount
        $minAmount = config('instapay.auto_request.min_amount', 20.00);
        if ($amount < $minAmount) {
            return null;
        }

        try {
            return $this->requestInstaPay($user, $amount, $settings->preferred_method);
        } catch (\Exception $e) {
            Log::warning('Auto InstaPay request failed', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
