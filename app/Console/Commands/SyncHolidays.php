<?php

namespace App\Console\Commands;

use App\Models\PublicHoliday;
use App\Services\HolidayService;
use Illuminate\Console\Command;

class SyncHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:sync
                            {--year= : The year to sync holidays for (defaults to current and next year)}
                            {--countries= : Comma-separated country codes (defaults to all supported countries)}
                            {--force : Force re-sync even if holidays exist}
                            {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync public holidays from external APIs for specified countries and years';

    /**
     * Default countries to sync if none specified
     *
     * @var array<string>
     */
    protected array $defaultCountries = [
        'US', 'GB', 'DE', 'FR', 'AU', 'CA', 'NL', 'BE', 'ES', 'IT',
        'IE', 'AT', 'CH', 'MT', 'NZ', 'SG', 'IN', 'BR', 'MX', 'ZA',
    ];

    public function __construct(
        protected HolidayService $holidayService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $yearOption = $this->option('year');
        $countriesOption = $this->option('countries');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        // Determine years to sync
        if ($yearOption) {
            $years = [intval($yearOption)];
        } else {
            $currentYear = now()->year;
            $years = [$currentYear, $currentYear + 1];
        }

        // Determine countries to sync
        if ($countriesOption) {
            $countries = array_map('strtoupper', array_map('trim', explode(',', $countriesOption)));
        } else {
            $countries = $this->defaultCountries;
        }

        // Validate countries
        $supportedCountries = PublicHoliday::getSupportedCountries();
        $validCountries = [];
        $invalidCountries = [];

        foreach ($countries as $country) {
            if (isset($supportedCountries[$country])) {
                $validCountries[] = $country;
            } else {
                $invalidCountries[] = $country;
            }
        }

        if (! empty($invalidCountries)) {
            $this->warn('Skipping unsupported countries: '.implode(', ', $invalidCountries));
        }

        if (empty($validCountries)) {
            $this->error('No valid countries to sync');

            return self::FAILURE;
        }

        $this->info('Holiday Sync Configuration:');
        $this->info('  Years: '.implode(', ', $years));
        $this->info('  Countries: '.implode(', ', $validCountries));
        $this->info('  Force: '.($force ? 'Yes' : 'No'));
        $this->info('  Dry Run: '.($dryRun ? 'Yes' : 'No'));
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $totalSynced = 0;
        $totalSkipped = 0;
        $totalFailed = 0;

        $progressBar = $this->output->createProgressBar(count($validCountries) * count($years));
        $progressBar->start();

        $results = [];

        foreach ($years as $year) {
            foreach ($validCountries as $country) {
                $countryName = $supportedCountries[$country];

                // Check if we should skip
                if (! $force && ! $dryRun) {
                    $existingCount = PublicHoliday::query()
                        ->forCountry($country)
                        ->forYear($year)
                        ->count();

                    if ($existingCount > 0) {
                        $results[] = [
                            'country' => "{$countryName} ({$country})",
                            'year' => $year,
                            'status' => 'Skipped',
                            'count' => $existingCount,
                            'reason' => 'Already exists',
                        ];
                        $totalSkipped++;
                        $progressBar->advance();

                        continue;
                    }
                }

                if ($dryRun) {
                    // In dry run, just fetch and count
                    try {
                        $holidays = $this->holidayService->fetchHolidaysFromAPI($country, $year);
                        $results[] = [
                            'country' => "{$countryName} ({$country})",
                            'year' => $year,
                            'status' => 'Would sync',
                            'count' => count($holidays),
                            'reason' => '',
                        ];
                    } catch (\Exception $e) {
                        $results[] = [
                            'country' => "{$countryName} ({$country})",
                            'year' => $year,
                            'status' => 'Would fail',
                            'count' => 0,
                            'reason' => $e->getMessage(),
                        ];
                        $totalFailed++;
                    }
                } else {
                    // Actually sync
                    try {
                        $count = $this->holidayService->syncHolidays($country, $year);
                        $results[] = [
                            'country' => "{$countryName} ({$country})",
                            'year' => $year,
                            'status' => 'Synced',
                            'count' => $count,
                            'reason' => '',
                        ];
                        $totalSynced += $count;
                    } catch (\Exception $e) {
                        $results[] = [
                            'country' => "{$countryName} ({$country})",
                            'year' => $year,
                            'status' => 'Failed',
                            'count' => 0,
                            'reason' => $e->getMessage(),
                        ];
                        $totalFailed++;
                    }
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results table
        $this->table(
            ['Country', 'Year', 'Status', 'Holidays', 'Notes'],
            array_map(function ($result) {
                return [
                    $result['country'],
                    $result['year'],
                    $result['status'],
                    $result['count'],
                    $result['reason'],
                ];
            }, $results)
        );

        // Summary
        $this->newLine();
        $this->info('Summary:');

        if ($dryRun) {
            $wouldSync = collect($results)->where('status', 'Would sync')->sum('count');
            $this->info("  Would sync: {$wouldSync} holidays");
        } else {
            $this->info("  Total synced: {$totalSynced} holidays");
            $this->info("  Skipped (existing): {$totalSkipped} country/year combinations");
        }

        if ($totalFailed > 0) {
            $this->error("  Failed: {$totalFailed} country/year combinations");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
