<?php

namespace App\Console\Commands;

use App\Services\AvailabilityForecastingService;
use Illuminate\Console\Command;

/**
 * WKR-013: Availability Forecasting Command
 *
 * Scheduled command to:
 * - Generate daily patterns from historical data
 * - Create predictions for next 14 days
 * - Update prediction accuracy
 *
 * Recommended schedule: Daily at 2:00 AM
 */
class GenerateAvailabilityForecasts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forecasting:generate
                            {--days=14 : Number of days to predict ahead}
                            {--update-accuracy : Also update accuracy of past predictions}
                            {--patterns-only : Only update patterns, skip predictions}
                            {--predictions-only : Only generate predictions, skip pattern analysis}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate availability predictions and demand forecasts for workers';

    /**
     * Execute the console command.
     */
    public function handle(AvailabilityForecastingService $service): int
    {
        $this->info('Starting availability forecasting...');
        $startTime = now();

        $days = (int) $this->option('days');
        $updateAccuracy = $this->option('update-accuracy');
        $patternsOnly = $this->option('patterns-only');
        $predictionsOnly = $this->option('predictions-only');

        try {
            // Update accuracy of past predictions first
            if ($updateAccuracy) {
                $this->info('Updating prediction accuracy for past dates...');
                $service->updatePredictionAccuracy();
                $this->info('Accuracy update complete.');
            }

            if ($patternsOnly) {
                // Only update patterns
                $this->info('Analyzing worker patterns...');
                $this->analyzeAllPatterns($service);
                $this->info('Pattern analysis complete.');

                return Command::SUCCESS;
            }

            if ($predictionsOnly) {
                // Only generate predictions
                $this->info("Generating predictions for next {$days} days...");
                $results = $this->generatePredictionsOnly($service, $days);
            } else {
                // Full run: patterns + predictions
                $this->info("Running full forecast generation for {$days} days...");
                $results = $service->generateDailyPredictions($days);
            }

            // Output results
            $this->newLine();
            $this->info('Forecasting Results:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Workers Processed', $results['workers_processed']],
                    ['Predictions Created', $results['predictions_created']],
                    ['Demand Forecasts Created', $results['forecasts_created']],
                    ['Errors', count($results['errors'])],
                ]
            );

            if (! empty($results['errors'])) {
                $this->newLine();
                $this->warn('Errors encountered:');
                foreach (array_slice($results['errors'], 0, 10) as $error) {
                    $this->error("  - {$error}");
                }
                if (count($results['errors']) > 10) {
                    $this->warn('  ... and '.(count($results['errors']) - 10).' more errors');
                }
            }

            $duration = now()->diffInSeconds($startTime);
            $this->newLine();
            $this->info("Completed in {$duration} seconds.");

            return empty($results['errors']) ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('Forecasting failed: '.$e->getMessage());
            report($e);

            return Command::FAILURE;
        }
    }

    /**
     * Analyze patterns for all workers.
     */
    protected function analyzeAllPatterns(AvailabilityForecastingService $service): void
    {
        $workers = \App\Models\User::where('user_type', 'worker')
            ->where('status', 'active')
            ->get();

        $bar = $this->output->createProgressBar($workers->count());
        $bar->start();

        foreach ($workers as $worker) {
            try {
                $service->analyzeWorkerPatterns($worker);
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Failed for worker {$worker->id}: ".$e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Generate predictions without pattern analysis.
     */
    protected function generatePredictionsOnly(AvailabilityForecastingService $service, int $days): array
    {
        $results = [
            'workers_processed' => 0,
            'predictions_created' => 0,
            'forecasts_created' => 0,
            'errors' => [],
        ];

        $workers = \App\Models\User::where('user_type', 'worker')
            ->where('status', 'active')
            ->get();

        $results['workers_processed'] = $workers->count();

        $bar = $this->output->createProgressBar($workers->count() * $days);
        $bar->start();

        foreach ($workers as $worker) {
            for ($i = 0; $i < $days; $i++) {
                try {
                    $date = now()->addDays($i);
                    $service->predictAvailability($worker, $date);
                    $results['predictions_created']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Worker {$worker->id}: ".$e->getMessage();
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        // Generate demand forecasts
        $this->info('Generating demand forecasts...');
        $regions = \App\Models\Shift::query()
            ->where('created_at', '>=', now()->subMonths(3))
            ->whereNotNull('location_city')
            ->distinct()
            ->pluck('location_city')
            ->take(20);

        foreach ($regions as $region) {
            for ($i = 0; $i < $days; $i++) {
                try {
                    $date = now()->addDays($i);
                    $service->forecastDemand($date, $region);
                    $results['forecasts_created']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Forecast {$region}: ".$e->getMessage();
                }
            }
        }

        return $results;
    }
}
