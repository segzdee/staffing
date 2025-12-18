<?php

namespace App\Console\Commands;

use App\Services\BookingConfirmationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SL-004: Command to expire stale booking confirmations.
 *
 * Runs periodically to automatically expire confirmations
 * that have passed their expiry time without full confirmation.
 */
class ExpireStaleConfirmations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'confirmations:expire
                            {--dry-run : Show what would be expired without actually expiring}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire stale booking confirmations that have passed their expiry time';

    /**
     * Execute the console command.
     */
    public function handle(BookingConfirmationService $service): int
    {
        $this->info('Checking for stale confirmations...');

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN - No confirmations will actually be expired.');

            // Count would-be expired
            $count = \App\Models\BookingConfirmation::shouldExpire()->count();
            $this->info("Found {$count} confirmation(s) that would be expired.");

            if ($count > 0) {
                $confirmations = \App\Models\BookingConfirmation::shouldExpire()
                    ->with(['shift', 'worker', 'business'])
                    ->limit(20)
                    ->get();

                $this->table(
                    ['ID', 'Shift', 'Worker', 'Business', 'Expires At', 'Status'],
                    $confirmations->map(function ($c) {
                        return [
                            $c->id,
                            $c->shift->title ?? 'N/A',
                            $c->worker->name ?? 'N/A',
                            $c->business->name ?? 'N/A',
                            $c->expires_at->format('Y-m-d H:i'),
                            $c->status,
                        ];
                    })
                );
            }

            return 0;
        }

        try {
            $expiredCount = $service->expireStaleConfirmations();

            $this->info("Expired {$expiredCount} stale confirmation(s).");

            Log::info('ExpireStaleConfirmations command completed', [
                'expired_count' => $expiredCount,
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");

            Log::error('ExpireStaleConfirmations command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
