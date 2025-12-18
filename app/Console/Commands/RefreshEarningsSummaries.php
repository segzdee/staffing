<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\EarningsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * WKR-006: Refresh Earnings Summaries Command
 *
 * This command refreshes cached earnings summaries for all workers.
 * It should be scheduled to run daily to keep summaries up to date.
 */
class RefreshEarningsSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'earnings:refresh-summaries
                            {--user= : Refresh summaries for a specific user ID}
                            {--active-only : Only refresh for workers with recent earnings}
                            {--days=90 : Number of days to consider as recent activity}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh cached earnings summaries for workers (WKR-006)';

    /**
     * Execute the console command.
     */
    public function handle(EarningsService $earningsService): int
    {
        $startTime = microtime(true);
        $this->info('Starting earnings summary refresh...');

        try {
            // Single user mode
            if ($userId = $this->option('user')) {
                return $this->refreshSingleUser($earningsService, (int) $userId);
            }

            // Build query for users
            $query = User::whereHas('workerProfile');

            // Active only filter
            if ($this->option('active-only')) {
                $days = (int) $this->option('days');
                $cutoffDate = now()->subDays($days);

                $query->whereHas('workerEarnings', function ($q) use ($cutoffDate) {
                    $q->where('earned_date', '>=', $cutoffDate);
                });

                $this->info("Filtering to workers with activity in last {$days} days...");
            }

            $workers = $query->get();
            $count = $workers->count();

            if ($count === 0) {
                $this->warn('No workers found to process.');

                return Command::SUCCESS;
            }

            $this->info("Found {$count} workers to process.");
            $bar = $this->output->createProgressBar($count);
            $bar->start();

            $successCount = 0;
            $errorCount = 0;
            $totalSummaries = 0;

            foreach ($workers as $worker) {
                try {
                    $stats = $earningsService->refreshAllSummaries($worker);
                    $totalSummaries += array_sum($stats);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("Failed to refresh summaries for worker {$worker->id}: ".$e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $elapsed = round(microtime(true) - $startTime, 2);

            $this->info('Refresh complete!');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Workers processed', $successCount],
                    ['Workers failed', $errorCount],
                    ['Summaries updated', $totalSummaries],
                    ['Time elapsed', "{$elapsed}s"],
                ]
            );

            if ($errorCount > 0) {
                $this->warn("Check logs for details on {$errorCount} failed workers.");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Fatal error: '.$e->getMessage());
            Log::error('Earnings summary refresh failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Refresh summaries for a single user.
     */
    protected function refreshSingleUser(EarningsService $earningsService, int $userId): int
    {
        $user = User::find($userId);

        if (! $user) {
            $this->error("User {$userId} not found.");

            return Command::FAILURE;
        }

        if (! $user->workerProfile) {
            $this->error("User {$userId} is not a worker.");

            return Command::FAILURE;
        }

        $this->info("Refreshing summaries for user: {$user->name} (ID: {$userId})");

        try {
            $stats = $earningsService->refreshAllSummaries($user);

            $this->info('Refresh complete!');
            $this->table(
                ['Period Type', 'Summaries Updated'],
                [
                    ['Daily', $stats['daily']],
                    ['Weekly', $stats['weekly']],
                    ['Monthly', $stats['monthly']],
                    ['Yearly', $stats['yearly']],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed: '.$e->getMessage());
            Log::error("Failed to refresh summaries for user {$userId}: ".$e->getMessage());

            return Command::FAILURE;
        }
    }
}
