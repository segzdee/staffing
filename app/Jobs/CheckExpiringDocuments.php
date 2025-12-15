<?php

namespace App\Jobs;

use App\Services\DocumentExpiryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Daily job to check for expiring documents and process expired ones.
 * WKR-006: Document Expiry Management
 * Scheduled to run daily at 02:00
 */
class CheckExpiringDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(DocumentExpiryService $service)
    {
        Log::info('Starting daily document expiry check');

        try {
            $summary = $service->checkExpiringDocuments();

            Log::info('Document expiry check completed', [
                'notifications_sent' => $summary['notifications_sent'],
                'documents_expired' => $summary['documents_expired'],
                'skills_deactivated' => $summary['skills_deactivated'],
                'workers_affected_count' => count($summary['workers_affected']),
            ]);

            // Log any critical issues (high number of expirations)
            if ($summary['documents_expired'] > 50) {
                Log::warning('High number of document expirations detected', [
                    'count' => $summary['documents_expired'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Document expiry check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
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
        Log::critical('Document expiry check job failed after all retries', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
