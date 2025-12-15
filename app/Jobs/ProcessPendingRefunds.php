<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPendingRefunds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 300; // 5 minutes

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RefundService $refundService)
    {
        Log::info('Starting pending refunds processing');

        // Get all pending refunds
        $pendingRefunds = Refund::pending()
            ->with(['business', 'shiftPayment'])
            ->orderBy('initiated_at', 'asc')
            ->limit(50) // Process in batches
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($pendingRefunds as $refund) {
            try {
                $success = $refundService->processRefund($refund);

                if ($success) {
                    $processed++;
                    Log::info("Successfully processed refund {$refund->refund_number}");
                } else {
                    $failed++;
                    Log::warning("Failed to process refund {$refund->refund_number}");
                }

                // Small delay to avoid rate limiting
                usleep(200000); // 0.2 seconds

            } catch (\Exception $e) {
                $failed++;
                Log::error("Error processing refund {$refund->refund_number}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Mark as failed
                $refund->markAsFailed($e->getMessage());
            }
        }

        Log::info('Pending refunds processing complete', [
            'total' => $pendingRefunds->count(),
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('ProcessPendingRefunds job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
