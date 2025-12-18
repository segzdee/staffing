<?php

namespace App\Services;

use App\Models\PriceAdjustment;
use App\Models\RegionalPricing;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GLO-009: Regional Pricing System - Regional Pricing Service
 *
 * Centralized service for all regional pricing operations including
 * PPP adjustments, rate validation, and pricing analytics.
 */
class RegionalPricingService
{
    /**
     * Cache key prefix for regional pricing.
     */
    protected const CACHE_PREFIX = 'regional_pricing_';

    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get regional pricing for a specific country and optional region.
     */
    public function getRegionalPricing(string $countryCode, ?string $regionCode = null): ?RegionalPricing
    {
        $cacheKey = self::CACHE_PREFIX."location_{$countryCode}_{$regionCode}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($countryCode, $regionCode) {
            return RegionalPricing::findForLocation($countryCode, $regionCode);
        });
    }

    /**
     * Calculate adjusted price based on user location and adjustment type.
     */
    public function calculateAdjustedPrice(
        float $basePrice,
        User $user,
        string $type = PriceAdjustment::TYPE_SERVICE_FEE,
        array $context = []
    ): array {
        // Get user's country and region from profile
        $countryCode = $this->getUserCountry($user);
        $regionCode = $this->getUserRegion($user);

        // Get regional pricing
        $regional = $this->getRegionalPricing($countryCode, $regionCode);

        if (! $regional) {
            // Use default pricing if no regional pricing found
            $regional = $this->getDefaultPricing();
        }

        // Start with PPP-adjusted base price
        $adjustedPrice = $regional->applyPPPAdjustment($basePrice);

        // Get any applicable price adjustments
        $adjustment = PriceAdjustment::findApplicable(
            $regional->id,
            $type,
            array_merge($context, [
                'user_type' => $user->role,
                'amount' => $adjustedPrice,
            ])
        );

        $appliedAdjustments = [];

        if ($adjustment) {
            $adjustedPrice = $adjustment->applyToPrice($adjustedPrice);
            $appliedAdjustments[] = [
                'id' => $adjustment->id,
                'name' => $adjustment->name ?? $adjustment->type_label,
                'type' => $adjustment->adjustment_type,
                'multiplier' => $adjustment->multiplier,
                'fixed_adjustment' => $adjustment->fixed_adjustment,
            ];
        }

        return [
            'original_price' => $basePrice,
            'adjusted_price' => $adjustedPrice,
            'currency_code' => $regional->currency_code,
            'country_code' => $regional->country_code,
            'region_code' => $regional->region_code,
            'ppp_factor' => $regional->ppp_factor,
            'applied_adjustments' => $appliedAdjustments,
        ];
    }

    /**
     * Apply PPP adjustment to a price for a specific country.
     */
    public function applyPPPAdjustment(float $price, string $countryCode): float
    {
        $regional = $this->getRegionalPricing($countryCode);

        if (! $regional) {
            return $price;
        }

        return $regional->applyPPPAdjustment($price);
    }

    /**
     * Get minimum and maximum rates for a country.
     */
    public function getMinMaxRates(string $countryCode, ?string $regionCode = null): array
    {
        $regional = $this->getRegionalPricing($countryCode, $regionCode);

        if (! $regional) {
            $regional = $this->getDefaultPricing();
        }

        return [
            'min_hourly_rate' => $regional->min_hourly_rate,
            'max_hourly_rate' => $regional->max_hourly_rate,
            'currency_code' => $regional->currency_code,
            'country_code' => $regional->country_code,
            'region_code' => $regional->region_code,
        ];
    }

    /**
     * Validate if a rate is within the allowed range for a region.
     */
    public function validateRateForRegion(float $rate, string $countryCode, ?string $regionCode = null): array
    {
        $regional = $this->getRegionalPricing($countryCode, $regionCode);

        if (! $regional) {
            $regional = $this->getDefaultPricing();
        }

        $isValid = $regional->isRateValid($rate);

        return [
            'is_valid' => $isValid,
            'rate' => $rate,
            'min_rate' => $regional->min_hourly_rate,
            'max_rate' => $regional->max_hourly_rate,
            'currency_code' => $regional->currency_code,
            'message' => $isValid
                ? 'Rate is within acceptable range'
                : "Rate must be between {$regional->min_hourly_rate} and {$regional->max_hourly_rate} {$regional->currency_code}",
        ];
    }

    /**
     * Sync PPP rates from World Bank API.
     */
    public function syncPPPRates(): array
    {
        $apiKey = config('regional_pricing.ppp_api_key');
        $results = [
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if (! config('regional_pricing.auto_sync_ppp')) {
            $results['errors'][] = 'PPP sync is disabled in configuration';

            return $results;
        }

        try {
            // World Bank API for PPP conversion factors
            $response = Http::timeout(30)->get('https://api.worldbank.org/v2/country/all/indicator/PA.NUS.PPP', [
                'format' => 'json',
                'date' => now()->subYear()->year,
                'per_page' => 300,
            ]);

            if (! $response->successful()) {
                $results['errors'][] = 'Failed to fetch PPP data from World Bank API';
                Log::error('Failed to fetch PPP data', ['status' => $response->status()]);

                return $results;
            }

            $data = $response->json();

            // World Bank API returns array where [1] contains the actual data
            if (! isset($data[1]) || ! is_array($data[1])) {
                $results['errors'][] = 'Invalid response format from World Bank API';

                return $results;
            }

            // Get US PPP as baseline (should be close to 1.0)
            $usPPP = 1.0;
            foreach ($data[1] as $item) {
                if (isset($item['country']['id']) && $item['country']['id'] === 'USA' && isset($item['value'])) {
                    $usPPP = (float) $item['value'];
                    break;
                }
            }

            DB::beginTransaction();

            foreach ($data[1] as $item) {
                if (! isset($item['country']['id'], $item['value']) || $item['value'] === null) {
                    continue;
                }

                $countryCode = $this->convertCountryCode($item['country']['id']);

                if (! $countryCode) {
                    continue;
                }

                // Calculate PPP factor relative to US
                $pppFactor = round((float) $item['value'] / $usPPP, 3);

                // Update all regional pricing entries for this country
                $updated = RegionalPricing::where('country_code', $countryCode)
                    ->update(['ppp_factor' => $pppFactor]);

                if ($updated > 0) {
                    $results['updated'] += $updated;
                }
            }

            DB::commit();

            // Clear cache
            $this->clearCache();

            Log::info('PPP rates synced successfully', $results);
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
            $results['failed']++;
            Log::error('PPP sync failed', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Get regional pricing analytics.
     */
    public function getRegionalAnalytics(): array
    {
        return Cache::remember(self::CACHE_PREFIX.'analytics', self::CACHE_TTL, function () {
            $regions = RegionalPricing::active()->get();

            $analytics = [
                'total_regions' => $regions->count(),
                'countries' => $regions->pluck('country_code')->unique()->count(),
                'by_currency' => $regions->groupBy('currency_code')
                    ->map(fn ($group) => $group->count())
                    ->toArray(),
                'ppp_distribution' => [
                    'min' => $regions->min('ppp_factor'),
                    'max' => $regions->max('ppp_factor'),
                    'avg' => round($regions->avg('ppp_factor'), 3),
                ],
                'rate_ranges' => [
                    'min_rate_avg' => round($regions->avg('min_hourly_rate'), 2),
                    'max_rate_avg' => round($regions->avg('max_hourly_rate'), 2),
                ],
                'fee_rates' => [
                    'platform_fee_avg' => round($regions->avg('platform_fee_rate'), 2),
                    'worker_fee_avg' => round($regions->avg('worker_fee_rate'), 2),
                ],
                'adjustments' => [
                    'total' => PriceAdjustment::count(),
                    'active' => PriceAdjustment::active()->valid()->count(),
                    'by_type' => PriceAdjustment::select('adjustment_type', DB::raw('count(*) as count'))
                        ->groupBy('adjustment_type')
                        ->pluck('count', 'adjustment_type')
                        ->toArray(),
                ],
                'top_regions_by_ppp' => $regions->sortByDesc('ppp_factor')
                    ->take(5)
                    ->map(fn ($r) => [
                        'country' => $r->country_name ?? $r->country_code,
                        'ppp_factor' => $r->ppp_factor,
                    ])
                    ->values()
                    ->toArray(),
            ];

            return $analytics;
        });
    }

    /**
     * Calculate platform fees for a shift.
     */
    public function calculateShiftFees(
        float $hourlyRate,
        float $hours,
        string $countryCode,
        ?string $regionCode = null,
        ?string $businessTier = null,
        ?string $workerTier = null
    ): array {
        $regional = $this->getRegionalPricing($countryCode, $regionCode)
            ?? $this->getDefaultPricing();

        $totalAmount = $hourlyRate * $hours;
        $platformFee = $regional->calculatePlatformFee($totalAmount, $businessTier);
        $workerFee = $regional->calculateWorkerFee($totalAmount, $workerTier);

        return [
            'hourly_rate' => $hourlyRate,
            'hours' => $hours,
            'subtotal' => $totalAmount,
            'platform_fee' => $platformFee,
            'platform_fee_rate' => $regional->platform_fee_rate,
            'worker_fee' => $workerFee,
            'worker_fee_rate' => $regional->worker_fee_rate,
            'worker_earnings' => $totalAmount - $workerFee,
            'business_total' => $totalAmount + $platformFee,
            'currency_code' => $regional->currency_code,
        ];
    }

    /**
     * Get all active regional pricing configurations.
     */
    public function getAllRegionalPricing(): Collection
    {
        return Cache::remember(self::CACHE_PREFIX.'all_active', self::CACHE_TTL, function () {
            return RegionalPricing::active()
                ->with('activeAdjustments')
                ->orderBy('country_name')
                ->orderBy('region_name')
                ->get();
        });
    }

    /**
     * Create or update regional pricing.
     */
    public function upsertRegionalPricing(array $data): RegionalPricing
    {
        $regional = RegionalPricing::updateOrCreate(
            [
                'country_code' => strtoupper($data['country_code']),
                'region_code' => $data['region_code'] ?? null,
            ],
            [
                'currency_code' => strtoupper($data['currency_code']),
                'ppp_factor' => $data['ppp_factor'] ?? 1.000,
                'min_hourly_rate' => $data['min_hourly_rate'],
                'max_hourly_rate' => $data['max_hourly_rate'],
                'platform_fee_rate' => $data['platform_fee_rate'] ?? 15.00,
                'worker_fee_rate' => $data['worker_fee_rate'] ?? 5.00,
                'tier_adjustments' => $data['tier_adjustments'] ?? RegionalPricing::DEFAULT_TIER_ADJUSTMENTS,
                'country_name' => $data['country_name'] ?? null,
                'region_name' => $data['region_name'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]
        );

        $this->clearCache();

        return $regional;
    }

    /**
     * Delete regional pricing.
     */
    public function deleteRegionalPricing(int $id): bool
    {
        $regional = RegionalPricing::findOrFail($id);
        $deleted = $regional->delete();

        if ($deleted) {
            $this->clearCache();
        }

        return $deleted;
    }

    /**
     * Create a price adjustment.
     */
    public function createPriceAdjustment(array $data): PriceAdjustment
    {
        $adjustment = PriceAdjustment::create($data);

        $this->clearCache();

        return $adjustment;
    }

    /**
     * Update a price adjustment.
     */
    public function updatePriceAdjustment(int $id, array $data): PriceAdjustment
    {
        $adjustment = PriceAdjustment::findOrFail($id);
        $adjustment->update($data);

        $this->clearCache();

        return $adjustment->fresh();
    }

    /**
     * Delete a price adjustment.
     */
    public function deletePriceAdjustment(int $id): bool
    {
        $adjustment = PriceAdjustment::findOrFail($id);
        $deleted = $adjustment->delete();

        if ($deleted) {
            $this->clearCache();
        }

        return $deleted;
    }

    /**
     * Bulk import regional pricing from array.
     */
    public function bulkImport(array $regions): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($regions as $index => $region) {
                try {
                    $exists = RegionalPricing::where('country_code', strtoupper($region['country_code'] ?? $region['country'] ?? ''))
                        ->where('region_code', $region['region_code'] ?? null)
                        ->exists();

                    $this->upsertRegionalPricing([
                        'country_code' => $region['country_code'] ?? $region['country'],
                        'region_code' => $region['region_code'] ?? null,
                        'currency_code' => $region['currency_code'] ?? $region['currency'],
                        'ppp_factor' => $region['ppp_factor'] ?? $region['ppp'] ?? 1.000,
                        'min_hourly_rate' => $region['min_hourly_rate'] ?? $region['min_rate'],
                        'max_hourly_rate' => $region['max_hourly_rate'] ?? $region['max_rate'],
                        'platform_fee_rate' => $region['platform_fee_rate'] ?? 15.00,
                        'worker_fee_rate' => $region['worker_fee_rate'] ?? 5.00,
                        'country_name' => $region['country_name'] ?? null,
                        'region_name' => $region['region_name'] ?? null,
                        'is_active' => $region['is_active'] ?? true,
                    ]);

                    if ($exists) {
                        $results['updated']++;
                    } else {
                        $results['created']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Row {$index}: ".$e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = 'Bulk import failed: '.$e->getMessage();
        }

        return $results;
    }

    /**
     * Export regional pricing to array.
     */
    public function exportRegionalPricing(): array
    {
        return RegionalPricing::with('priceAdjustments')
            ->get()
            ->map(fn ($r) => [
                'country_code' => $r->country_code,
                'region_code' => $r->region_code,
                'currency_code' => $r->currency_code,
                'ppp_factor' => $r->ppp_factor,
                'min_hourly_rate' => $r->min_hourly_rate,
                'max_hourly_rate' => $r->max_hourly_rate,
                'platform_fee_rate' => $r->platform_fee_rate,
                'worker_fee_rate' => $r->worker_fee_rate,
                'tier_adjustments' => $r->tier_adjustments,
                'country_name' => $r->country_name,
                'region_name' => $r->region_name,
                'is_active' => $r->is_active,
                'adjustments_count' => $r->priceAdjustments->count(),
            ])
            ->toArray();
    }

    /**
     * Clear all regional pricing cache.
     */
    public function clearCache(): void
    {
        // Clear specific keys
        Cache::forget(self::CACHE_PREFIX.'all_active');
        Cache::forget(self::CACHE_PREFIX.'analytics');

        // Clear location-specific cache (this would need cache tags in production)
        $regions = RegionalPricing::all();
        foreach ($regions as $region) {
            Cache::forget(self::CACHE_PREFIX."location_{$region->country_code}_{$region->region_code}");
        }
    }

    /**
     * Get the user's country code.
     */
    protected function getUserCountry(User $user): string
    {
        // Try to get from worker profile
        if ($user->role === 'worker' && $user->workerProfile) {
            return $user->workerProfile->country ?? config('regional_pricing.default_country', 'US');
        }

        // Try to get from business profile
        if ($user->role === 'business' && $user->businessProfile) {
            return $user->businessProfile->country ?? config('regional_pricing.default_country', 'US');
        }

        // Try to get from agency profile
        if ($user->role === 'agency' && $user->agencyProfile) {
            return $user->agencyProfile->country ?? config('regional_pricing.default_country', 'US');
        }

        return config('regional_pricing.default_country', 'US');
    }

    /**
     * Get the user's region code.
     */
    protected function getUserRegion(User $user): ?string
    {
        // Try to get from worker profile
        if ($user->role === 'worker' && $user->workerProfile) {
            return $user->workerProfile->state ?? null;
        }

        // Try to get from business profile
        if ($user->role === 'business' && $user->businessProfile) {
            return $user->businessProfile->state ?? null;
        }

        // Try to get from agency profile
        if ($user->role === 'agency' && $user->agencyProfile) {
            return $user->agencyProfile->state ?? null;
        }

        return null;
    }

    /**
     * Get default pricing configuration.
     */
    protected function getDefaultPricing(): RegionalPricing
    {
        $defaultCountry = config('regional_pricing.default_country', 'US');

        $regional = RegionalPricing::findForLocation($defaultCountry);

        if ($regional) {
            return $regional;
        }

        // Create a temporary default if none exists
        return new RegionalPricing([
            'country_code' => $defaultCountry,
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'tier_adjustments' => RegionalPricing::DEFAULT_TIER_ADJUSTMENTS,
            'is_active' => true,
        ]);
    }

    /**
     * Convert World Bank 3-letter country code to 2-letter ISO code.
     */
    protected function convertCountryCode(string $code): ?string
    {
        $mapping = [
            'USA' => 'US', 'GBR' => 'GB', 'CAN' => 'CA', 'AUS' => 'AU',
            'DEU' => 'DE', 'FRA' => 'FR', 'IND' => 'IN', 'PHL' => 'PH',
            'NGA' => 'NG', 'ZAF' => 'ZA', 'BRA' => 'BR', 'MEX' => 'MX',
            'JPN' => 'JP', 'KOR' => 'KR', 'CHN' => 'CN', 'SGP' => 'SG',
            'NLD' => 'NL', 'BEL' => 'BE', 'ESP' => 'ES', 'ITA' => 'IT',
            'POL' => 'PL', 'SWE' => 'SE', 'NOR' => 'NO', 'DNK' => 'DK',
            'FIN' => 'FI', 'IRL' => 'IE', 'AUT' => 'AT', 'CHE' => 'CH',
            'NZL' => 'NZ', 'ARG' => 'AR', 'CHL' => 'CL', 'COL' => 'CO',
            'PER' => 'PE', 'ARE' => 'AE', 'SAU' => 'SA', 'EGY' => 'EG',
            'KEN' => 'KE', 'GHA' => 'GH', 'TZA' => 'TZ', 'UGA' => 'UG',
        ];

        return $mapping[$code] ?? null;
    }
}
