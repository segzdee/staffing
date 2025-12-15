<?php

namespace App\Jobs;

use App\Models\AgencyPerformanceScorecard;
use App\Models\User;
use App\Notifications\Agency\AdminReviewRequiredNotification;
use App\Services\AgencyPerformanceNotificationService;
use App\Services\AgencyPerformanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * GenerateAgencyScorecards Job
 *
 * Runs weekly to generate performance scorecards for all agencies.
 * After generating scorecards, processes notifications based on status changes.
 *
 * TASK: AGY-005 Performance Monitoring & Notification System
 *
 * Schedule: Every Monday at 1:00 AM
 * Command: php artisan schedule:run
 * Kernel entry: $schedule->job(new GenerateAgencyScorecards)->weekly()->mondays()->at('01:00');
 */
class GenerateAgencyScorecards implements ShouldQueue
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
     * Period start date (null for previous week).
     *
     * @var \Carbon\Carbon|null
     */
    protected $periodStart;

    /**
     * Period end date (null for previous week).
     *
     * @var \Carbon\Carbon|null
     */
    protected $periodEnd;

    /**
     * Create a new job instance.
     *
     * @param \Carbon\Carbon|null $periodStart
     * @param \Carbon\Carbon|null $periodEnd
     * @return void
     */
    public function __construct($periodStart = null, $periodEnd = null)
    {
        $this->periodStart = $periodStart;
        $this->periodEnd = $periodEnd;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        AgencyPerformanceService $performanceService,
        AgencyPerformanceNotificationService $notificationService
    ) {
        Log::info("Starting weekly agency scorecard generation", [
            'period_start' => $this->periodStart ? $this->periodStart->toDateString() : 'previous_week',
            'period_end' => $this->periodEnd ? $this->periodEnd->toDateString() : 'previous_week',
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            // Generate scorecards for all agencies
            $summary = $performanceService->generateWeeklyScorecards(
                $this->periodStart,
                $this->periodEnd
            );

            Log::info("Agency scorecard generation completed", $summary);

            // Process notifications for each scorecard
            $notificationSummary = $this->processNotifications($notificationService, $summary);

            // Merge notification summary into main summary
            $summary['notifications'] = $notificationSummary;

            // Send summary to admin
            $this->notifyAdmin($summary);

            // Alert if critical issues detected
            if ($summary['red'] > 0 || $summary['sanctions_applied'] > 0) {
                $this->alertCriticalIssues($summary);
            }
        } catch (\Exception $e) {
            Log::error("Failed to generate agency scorecards", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Process notifications for all generated scorecards.
     *
     * @param AgencyPerformanceNotificationService $notificationService
     * @param array $summary
     * @return array
     */
    protected function processNotifications(
        AgencyPerformanceNotificationService $notificationService,
        array $summary
    ): array {
        $periodStart = $this->periodStart ?? now()->subWeek()->startOfWeek();
        $periodEnd = $this->periodEnd ?? now()->subWeek()->endOfWeek();

        // Get all scorecards just generated for this period
        $scorecards = AgencyPerformanceScorecard::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->get();

        $notificationSummary = [
            'total_processed' => 0,
            'notifications_sent' => 0,
            'yellow_warnings' => 0,
            'red_alerts' => 0,
            'fee_increases' => 0,
            'suspensions' => 0,
            'improvements' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($scorecards as $scorecard) {
            $notificationSummary['total_processed']++;

            try {
                $result = $notificationService->processScorecard($scorecard);

                if (!empty($result['notifications_sent'])) {
                    $notificationSummary['notifications_sent'] += count($result['notifications_sent']);

                    // Count by type
                    foreach ($result['notifications_sent'] as $notification) {
                        switch ($notification['type']) {
                            case 'yellow_warning':
                                $notificationSummary['yellow_warnings']++;
                                break;
                            case 'red_alert':
                                $notificationSummary['red_alerts']++;
                                break;
                            case 'fee_increase':
                                $notificationSummary['fee_increases']++;
                                break;
                            case 'suspension':
                                $notificationSummary['suspensions']++;
                                break;
                            case 'improvement':
                                $notificationSummary['improvements']++;
                                break;
                        }
                    }
                }

                if (!empty($result['skipped'])) {
                    $notificationSummary['skipped']++;
                }
            } catch (\Exception $e) {
                $notificationSummary['errors']++;
                Log::error("Failed to process notification for scorecard", [
                    'scorecard_id' => $scorecard->id,
                    'agency_id' => $scorecard->agency_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Notification processing completed", $notificationSummary);

        return $notificationSummary;
    }

    /**
     * Send summary notification to admin.
     *
     * @param array $summary
     * @return void
     */
    protected function notifyAdmin($summary)
    {
        Log::info("Agency scorecard summary", [
            'total_agencies' => $summary['total_agencies'],
            'green_status' => $summary['green'],
            'yellow_status' => $summary['yellow'],
            'red_status' => $summary['red'],
            'warnings_sent' => $summary['warnings_sent'],
            'sanctions_applied' => $summary['sanctions_applied'],
            'notifications' => $summary['notifications'] ?? [],
        ]);

        // Send summary email to admins
        $admins = User::where('role', 'admin')->get();

        if ($admins->isNotEmpty()) {
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\Agency\WeeklyScorecardSummaryNotification($summary));
            }
        }
    }

    /**
     * Alert admin of critical performance issues.
     *
     * @param array $summary
     * @return void
     */
    protected function alertCriticalIssues($summary)
    {
        Log::warning("Critical agency performance issues detected", [
            'red_scorecards' => $summary['red'],
            'sanctions_applied' => $summary['sanctions_applied'],
            'suspensions' => $summary['notifications']['suspensions'] ?? 0,
        ]);

        // Get scorecards with critical issues for this period
        $periodStart = $this->periodStart ?? now()->subWeek()->startOfWeek();
        $periodEnd = $this->periodEnd ?? now()->subWeek()->endOfWeek();

        $criticalScorecards = AgencyPerformanceScorecard::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->where('status', 'red')
            ->with(['agency', 'agency.agencyProfile'])
            ->get();

        // Send urgent notification to admins
        $admins = User::where('role', 'admin')->get();

        if ($admins->isNotEmpty() && $criticalScorecards->isNotEmpty()) {
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\Agency\CriticalPerformanceAlertNotification(
                    $criticalScorecards,
                    $summary
                ));
            }
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
        Log::critical("Agency scorecard generation job failed after all retries", [
            'error' => $exception->getMessage(),
        ]);

        // Send critical alert to all admins
        $admins = User::where('role', 'admin')->get();

        if ($admins->isNotEmpty()) {
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\Agency\ScorecardGenerationFailedNotification(
                    $exception->getMessage()
                ));
            }
        }
    }
}
