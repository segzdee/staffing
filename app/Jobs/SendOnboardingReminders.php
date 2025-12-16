<?php

namespace App\Jobs;

use App\Services\OnboardingReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendOnboardingReminders Job
 *
 * Processes and sends all pending onboarding reminders.
 * Should be scheduled to run every 15-30 minutes.
 */
class SendOnboardingReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Execute the job.
     */
    public function handle(OnboardingReminderService $reminderService): void
    {
        Log::info('Starting SendOnboardingReminders job');

        try {
            // Process pending reminders
            $results = $reminderService->processPendingReminders();

            Log::info('SendOnboardingReminders completed', $results);

            // Also process stuck users (users stuck on a step for 3+ days)
            $stuckResults = $reminderService->processStuckUsers(3);

            Log::info('ProcessStuckUsers completed', $stuckResults);

        } catch (\Exception $e) {
            Log::error('SendOnboardingReminders failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendOnboardingReminders job failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }
}
