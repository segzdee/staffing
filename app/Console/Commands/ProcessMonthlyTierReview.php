<?php

namespace App\Console\Commands;

use App\Services\WorkerTierService;
use Illuminate\Console\Command;

/**
 * WKR-007: Process Monthly Tier Review Command
 *
 * Scheduled command to review all worker tiers monthly.
 * Can upgrade workers who meet higher tier requirements
 * or downgrade workers who no longer meet their current tier requirements.
 */
class ProcessMonthlyTierReview extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workers:process-tier-review
                            {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Review and update worker career tiers based on their metrics';

    /**
     * Execute the console command.
     */
    public function handle(WorkerTierService $tierService): int
    {
        $this->info('Starting monthly worker tier review...');

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be applied');
            $this->newLine();
        }

        try {
            $startTime = now();

            // For dry run, we'll just show current stats
            if ($dryRun) {
                $this->dryRunPreview($tierService);
            } else {
                $stats = $tierService->processMonthlyTierReview();

                $this->newLine();
                $this->info('Review completed!');
                $this->newLine();

                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Workers Processed', $stats['processed']],
                        ['Upgrades', $stats['upgraded']],
                        ['Downgrades', $stats['downgraded']],
                        ['Unchanged', $stats['unchanged']],
                    ]
                );

                $duration = now()->diffInSeconds($startTime);
                $this->info("Processing time: {$duration} seconds");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to process tier review: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Preview what changes would be made without applying them.
     */
    protected function dryRunPreview(WorkerTierService $tierService): void
    {
        $this->info('Analyzing worker tier eligibility...');
        $this->newLine();

        // Get current tier distribution
        $tiers = $tierService->getAllTiersWithStats();

        $this->info('Current tier distribution:');
        $this->table(
            ['Tier', 'Level', 'Workers'],
            $tiers->map(fn ($tier) => [
                $tier['tier']['name'],
                $tier['tier']['level'],
                $tier['worker_count'],
            ])->toArray()
        );

        $this->newLine();
        $this->info('To apply changes, run without --dry-run flag.');
    }
}
