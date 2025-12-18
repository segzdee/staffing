<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\CrossBorderTransfer;
use App\Models\PaymentCorridor;
use App\Models\User;
use App\Support\IBANValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GLO-008: Cross-Border Payments Service
 *
 * Handles all cross-border payment routing, validation, and transfer operations.
 */
class CrossBorderPaymentService
{
    /**
     * Default source currency for the platform.
     */
    protected string $defaultSourceCurrency = 'USD';

    /**
     * Exchange rate provider (could be integrated with external API).
     */
    protected ?ExchangeRateService $exchangeRateService = null;

    public function __construct(?ExchangeRateService $exchangeRateService = null)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    // =========================================
    // Corridor Selection
    // =========================================

    /**
     * Get the best payment corridor for a transfer.
     *
     * Selection criteria:
     * 1. Lowest total fee
     * 2. Fastest delivery
     * 3. Active corridor
     */
    public function getBestCorridor(
        string $sourceCountry,
        string $destCountry,
        ?float $amount = null
    ): ?PaymentCorridor {
        $query = PaymentCorridor::query()
            ->active()
            ->fromCountry($sourceCountry)
            ->toCountry($destCountry);

        if ($amount !== null) {
            $query->withinAmountLimits($amount);
        }

        // Get all matching corridors
        $corridors = $query->get();

        if ($corridors->isEmpty()) {
            // Try to find a corridor via SWIFT as fallback
            return PaymentCorridor::query()
                ->active()
                ->fromCountry($sourceCountry)
                ->toCountry($destCountry)
                ->byMethod(PaymentCorridor::METHOD_SWIFT)
                ->first();
        }

        // Sort by total cost (for a reference amount) then by speed
        $referenceAmount = $amount ?? 1000.00;

        return $corridors->sortBy(function (PaymentCorridor $corridor) use ($referenceAmount) {
            $fee = $corridor->calculateFee($referenceAmount);

            // Weight: fee is primary, days is secondary
            return ($fee * 100) + $corridor->estimated_days_max;
        })->first();
    }

    /**
     * Get all available corridors for a route.
     *
     * @return \Illuminate\Database\Eloquent\Collection<PaymentCorridor>
     */
    public function getAvailableCorridors(
        string $sourceCountry,
        string $destCountry,
        ?float $amount = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = PaymentCorridor::query()
            ->active()
            ->fromCountry($sourceCountry)
            ->toCountry($destCountry);

        if ($amount !== null) {
            $query->withinAmountLimits($amount);
        }

        return $query->orderBy('fee_fixed')
            ->orderBy('estimated_days_min')
            ->get();
    }

    /**
     * Get corridor for a specific payment method.
     */
    public function getCorridorByMethod(
        string $sourceCountry,
        string $destCountry,
        string $paymentMethod
    ): ?PaymentCorridor {
        return PaymentCorridor::query()
            ->active()
            ->fromCountry($sourceCountry)
            ->toCountry($destCountry)
            ->byMethod($paymentMethod)
            ->first();
    }

    // =========================================
    // Fee Calculation
    // =========================================

    /**
     * Calculate fees for a transfer.
     *
     * @return array{
     *     fee_fixed: float,
     *     fee_percent: float,
     *     total_fee: float,
     *     source_amount: float,
     *     total_deduction: float,
     *     destination_amount: float,
     *     exchange_rate: float
     * }
     */
    public function calculateFees(PaymentCorridor $corridor, float $amount): array
    {
        $feeFixed = (float) $corridor->fee_fixed;
        $feePercent = $amount * ((float) $corridor->fee_percent / 100);
        $totalFee = round($feeFixed + $feePercent, 2);

        // Get exchange rate
        $exchangeRate = $this->getExchangeRate(
            $corridor->source_currency,
            $corridor->destination_currency
        );

        $destinationAmount = round($amount * $exchangeRate, 2);

        return [
            'fee_fixed' => $feeFixed,
            'fee_percent' => round($feePercent, 2),
            'total_fee' => $totalFee,
            'source_amount' => $amount,
            'total_deduction' => round($amount + $totalFee, 2),
            'destination_amount' => $destinationAmount,
            'exchange_rate' => $exchangeRate,
            'source_currency' => $corridor->source_currency,
            'destination_currency' => $corridor->destination_currency,
        ];
    }

    /**
     * Get a quote for a cross-border transfer.
     *
     * @return array{
     *     success: bool,
     *     corridor: ?PaymentCorridor,
     *     fees: ?array,
     *     delivery: ?array,
     *     error: ?string
     * }
     */
    public function getQuote(
        string $sourceCountry,
        string $destCountry,
        float $amount,
        ?string $preferredMethod = null
    ): array {
        // Get corridor
        $corridor = $preferredMethod
            ? $this->getCorridorByMethod($sourceCountry, $destCountry, $preferredMethod)
            : $this->getBestCorridor($sourceCountry, $destCountry, $amount);

        if (! $corridor) {
            return [
                'success' => false,
                'corridor' => null,
                'fees' => null,
                'delivery' => null,
                'error' => 'No payment corridor available for this route.',
            ];
        }

        // Check amount limits
        if (! $corridor->supportsAmount($amount)) {
            return [
                'success' => false,
                'corridor' => $corridor,
                'fees' => null,
                'delivery' => null,
                'error' => sprintf(
                    'Amount must be between %s and %s for this payment method.',
                    $corridor->min_amount ? number_format($corridor->min_amount, 2) : '0',
                    $corridor->max_amount ? number_format($corridor->max_amount, 2) : 'unlimited'
                ),
            ];
        }

        $fees = $this->calculateFees($corridor, $amount);
        $delivery = $this->estimateArrival($corridor);

        return [
            'success' => true,
            'corridor' => $corridor,
            'fees' => $fees,
            'delivery' => $delivery,
            'error' => null,
        ];
    }

    // =========================================
    // Delivery Estimation
    // =========================================

    /**
     * Estimate arrival time for a transfer.
     *
     * @return array{
     *     min_date: \Carbon\Carbon,
     *     max_date: \Carbon\Carbon,
     *     min_days: int,
     *     max_days: int,
     *     display: string
     * }
     */
    public function estimateArrival(PaymentCorridor $corridor): array
    {
        $now = now();
        $minDate = $this->addBusinessDays($now->copy(), $corridor->estimated_days_min);
        $maxDate = $this->addBusinessDays($now->copy(), $corridor->estimated_days_max);

        return [
            'min_date' => $minDate,
            'max_date' => $maxDate,
            'min_days' => $corridor->estimated_days_min,
            'max_days' => $corridor->estimated_days_max,
            'display' => $corridor->getEstimatedDeliveryRange(),
        ];
    }

    /**
     * Add business days to a date (skipping weekends).
     */
    protected function addBusinessDays(\Carbon\Carbon $date, int $days): \Carbon\Carbon
    {
        $added = 0;
        while ($added < $days) {
            $date->addDay();
            if (! $date->isWeekend()) {
                $added++;
            }
        }

        return $date;
    }

    // =========================================
    // Validation
    // =========================================

    /**
     * Validate an IBAN.
     */
    public function validateIBAN(string $iban): bool
    {
        return IBANValidator::validate($iban);
    }

    /**
     * Get IBAN validation error message.
     */
    public function getIBANValidationError(string $iban): ?string
    {
        return IBANValidator::getValidationError($iban);
    }

    /**
     * Validate a US routing number (ABA).
     * Uses the checksum algorithm based on position weights.
     */
    public function validateRoutingNumber(string $routing): bool
    {
        // Remove any formatting
        $routing = preg_replace('/[^0-9]/', '', $routing);

        // Must be exactly 9 digits
        if (strlen($routing) !== 9) {
            return false;
        }

        // ABA checksum algorithm
        // 3(d1 + d4 + d7) + 7(d2 + d5 + d8) + (d3 + d6 + d9) mod 10 = 0
        $sum = 3 * ($routing[0] + $routing[3] + $routing[6])
             + 7 * ($routing[1] + $routing[4] + $routing[7])
             + ($routing[2] + $routing[5] + $routing[8]);

        return ($sum % 10) === 0;
    }

    /**
     * Validate a UK sort code.
     */
    public function validateSortCode(string $sortCode): bool
    {
        // Remove any formatting (dashes, spaces)
        $sortCode = preg_replace('/[^0-9]/', '', $sortCode);

        // Must be exactly 6 digits
        if (strlen($sortCode) !== 6) {
            return false;
        }

        // All digits must be numeric (already ensured by regex)
        return true;
    }

    /**
     * Validate an Australian BSB code.
     */
    public function validateBSBCode(string $bsb): bool
    {
        // Remove any formatting (dashes)
        $bsb = preg_replace('/[^0-9]/', '', $bsb);

        // Must be exactly 6 digits
        return strlen($bsb) === 6;
    }

    /**
     * Validate a SWIFT/BIC code.
     */
    public function validateSwiftBic(string $swift): bool
    {
        // SWIFT/BIC format: 4 letters (bank) + 2 letters (country) + 2 alphanumeric (location) + optional 3 alphanumeric (branch)
        $swift = strtoupper(preg_replace('/\s+/', '', $swift));

        // 8 or 11 characters
        if (strlen($swift) !== 8 && strlen($swift) !== 11) {
            return false;
        }

        // First 6 must be letters
        if (! preg_match('/^[A-Z]{6}/', $swift)) {
            return false;
        }

        // Characters 7-8 must be alphanumeric
        if (! preg_match('/^[A-Z]{6}[A-Z0-9]{2}/', $swift)) {
            return false;
        }

        // If 11 characters, last 3 must be alphanumeric
        if (strlen($swift) === 11 && ! preg_match('/^[A-Z]{6}[A-Z0-9]{5}$/', $swift)) {
            return false;
        }

        return true;
    }

    /**
     * Validate bank account based on country requirements.
     */
    public function validateBankAccount(BankAccount $account): array
    {
        $errors = [];

        // Validate based on country
        if ($account->isUsAccount()) {
            if (empty($account->routing_number)) {
                $errors[] = 'Routing number is required for US accounts.';
            } elseif (! $this->validateRoutingNumber($account->routing_number)) {
                $errors[] = 'Invalid routing number.';
            }

            if (empty($account->account_number)) {
                $errors[] = 'Account number is required.';
            }
        } elseif ($account->isUkAccount()) {
            if (empty($account->sort_code)) {
                $errors[] = 'Sort code is required for UK accounts.';
            } elseif (! $this->validateSortCode($account->sort_code)) {
                $errors[] = 'Invalid sort code.';
            }

            if (empty($account->account_number)) {
                $errors[] = 'Account number is required.';
            }
        } elseif ($account->isAustralianAccount()) {
            if (empty($account->bsb_code)) {
                $errors[] = 'BSB code is required for Australian accounts.';
            } elseif (! $this->validateBSBCode($account->bsb_code)) {
                $errors[] = 'Invalid BSB code.';
            }

            if (empty($account->account_number)) {
                $errors[] = 'Account number is required.';
            }
        } elseif ($account->isSepaCountry()) {
            if (empty($account->iban)) {
                $errors[] = 'IBAN is required for SEPA accounts.';
            } elseif (! $this->validateIBAN($account->iban)) {
                $errors[] = $this->getIBANValidationError($account->iban) ?? 'Invalid IBAN.';
            }
        } else {
            // International accounts need IBAN or account number + SWIFT
            if (empty($account->iban) && empty($account->account_number)) {
                $errors[] = 'IBAN or account number is required.';
            }

            if (empty($account->iban) && empty($account->swift_bic)) {
                $errors[] = 'SWIFT/BIC code is required for international transfers.';
            }

            if (! empty($account->swift_bic) && ! $this->validateSwiftBic($account->swift_bic)) {
                $errors[] = 'Invalid SWIFT/BIC code.';
            }

            if (! empty($account->iban) && ! $this->validateIBAN($account->iban)) {
                $errors[] = $this->getIBANValidationError($account->iban) ?? 'Invalid IBAN.';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    // =========================================
    // Transfer Operations
    // =========================================

    /**
     * Initiate a cross-border transfer.
     *
     * @return array{
     *     success: bool,
     *     transfer: ?CrossBorderTransfer,
     *     error: ?string
     * }
     */
    public function initiateTransfer(
        User $user,
        BankAccount $account,
        float $amount,
        ?string $preferredMethod = null
    ): array {
        // Validate bank account
        $validation = $this->validateBankAccount($account);
        if (! $validation['valid']) {
            return [
                'success' => false,
                'transfer' => null,
                'error' => implode(' ', $validation['errors']),
            ];
        }

        // Get the source country (platform's country or user's country)
        $sourceCountry = config('services.platform.country', 'US');

        // Get quote
        $quote = $this->getQuote(
            $sourceCountry,
            $account->country_code,
            $amount,
            $preferredMethod
        );

        if (! $quote['success']) {
            return [
                'success' => false,
                'transfer' => null,
                'error' => $quote['error'],
            ];
        }

        $corridor = $quote['corridor'];
        $fees = $quote['fees'];
        $delivery = $quote['delivery'];

        try {
            $transfer = DB::transaction(function () use ($user, $account, $corridor, $fees, $delivery) {
                return CrossBorderTransfer::create([
                    'user_id' => $user->id,
                    'bank_account_id' => $account->id,
                    'source_currency' => $fees['source_currency'],
                    'destination_currency' => $fees['destination_currency'],
                    'source_amount' => $fees['source_amount'],
                    'destination_amount' => $fees['destination_amount'],
                    'exchange_rate' => $fees['exchange_rate'],
                    'fee_amount' => $fees['total_fee'],
                    'payment_method' => $corridor->payment_method,
                    'status' => CrossBorderTransfer::STATUS_PENDING,
                    'estimated_arrival_at' => $delivery['max_date'],
                ]);
            });

            Log::info('Cross-border transfer initiated', [
                'transfer_id' => $transfer->id,
                'reference' => $transfer->transfer_reference,
                'user_id' => $user->id,
                'amount' => $amount,
                'destination_country' => $account->country_code,
            ]);

            return [
                'success' => true,
                'transfer' => $transfer,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to initiate cross-border transfer', [
                'user_id' => $user->id,
                'bank_account_id' => $account->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'transfer' => null,
                'error' => 'Failed to initiate transfer. Please try again.',
            ];
        }
    }

    /**
     * Process a pending transfer.
     *
     * This would integrate with actual payment providers like:
     * - Stripe (via Stripe Connect payouts)
     * - Wise (TransferWise API)
     * - CurrencyCloud
     * - PayPal Payouts
     */
    public function processTransfer(CrossBorderTransfer $transfer): void
    {
        if (! $transfer->isPending()) {
            Log::warning('Attempted to process non-pending transfer', [
                'transfer_id' => $transfer->id,
                'status' => $transfer->status,
            ]);

            return;
        }

        try {
            // Mark as processing
            $transfer->markAsProcessing();

            // Here we would integrate with the actual payment provider
            // For now, we'll simulate the process

            $bankAccount = $transfer->bankAccount;

            // Determine which provider to use based on payment method
            $providerReference = $this->submitToProvider($transfer, $bankAccount);

            // Mark as sent
            $transfer->markAsSent($providerReference);

            Log::info('Cross-border transfer processed', [
                'transfer_id' => $transfer->id,
                'reference' => $transfer->transfer_reference,
                'provider_reference' => $providerReference,
            ]);
        } catch (\Exception $e) {
            $transfer->markAsFailed($e->getMessage());

            Log::error('Cross-border transfer processing failed', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Submit transfer to payment provider (stub for integration).
     */
    protected function submitToProvider(CrossBorderTransfer $transfer, BankAccount $bankAccount): string
    {
        // This is where you'd integrate with actual payment providers
        // Examples:
        // - SEPA: Submit to Stripe, Wise, or a SEPA gateway
        // - ACH: Submit to Stripe or ACH provider
        // - SWIFT: Submit to banking partner or TransferWise
        // - Faster Payments: Submit to UK banking partner

        // For now, generate a simulated provider reference
        $prefix = match ($transfer->payment_method) {
            PaymentCorridor::METHOD_SEPA => 'SEPA',
            PaymentCorridor::METHOD_ACH => 'ACH',
            PaymentCorridor::METHOD_SWIFT => 'SWIFT',
            PaymentCorridor::METHOD_FASTER_PAYMENTS => 'FPS',
            default => 'LOCAL',
        };

        return $prefix.'-'.strtoupper(uniqid());
    }

    /**
     * Get the current status of a transfer.
     */
    public function getTransferStatus(CrossBorderTransfer $transfer): string
    {
        // In a real implementation, this would query the payment provider
        // to get the current status

        return $transfer->status;
    }

    /**
     * Get detailed transfer status with tracking info.
     *
     * @return array{
     *     status: string,
     *     status_label: string,
     *     status_color: string,
     *     timeline: array,
     *     estimated_arrival: ?string,
     *     can_cancel: bool
     * }
     */
    public function getTransferDetails(CrossBorderTransfer $transfer): array
    {
        $timeline = [];

        // Build timeline
        $timeline[] = [
            'status' => 'created',
            'label' => 'Transfer Created',
            'date' => $transfer->created_at,
            'completed' => true,
        ];

        if ($transfer->status !== CrossBorderTransfer::STATUS_PENDING) {
            $timeline[] = [
                'status' => 'processing',
                'label' => 'Processing',
                'date' => $transfer->updated_at,
                'completed' => true,
            ];
        }

        if ($transfer->sent_at) {
            $timeline[] = [
                'status' => 'sent',
                'label' => 'Sent to Bank',
                'date' => $transfer->sent_at,
                'completed' => true,
            ];
        }

        if ($transfer->completed_at) {
            $timeline[] = [
                'status' => 'completed',
                'label' => 'Completed',
                'date' => $transfer->completed_at,
                'completed' => true,
            ];
        }

        return [
            'status' => $transfer->status,
            'status_label' => $transfer->getStatusLabel(),
            'status_color' => $transfer->getStatusColor(),
            'timeline' => $timeline,
            'estimated_arrival' => $transfer->estimated_arrival_at?->format('M j, Y'),
            'can_cancel' => $transfer->isPending(),
        ];
    }

    /**
     * Cancel a pending transfer.
     */
    public function cancelTransfer(CrossBorderTransfer $transfer): array
    {
        if (! $transfer->isPending()) {
            return [
                'success' => false,
                'error' => 'Only pending transfers can be cancelled.',
            ];
        }

        try {
            $transfer->markAsFailed('Cancelled by user');

            Log::info('Cross-border transfer cancelled', [
                'transfer_id' => $transfer->id,
                'reference' => $transfer->transfer_reference,
            ]);

            return [
                'success' => true,
                'message' => 'Transfer has been cancelled.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to cancel transfer.',
            ];
        }
    }

    // =========================================
    // Exchange Rates
    // =========================================

    /**
     * Get exchange rate between two currencies.
     */
    public function getExchangeRate(string $from, string $to): float
    {
        // Same currency
        if (strtoupper($from) === strtoupper($to)) {
            return 1.0;
        }

        // Use exchange rate service if available
        if ($this->exchangeRateService) {
            try {
                return $this->exchangeRateService->getRate($from, $to);
            } catch (\Exception $e) {
                Log::warning('Exchange rate service failed, using fallback', [
                    'from' => $from,
                    'to' => $to,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback to static rates (in production, use a real exchange rate API)
        return $this->getFallbackExchangeRate($from, $to);
    }

    /**
     * Get fallback exchange rate (for development/testing).
     */
    protected function getFallbackExchangeRate(string $from, string $to): float
    {
        // Approximate rates as of late 2024 (these should NOT be used in production)
        $usdRates = [
            'EUR' => 0.92,
            'GBP' => 0.79,
            'CAD' => 1.36,
            'AUD' => 1.53,
            'JPY' => 149.50,
            'CHF' => 0.88,
            'INR' => 83.12,
            'MXN' => 17.15,
            'BRL' => 4.97,
            'ZAR' => 18.75,
            'NGN' => 1550.00,
            'KES' => 153.50,
            'PHP' => 55.80,
        ];

        $from = strtoupper($from);
        $to = strtoupper($to);

        // Convert from USD
        if ($from === 'USD' && isset($usdRates[$to])) {
            return $usdRates[$to];
        }

        // Convert to USD
        if ($to === 'USD' && isset($usdRates[$from])) {
            return 1 / $usdRates[$from];
        }

        // Cross rates via USD
        if (isset($usdRates[$from]) && isset($usdRates[$to])) {
            return $usdRates[$to] / $usdRates[$from];
        }

        // Default to 1:1 if unknown
        return 1.0;
    }

    // =========================================
    // Reporting
    // =========================================

    /**
     * Get transfer statistics for a user.
     *
     * @return array{
     *     total_transfers: int,
     *     total_amount: float,
     *     pending_count: int,
     *     completed_count: int,
     *     failed_count: int
     * }
     */
    public function getUserTransferStats(User $user): array
    {
        $stats = CrossBorderTransfer::query()
            ->where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(source_amount) as total_amount,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as failed
            ', [
                CrossBorderTransfer::STATUS_PENDING,
                CrossBorderTransfer::STATUS_COMPLETED,
                CrossBorderTransfer::STATUS_FAILED,
                CrossBorderTransfer::STATUS_RETURNED,
            ])
            ->first();

        return [
            'total_transfers' => (int) $stats->total,
            'total_amount' => (float) ($stats->total_amount ?? 0),
            'pending_count' => (int) $stats->pending,
            'completed_count' => (int) $stats->completed,
            'failed_count' => (int) $stats->failed,
        ];
    }

    /**
     * Get recent transfers for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection<CrossBorderTransfer>
     */
    public function getUserRecentTransfers(User $user, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return CrossBorderTransfer::query()
            ->where('user_id', $user->id)
            ->with('bankAccount')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
