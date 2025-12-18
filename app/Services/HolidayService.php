<?php

namespace App\Services;

use App\Models\HolidayCalendar;
use App\Models\PublicHoliday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing public holidays and custom business calendars
 */
class HolidayService
{
    /**
     * Cache TTL in seconds (1 hour for holiday lookups)
     */
    protected const CACHE_TTL = 3600;

    protected HolidayAPIService $apiService;

    public function __construct(HolidayAPIService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * Get holidays for a country and year
     *
     * @param  string  $country  ISO 3166-1 alpha-2 country code
     * @param  string|null  $region  State/province code
     * @return Collection<int, PublicHoliday>
     */
    public function getHolidays(string $country, int $year, ?string $region = null): Collection
    {
        $country = strtoupper($country);
        $cacheKey = "holidays_{$country}_{$year}".($region ? "_{$region}" : '');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($country, $year, $region) {
            return PublicHoliday::query()
                ->forRegion($country, $region)
                ->forYear($year)
                ->orderBy('date')
                ->get();
        });
    }

    /**
     * Check if a date is a holiday
     */
    public function isHoliday(Carbon $date, string $country, ?string $region = null): bool
    {
        $country = strtoupper($country);
        $cacheKey = "is_holiday_{$country}_{$date->toDateString()}".($region ? "_{$region}" : '');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date, $country, $region) {
            return PublicHoliday::query()
                ->forRegion($country, $region)
                ->forDate($date)
                ->observed()
                ->exists();
        });
    }

    /**
     * Get holiday info for a specific date
     */
    public function getHolidayInfo(Carbon $date, string $country, ?string $region = null): ?PublicHoliday
    {
        $country = strtoupper($country);
        $cacheKey = "holiday_info_{$country}_{$date->toDateString()}".($region ? "_{$region}" : '');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($date, $country, $region) {
            return PublicHoliday::query()
                ->forRegion($country, $region)
                ->forDate($date)
                ->observed()
                ->first();
        });
    }

    /**
     * Get surge multiplier for a date
     */
    public function getSurgeMultiplier(Carbon $date, string $country, ?string $region = null): float
    {
        $holiday = $this->getHolidayInfo($date, $country, $region);

        if (! $holiday) {
            return 1.0;
        }

        return (float) $holiday->surge_multiplier;
    }

    /**
     * Get surge multiplier using business calendar if available
     */
    public function getSurgeMultiplierForBusiness(Carbon $date, User $business, ?string $region = null): float
    {
        $calendar = $this->getBusinessCalendar($business);

        if ($calendar) {
            return $calendar->getSurgeMultiplier($date, $region);
        }

        // Get country from business profile
        $countryCode = $business->businessProfile?->country ?? 'US';

        return $this->getSurgeMultiplier($date, $countryCode, $region);
    }

    /**
     * Check if a date is a holiday for a business
     */
    public function isHolidayForBusiness(Carbon $date, User $business, ?string $region = null): bool
    {
        $calendar = $this->getBusinessCalendar($business);

        if ($calendar) {
            return $calendar->isHoliday($date, $region);
        }

        // Get country from business profile
        $countryCode = $business->businessProfile?->country ?? 'US';

        return $this->isHoliday($date, $countryCode, $region);
    }

    /**
     * Fetch holidays from external API
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchHolidaysFromAPI(string $country, int $year): array
    {
        return $this->apiService->fetchHolidays($country, $year);
    }

    /**
     * Sync holidays from API to database
     *
     * @return int Number of holidays synced
     */
    public function syncHolidays(string $country, int $year): int
    {
        $country = strtoupper($country);

        try {
            $holidays = $this->apiService->fetchHolidays($country, $year);

            if (empty($holidays)) {
                Log::warning("No holidays returned from API for {$country}/{$year}");

                return 0;
            }

            $count = 0;

            DB::transaction(function () use ($holidays, &$count) {
                foreach ($holidays as $holidayData) {
                    if (empty($holidayData['date'])) {
                        continue;
                    }

                    PublicHoliday::createOrUpdateFromAPI($holidayData);
                    $count++;
                }
            });

            // Clear cache for this country/year
            $this->clearHolidayCache($country, $year);

            Log::info("Synced {$count} holidays for {$country}/{$year}");

            return $count;
        } catch (\Exception $e) {
            Log::error("Failed to sync holidays for {$country}/{$year}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync holidays for multiple countries
     *
     * @param  array<string>  $countries
     * @return array<string, int>
     */
    public function syncMultipleCountries(array $countries, int $year): array
    {
        $results = [];

        foreach ($countries as $country) {
            try {
                $results[$country] = $this->syncHolidays($country, $year);
            } catch (\Exception $e) {
                $results[$country] = 0;
                Log::error("Failed to sync {$country}: ".$e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Get upcoming holidays
     *
     * @return Collection<int, PublicHoliday>
     */
    public function getUpcomingHolidays(string $country, int $days = 30, ?string $region = null): Collection
    {
        $country = strtoupper($country);
        $cacheKey = "upcoming_holidays_{$country}_{$days}".($region ? "_{$region}" : '');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($country, $days, $region) {
            return PublicHoliday::query()
                ->forRegion($country, $region)
                ->upcoming($days)
                ->observed()
                ->get();
        });
    }

    /**
     * Get holidays between two dates
     *
     * @return Collection<int, PublicHoliday>
     */
    public function getHolidaysByDateRange(string $country, Carbon $start, Carbon $end, ?string $region = null): Collection
    {
        $country = strtoupper($country);

        return PublicHoliday::query()
            ->forRegion($country, $region)
            ->betweenDates($start, $end)
            ->observed()
            ->get();
    }

    /**
     * Get holidays in date range that affect shifts (for UI display)
     *
     * @return Collection<int, array{date: string, name: string, surge_multiplier: float, type: string}>
     */
    public function getHolidaysForShiftRange(string $country, Carbon $start, Carbon $end, ?string $region = null): Collection
    {
        return $this->getHolidaysByDateRange($country, $start, $end, $region)
            ->map(function (PublicHoliday $holiday) {
                return [
                    'date' => $holiday->date->toDateString(),
                    'name' => $holiday->display_name,
                    'surge_multiplier' => $holiday->surge_multiplier,
                    'surge_percentage' => $holiday->surge_percentage,
                    'type' => $holiday->type,
                    'type_label' => $holiday->type_label,
                ];
            });
    }

    /**
     * Create a custom calendar for a business
     *
     * @param  array{name: string, country_code: string, included_holidays?: array<int>, excluded_holidays?: array<int>, custom_dates?: array<array{date: string, name: string, surge_multiplier?: float}>}  $data
     */
    public function createCustomCalendar(User $business, array $data): HolidayCalendar
    {
        return HolidayCalendar::create([
            'business_id' => $business->id,
            'name' => $data['name'],
            'country_code' => strtoupper($data['country_code']),
            'included_holidays' => $data['included_holidays'] ?? null,
            'excluded_holidays' => $data['excluded_holidays'] ?? null,
            'custom_dates' => $data['custom_dates'] ?? null,
            'is_default' => false,
        ]);
    }

    /**
     * Update a custom calendar
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCustomCalendar(HolidayCalendar $calendar, array $data): HolidayCalendar
    {
        $calendar->update($data);

        return $calendar->fresh();
    }

    /**
     * Get business calendar
     */
    public function getBusinessCalendar(User $business): ?HolidayCalendar
    {
        // Get the business's country from their profile
        $countryCode = $business->businessProfile?->country ?? 'US';

        return HolidayCalendar::query()
            ->forBusiness($business->id)
            ->forCountry($countryCode)
            ->first();
    }

    /**
     * Get or create a calendar for a business
     */
    public function getOrCreateBusinessCalendar(User $business, ?string $countryCode = null): HolidayCalendar
    {
        $countryCode = $countryCode ?? $business->businessProfile?->country ?? 'US';

        return HolidayCalendar::getOrCreateForBusiness($business, $countryCode);
    }

    /**
     * Get the default calendar for a country
     */
    public function getDefaultCalendar(string $countryCode): ?HolidayCalendar
    {
        return HolidayCalendar::query()
            ->defaults()
            ->forCountry($countryCode)
            ->first();
    }

    /**
     * Create or get default calendar for a country
     */
    public function getOrCreateDefaultCalendar(string $countryCode): HolidayCalendar
    {
        $existing = $this->getDefaultCalendar($countryCode);

        if ($existing) {
            return $existing;
        }

        return HolidayCalendar::createDefaultForCountry($countryCode);
    }

    /**
     * Get all holidays for displaying in a calendar view
     *
     * @return array<string, array{holidays: Collection, stats: array}>
     */
    public function getCalendarView(string $country, int $year, ?string $region = null): array
    {
        $holidays = $this->getHolidays($country, $year, $region);

        // Group by month
        $byMonth = $holidays->groupBy(function (PublicHoliday $holiday) {
            return $holiday->date->format('Y-m');
        });

        $stats = [
            'total' => $holidays->count(),
            'national' => $holidays->where('is_national', true)->count(),
            'regional' => $holidays->where('is_national', false)->count(),
            'by_type' => $holidays->groupBy('type')->map->count()->toArray(),
        ];

        return [
            'holidays' => $holidays,
            'by_month' => $byMonth,
            'stats' => $stats,
        ];
    }

    /**
     * Get holiday density for date range (for scheduling recommendations)
     *
     * @return array<string, float>
     */
    public function getHolidayDensity(string $country, Carbon $start, Carbon $end, ?string $region = null): array
    {
        $holidays = $this->getHolidaysByDateRange($country, $start, $end, $region);
        $totalDays = $start->diffInDays($end) + 1;

        $density = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $weekKey = $current->format('Y-W');
            if (! isset($density[$weekKey])) {
                $density[$weekKey] = 0;
            }

            $isHoliday = $holidays->contains(function (PublicHoliday $holiday) use ($current) {
                return $holiday->date->isSameDay($current);
            });

            if ($isHoliday) {
                $density[$weekKey]++;
            }

            $current->addDay();
        }

        // Calculate percentage for each week
        foreach ($density as $week => $count) {
            $density[$week] = round(($count / 7) * 100, 1);
        }

        return $density;
    }

    /**
     * Search holidays by name
     *
     * @return Collection<int, PublicHoliday>
     */
    public function searchHolidays(string $query, ?string $country = null, ?int $year = null): Collection
    {
        $builder = PublicHoliday::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('local_name', 'like', "%{$query}%");
            });

        if ($country) {
            $builder->forCountry($country);
        }

        if ($year) {
            $builder->forYear($year);
        }

        return $builder->orderBy('date')->get();
    }

    /**
     * Clear holiday cache for a country/year
     */
    public function clearHolidayCache(string $country, ?int $year = null): void
    {
        $country = strtoupper($country);

        if ($year) {
            $years = [$year];
        } else {
            $years = [now()->year - 1, now()->year, now()->year + 1];
        }

        foreach ($years as $y) {
            Cache::forget("holidays_{$country}_{$y}");
        }

        // Clear API cache
        $this->apiService->clearCache($country, $year ?? now()->year);
    }

    /**
     * Get supported countries
     *
     * @return array<string, string>
     */
    public function getSupportedCountries(): array
    {
        return PublicHoliday::getSupportedCountries();
    }

    /**
     * Check if a country is supported
     */
    public function isCountrySupported(string $countryCode): bool
    {
        return array_key_exists(strtoupper($countryCode), PublicHoliday::getSupportedCountries());
    }

    /**
     * Get holiday statistics for a country
     *
     * @return array<string, mixed>
     */
    public function getHolidayStatistics(string $country, int $year): array
    {
        $holidays = $this->getHolidays($country, $year);

        return [
            'total' => $holidays->count(),
            'national' => $holidays->where('is_national', true)->count(),
            'regional' => $holidays->where('is_national', false)->count(),
            'observed' => $holidays->where('is_observed', true)->count(),
            'by_type' => $holidays->groupBy('type')->map->count()->toArray(),
            'by_month' => $holidays->groupBy(fn ($h) => $h->date->format('F'))->map->count()->toArray(),
            'avg_surge_multiplier' => round($holidays->avg('surge_multiplier'), 2),
        ];
    }

    /**
     * Add a custom holiday manually
     *
     * @param  array<string, mixed>  $data
     */
    public function addCustomHoliday(array $data): PublicHoliday
    {
        return PublicHoliday::create([
            'country_code' => strtoupper($data['country_code']),
            'region_code' => $data['region_code'] ?? null,
            'name' => $data['name'],
            'local_name' => $data['local_name'] ?? null,
            'date' => $data['date'],
            'is_national' => $data['is_national'] ?? false,
            'is_observed' => $data['is_observed'] ?? true,
            'type' => $data['type'] ?? PublicHoliday::TYPE_CULTURAL,
            'surge_multiplier' => $data['surge_multiplier'] ?? PublicHoliday::DEFAULT_SURGE_MULTIPLIER,
            'shifts_restricted' => $data['shifts_restricted'] ?? false,
        ]);
    }

    /**
     * Update a holiday
     *
     * @param  array<string, mixed>  $data
     */
    public function updateHoliday(PublicHoliday $holiday, array $data): PublicHoliday
    {
        $holiday->update($data);
        $this->clearHolidayCache($holiday->country_code, $holiday->date->year);

        return $holiday->fresh();
    }

    /**
     * Delete a holiday
     */
    public function deleteHoliday(PublicHoliday $holiday): bool
    {
        $this->clearHolidayCache($holiday->country_code, $holiday->date->year);

        return $holiday->delete();
    }

    /**
     * Get next holiday for a country
     */
    public function getNextHoliday(string $country, ?string $region = null): ?PublicHoliday
    {
        return PublicHoliday::query()
            ->forRegion(strtoupper($country), $region)
            ->where('date', '>=', Carbon::today())
            ->observed()
            ->orderBy('date')
            ->first();
    }

    /**
     * Check if shifts are restricted on a date
     */
    public function areShiftsRestricted(Carbon $date, string $country, ?string $region = null): bool
    {
        $holiday = $this->getHolidayInfo($date, $country, $region);

        return $holiday?->shifts_restricted ?? false;
    }
}
