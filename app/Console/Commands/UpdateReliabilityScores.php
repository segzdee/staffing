<?php

namespace App\Console\Commands;

use App\Models\WorkerProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateReliabilityScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workers:update-reliability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate reliability scores for all workers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating reliability scores for all workers...');

        try {
            $profiles = WorkerProfile::all();

            if ($profiles->isEmpty()) {
                $this->info('No worker profiles found.');
                return 0;
            }

            $this->info("Processing {$profiles->count()} worker profile(s)...");

            $updated = 0;
            $bar = $this->output->createProgressBar($profiles->count());
            $bar->start();

            foreach ($profiles as $profile) {
                try {
                    $oldScore = $profile->reliability_score;
                    $profile->updateReliabilityScore();
                    $newScore = $profile->reliability_score;

                    if ($oldScore != $newScore) {
                        $updated++;
                    }

                    $bar->advance();
                } catch (\Exception $e) {
                    Log::error("Error updating reliability for worker {$profile->user_id}: " . $e->getMessage());
                }
            }

            $bar->finish();
            $this->newLine();
            $this->info("\nSummary: {$updated} score(s) updated");

            return 0;
        } catch (\Exception $e) {
            Log::error("UpdateReliabilityScores command error: " . $e->getMessage());
            $this->error("Command failed: {$e->getMessage()}");
            return 1;
        }
    }
}
