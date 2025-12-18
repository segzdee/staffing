<?php

namespace App\Console\Commands;

use App\Services\BookingConfirmationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * SL-004: Command to send confirmation reminders.
 *
 * Runs periodically to send reminders to workers and businesses
 * who have pending confirmations approaching their expiry time.
 */
class SendConfirmationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'confirmations:remind
                            {--dry-run : Show what reminders would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for pending booking confirmations approaching expiry';

    /**
     * Execute the console command.
     */
    public function handle(BookingConfirmationService $service): int
    {
        $this->info('Checking for confirmations needing reminders...');

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN - No reminders will actually be sent.');

            $confirmations = \App\Models\BookingConfirmation::needingReminder()
                ->with(['shift', 'worker', 'business'])
                ->get();

            $this->info("Found {$confirmations->count()} confirmation(s) needing reminders.");

            if ($confirmations->count() > 0) {
                $tableData = [];

                foreach ($confirmations as $c) {
                    $recipients = [];
                    if (! $c->worker_confirmed) {
                        $recipients[] = 'Worker: '.($c->worker->name ?? 'N/A');
                    }
                    if (! $c->business_confirmed) {
                        $recipients[] = 'Business: '.($c->business->name ?? 'N/A');
                    }

                    $tableData[] = [
                        $c->id,
                        $c->shift->title ?? 'N/A',
                        implode(', ', $recipients),
                        round($c->hoursUntilExpiration(), 1).' hours',
                        $c->reminder_sent_at ? $c->reminder_sent_at->format('Y-m-d H:i') : 'Never',
                    ];
                }

                $this->table(
                    ['ID', 'Shift', 'Recipients', 'Expires In', 'Last Reminder'],
                    $tableData
                );
            }

            return 0;
        }

        try {
            $sentCount = $service->sendReminders();

            $total = $sentCount['worker'] + $sentCount['business'];
            $this->info("Sent {$total} reminder(s).");
            $this->info("  - Workers: {$sentCount['worker']}");
            $this->info("  - Businesses: {$sentCount['business']}");

            Log::info('SendConfirmationReminders command completed', [
                'worker_reminders' => $sentCount['worker'],
                'business_reminders' => $sentCount['business'],
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");

            Log::error('SendConfirmationReminders command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
