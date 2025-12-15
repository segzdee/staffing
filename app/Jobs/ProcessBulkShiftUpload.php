<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\BulkShiftService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Job for processing bulk shift uploads asynchronously.
 * BIZ-005: Bulk Shift Posting
 */
class ProcessBulkShiftUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $businessId;
    protected $validatedData;
    protected $batchId;

    public $timeout = 1800; // 30 minutes for large uploads
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param int $businessId
     * @param array $validatedData
     * @param string $batchId
     */
    public function __construct(int $businessId, array $validatedData, string $batchId)
    {
        $this->businessId = $businessId;
        $this->validatedData = $validatedData;
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(BulkShiftService $service)
    {
        Log::info('Starting bulk shift upload processing', [
            'batch_id' => $this->batchId,
            'business_id' => $this->businessId,
            'total_rows' => count($this->validatedData),
        ]);

        try {
            $business = User::findOrFail($this->businessId);

            // Update progress in cache
            Cache::put("bulk_upload_{$this->batchId}_status", 'processing', 3600);
            Cache::put("bulk_upload_{$this->batchId}_progress", 0, 3600);

            // Process shifts
            $results = $service->processShifts($business, $this->validatedData, $this->batchId);

            // Store results in cache for 1 hour
            Cache::put("bulk_upload_{$this->batchId}_results", $results, 3600);
            Cache::put("bulk_upload_{$this->batchId}_status", 'completed', 3600);
            Cache::put("bulk_upload_{$this->batchId}_progress", 100, 3600);

            // Send notification to business
            $business->notify(new \App\Notifications\BulkUploadCompletedNotification($results));

            Log::info('Bulk shift upload completed', [
                'batch_id' => $this->batchId,
                'successful' => $results['successful'],
                'failed' => $results['failed'],
            ]);
        } catch (\Exception $e) {
            Cache::put("bulk_upload_{$this->batchId}_status", 'failed', 3600);

            Log::error('Bulk shift upload failed', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        Log::critical('Bulk shift upload job failed after all retries', [
            'batch_id' => $this->batchId,
            'business_id' => $this->businessId,
            'error' => $exception->getMessage(),
        ]);

        Cache::put("bulk_upload_{$this->batchId}_status", 'failed', 3600);
        Cache::put("bulk_upload_{$this->batchId}_error", $exception->getMessage(), 3600);

        // Notify business of failure
        try {
            $business = User::find($this->businessId);
            if ($business) {
                $business->notify(new \App\Notifications\BulkUploadFailedNotification($this->batchId, $exception->getMessage()));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send bulk upload failure notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
