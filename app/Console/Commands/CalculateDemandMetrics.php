<?php

namespace App\Console\Commands;

use App\Services\DemandTrackingService;
use App\Services\SurgeEventService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SL-008: Daily Demand Metrics Calculation Command
 *
 * Calculates and updates demand metrics for surge pricing.
 * Should be scheduled to run daily (recommended: early morning for previous day).
 */
class CalculateDemandMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'surge:calculate-demand
                            {--date= : Specific date to calculate (YYYY-MM-DD). Defaults to yesterday.}
                            {--range= : Number of days to calculate backwards from date. Default: 1}
                            {--import-events : Also import events from external APIs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate daily demand metrics for surge pricing';

    /**
     * Execute the console command.
     */
    public function handle(DemandTrackingService $demandService, SurgeEventService $eventService): int
    {
        $this->info('Starting demand metrics calculation...');

        // Determine the date to process
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $range = (int) ($this->option('range') ?? 1);

        // Calculate metrics for the specified range
        $this->withProgressBar(range(0, $range - 1), function ($dayOffset) use ($demandService, $date) {
            $targetDate = $date->copy()->subDays($dayOffset);
            $demandService->calculateDailyMetrics($targetDate);
        });

        $this->newLine();
        $this->info("Demand metrics calculated for {$range} day(s) ending {$date->toDateString()}");

        // Optionally import events
        if ($this->option('import-events')) {
            $this->info('Importing events from external APIs...');

            try {
                $imported = $eventService->importEventsFromAPI();
                $this->info("Imported {$imported} events from external APIs");
            } catch (\Exception $e) {
                $this->warn("Event import failed: {$e->getMessage()}");
                Log::error('Event import failed during demand metrics calculation', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Deactivate expired events
        $deactivated = $eventService->deactivateExpiredEvents();
        if ($deactivated > 0) {
            $this->info("Deactivated {$deactivated} expired surge events");
        }

        // Output summary
        $this->displaySummary($demandService, $date);

        Log::info('Demand metrics calculation completed', [
            'date' => $date->toDateString(),
            'range' => $range,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Display a summary of the current demand status.
     */
    protected function displaySummary(DemandTrackingService $demandService, Carbon $date): void
    {
        $this->newLine();
        $this->info('=== Demand Metrics Summary ===');

        // Get heatmap for global metrics
        $heatmap = $demandService->getDemandHeatmap(null, 7);

        $headers = ['Date', 'Day', 'Shifts Posted', 'Shifts Filled', 'Fill Rate', 'Surge'];
        $rows = [];

        foreach ($heatmap as $dateKey => $data) {
            $rows[] = [
                $data['date'],
                $data['day_name'],
                $data['shifts_posted'],
                $data['shifts_filled'],
                number_format($data['fill_rate'], 1).'%',
                $data['calculated_surge'].'x',
            ];
        }

        $this->table($headers, $rows);

        // Show forecast for next 3 days
        $this->newLine();
        $this->info('=== 3-Day Forecast (Global) ===');

        $forecast = $demandService->getSurgeForecast(null, 3);
        $forecastHeaders = ['Date', 'Day', 'Predicted Surge', 'Events', 'Confidence'];
        $forecastRows = [];

        foreach ($forecast as $dateKey => $data) {
            $eventNames = collect($data['events'])->pluck('name')->join(', ') ?: 'None';
            $forecastRows[] = [
                $data['date'],
                $data['day_name'],
                $data['estimated_surge'].'x',
                strlen($eventNames) > 30 ? substr($eventNames, 0, 30).'...' : $eventNames,
                $data['predicted_demand']['confidence'],
            ];
        }

        $this->table($forecastHeaders, $forecastRows);
    }
}
