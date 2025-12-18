<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PublicHoliday;
use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * GLO-007: Admin Holiday Management Controller
 *
 * Provides administrative endpoints for managing public holidays,
 * syncing from external APIs, and customizing surge multipliers.
 */
class HolidayController extends Controller
{
    public function __construct(
        protected HolidayService $holidayService
    ) {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display the admin holiday management dashboard
     */
    public function index(Request $request): View
    {
        $year = (int) $request->get('year', now()->year);
        $country = $request->get('country');

        $query = PublicHoliday::query()->forYear($year);

        if ($country) {
            $query->forCountry($country);
        }

        $holidays = $query->orderBy('date')->paginate(50);

        // Get statistics
        $stats = [
            'total' => PublicHoliday::forYear($year)->count(),
            'countries_covered' => PublicHoliday::forYear($year)->distinct('country_code')->count('country_code'),
            'by_type' => PublicHoliday::forYear($year)
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];

        $supportedCountries = PublicHoliday::getSupportedCountries();

        return view('admin.holidays.index', [
            'holidays' => $holidays,
            'stats' => $stats,
            'supportedCountries' => $supportedCountries,
            'selectedCountry' => $country,
            'selectedYear' => $year,
            'availableYears' => [now()->year - 1, now()->year, now()->year + 1, now()->year + 2],
        ]);
    }

    /**
     * Show form to create a new holiday
     */
    public function create(): View
    {
        return view('admin.holidays.create', [
            'supportedCountries' => PublicHoliday::getSupportedCountries(),
            'holidayTypes' => PublicHoliday::getTypes(),
        ]);
    }

    /**
     * Store a new holiday
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'country_code' => 'required|string|size:2',
            'region_code' => 'nullable|string|max:10',
            'name' => 'required|string|max:255',
            'local_name' => 'nullable|string|max:255',
            'date' => 'required|date',
            'is_national' => 'boolean',
            'is_observed' => 'boolean',
            'type' => 'required|in:public,bank,religious,cultural,observance',
            'surge_multiplier' => 'required|numeric|min:1|max:3',
            'shifts_restricted' => 'boolean',
        ]);

        $validated['country_code'] = strtoupper($validated['country_code']);
        $validated['is_national'] = $request->boolean('is_national');
        $validated['is_observed'] = $request->boolean('is_observed');
        $validated['shifts_restricted'] = $request->boolean('shifts_restricted');

        $holiday = $this->holidayService->addCustomHoliday($validated);

        return redirect()->route('admin.holidays.index', ['year' => Carbon::parse($validated['date'])->year])
            ->with('success', "Holiday '{$holiday->name}' created successfully.");
    }

    /**
     * Show form to edit a holiday
     */
    public function edit(PublicHoliday $holiday): View
    {
        return view('admin.holidays.edit', [
            'holiday' => $holiday,
            'supportedCountries' => PublicHoliday::getSupportedCountries(),
            'holidayTypes' => PublicHoliday::getTypes(),
        ]);
    }

    /**
     * Update a holiday
     */
    public function update(Request $request, PublicHoliday $holiday): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'local_name' => 'nullable|string|max:255',
            'date' => 'required|date',
            'is_national' => 'boolean',
            'is_observed' => 'boolean',
            'type' => 'required|in:public,bank,religious,cultural,observance',
            'surge_multiplier' => 'required|numeric|min:1|max:3',
            'shifts_restricted' => 'boolean',
        ]);

        $validated['is_national'] = $request->boolean('is_national');
        $validated['is_observed'] = $request->boolean('is_observed');
        $validated['shifts_restricted'] = $request->boolean('shifts_restricted');

        $this->holidayService->updateHoliday($holiday, $validated);

        return redirect()->route('admin.holidays.index', ['year' => $holiday->date->year])
            ->with('success', "Holiday '{$holiday->name}' updated successfully.");
    }

    /**
     * Delete a holiday
     */
    public function destroy(PublicHoliday $holiday): RedirectResponse
    {
        $name = $holiday->name;
        $year = $holiday->date->year;

        $this->holidayService->deleteHoliday($holiday);

        return redirect()->route('admin.holidays.index', ['year' => $year])
            ->with('success', "Holiday '{$name}' deleted successfully.");
    }

    /**
     * Show sync form
     */
    public function showSync(): View
    {
        $supportedCountries = PublicHoliday::getSupportedCountries();

        // Check which countries already have data
        $countriesWithData = PublicHoliday::forYear(now()->year)
            ->distinct('country_code')
            ->pluck('country_code')
            ->toArray();

        return view('admin.holidays.sync', [
            'supportedCountries' => $supportedCountries,
            'countriesWithData' => $countriesWithData,
            'availableYears' => [now()->year, now()->year + 1],
        ]);
    }

    /**
     * Sync holidays from external API
     */
    public function sync(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'countries' => 'required|array|min:1',
            'countries.*' => 'string|size:2',
            'year' => 'required|integer|min:'.now()->year.'|max:'.(now()->year + 2),
            'force' => 'boolean',
        ]);

        $countries = array_map('strtoupper', $validated['countries']);
        $year = (int) $validated['year'];

        $results = $this->holidayService->syncMultipleCountries($countries, $year);

        $totalSynced = array_sum($results);
        $successCount = count(array_filter($results, fn ($count) => $count > 0));
        $failedCount = count($results) - $successCount;

        $message = "Synced {$totalSynced} holidays for {$successCount} countries.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} countries failed to sync.";
        }

        return redirect()->route('admin.holidays.index', ['year' => $year])
            ->with('success', $message);
    }

    /**
     * API: Sync holidays via AJAX
     */
    public function syncApi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => 'required|string|size:2',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $country = strtoupper($validated['country']);
        $year = (int) $validated['year'];

        try {
            $count = $this->holidayService->syncHolidays($country, $year);

            return response()->json([
                'success' => true,
                'message' => "Synced {$count} holidays for {$country} ({$year})",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync holidays: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update surge multipliers
     */
    public function bulkUpdateSurge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'country_code' => 'required|string|size:2',
            'type' => 'required|in:public,bank,religious,cultural,observance',
            'surge_multiplier' => 'required|numeric|min:1|max:3',
        ]);

        $updated = PublicHoliday::query()
            ->where('country_code', strtoupper($validated['country_code']))
            ->where('type', $validated['type'])
            ->update(['surge_multiplier' => $validated['surge_multiplier']]);

        // Clear cache
        $this->holidayService->clearHolidayCache($validated['country_code']);

        return redirect()->back()
            ->with('success', "Updated surge multiplier for {$updated} holidays.");
    }

    /**
     * Export holidays as CSV
     */
    public function export(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $country = $request->get('country');

        $query = PublicHoliday::query()->forYear($year);

        if ($country) {
            $query->forCountry($country);
        }

        $holidays = $query->orderBy('country_code')->orderBy('date')->get();

        $filename = "holidays_{$year}".($country ? "_{$country}" : '').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($holidays) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Country Code',
                'Region Code',
                'Name',
                'Local Name',
                'Date',
                'Type',
                'Is National',
                'Is Observed',
                'Surge Multiplier',
                'Shifts Restricted',
            ]);

            foreach ($holidays as $holiday) {
                fputcsv($file, [
                    $holiday->country_code,
                    $holiday->region_code,
                    $holiday->name,
                    $holiday->local_name,
                    $holiday->date->toDateString(),
                    $holiday->type,
                    $holiday->is_national ? 'Yes' : 'No',
                    $holiday->is_observed ? 'Yes' : 'No',
                    $holiday->surge_multiplier,
                    $holiday->shifts_restricted ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import holidays from CSV
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $imported = 0;
        $errors = [];

        if (($handle = fopen($path, 'r')) !== false) {
            $header = fgetcsv($handle); // Skip header row

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    if (count($row) < 5) {
                        continue;
                    }

                    PublicHoliday::updateOrCreate(
                        [
                            'country_code' => strtoupper($row[0]),
                            'region_code' => $row[1] ?: null,
                            'date' => $row[4],
                            'name' => $row[2],
                        ],
                        [
                            'local_name' => $row[3] ?: null,
                            'type' => $row[5] ?? 'public',
                            'is_national' => ($row[6] ?? 'Yes') === 'Yes',
                            'is_observed' => ($row[7] ?? 'Yes') === 'Yes',
                            'surge_multiplier' => $row[8] ?? 1.50,
                            'shifts_restricted' => ($row[9] ?? 'No') === 'Yes',
                        ]
                    );
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = 'Row: '.implode(',', $row).' - '.$e->getMessage();
                }
            }
            fclose($handle);
        }

        $message = "Imported {$imported} holidays.";
        if (! empty($errors)) {
            $message .= ' '.count($errors).' rows had errors.';
        }

        return redirect()->route('admin.holidays.index')
            ->with('success', $message);
    }

    /**
     * Get holiday statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $year = (int) $request->get('year', now()->year);
        $country = $request->get('country');

        if ($country) {
            $stats = $this->holidayService->getHolidayStatistics($country, $year);
        } else {
            // Global statistics
            $stats = [
                'total' => PublicHoliday::forYear($year)->count(),
                'countries' => PublicHoliday::forYear($year)->distinct('country_code')->count('country_code'),
                'by_type' => PublicHoliday::forYear($year)
                    ->selectRaw('type, count(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'by_country' => PublicHoliday::forYear($year)
                    ->selectRaw('country_code, count(*) as count')
                    ->groupBy('country_code')
                    ->pluck('count', 'country_code')
                    ->toArray(),
            ];
        }

        return response()->json([
            'success' => true,
            'year' => $year,
            'country' => $country,
            'statistics' => $stats,
        ]);
    }

    /**
     * Clear holiday cache
     */
    public function clearCache(Request $request): RedirectResponse
    {
        $country = $request->get('country');

        if ($country) {
            $this->holidayService->clearHolidayCache($country);
            $message = "Cache cleared for {$country}.";
        } else {
            // Clear all countries
            foreach (array_keys(PublicHoliday::getSupportedCountries()) as $code) {
                $this->holidayService->clearHolidayCache($code);
            }
            $message = 'Cache cleared for all countries.';
        }

        return redirect()->back()->with('success', $message);
    }
}
