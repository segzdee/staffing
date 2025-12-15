<?php

namespace App\Jobs;

use App\Services\UrgentFillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RouteUrgentShiftsToAgencies Job
 *
 * Runs every 15 minutes to detect and route urgent shifts to qualified agencies.
 *
 * TASK: AGY-004 Urgent Fill Routing
 *
 * Schedule: Every 15 minutes
 * Command: php artisan schedule:run
 * Kernel entry: $schedule->job(new RouteUrgentShiftsToAgencies)->everyFifteenMinutes();
 */
class RouteUrgentShiftsToAgencies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60; // 1 minute

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
    public function handle(UrgentFillService $urgentFillService)
    {
        Log::info("Starting urgent shift routing check", [
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            // Step 1: Detect urgent shifts
            $urgentShifts = $urgentFillService->detectUrgentShifts();

            if (empty($urgentShifts)) {
                Log::info("No urgent shifts detected");
                return;
            }

            Log::info("Urgent shifts detected", [
                'count' => count($urgentShifts),
            ]);

            // Step 2: Route to agencies
            $routingSummary = $urgentFillService->routeToAgencies();

            Log::info("Urgent shift routing completed", $routingSummary);

            // Step 3: Check SLA compliance
            $slaSummary = $urgentFillService->checkSLACompliance();

            if ($slaSummary['breached'] > 0) {
                Log::warning("SLA breaches detected", $slaSummary);
                $this->notifyAdminOfBreaches($slaSummary);
            }
        } catch (\Exception $e) {
            Log::error("Failed to route urgent shifts", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Notify admin of SLA breaches.
     *
     * @param array $slaSummary
     * @return void
     */
    protected function notifyAdminOfBreaches($slaSummary)
    {
        // TODO: Implement admin notification (email/Slack/in-app)

        Log::critical("Urgent shift SLA breaches require attention", [
            'total_breached' => $slaSummary['breached'],
            'approaching_breach' => $slaSummary['approaching_breach'],
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
        Log::critical("Urgent shift routing job failed after all retries", [
            'error' => $exception->getMessage(),
        ]);

        // TODO: Send critical alert to admin
    }
}
