<?php

namespace App\Jobs;

use App\Notifications\AdminAlertNotification;
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
     * @param  int|null  $agencyId
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
        Log::info('Starting weekly agency commission processing', [
            'agency_id' => $this->agencyId ?? 'all',
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            // Process payouts
            $summary = $commissionService->processWeeklyPayouts($this->agencyId);

            Log::info('Agency commission processing completed', $summary);

            // Send summary notification to admin
            $this->notifyAdmin($summary);
        } catch (\Exception $e) {
            Log::error('Failed to process agency commissions', [
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
     * @param  array  $summary
     * @return void
     */
    protected function notifyAdmin($summary)
    {
        // Determine severity based on failed payouts
        $severity = $summary['failed'] > 0
            ? AdminAlertNotification::SEVERITY_WARNING
            : AdminAlertNotification::SEVERITY_INFO;

        $message = sprintf(
            'Weekly commission processing completed. %d agencies processed, %d successful, %d failed. Total amount: %s',
            $summary['total_agencies'],
            $summary['successful'],
            $summary['failed'],
            number_format($summary['total_amount'], 2)
        );

        AdminAlertNotification::send(
            title: 'Weekly Agency Commission Processing Complete',
            message: $message,
            severity: $severity,
            context: [
                'total_agencies' => $summary['total_agencies'],
                'successful_payouts' => $summary['successful'],
                'failed_payouts' => $summary['failed'],
                'total_amount' => $summary['total_amount'],
                'processed_at' => now()->toDateTimeString(),
            ],
            actionUrl: '/panel/admin/agency-commissions',
            actionLabel: 'View Commission Details',
            category: 'agency_commissions'
        );

        Log::info('Agency commission payout summary', [
            'total_agencies' => $summary['total_agencies'],
            'successful_payouts' => $summary['successful'],
            'failed_payouts' => $summary['failed'],
            'total_amount' => $summary['total_amount'],
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::critical('Agency commission processing job failed after all retries', [
            'agency_id' => $this->agencyId,
            'error' => $exception->getMessage(),
        ]);

        $agencyInfo = $this->agencyId ? "Agency ID: {$this->agencyId}" : 'All agencies';

        AdminAlertNotification::send(
            title: 'Agency Commission Processing Job Failed',
            message: "The ProcessAgencyCommissions job has failed after all retry attempts. {$agencyInfo}. Commission payouts may not have been processed.",
            severity: AdminAlertNotification::SEVERITY_CRITICAL,
            context: [
                'agency_id' => $this->agencyId ?? 'all',
                'error' => $exception->getMessage(),
                'job' => 'ProcessAgencyCommissions',
                'failed_at' => now()->toDateTimeString(),
            ],
            actionUrl: '/panel/admin/failed-jobs',
            actionLabel: 'View Failed Jobs',
            category: 'job_failure'
        );
    }
}
