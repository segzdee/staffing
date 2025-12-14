<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ShiftPaymentService;
use Illuminate\Support\Facades\Log;

class ProcessInstantPayouts implements ShouldQueue
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
    public $timeout = 300; // 5 minutes

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
     * Processes all shift payments ready for instant payout (15 minutes after release)
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Processing instant payouts job started');

        try {
            $paymentService = new ShiftPaymentService();
            $result = $paymentService->processReadyPayouts();

            Log::info('Instant payouts processed', [
                'processed' => $result['processed'] ?? 0,
                'failed' => $result['failed'] ?? 0
            ]);

        } catch (\Exception $e) {
            Log::error('Error in ProcessInstantPayouts job: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('ProcessInstantPayouts job failed after all retries', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Could send admin notification here
    }
}
