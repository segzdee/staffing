<?php

namespace App\Console\Commands;

use App\Models\TaxReport;
use App\Services\TaxReportingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * FIN-007: Generate Annual Tax Reports Command
 *
 * Scheduled to run in January to generate tax reports for the previous year.
 * Generates 1099-NEC for US workers meeting the $600 threshold.
 */
class GenerateAnnualTaxReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tax:generate-annual-reports
                            {--year= : Tax year to generate reports for (defaults to previous year)}
                            {--type= : Report type (1099_nec, p60, annual_statement)}
                            {--send : Send reports via email after generation}
                            {--dry-run : Show what would be generated without actually creating reports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate annual tax reports (1099-NEC, P60, etc.) for workers';

    protected TaxReportingService $taxReportingService;

    public function __construct(TaxReportingService $taxReportingService)
    {
        parent::__construct();
        $this->taxReportingService = $taxReportingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = $this->option('year') ?? (now()->year - 1);
        $reportType = $this->option('type');
        $sendReports = $this->option('send');
        $dryRun = $this->option('dry-run');

        $this->info("Starting annual tax report generation for year {$year}");

        if ($dryRun) {
            $this->warn('DRY RUN - No reports will actually be generated');
        }

        Log::info('Starting annual tax report generation', [
            'year' => $year,
            'report_type' => $reportType,
            'send_reports' => $sendReports,
            'dry_run' => $dryRun,
        ]);

        // Get workers meeting 1099 threshold
        $eligibleWorkers = $this->taxReportingService->getWorkersMeeting1099Threshold($year);

        $this->info(sprintf('Found %d workers meeting the $%s threshold', $eligibleWorkers->count(), TaxReport::US_1099_THRESHOLD));

        if ($eligibleWorkers->isEmpty()) {
            $this->warn('No workers found meeting the threshold. Exiting.');

            return self::SUCCESS;
        }

        // Check for existing reports
        $existingReports = TaxReport::where('tax_year', $year)
            ->when($reportType, fn ($q) => $q->where('report_type', $reportType))
            ->pluck('user_id')
            ->toArray();

        $workersToProcess = $eligibleWorkers->reject(function ($worker) use ($existingReports) {
            return in_array($worker->id, $existingReports);
        });

        $this->info(sprintf('%d workers already have reports, %d need reports generated',
            count($existingReports),
            $workersToProcess->count()
        ));

        if ($workersToProcess->isEmpty()) {
            $this->info('All eligible workers already have reports. Exiting.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->displayDryRunSummary($workersToProcess, $year);

            return self::SUCCESS;
        }

        // Generate reports
        $progressBar = $this->output->createProgressBar($workersToProcess->count());
        $progressBar->start();

        $results = [
            'generated' => 0,
            'skipped' => 0,
            'sent' => 0,
            'errors' => [],
        ];

        foreach ($workersToProcess as $worker) {
            try {
                // Determine report type based on worker's country
                $countryCode = $worker->workerProfile?->country_code ?? 'US';
                $type = $reportType ?? TaxReport::getPrimaryReportTypeForCountry($countryCode);

                // Generate the appropriate report
                $report = match ($type) {
                    TaxReport::TYPE_1099_NEC => $this->taxReportingService->generate1099NEC($worker, $year),
                    TaxReport::TYPE_P60 => $this->taxReportingService->generateP60($worker, $year),
                    default => $this->taxReportingService->generateAnnualReport($worker, $year),
                };

                $results['generated']++;

                // Send if requested
                if ($sendReports && $report->isGenerated()) {
                    try {
                        $this->taxReportingService->emailTaxReport($report);
                        $results['sent']++;
                    } catch (\Exception $e) {
                        Log::warning("Failed to send tax report for user {$worker->id}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'user_id' => $worker->id,
                    'user_name' => $worker->name,
                    'error' => $e->getMessage(),
                ];
                Log::error("Failed to generate tax report for user {$worker->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->displayResults($results);

        Log::info('Annual tax report generation completed', $results);

        return count($results['errors']) > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Display dry run summary.
     */
    protected function displayDryRunSummary($workers, int $year): void
    {
        $this->newLine();
        $this->info('=== DRY RUN SUMMARY ===');
        $this->newLine();

        $headers = ['User ID', 'Name', 'Email', 'Country', 'Estimated Earnings'];
        $rows = [];

        foreach ($workers->take(20) as $worker) {
            $earnings = $this->taxReportingService->calculateYearlyEarnings($worker, $year);
            $rows[] = [
                $worker->id,
                $worker->name,
                $worker->email,
                $worker->workerProfile?->country_code ?? 'US',
                '$'.number_format($earnings['total_gross'], 2),
            ];
        }

        $this->table($headers, $rows);

        if ($workers->count() > 20) {
            $this->info(sprintf('... and %d more workers', $workers->count() - 20));
        }

        $this->newLine();
        $this->info(sprintf('Total workers to process: %d', $workers->count()));
    }

    /**
     * Display generation results.
     */
    protected function displayResults(array $results): void
    {
        $this->info('=== GENERATION RESULTS ===');
        $this->newLine();

        $this->line(sprintf('Reports Generated: %d', $results['generated']));
        $this->line(sprintf('Reports Sent: %d', $results['sent']));
        $this->line(sprintf('Skipped: %d', $results['skipped']));
        $this->line(sprintf('Errors: %d', count($results['errors'])));

        if (count($results['errors']) > 0) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($results['errors'] as $error) {
                $this->line(sprintf('  - User %d (%s): %s',
                    $error['user_id'],
                    $error['user_name'] ?? 'Unknown',
                    $error['error']
                ));
            }
        }

        $this->newLine();

        if ($results['generated'] > 0) {
            $this->info('Tax report generation completed successfully!');
        }
    }
}
