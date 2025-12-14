<?php

namespace App\Console\Commands;

use App\Models\ShiftAssignment;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessNoShows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:process-no-shows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark workers as no-show if not checked in 30 minutes after shift start time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing no-shows...');

        try {
            $now = Carbon::now();
            $thirtyMinutesAgo = $now->copy()->subMinutes(30);

            // Find assignments that should have started but worker hasn't checked in
            $noShowAssignments = ShiftAssignment::with(['shift', 'worker'])
                ->where('status', 'assigned')
                ->whereHas('shift', function($query) use ($thirtyMinutesAgo) {
                    $query->whereRaw("CONCAT(shift_date, ' ', start_time) <= ?", [$thirtyMinutesAgo->format('Y-m-d H:i:s')])
                          ->where('status', '!=', 'cancelled');
                })
                ->whereNull('check_in_time')
                ->get();

            if ($noShowAssignments->isEmpty()) {
                $this->info('No no-shows detected.');
                return 0;
            }

            $this->info("Found {$noShowAssignments->count()} potential no-show(s).");

            $marked = 0;

            foreach ($noShowAssignments as $assignment) {
                try {
                    // Mark as no-show
                    $assignment->markNoShow();

                    // Update worker profile
                    if ($assignment->worker && $assignment->worker->workerProfile) {
                        $profile = $assignment->worker->workerProfile;
                        $profile->increment('total_no_shows');
                        $profile->updateReliabilityScore();
                    }

                    // Update shift filled count
                    $shift = $assignment->shift;
                    if ($shift->filled_workers > 0) {
                        $shift->decrement('filled_workers');
                    }

                    // Refund escrow payment if exists
                    if ($assignment->payment && $assignment->payment->isInEscrow()) {
                        // Payment service should handle refund
                        // For now, just log it
                        Log::info("No-show detected - escrow refund needed", [
                            'assignment_id' => $assignment->id,
                            'payment_id' => $assignment->payment->id,
                        ]);
                    }

                    $marked++;
                    $this->info("✓ Marked assignment {$assignment->id} as no-show (Worker: {$assignment->worker->name})");

                } catch (\Exception $e) {
                    Log::error("Error processing no-show for assignment {$assignment->id}: " . $e->getMessage());
                    $this->error("Error processing assignment {$assignment->id}: {$e->getMessage()}");
                }
            }

            $this->info("\nSummary: {$marked} no-show(s) processed");

            return 0;
        } catch (\Exception $e) {
            Log::error("ProcessNoShows command error: " . $e->getMessage());
            $this->error("Command failed: {$e->getMessage()}");
            return 1;
        }
    }
}
