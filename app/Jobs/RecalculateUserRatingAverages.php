<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RatingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * WKR-004: Job to recalculate all user rating averages.
 *
 * This job is designed for one-time migration after adding the 4-category rating system.
 * It can also be run periodically to ensure rating averages stay synchronized.
 */
class RecalculateUserRatingAverages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 900; // 15 minutes for large user base

    /**
     * User ID to recalculate (optional, null means all users)
     */
    protected ?int $userId;

    /**
     * User type filter ('worker', 'business', or null for all)
     */
    protected ?string $userType;

    /**
     * Create a new job instance.
     *
     * @param  int|null  $userId  Specific user ID or null for all users
     * @param  string|null  $userType  'worker', 'business', or null for all
     */
    public function __construct(?int $userId = null, ?string $userType = null)
    {
        $this->userId = $userId;
        $this->userType = $userType;
    }

    /**
     * Execute the job.
     */
    public function handle(RatingService $ratingService): void
    {
        Log::info('Starting RecalculateUserRatingAverages job', [
            'user_id' => $this->userId ?? 'all',
            'user_type' => $this->userType ?? 'all',
        ]);

        $startTime = microtime(true);
        $usersProcessed = 0;
        $usersSuccessful = 0;
        $usersFailed = 0;

        try {
            // Build query based on parameters
            $query = User::query();

            if ($this->userId) {
                $query->where('id', $this->userId);
            }

            if ($this->userType) {
                $query->where('user_type', $this->userType);
            } else {
                $query->whereIn('user_type', ['worker', 'business']);
            }

            // Only process active users
            $query->whereIn('status', ['active', 'suspended']);

            // Process in chunks to handle large datasets
            $query->chunk(100, function ($users) use ($ratingService, &$usersProcessed, &$usersSuccessful, &$usersFailed) {
                foreach ($users as $user) {
                    $usersProcessed++;

                    try {
                        // Recalculate averages for this user
                        $ratingService->recalculateAllAverages($user);
                        $usersSuccessful++;

                        Log::debug('Recalculated rating averages', [
                            'user_id' => $user->id,
                            'user_type' => $user->user_type,
                        ]);
                    } catch (\Exception $e) {
                        $usersFailed++;
                        Log::error('Error recalculating rating averages for user', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        // Continue with next user instead of failing entire job
                    }
                }

                // Brief pause between chunks to prevent database overload
                usleep(50000); // 0.05 second pause
            });

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info('RecalculateUserRatingAverages job completed', [
                'users_processed' => $usersProcessed,
                'users_successful' => $usersSuccessful,
                'users_failed' => $usersFailed,
                'execution_time_seconds' => $executionTime,
                'avg_time_per_user_ms' => $usersProcessed > 0 ? round(($executionTime * 1000) / $usersProcessed, 2) : 0,
            ]);
        } catch (\Exception $e) {
            Log::error('RecalculateUserRatingAverages job failed', [
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
        Log::error('RecalculateUserRatingAverages job failed permanently', [
            'user_id' => $this->userId ?? 'all',
            'user_type' => $this->userType ?? 'all',
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Create a job for recalculating all workers.
     */
    public static function forAllWorkers(): self
    {
        return new self(null, 'worker');
    }

    /**
     * Create a job for recalculating all businesses.
     */
    public static function forAllBusinesses(): self
    {
        return new self(null, 'business');
    }

    /**
     * Create a job for recalculating a specific user.
     */
    public static function forUser(int $userId): self
    {
        return new self($userId);
    }
}
