<?php

namespace App\Jobs;

use App\Services\AgencyCommissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessAgencyCommissions Job
 *
 * Runs weekly to process agency commission payouts.
 *
 * TASK: AGY-003 Commission Automation
 *
 * Schedule: Every Monday at 2:00 AM
 * Command: php artisan schedule:run
 * Kernel entry: $schedule->job(new ProcessAgencyCommissions)->weekly()->mondays()->at('02:00');
 */
class ProcessAgencyCommissions implements ShouldQueue
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
     * Process specific agency ID (null for all agencies).
     *
     * @var int|null
     */
    protected $agencyId;

    /**
     * Create a new job instance.
     *
     * @param int|null $agencyId
     * @return void
     */
    public function __construct($agencyId = null)
    {
        $this->agencyId = $agencyId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AgencyCommissionService $commissionService)
    {
        Log::info("Starting weekly agency commission processing", [
            'agency_id' => $this->agencyId ?? 'all',
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            // Process payouts
            $summary = $commissionService->processWeeklyPayouts($this->agencyId);

            Log::info("Agency commission processing completed", $summary);

            // Send summary notification to admin
            $this->notifyAdmin($summary);
        } catch (\Exception $e) {
            Log::error("Failed to process agency commissions", [
                'agency_id' => $this->agencyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Send summary notification to admin.
     *
     * @param array $summary
     * @return void
     */
    protected function notifyAdmin($summary)
    {
        // TODO: Implement admin notification
        // This could be email, Slack, or in-app notification

        Log::info("Agency commission payout summary", [
            'total_agencies' => $summary['total_agencies'],
            'successful_payouts' => $summary['successful'],
            'failed_payouts' => $summary['failed'],
            'total_amount' => $summary['total_amount'],
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::critical("Agency commission processing job failed after all retries", [
            'agency_id' => $this->agencyId,
            'error' => $exception->getMessage(),
        ]);

        // TODO: Send critical alert to admin
    }
}
