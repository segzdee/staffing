<?php

namespace App\Console\Commands;

use App\Models\ShiftApplication;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirePendingApplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'applications:expire-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-expire pending applications for shifts that have already started';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Expiring pending applications for started shifts...');

        try {
            $now = Carbon::now();

            // Find pending applications for shifts that have already started
            $expiredApplications = ShiftApplication::with(['shift', 'worker'])
                ->where('status', 'pending')
                ->whereHas('shift', function($query) use ($now) {
                    $query->whereRaw("CONCAT(shift_date, ' ', start_time) <= ?", [$now->format('Y-m-d H:i:s')])
                          ->where('status', '!=', 'cancelled');
                })
                ->get();

            if ($expiredApplications->isEmpty()) {
                $this->info('No applications to expire.');
                return 0;
            }

            $this->info("Found {$expiredApplications->count()} application(s) to expire.");

            $expired = 0;

            foreach ($expiredApplications as $application) {
                try {
                    $application->update([
                        'status' => 'expired',
                        'responded_at' => now(),
                    ]);

                    $expired++;
                    $this->info("✓ Expired application {$application->id} for shift {$application->shift_id}");

                } catch (\Exception $e) {
                    Log::error("Error expiring application {$application->id}: " . $e->getMessage());
                    $this->error("Error expiring application {$application->id}: {$e->getMessage()}");
                }
            }

            $this->info("\nSummary: {$expired} application(s) expired");

            return 0;
        } catch (\Exception $e) {
            Log::error("ExpirePendingApplications command error: " . $e->getMessage());
            $this->error("Command failed: {$e->getMessage()}");
            return 1;
        }
    }
}
