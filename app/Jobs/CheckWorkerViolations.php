<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WorkerSuspensionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckWorkerViolations implements ShouldQueue
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
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(WorkerSuspensionService $suspensionService)
    {
        Log::info('Starting CheckWorkerViolations job');

        $startTime = microtime(true);
        $workersChecked = 0;
        $workersSuspended = 0;
        $workersReinstated = 0;

        try {
            // First, auto-reinstate workers whose suspension has expired
            $reinstated = $suspensionService->autoReinstateWorkers();
            $workersReinstated += $reinstated;

            // Get all active workers (and those currently suspended, to check if they need additional suspension)
            $workers = User::whereIn('user_type', ['worker'])
                ->whereIn('status', ['active', 'suspended'])
                ->get();

            foreach ($workers as $worker) {
                $workersChecked++;

                try {
                    // Skip if already suspended
                    if ($worker->isSuspended()) {
                        continue;
                    }

                    // Check for no-show violations
                    $noShowResult = $suspensionService->checkNoShowViolations($worker);
                    if ($noShowResult['suspended']) {
                        $workersSuspended++;
                        Log::info("Worker suspended for no-show", [
                            'worker_id' => $worker->id,
                            'worker_email' => $worker->email,
                            'reason' => $noShowResult['reason'],
                            'days' => $noShowResult['days']
                        ]);
                        continue; // Skip late cancellation check since already suspended
                    }

                    // Check for late cancellation pattern violations
                    $cancellationResult = $suspensionService->checkLateCancellationPattern($worker);
                    if ($cancellationResult['suspended']) {
                        $workersSuspended++;
                        Log::info("Worker suspended for late cancellations", [
                            'worker_id' => $worker->id,
                            'worker_email' => $worker->email,
                            'reason' => $cancellationResult['reason'],
                            'days' => $cancellationResult['days']
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Error checking violations for worker", [
                        'worker_id' => $worker->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue with next worker instead of failing entire job
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            Log::info('CheckWorkerViolations job completed', [
                'workers_checked' => $workersChecked,
                'workers_suspended' => $workersSuspended,
                'workers_reinstated' => $workersReinstated,
                'execution_time_seconds' => $executionTime
            ]);
        } catch (\Exception $e) {
            Log::error('CheckWorkerViolations job failed', [
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
        Log::error('CheckWorkerViolations job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
