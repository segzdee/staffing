<?php

namespace App\Services;

use App\Models\CurrencyConversion;
use App\Models\CurrencyWallet;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GLO-001: Multi-Currency Support - Currency Service
 *
 * Centralized service for all currency-related operations including
 * wallet management, balance operations, and currency conversions.
 */
class CurrencyService
{
    public function __construct(
        protected ExchangeRateService $exchangeRateService
    ) {}

    /**
     * Get or create a wallet for a user in a specific currency.
     */
    public function getOrCreateWallet(User $user, string $currency): CurrencyWallet
    {
        $currency = strtoupper($currency);

        if (! $this->isSupportedCurrency($currency)) {
            throw new \InvalidArgumentException("Currency {$currency} is not supported.");
        }

        $wallet = CurrencyWallet::firstOrCreate(
            [
                'user_id' => $user->id,
                'currency_code' => $currency,
            ],
            [
                'balance' => 0,
                'pending_balance' => 0,
                'is_primary' => ! $user->currencyWallets()->exists(),
            ]
        );

        return $wallet;
    }

    /**
     * Get the balance for a user in a specific currency.
     */
    public function getBalance(User $user, string $currency): float
    {
        $wallet = CurrencyWallet::where('user_id', $user->id)
            ->where('currency_code', strtoupper($currency))
            ->first();

        return $wallet ? (float) $wallet->balance : 0.0;
    }

    /**
     * Get the pending balance for a user in a specific currency.
     */
    public function getPendingBalance(User $user, string $currency): float
    {
        $wallet = CurrencyWallet::where('user_id', $user->id)
            ->where('currency_code', strtoupper($currency))
            ->first();

        return $wallet ? (float) $wallet->pending_balance : 0.0;
    }

    /**
     * Get the total balance (available + pending) for a user in a specific currency.
     */
    public function getTotalBalance(User $user, string $currency): float
    {
        $wallet = CurrencyWallet::where('user_id', $user->id)
            ->where('currency_code', strtoupper($currency))
            ->first();

        return $wallet ? $wallet->total_balance : 0.0;
    }

    /**
     * Credit a wallet (add funds).
     *
     * @throws \InvalidArgumentException
     */
    public function credit(CurrencyWallet $wallet, float $amount, ?string $description = null): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit amount must be positive.');
        }

        DB::transaction(function () use ($wallet, $amount, $description) {
            $wallet->credit($amount);

            Log::info('Wallet credited', [
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'currency' => $wallet->currency_code,
                'amount' => $amount,
                'description' => $description,
                'new_balance' => $wallet->fresh()->balance,
            ]);
        });
    }

    /**
     * Debit a wallet (remove funds).
     *
     * @throws \InvalidArgumentException
     */
    public function debit(CurrencyWallet $wallet, float $amount, ?string $description = null): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Debit amount must be positive.');
        }

        if (! $wallet->hasSufficientBalance($amount)) {
            throw new \InvalidArgumentException(
                "Insufficient balance. Available: {$wallet->formatted_balance}"
            );
        }

        DB::transaction(function () use ($wallet, $amount, $description) {
            $wallet->debit($amount);

            Log::info('Wallet debited', [
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'currency' => $wallet->currency_code,
                'amount' => $amount,
                'description' => $description,
                'new_balance' => $wallet->fresh()->balance,
            ]);
        });
    }

    /**
     * Add funds to pending balance.
     */
    public function addPending(CurrencyWallet $wallet, float $amount, ?string $description = null): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Pending amount must be positive.');
        }

        DB::transaction(function () use ($wallet, $amount, $description) {
            $wallet->addPending($amount);

            Log::info('Pending balance added', [
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'currency' => $wallet->currency_code,
                'amount' => $amount,
                'description' => $description,
            ]);
        });
    }

    /**
     * Release pending funds to available balance.
     */
    public function releasePending(CurrencyWallet $wallet, float $amount, ?string $description = null): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Release amount must be positive.');
        }

        DB::transaction(function () use ($wallet, $amount, $description) {
            $wallet->releasePending($amount);

            Log::info('Pending balance released', [
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'currency' => $wallet->currency_code,
                'amount' => $amount,
                'description' => $description,
            ]);
        });
    }

    /**
     * Convert currency from one wallet to another.
     */
    public function convert(
        User $user,
        string $fromCurrency,
        string $toCurrency,
        float $amount,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): CurrencyConversion {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        // Validate currencies
        if (! $this->isSupportedCurrency($fromCurrency) || ! $this->isSupportedCurrency($toCurrency)) {
            throw new \InvalidArgumentException('Invalid currency code.');
        }

        if ($fromCurrency === $toCurrency) {
            throw new \InvalidArgumentException('Cannot convert to the same currency.');
        }

        // Check minimum conversion amount
        $minAmount = config('currencies.minimum_conversion_amount', 10.00);
        if ($amount < $minAmount) {
            throw new \InvalidArgumentException(
                "Minimum conversion amount is {$minAmount} {$fromCurrency}."
            );
        }

        // Get exchange rate
        $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency);

        // Calculate conversion
        $feePercent = config('currencies.conversion_fee_percent', 1.5);
        $feeAmount = $amount * ($feePercent / 100);
        $netAmount = $amount - $feeAmount;
        $toAmount = $netAmount * $exchangeRate;

        // Round to appropriate decimal places
        $toDecimals = config("currencies.rounding.{$toCurrency}", 2);
        $toAmount = round($toAmount, $toDecimals);

        return DB::transaction(function () use (
            $user,
            $fromCurrency,
            $toCurrency,
            $amount,
            $toAmount,
            $exchangeRate,
            $feeAmount,
            $referenceType,
            $referenceId
        ) {
            // Get or create wallets
            $fromWallet = $this->getOrCreateWallet($user, $fromCurrency);
            $toWallet = $this->getOrCreateWallet($user, $toCurrency);

            // Check balance
            if (! $fromWallet->hasSufficientBalance($amount)) {
                throw new \InvalidArgumentException(
                    "Insufficient balance in {$fromCurrency} wallet."
                );
            }

            // Perform conversion
            $fromWallet->debit($amount);
            $toWallet->credit($toAmount);

            // Create audit record
            $conversion = CurrencyConversion::create([
                'user_id' => $user->id,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'from_amount' => $amount,
                'to_amount' => $toAmount,
                'exchange_rate' => $exchangeRate,
                'fee_amount' => $feeAmount,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'status' => CurrencyConversion::STATUS_COMPLETED,
            ]);

            Log::info('Currency conversion completed', [
                'conversion_id' => $conversion->id,
                'user_id' => $user->id,
                'pair' => "{$fromCurrency}/{$toCurrency}",
                'from_amount' => $amount,
                'to_amount' => $toAmount,
                'rate' => $exchangeRate,
                'fee' => $feeAmount,
            ]);

            return $conversion;
        });
    }

    /**
     * Get the current exchange rate between two currencies.
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";
        $cacheTtl = config('currencies.cache_ttl', 3600);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($fromCurrency, $toCurrency) {
            // Try to get from database
            $rate = ExchangeRate::getLatestRate($fromCurrency, $toCurrency);

            if ($rate && ! $rate->is_stale) {
                return (float) $rate->rate;
            }

            // Try inverse rate
            $inverseRate = ExchangeRate::getLatestRate($toCurrency, $fromCurrency);
            if ($inverseRate && ! $inverseRate->is_stale) {
                return (float) $inverseRate->inverse_rate;
            }

            // Try to calculate via base currency (EUR)
            $baseCurrency = config('currencies.default', 'EUR');
            if ($fromCurrency !== $baseCurrency && $toCurrency !== $baseCurrency) {
                $fromToBase = $this->getDirectRate($fromCurrency, $baseCurrency);
                $baseToTarget = $this->getDirectRate($baseCurrency, $toCurrency);

                if ($fromToBase && $baseToTarget) {
                    return $fromToBase * $baseToTarget;
                }
            }

            // Fall back to static rates
            return $this->getFallbackRate($fromCurrency, $toCurrency);
        });
    }

    /**
     * Get direct rate from database without cross-calculation.
     */
    protected function getDirectRate(string $from, string $to): ?float
    {
        $rate = ExchangeRate::getLatestRate($from, $to);
        if ($rate) {
            return (float) $rate->rate;
        }

        $inverseRate = ExchangeRate::getLatestRate($to, $from);
        if ($inverseRate) {
            return (float) $inverseRate->inverse_rate;
        }

        return null;
    }

    /**
     * Get fallback rate from config.
     */
    protected function getFallbackRate(string $from, string $to): float
    {
        $fallbackRates = config('currencies.fallback_rates', []);
        $baseCurrency = config('currencies.default', 'EUR');

        // Get rates relative to base currency
        $fromRate = $fallbackRates[$from] ?? 1.0;
        $toRate = $fallbackRates[$to] ?? 1.0;

        if ($fromRate == 0) {
            return 0;
        }

        // Convert: from -> base -> to
        return $toRate / $fromRate;
    }

    /**
     * Fetch and update exchange rates from external source.
     */
    public function fetchExchangeRates(): void
    {
        $this->exchangeRateService->updateRates();
    }

    /**
     * Format a currency amount with symbol.
     */
    public function formatCurrency(float $amount, string $currency): string
    {
        $currency = strtoupper($currency);
        $symbol = config("currencies.symbols.{$currency}", $currency);
        $decimals = config("currencies.rounding.{$currency}", 2);
        $symbolBefore = config("currencies.symbol_before.{$currency}", true);

        $formatted = number_format($amount, $decimals);

        return $symbolBefore
            ? "{$symbol}{$formatted}"
            : "{$formatted} {$symbol}";
    }

    /**
     * Get list of supported currencies.
     *
     * @return array<string>
     */
    public function getSupportedCurrencies(): array
    {
        return config('currencies.supported', []);
    }

    /**
     * Check if a currency is supported.
     */
    public function isSupportedCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->getSupportedCurrencies());
    }

    /**
     * Get all wallets for a user.
     */
    public function getUserWallets(User $user): Collection
    {
        return $user->currencyWallets()->orderBy('is_primary', 'desc')->get();
    }

    /**
     * Get user's primary wallet.
     */
    public function getPrimaryWallet(User $user): ?CurrencyWallet
    {
        return $user->currencyWallets()->primary()->first();
    }

    /**
     * Get the default currency for a country code.
     */
    public function getDefaultCurrencyForCountry(?string $countryCode): string
    {
        if (! $countryCode) {
            return config('currencies.default', 'EUR');
        }

        $countryCode = strtoupper($countryCode);
        $regionalDefaults = config('currencies.regional_defaults', []);

        return $regionalDefaults[$countryCode] ?? $regionalDefaults['default'] ?? 'EUR';
    }

    /**
     * Get total balance across all wallets converted to a single currency.
     */
    public function getTotalBalanceInCurrency(User $user, string $targetCurrency): float
    {
        $wallets = $this->getUserWallets($user);
        $total = 0.0;

        foreach ($wallets as $wallet) {
            if ($wallet->currency_code === strtoupper($targetCurrency)) {
                $total += $wallet->total_balance;
            } else {
                $rate = $this->getExchangeRate($wallet->currency_code, $targetCurrency);
                $total += $wallet->total_balance * $rate;
            }
        }

        return round($total, config("currencies.rounding.{$targetCurrency}", 2));
    }

    /**
     * Get currency details including symbol, name, and decimals.
     *
     * @return array<string, mixed>
     */
    public function getCurrencyDetails(string $currency): array
    {
        $currency = strtoupper($currency);

        return [
            'code' => $currency,
            'name' => config("currencies.names.{$currency}", $currency),
            'symbol' => config("currencies.symbols.{$currency}", $currency),
            'decimals' => config("currencies.rounding.{$currency}", 2),
            'symbol_before' => config("currencies.symbol_before.{$currency}", true),
        ];
    }

    /**
     * Get all supported currencies with their details.
     *
     * @return array<array<string, mixed>>
     */
    public function getAllCurrencyDetails(): array
    {
        $currencies = [];

        foreach ($this->getSupportedCurrencies() as $code) {
            $currencies[$code] = $this->getCurrencyDetails($code);
        }

        return $currencies;
    }

    /**
     * Preview a conversion without executing it.
     *
     * @return array<string, mixed>
     */
    public function previewConversion(string $from, string $to, float $amount): array
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        $exchangeRate = $this->getExchangeRate($from, $to);
        $feePercent = config('currencies.conversion_fee_percent', 1.5);
        $feeAmount = $amount * ($feePercent / 100);
        $netAmount = $amount - $feeAmount;
        $toAmount = $netAmount * $exchangeRate;

        $toDecimals = config("currencies.rounding.{$to}", 2);
        $toAmount = round($toAmount, $toDecimals);

        return [
            'from_currency' => $from,
            'to_currency' => $to,
            'from_amount' => $amount,
            'to_amount' => $toAmount,
            'exchange_rate' => $exchangeRate,
            'fee_percent' => $feePercent,
            'fee_amount' => $feeAmount,
            'effective_rate' => $amount > 0 ? $toAmount / $amount : 0,
            'formatted_from' => $this->formatCurrency($amount, $from),
            'formatted_to' => $this->formatCurrency($toAmount, $to),
            'formatted_fee' => $this->formatCurrency($feeAmount, $from),
        ];
    }
}
