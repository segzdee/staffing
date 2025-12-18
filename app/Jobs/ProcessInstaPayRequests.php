<?php

namespace App\Jobs;

use App\Services\InstaPayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * FIN-004: InstaPay Request Processing Job
 *
 * Processes pending InstaPay requests in batches.
 * This job is scheduled to run periodically to process
 * pending payout requests through the appropriate payment gateway.
 */
class ProcessInstaPayRequests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('instapay');
    }

    /**
     * Execute the job.
     */
    public function handle(InstaPayService $instaPayService): void
    {
        // Check if batch processing is enabled
        if (! config('instapay.batch.enabled', true)) {
            Log::info('InstaPay batch processing is disabled');

            return;
        }

        // Check if it's a processing day
        $processingDays = config('instapay.processing_days', [
            'monday', 'tuesday', 'wednesday', 'thursday', 'friday',
        ]);

        $today = strtolower(now()->format('l'));

        if (! in_array($today, $processingDays)) {
            Log::info('InstaPay batch skipped - not a processing day', [
                'day' => $today,
            ]);

            return;
        }

        Log::info('Starting InstaPay batch processing');

        try {
            $processed = $instaPayService->processAllPendingRequests();

            Log::info('InstaPay batch processing completed', [
                'processed' => $processed,
            ]);
        } catch (\Exception $e) {
            Log::error('InstaPay batch processing failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessInstaPayRequests job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
