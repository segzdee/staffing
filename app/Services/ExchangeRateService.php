<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GLO-001: Multi-Currency Support - Exchange Rate Service
 *
 * Service for fetching exchange rates from external APIs (ECB, OpenExchangeRates).
 * Handles rate updates, caching, and fallback mechanisms.
 */
class ExchangeRateService
{
    /**
     * Supported rate sources.
     */
    public const SOURCE_ECB = 'ecb';

    public const SOURCE_OPENEXCHANGERATES = 'openexchangerates';

    public const SOURCE_FIXER = 'fixer';

    /**
     * Fetch exchange rates from the European Central Bank.
     *
     * @return array<string, float>
     */
    public function fetchFromECB(): array
    {
        $rates = [];
        $baseCurrency = 'EUR';

        try {
            // ECB publishes daily reference rates as XML
            $response = Http::timeout(30)->get(
                'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml'
            );

            if (! $response->successful()) {
                Log::warning('ECB API returned non-success status', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            $xml = simplexml_load_string($response->body());

            if (! $xml) {
                Log::warning('Failed to parse ECB XML response');

                return [];
            }

            // Navigate to the rate data
            foreach ($xml->Cube->Cube->Cube as $rate) {
                $currency = (string) $rate['currency'];
                $rateValue = (float) $rate['rate'];

                if ($currency && $rateValue > 0) {
                    $rates[$currency] = $rateValue;
                }
            }

            // Add EUR as base (rate = 1)
            $rates['EUR'] = 1.0;

            Log::info('Fetched exchange rates from ECB', [
                'count' => count($rates),
                'currencies' => array_keys($rates),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch rates from ECB', [
                'error' => $e->getMessage(),
            ]);
        }

        return $rates;
    }

    /**
     * Fetch exchange rates from Open Exchange Rates API.
     *
     * @return array<string, float>
     */
    public function fetchFromOpenExchangeRates(): array
    {
        $rates = [];
        $apiKey = config('currencies.openexchangerates_api_key');

        if (! $apiKey) {
            Log::warning('OpenExchangeRates API key not configured');

            return [];
        }

        try {
            $response = Http::timeout(30)->get(
                config('currencies.openexchangerates_url').'latest.json',
                [
                    'app_id' => $apiKey,
                    'base' => 'USD', // Free tier only supports USD base
                ]
            );

            if (! $response->successful()) {
                Log::warning('OpenExchangeRates API returned non-success status', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            if (isset($data['rates']) && is_array($data['rates'])) {
                $rates = $data['rates'];

                Log::info('Fetched exchange rates from OpenExchangeRates', [
                    'count' => count($rates),
                    'base' => 'USD',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to fetch rates from OpenExchangeRates', [
                'error' => $e->getMessage(),
            ]);
        }

        return $rates;
    }

    /**
     * Fetch exchange rates from Fixer.io API.
     *
     * @return array<string, float>
     */
    public function fetchFromFixer(): array
    {
        $rates = [];
        $apiKey = config('currencies.fixer_api_key');

        if (! $apiKey) {
            Log::warning('Fixer API key not configured');

            return [];
        }

        try {
            $response = Http::timeout(30)->get(
                config('currencies.fixer_url').'latest',
                [
                    'access_key' => $apiKey,
                    'base' => 'EUR', // Free tier supports EUR base
                ]
            );

            if (! $response->successful()) {
                Log::warning('Fixer API returned non-success status', [
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            if (isset($data['success']) && $data['success'] && isset($data['rates'])) {
                $rates = $data['rates'];

                Log::info('Fetched exchange rates from Fixer', [
                    'count' => count($rates),
                    'base' => 'EUR',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to fetch rates from Fixer', [
                'error' => $e->getMessage(),
            ]);
        }

        return $rates;
    }

    /**
     * Update exchange rates from configured source.
     */
    public function updateRates(): bool
    {
        $source = config('currencies.exchange_rate_source', self::SOURCE_ECB);
        $baseCurrency = config('currencies.default', 'EUR');
        $supportedCurrencies = config('currencies.supported', []);

        // Fetch rates based on configured source
        $rates = match ($source) {
            self::SOURCE_ECB => $this->fetchFromECB(),
            self::SOURCE_OPENEXCHANGERATES => $this->convertToEurBase($this->fetchFromOpenExchangeRates()),
            self::SOURCE_FIXER => $this->fetchFromFixer(),
            default => $this->fetchFromECB(),
        };

        if (empty($rates)) {
            // Try fallback sources
            Log::warning("Primary source {$source} failed, trying fallbacks");

            $rates = $this->fetchFromECB();
            $source = self::SOURCE_ECB;

            if (empty($rates)) {
                $rates = $this->convertToEurBase($this->fetchFromOpenExchangeRates());
                $source = self::SOURCE_OPENEXCHANGERATES;
            }
        }

        if (empty($rates)) {
            Log::error('All exchange rate sources failed');

            return false;
        }

        $rateDate = now();
        $updatedCount = 0;

        // Store rates for all supported currency pairs
        foreach ($supportedCurrencies as $targetCurrency) {
            if ($targetCurrency === $baseCurrency) {
                continue;
            }

            if (! isset($rates[$targetCurrency])) {
                Log::warning("No rate found for {$targetCurrency}");

                continue;
            }

            $rate = $rates[$targetCurrency];

            ExchangeRate::updateOrCreateRate(
                $baseCurrency,
                $targetCurrency,
                $rate,
                $source,
                $rateDate
            );

            $updatedCount++;
        }

        // Clear exchange rate cache
        $this->clearRateCache();

        Log::info('Exchange rates updated successfully', [
            'source' => $source,
            'count' => $updatedCount,
            'date' => $rateDate->toDateString(),
        ]);

        return true;
    }

    /**
     * Convert USD-based rates to EUR-based rates.
     *
     * @param  array<string, float>  $usdBasedRates
     * @return array<string, float>
     */
    protected function convertToEurBase(array $usdBasedRates): array
    {
        if (empty($usdBasedRates) || ! isset($usdBasedRates['EUR'])) {
            return [];
        }

        $eurToUsd = 1 / $usdBasedRates['EUR'];
        $eurBasedRates = [];

        foreach ($usdBasedRates as $currency => $rate) {
            // Convert: EUR -> USD -> Target Currency
            $eurBasedRates[$currency] = $rate * $eurToUsd;
        }

        return $eurBasedRates;
    }

    /**
     * Clear the exchange rate cache.
     */
    public function clearRateCache(): void
    {
        $supportedCurrencies = config('currencies.supported', []);

        foreach ($supportedCurrencies as $from) {
            foreach ($supportedCurrencies as $to) {
                if ($from !== $to) {
                    Cache::forget("exchange_rate_{$from}_{$to}");
                }
            }
        }

        Log::info('Exchange rate cache cleared');
    }

    /**
     * Get the latest rate for a currency pair.
     */
    public function getRate(string $from, string $to): ?float
    {
        $rate = ExchangeRate::getLatestRate($from, $to);

        return $rate ? (float) $rate->rate : null;
    }

    /**
     * Get rate history for a currency pair.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRateHistory(string $from, string $to, int $days = 30)
    {
        return ExchangeRate::getHistory($from, $to, $days);
    }

    /**
     * Check if exchange rates are stale.
     */
    public function areRatesStale(): bool
    {
        $latestRate = ExchangeRate::orderBy('rate_date', 'desc')->first();

        if (! $latestRate) {
            return true;
        }

        return $latestRate->is_stale;
    }

    /**
     * Get rate status for admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getRateStatus(): array
    {
        $latestRate = ExchangeRate::orderBy('rate_date', 'desc')->first();
        $supportedCurrencies = config('currencies.supported', []);
        $baseCurrency = config('currencies.default', 'EUR');

        // Count available rates
        $availableRates = ExchangeRate::where('base_currency', $baseCurrency)
            ->where('rate_date', '>=', now()->subHours(48))
            ->distinct('target_currency')
            ->count();

        $missingRates = [];
        foreach ($supportedCurrencies as $currency) {
            if ($currency === $baseCurrency) {
                continue;
            }

            $rate = ExchangeRate::getLatestRate($baseCurrency, $currency);
            if (! $rate || $rate->is_stale) {
                $missingRates[] = $currency;
            }
        }

        return [
            'last_updated' => $latestRate?->rate_date,
            'source' => $latestRate?->source ?? 'unknown',
            'is_stale' => $this->areRatesStale(),
            'available_rates' => $availableRates,
            'total_supported' => count($supportedCurrencies) - 1, // Exclude base currency
            'missing_rates' => $missingRates,
            'age_hours' => $latestRate ? $latestRate->rate_date->diffInHours(now()) : null,
        ];
    }

    /**
     * Get all current rates as an array.
     *
     * @return array<string, array<string, float>>
     */
    public function getAllCurrentRates(): array
    {
        $supportedCurrencies = config('currencies.supported', []);
        $baseCurrency = config('currencies.default', 'EUR');
        $rates = [];

        foreach ($supportedCurrencies as $currency) {
            if ($currency === $baseCurrency) {
                continue;
            }

            $rate = ExchangeRate::getLatestRate($baseCurrency, $currency);

            if ($rate) {
                $rates[$currency] = [
                    'rate' => (float) $rate->rate,
                    'inverse_rate' => (float) $rate->inverse_rate,
                    'date' => $rate->rate_date->toDateString(),
                    'source' => $rate->source,
                    'is_stale' => $rate->is_stale,
                ];
            }
        }

        return $rates;
    }

    /**
     * Calculate the cross rate between two non-base currencies.
     */
    public function getCrossRate(string $from, string $to): ?float
    {
        $baseCurrency = config('currencies.default', 'EUR');

        if ($from === $baseCurrency) {
            return $this->getRate($from, $to);
        }

        if ($to === $baseCurrency) {
            $rate = $this->getRate($baseCurrency, $from);

            return $rate ? (1 / $rate) : null;
        }

        // Cross rate: from -> base -> to
        $fromToBase = $this->getRate($baseCurrency, $from);
        $baseToTo = $this->getRate($baseCurrency, $to);

        if (! $fromToBase || ! $baseToTo) {
            return null;
        }

        return $baseToTo / $fromToBase;
    }
}
