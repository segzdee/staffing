<?php

namespace App\Jobs;

use App\Services\OnboardingAnalyticsService;
use App\Models\OnboardingCohort;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * GenerateOnboardingReport Job
 *
 * Generates daily onboarding analytics reports.
 * Includes funnel data, completion rates, and intervention opportunities.
 *
 * Should be scheduled daily (e.g., at 6 AM).
 */
class GenerateOnboardingReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The date to generate the report for
     */
    protected ?Carbon $reportDate;

    /**
     * Whether to send email notification
     */
    protected bool $sendEmail;

    /**
     * Create a new job instance.
     */
    public function __construct(?Carbon $reportDate = null, bool $sendEmail = true)
    {
        $this->reportDate = $reportDate ?? now()->subDay();
        $this->sendEmail = $sendEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(OnboardingAnalyticsService $analyticsService): void
    {
        Log::info('Starting GenerateOnboardingReport job', [
            'report_date' => $this->reportDate->toDateString(),
        ]);

        try {
            // Generate the daily report
            $report = $analyticsService->generateDailyReport($this->reportDate);

            // Refresh cohort metrics for active experiments
            $this->refreshCohortMetrics();

            // Save report to storage
            $reportPath = $this->saveReport($report);

            // Log summary
            Log::info('OnboardingReport generated', [
                'date' => $this->reportDate->toDateString(),
                'total_signups' => $report['summary']['metrics']['total_signups'] ?? 0,
                'completion_rate' => $report['summary']['metrics']['completion_rate'] ?? 0,
                'interventions_needed' => $report['interventions_needed']['total_count'] ?? 0,
                'report_path' => $reportPath,
            ]);

            // Send email notification if enabled
            if ($this->sendEmail) {
                $this->sendReportEmail($report);
            }

        } catch (\Exception $e) {
            Log::error('GenerateOnboardingReport failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Refresh metrics for active cohorts
     */
    protected function refreshCohortMetrics(): void
    {
        $activeCohorts = OnboardingCohort::running()->get();

        foreach ($activeCohorts as $cohort) {
            try {
                $cohort->refreshMetrics();
            } catch (\Exception $e) {
                Log::warning('Failed to refresh cohort metrics', [
                    'cohort_id' => $cohort->cohort_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Save report to storage
     */
    protected function saveReport(array $report): string
    {
        $filename = 'onboarding_report_' . $this->reportDate->format('Y-m-d') . '.json';
        $path = 'reports/onboarding/' . $this->reportDate->format('Y/m') . '/' . $filename;

        Storage::put($path, json_encode($report, JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * Send report email to admins
     */
    protected function sendReportEmail(array $report): void
    {
        // Get admin email recipients from config
        $recipients = config('mail.admin_recipients', []);

        if (empty($recipients)) {
            Log::info('No admin recipients configured for onboarding report email');
            return;
        }

        // In production, send actual email
        // Mail::to($recipients)->send(new OnboardingReportMail($report));

        Log::info('Onboarding report email would be sent', [
            'recipients' => $recipients,
            'date' => $this->reportDate->toDateString(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateOnboardingReport job failed permanently', [
            'error' => $exception->getMessage(),
            'report_date' => $this->reportDate->toDateString(),
        ]);
    }
}
