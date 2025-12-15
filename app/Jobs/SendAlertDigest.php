<?php

namespace App\Jobs;

use App\Services\AlertingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ADM-004: Send Alert Digest
 *
 * Scheduled job to send grouped alert digests.
 * Run every 4 hours to summarize suppressed alerts.
 */
class SendAlertDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    /**
     * Execute the job.
     */
    public function handle(AlertingService $alertingService): void
    {
        try {
            Log::info('Starting alert digest job');

            $alertingService->sendDigestSummary();

            Log::info('Alert digest job completed successfully');
        } catch (\Exception $e) {
            Log::error('Alert digest job failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
