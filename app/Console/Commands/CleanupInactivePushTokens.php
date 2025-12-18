<?php

namespace App\Console\Commands;

use App\Services\PushNotificationService;
use Illuminate\Console\Command;

/**
 * COM-002: Cleanup Inactive Push Tokens Command
 *
 * This command removes push notification tokens that haven't been used
 * within the configured inactive period (default: 90 days).
 *
 * Should be scheduled to run daily:
 * $schedule->command('push:cleanup-tokens')->daily();
 */
class CleanupInactivePushTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:cleanup-tokens
                            {--days= : Override the default inactive days threshold}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove push notification tokens that have been inactive for too long';

    /**
     * Execute the console command.
     */
    public function handle(PushNotificationService $pushService): int
    {
        $days = $this->option('days') ?? config('firebase.tokens.inactive_days', 90);
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up push tokens inactive for {$days}+ days...");

        if ($dryRun) {
            $this->warn('DRY RUN - No tokens will be deleted');

            // Count how many would be deleted
            $count = \App\Models\PushNotificationToken::notUsedSince($days)->count();
            $this->info("Would delete {$count} inactive token(s)");
        } else {
            $count = $pushService->cleanupInactiveTokens();
            $this->info("Deleted {$count} inactive token(s)");
        }

        return Command::SUCCESS;
    }
}
