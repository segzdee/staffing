<?php

namespace App\Http\Controllers;

use App\Models\PublicHoliday;
use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * GLO-007: Holiday Calendar Controller
 *
 * Provides public-facing endpoints for viewing holiday calendars
 * and checking holiday status for shift planning.
 */
class HolidayController extends Controller
{
    public function __construct(
        protected HolidayService $holidayService
    ) {}

    /**
     * Display the holiday calendar view
     */
    public function index(Request $request): View
    {
        $year = (int) $request->get('year', now()->year);
        $country = $request->get('country', 'US');

        // Validate country
        $supportedCountries = PublicHoliday::getSupportedCountries();
        if (! isset($supportedCountries[$country])) {
            $country = 'US';
        }

        $calendarData = $this->holidayService->getCalendarView($country, $year);
        $upcomingHolidays = $this->holidayService->getUpcomingHolidays($country, 60);

        return view('holidays.index', [
            'holidays' => $calendarData['holidays'],
            'byMonth' => $calendarData['by_month'],
            'stats' => $calendarData['stats'],
            'upcomingHolidays' => $upcomingHolidays,
            'supportedCountries' => $supportedCountries,
            'selectedCountry' => $country,
            'selectedYear' => $year,
            'availableYears' => [now()->year - 1, now()->year, now()->year + 1],
        ]);
    }

    /**
     * API: Get holidays for a country/year
     */
    public function getHolidays(Request $request): JsonResponse
    {
        $request->validate([
            'country' => 'required|string|size:2',
            'year' => 'required|integer|min:2020|max:2030',
            'region' => 'nullable|string',
        ]);

        $country = strtoupper($request->country);
        $year = (int) $request->year;
        $region = $request->region;

        $holidays = $this->holidayService->getHolidays($country, $year, $region);

        return response()->json([
            'success' => true,
            'country' => $country,
            'year' => $year,
            'region' => $region,
            'count' => $holidays->count(),
            'holidays' => $holidays->map(function (PublicHoliday $holiday) {
                return [
                    'id' => $holiday->id,
                    'name' => $holiday->name,
                    'local_name' => $holiday->local_name,
                    'date' => $holiday->date->toDateString(),
                    'formatted_date' => $holiday->formatted_date,
                    'type' => $holiday->type,
                    'type_label' => $holiday->type_label,
                    'is_national' => $holiday->is_national,
                    'is_observed' => $holiday->is_observed,
                    'surge_multiplier' => $holiday->surge_multiplier,
                    'surge_percentage' => $holiday->surge_percentage,
                ];
            }),
        ]);
    }

    /**
     * API: Check if a specific date is a holiday
     */
    public function checkDate(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'country' => 'required|string|size:2',
            'region' => 'nullable|string',
        ]);

        $date = Carbon::parse($request->date);
        $country = strtoupper($request->country);
        $region = $request->region;

        $isHoliday = $this->holidayService->isHoliday($date, $country, $region);
        $holidayInfo = null;
        $surgeMultiplier = 1.0;

        if ($isHoliday) {
            $holiday = $this->holidayService->getHolidayInfo($date, $country, $region);
            if ($holiday) {
                $surgeMultiplier = (float) $holiday->surge_multiplier;
                $holidayInfo = [
                    'id' => $holiday->id,
                    'name' => $holiday->display_name,
                    'type' => $holiday->type,
                    'type_label' => $holiday->type_label,
                    'surge_multiplier' => $surgeMultiplier,
                    'surge_percentage' => $holiday->surge_percentage,
                    'shifts_restricted' => $holiday->shifts_restricted,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'country' => $country,
            'region' => $region,
            'is_holiday' => $isHoliday,
            'surge_multiplier' => $surgeMultiplier,
            'holiday' => $holidayInfo,
        ]);
    }

    /**
     * API: Get upcoming holidays for a country
     */
    public function upcoming(Request $request): JsonResponse
    {
        $request->validate([
            'country' => 'required|string|size:2',
            'days' => 'nullable|integer|min:1|max:365',
            'region' => 'nullable|string',
        ]);

        $country = strtoupper($request->country);
        $days = (int) $request->get('days', 30);
        $region = $request->region;

        $holidays = $this->holidayService->getUpcomingHolidays($country, $days, $region);

        return response()->json([
            'success' => true,
            'country' => $country,
            'days' => $days,
            'count' => $holidays->count(),
            'holidays' => $holidays->map(function (PublicHoliday $holiday) {
                return [
                    'id' => $holiday->id,
                    'name' => $holiday->display_name,
                    'date' => $holiday->date->toDateString(),
                    'formatted_date' => $holiday->short_date,
                    'days_until' => $holiday->daysUntil(),
                    'type' => $holiday->type,
                    'surge_multiplier' => $holiday->surge_multiplier,
                ];
            }),
        ]);
    }

    /**
     * API: Get holidays for a date range (for shift planning calendar)
     */
    public function forDateRange(Request $request): JsonResponse
    {
        $request->validate([
            'country' => 'required|string|size:2',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'region' => 'nullable|string',
        ]);

        $country = strtoupper($request->country);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $region = $request->region;

        $holidays = $this->holidayService->getHolidaysByDateRange($country, $startDate, $endDate, $region);

        // Create a lookup by date for calendar integration
        $byDate = $holidays->keyBy(fn ($h) => $h->date->toDateString())
            ->map(function (PublicHoliday $holiday) {
                return [
                    'name' => $holiday->display_name,
                    'type' => $holiday->type,
                    'surge_multiplier' => $holiday->surge_multiplier,
                ];
            });

        return response()->json([
            'success' => true,
            'country' => $country,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'count' => $holidays->count(),
            'holidays' => $holidays->map(function (PublicHoliday $holiday) {
                return [
                    'id' => $holiday->id,
                    'name' => $holiday->display_name,
                    'date' => $holiday->date->toDateString(),
                    'type' => $holiday->type,
                    'surge_multiplier' => $holiday->surge_multiplier,
                ];
            }),
            'by_date' => $byDate,
        ]);
    }

    /**
     * API: Get list of supported countries
     */
    public function countries(): JsonResponse
    {
        $countries = PublicHoliday::getSupportedCountries();

        return response()->json([
            'success' => true,
            'count' => count($countries),
            'countries' => collect($countries)->map(function ($name, $code) {
                return [
                    'code' => $code,
                    'name' => $name,
                ];
            })->values(),
        ]);
    }

    /**
     * API: Get next holiday for a country
     */
    public function next(Request $request): JsonResponse
    {
        $request->validate([
            'country' => 'required|string|size:2',
            'region' => 'nullable|string',
        ]);

        $country = strtoupper($request->country);
        $region = $request->region;

        $holiday = $this->holidayService->getNextHoliday($country, $region);

        if (! $holiday) {
            return response()->json([
                'success' => true,
                'country' => $country,
                'next_holiday' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'country' => $country,
            'next_holiday' => [
                'id' => $holiday->id,
                'name' => $holiday->display_name,
                'date' => $holiday->date->toDateString(),
                'formatted_date' => $holiday->formatted_date,
                'days_until' => $holiday->daysUntil(),
                'type' => $holiday->type,
                'type_label' => $holiday->type_label,
                'surge_multiplier' => $holiday->surge_multiplier,
            ],
        ]);
    }

    /**
     * API: Search holidays by name
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'country' => 'nullable|string|size:2',
            'year' => 'nullable|integer|min:2020|max:2030',
        ]);

        $query = $request->query('query');
        $country = $request->country ? strtoupper($request->country) : null;
        $year = $request->year ? (int) $request->year : null;

        $holidays = $this->holidayService->searchHolidays($query, $country, $year);

        return response()->json([
            'success' => true,
            'query' => $query,
            'count' => $holidays->count(),
            'holidays' => $holidays->map(function (PublicHoliday $holiday) {
                return [
                    'id' => $holiday->id,
                    'name' => $holiday->name,
                    'local_name' => $holiday->local_name,
                    'date' => $holiday->date->toDateString(),
                    'country_code' => $holiday->country_code,
                    'type' => $holiday->type,
                ];
            }),
        ]);
    }
}
