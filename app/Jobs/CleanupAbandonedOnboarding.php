<?php

namespace App\Jobs;

use App\Models\OnboardingProgress;
use App\Models\OnboardingEvent;
use App\Models\OnboardingReminder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * CleanupAbandonedOnboarding Job
 *
 * Identifies users who have abandoned onboarding and performs cleanup:
 * - Marks abandoned onboarding progress
 * - Logs abandonment events for analytics
 * - Cancels pending reminders for inactive users
 *
 * Should be scheduled weekly.
 */
class CleanupAbandonedOnboarding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Days of inactivity before marking as abandoned
     */
    protected int $abandonmentThreshold;

    /**
     * Create a new job instance.
     */
    public function __construct(int $abandonmentThreshold = 30)
    {
        $this->abandonmentThreshold = $abandonmentThreshold;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting CleanupAbandonedOnboarding job', [
            'abandonment_threshold_days' => $this->abandonmentThreshold,
        ]);

        try {
            $results = [
                'abandoned_users' => 0,
                'cancelled_reminders' => 0,
                'events_logged' => 0,
            ];

            // Find users who haven't completed onboarding and haven't been active
            $abandonedUsers = User::where('onboarding_completed', false)
                ->where('created_at', '<', now()->subDays($this->abandonmentThreshold))
                ->whereDoesntHave('onboardingProgress', function ($query) {
                    $query->where('updated_at', '>=', now()->subDays($this->abandonmentThreshold));
                })
                ->get();

            foreach ($abandonedUsers as $user) {
                // Check if already marked as abandoned
                $alreadyAbandoned = OnboardingEvent::forUser($user->id)
                    ->ofType(OnboardingEvent::EVENT_ONBOARDING_ABANDONED)
                    ->exists();

                if ($alreadyAbandoned) {
                    continue;
                }

                DB::transaction(function () use ($user, &$results) {
                    // Log abandonment event
                    $lastProgress = OnboardingProgress::forUser($user->id)
                        ->with('step')
                        ->orderBy('updated_at', 'desc')
                        ->first();

                    OnboardingEvent::log(
                        $user->id,
                        OnboardingEvent::EVENT_ONBOARDING_ABANDONED,
                        $lastProgress?->step?->step_id,
                        [
                            'last_activity' => $lastProgress?->updated_at?->toDateTimeString(),
                            'overall_progress' => $this->calculateProgress($user->id),
                            'days_inactive' => $this->abandonmentThreshold,
                        ]
                    );
                    $results['events_logged']++;

                    // Cancel all pending reminders
                    $cancelled = OnboardingReminder::cancelAllForUser(
                        $user->id,
                        'Onboarding abandoned after ' . $this->abandonmentThreshold . ' days of inactivity'
                    );
                    $results['cancelled_reminders'] += $cancelled;

                    $results['abandoned_users']++;
                });
            }

            // Clean up old events (keep last 90 days for detailed analysis)
            $deletedEvents = OnboardingEvent::where('created_at', '<', now()->subDays(365))
                ->delete();

            $results['deleted_old_events'] = $deletedEvents;

            Log::info('CleanupAbandonedOnboarding completed', $results);

        } catch (\Exception $e) {
            Log::error('CleanupAbandonedOnboarding failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate overall progress for a user
     */
    protected function calculateProgress(int $userId): float
    {
        $progress = OnboardingProgress::forUser($userId)->with('step')->get();

        if ($progress->isEmpty()) {
            return 0;
        }

        $totalWeight = $progress->sum(fn($p) => $p->step?->weight ?? 0);
        $completedWeight = $progress
            ->where('status', 'completed')
            ->sum(fn($p) => $p->step?->weight ?? 0);

        return $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100, 1) : 0;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CleanupAbandonedOnboarding job failed permanently', [
            'error' => $exception->getMessage(),
        ]);
    }
}
