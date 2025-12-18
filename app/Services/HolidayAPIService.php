<?php

namespace App\Services;

use App\Models\PublicHoliday;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching holiday data from external APIs
 *
 * Primary: Nager.Date API (free, no API key required)
 * Backup: Calendarific API (requires API key)
 */
class HolidayAPIService
{
    /**
     * Nager.Date API base URL
     */
    protected const NAGER_API_BASE = 'https://date.nager.at/api/v3';

    /**
     * Calendarific API base URL
     */
    protected const CALENDARIFIC_API_BASE = 'https://calendarific.com/api/v2';

    /**
     * Cache TTL in seconds (24 hours)
     */
    protected const CACHE_TTL = 86400;

    /**
     * Countries supported by Nager.Date API
     *
     * @var array<string>
     */
    protected array $nagerSupportedCountries = [
        'AD', 'AL', 'AR', 'AT', 'AU', 'AX', 'BA', 'BB', 'BE', 'BG', 'BJ', 'BO', 'BR', 'BS', 'BW',
        'BY', 'BZ', 'CA', 'CH', 'CL', 'CN', 'CO', 'CR', 'CU', 'CY', 'CZ', 'DE', 'DK', 'DO', 'EC',
        'EE', 'EG', 'ES', 'FI', 'FO', 'FR', 'GA', 'GB', 'GD', 'GI', 'GL', 'GM', 'GR', 'GT', 'GY',
        'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IM', 'IS', 'IT', 'JE', 'JM', 'JP', 'KE', 'KR', 'LI',
        'LS', 'LT', 'LU', 'LV', 'MA', 'MC', 'MD', 'ME', 'MG', 'MK', 'MN', 'MT', 'MX', 'MZ', 'NA',
        'NE', 'NG', 'NI', 'NL', 'NO', 'NZ', 'PA', 'PE', 'PG', 'PH', 'PL', 'PR', 'PT', 'PY', 'RO',
        'RS', 'RU', 'RW', 'SE', 'SG', 'SI', 'SJ', 'SK', 'SM', 'SR', 'SV', 'TN', 'TR', 'UA', 'US',
        'UY', 'VA', 'VE', 'VN', 'ZA', 'ZW',
    ];

    /**
     * Calendarific API key
     */
    protected ?string $calendarificApiKey;

    public function __construct()
    {
        $this->calendarificApiKey = config('services.calendarific.api_key');
    }

    /**
     * Fetch holidays from the best available API
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     * @param  int  $year  The year to fetch holidays for
     * @return array<int, array{
     *     country_code: string,
     *     region_code: string|null,
     *     name: string,
     *     local_name: string|null,
     *     date: string,
     *     is_national: bool,
     *     is_observed: bool,
     *     type: string,
     *     surge_multiplier: float
     * }>
     */
    public function fetchHolidays(string $countryCode, int $year): array
    {
        $countryCode = strtoupper($countryCode);
        $cacheKey = "holidays_api_{$countryCode}_{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($countryCode, $year) {
            // Try Nager.Date first (free, no API key)
            if ($this->isNagerSupported($countryCode)) {
                try {
                    $holidays = $this->fetchFromNager($countryCode, $year);
                    if (! empty($holidays)) {
                        return $holidays;
                    }
                } catch (Exception $e) {
                    Log::warning("Nager.Date API failed for {$countryCode}/{$year}: ".$e->getMessage());
                }
            }

            // Fall back to Calendarific if API key is configured
            if ($this->calendarificApiKey) {
                try {
                    return $this->fetchFromCalendarific($countryCode, $year);
                } catch (Exception $e) {
                    Log::warning("Calendarific API failed for {$countryCode}/{$year}: ".$e->getMessage());
                }
            }

            Log::error("All holiday APIs failed for {$countryCode}/{$year}");

            return [];
        });
    }

    /**
     * Fetch holidays from Nager.Date API
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchFromNager(string $countryCode, int $year): array
    {
        $url = self::NAGER_API_BASE."/PublicHolidays/{$year}/{$countryCode}";

        $response = Http::timeout(30)
            ->retry(3, 1000)
            ->get($url);

        if (! $response->successful()) {
            throw new Exception("Nager.Date API returned status {$response->status()}");
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new Exception('Nager.Date API returned invalid data');
        }

        return array_map(function ($holiday) use ($countryCode) {
            return $this->normalizeNagerHoliday($holiday, $countryCode);
        }, $data);
    }

    /**
     * Fetch holidays from Calendarific API
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchFromCalendarific(string $countryCode, int $year): array
    {
        if (! $this->calendarificApiKey) {
            throw new Exception('Calendarific API key not configured');
        }

        $url = self::CALENDARIFIC_API_BASE.'/holidays';

        $response = Http::timeout(30)
            ->retry(3, 1000)
            ->get($url, [
                'api_key' => $this->calendarificApiKey,
                'country' => $countryCode,
                'year' => $year,
            ]);

        if (! $response->successful()) {
            throw new Exception("Calendarific API returned status {$response->status()}");
        }

        $data = $response->json();

        if (! isset($data['response']['holidays']) || ! is_array($data['response']['holidays'])) {
            throw new Exception('Calendarific API returned invalid data');
        }

        return array_map(function ($holiday) use ($countryCode) {
            return $this->normalizeCalendarificHoliday($holiday, $countryCode);
        }, $data['response']['holidays']);
    }

    /**
     * Normalize Nager.Date holiday data to our format
     *
     * @param  array<string, mixed>  $holiday
     * @return array<string, mixed>
     */
    protected function normalizeNagerHoliday(array $holiday, string $countryCode): array
    {
        // Determine holiday type based on Nager.Date types
        $type = $this->mapNagerType($holiday['types'] ?? []);

        // Determine if it's observed (fixed or actual day off)
        $isObserved = ! in_array('Optional', $holiday['types'] ?? []);

        // Calculate surge multiplier based on type
        $surgeMultiplier = $this->calculateSurgeMultiplier($type);

        return [
            'country_code' => $countryCode,
            'region_code' => $this->extractRegionCode($holiday),
            'name' => $holiday['name'] ?? 'Unknown Holiday',
            'local_name' => $holiday['localName'] ?? null,
            'date' => $holiday['date'] ?? null,
            'is_national' => $holiday['global'] ?? true,
            'is_observed' => $isObserved,
            'type' => $type,
            'surge_multiplier' => $surgeMultiplier,
            'shifts_restricted' => false,
        ];
    }

    /**
     * Normalize Calendarific holiday data to our format
     *
     * @param  array<string, mixed>  $holiday
     * @return array<string, mixed>
     */
    protected function normalizeCalendarificHoliday(array $holiday, string $countryCode): array
    {
        // Determine holiday type based on Calendarific type
        $type = $this->mapCalendarificType($holiday['type'] ?? []);

        // Determine if it's a national holiday
        $isNational = in_array('National holiday', $holiday['type'] ?? []);

        // Calculate surge multiplier based on type
        $surgeMultiplier = $this->calculateSurgeMultiplier($type);

        // Extract date from nested structure
        $date = $holiday['date']['iso'] ?? null;

        return [
            'country_code' => $countryCode,
            'region_code' => $this->extractCalendarificRegion($holiday),
            'name' => $holiday['name'] ?? 'Unknown Holiday',
            'local_name' => $holiday['name'] ?? null,
            'date' => $date,
            'is_national' => $isNational,
            'is_observed' => $isNational || in_array('Common local holiday', $holiday['type'] ?? []),
            'type' => $type,
            'surge_multiplier' => $surgeMultiplier,
            'shifts_restricted' => false,
        ];
    }

    /**
     * Map Nager.Date holiday types to our type system
     *
     * @param  array<string>  $types
     */
    protected function mapNagerType(array $types): string
    {
        if (in_array('Bank', $types)) {
            return PublicHoliday::TYPE_BANK;
        }
        if (in_array('Observance', $types)) {
            return PublicHoliday::TYPE_OBSERVANCE;
        }
        if (in_array('Optional', $types)) {
            return PublicHoliday::TYPE_CULTURAL;
        }

        return PublicHoliday::TYPE_PUBLIC;
    }

    /**
     * Map Calendarific holiday types to our type system
     *
     * @param  array<string>  $types
     */
    protected function mapCalendarificType(array $types): string
    {
        foreach ($types as $type) {
            $typeLower = strtolower($type);
            if (str_contains($typeLower, 'bank')) {
                return PublicHoliday::TYPE_BANK;
            }
            if (str_contains($typeLower, 'religious')) {
                return PublicHoliday::TYPE_RELIGIOUS;
            }
            if (str_contains($typeLower, 'observance')) {
                return PublicHoliday::TYPE_OBSERVANCE;
            }
        }

        if (in_array('National holiday', $types)) {
            return PublicHoliday::TYPE_PUBLIC;
        }

        return PublicHoliday::TYPE_CULTURAL;
    }

    /**
     * Extract region code from Nager.Date holiday data
     *
     * @param  array<string, mixed>  $holiday
     */
    protected function extractRegionCode(array $holiday): ?string
    {
        $counties = $holiday['counties'] ?? null;

        if (empty($counties)) {
            return null;
        }

        // If multiple counties, this is a regional holiday
        // Return the first county code (stripped of country prefix)
        if (is_array($counties) && count($counties) === 1) {
            $county = $counties[0];
            // Format is usually "US-CA" or "DE-BY"
            $parts = explode('-', $county);

            return count($parts) > 1 ? $parts[1] : $county;
        }

        // Multiple counties - still return first but mark as regional
        if (is_array($counties) && ! empty($counties)) {
            $county = $counties[0];
            $parts = explode('-', $county);

            return count($parts) > 1 ? $parts[1] : $county;
        }

        return null;
    }

    /**
     * Extract region code from Calendarific holiday data
     *
     * @param  array<string, mixed>  $holiday
     */
    protected function extractCalendarificRegion(array $holiday): ?string
    {
        $states = $holiday['states'] ?? null;

        if (empty($states) || $states === 'All') {
            return null;
        }

        if (is_array($states) && ! empty($states)) {
            return $states[0]['iso'] ?? $states[0]['abbrev'] ?? null;
        }

        return null;
    }

    /**
     * Calculate surge multiplier based on holiday type
     */
    protected function calculateSurgeMultiplier(string $type): float
    {
        return match ($type) {
            PublicHoliday::TYPE_PUBLIC => 1.50,
            PublicHoliday::TYPE_BANK => 1.50,
            PublicHoliday::TYPE_RELIGIOUS => 1.40,
            PublicHoliday::TYPE_CULTURAL => 1.25,
            PublicHoliday::TYPE_OBSERVANCE => 1.15,
            default => 1.50,
        };
    }

    /**
     * Check if a country is supported by Nager.Date API
     */
    public function isNagerSupported(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), $this->nagerSupportedCountries);
    }

    /**
     * Get list of available countries from Nager.Date API
     *
     * @return array<array{countryCode: string, name: string}>
     */
    public function getAvailableCountries(): array
    {
        $cacheKey = 'nager_available_countries';

        return Cache::remember($cacheKey, self::CACHE_TTL * 7, function () {
            try {
                $response = Http::timeout(30)
                    ->retry(3, 1000)
                    ->get(self::NAGER_API_BASE.'/AvailableCountries');

                if ($response->successful()) {
                    return $response->json() ?? [];
                }
            } catch (Exception $e) {
                Log::warning('Failed to fetch available countries: '.$e->getMessage());
            }

            // Return our static list as fallback
            return array_map(function ($code, $name) {
                return ['countryCode' => $code, 'name' => $name];
            }, array_keys(PublicHoliday::getSupportedCountries()), PublicHoliday::getSupportedCountries());
        });
    }

    /**
     * Check if holidays exist for a country/year
     */
    public function hasHolidays(string $countryCode, int $year): bool
    {
        $cacheKey = "holidays_exist_{$countryCode}_{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($countryCode, $year) {
            $holidays = $this->fetchHolidays($countryCode, $year);

            return ! empty($holidays);
        });
    }

    /**
     * Clear cached holidays for a country/year
     */
    public function clearCache(string $countryCode, int $year): void
    {
        $cacheKey = "holidays_api_{$countryCode}_{$year}";
        Cache::forget($cacheKey);
    }

    /**
     * Clear all cached holiday data
     */
    public function clearAllCache(): void
    {
        // Clear for all supported countries and recent years
        $years = [now()->year - 1, now()->year, now()->year + 1];

        foreach (PublicHoliday::getSupportedCountries() as $code => $name) {
            foreach ($years as $year) {
                $this->clearCache($code, $year);
            }
        }

        Cache::forget('nager_available_countries');
    }
}
