<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ReliabilityScoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateReliabilityScores implements ShouldQueue
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
    public $timeout = 600; // 10 minutes for large worker base

    /**
     * Worker ID to recalculate (optional, null means all workers)
     *
     * @var int|null
     */
    protected $workerId;

    /**
     * Create a new job instance.
     *
     * @param int|null $workerId
     * @return void
     */
    public function __construct(?int $workerId = null)
    {
        $this->workerId = $workerId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ReliabilityScoreService $scoreService)
    {
        Log::info('Starting RecalculateReliabilityScores job', [
            'worker_id' => $this->workerId ?? 'all'
        ]);

        $startTime = microtime(true);
        $workersProcessed = 0;
        $workersSuccessful = 0;
        $workersFailed = 0;

        try {
            // Get workers to process
            if ($this->workerId) {
                $workers = User::where('id', $this->workerId)
                    ->where('user_type', 'worker')
                    ->get();
            } else {
                // Get all workers with active or suspended status
                $workers = User::where('user_type', 'worker')
                    ->whereIn('status', ['active', 'suspended'])
                    ->get();
            }

            foreach ($workers as $worker) {
                $workersProcessed++;

                try {
                    // Calculate and save the score
                    $scoreData = $scoreService->recalculateAndSave($worker);

                    // Update the cached score in the user table
                    $worker->updateReliabilityScore($scoreData['score']);

                    $workersSuccessful++;

                    // Log significant score changes
                    $previousScore = $worker->reliabilityScoreHistory()
                        ->skip(1)
                        ->first();

                    if ($previousScore) {
                        $scoreDiff = round($scoreData['score'] - $previousScore->score, 2);
                        if (abs($scoreDiff) >= 10) {
                            Log::info("Significant reliability score change", [
                                'worker_id' => $worker->id,
                                'worker_email' => $worker->email,
                                'old_score' => $previousScore->score,
                                'new_score' => $scoreData['score'],
                                'difference' => $scoreDiff,
                                'grade' => $scoreData['grade']
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    $workersFailed++;
                    Log::error("Error recalculating reliability score for worker", [
                        'worker_id' => $worker->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue with next worker instead of failing entire job
                }

                // Brief pause every 100 workers to prevent overwhelming the database
                if ($workersProcessed % 100 === 0) {
                    usleep(100000); // 0.1 second pause
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info('RecalculateReliabilityScores job completed', [
                'workers_processed' => $workersProcessed,
                'workers_successful' => $workersSuccessful,
                'workers_failed' => $workersFailed,
                'execution_time_seconds' => $executionTime,
                'avg_time_per_worker_ms' => $workersProcessed > 0 ? round(($executionTime * 1000) / $workersProcessed, 2) : 0
            ]);
        } catch (\Exception $e) {
            Log::error('RecalculateReliabilityScores job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('RecalculateReliabilityScores job failed permanently', [
            'worker_id' => $this->workerId ?? 'all',
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
