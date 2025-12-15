<?php

namespace App\Jobs;

use App\Models\VerificationQueue;
use App\Models\User;
use App\Notifications\VerificationSLAWarningNotification;
use App\Notifications\VerificationSLABreachedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * MonitorVerificationSLAs Job - ADM-001
 *
 * Runs hourly to monitor verification queue SLA compliance:
 * - Updates SLA status for all pending/in_review verifications
 * - Sends warning notifications at 80% of SLA time elapsed
 * - Sends breach notifications when SLA is exceeded
 */
class MonitorVerificationSLAs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('MonitorVerificationSLAs: Starting SLA monitoring job');

        $stats = [
            'total_checked' => 0,
            'status_updates' => 0,
            'warnings_sent' => 0,
            'breach_alerts_sent' => 0,
            'errors' => 0,
        ];

        try {
            // Step 1: Update SLA status for all actionable verifications
            $stats = array_merge($stats, $this->updateAllSLAStatuses());

            // Step 2: Send warnings for at-risk items that haven't been notified
            $stats['warnings_sent'] = $this->sendSLAWarnings();

            // Step 3: Send breach alerts for breached items that haven't been notified
            $stats['breach_alerts_sent'] = $this->sendSLABreachAlerts();

            Log::info('MonitorVerificationSLAs: Job completed', $stats);

        } catch (\Exception $e) {
            Log::error('MonitorVerificationSLAs: Job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update SLA status for all actionable verifications
     */
    protected function updateAllSLAStatuses(): array
    {
        $stats = [
            'total_checked' => 0,
            'status_updates' => 0,
        ];

        // Get all pending/in_review verifications
        $verifications = VerificationQueue::actionable()
            ->whereNotNull('sla_deadline')
            ->get();

        $stats['total_checked'] = $verifications->count();

        foreach ($verifications as $verification) {
            $oldStatus = $verification->sla_status;

            // Update SLA status based on current time
            $verification->updateSLAStatus();

            // Only save if status changed
            if ($verification->sla_status !== $oldStatus) {
                $verification->save();
                $stats['status_updates']++;

                Log::debug('MonitorVerificationSLAs: SLA status updated', [
                    'verification_id' => $verification->id,
                    'old_status' => $oldStatus,
                    'new_status' => $verification->sla_status,
                ]);
            }
        }

        return $stats;
    }

    /**
     * Send SLA warning notifications for at-risk verifications
     */
    protected function sendSLAWarnings(): int
    {
        $warningsSent = 0;

        // Get verifications that need warning notifications
        $atRiskVerifications = VerificationQueue::needsSLAWarning()
            ->with('verifiable')
            ->get();

        if ($atRiskVerifications->isEmpty()) {
            return 0;
        }

        // Get admin users to notify
        $adminUsers = $this->getVerificationAdmins();

        if ($adminUsers->isEmpty()) {
            Log::warning('MonitorVerificationSLAs: No admin users found to notify about SLA warnings');
            return 0;
        }

        foreach ($atRiskVerifications as $verification) {
            try {
                // Send notification to admin team
                Notification::send($adminUsers, new VerificationSLAWarningNotification($verification));

                // Mark as notified to prevent duplicate notifications
                $verification->update(['sla_warning_sent_at' => now()]);

                $warningsSent++;

                Log::info('MonitorVerificationSLAs: SLA warning sent', [
                    'verification_id' => $verification->id,
                    'type' => $verification->verification_type,
                    'sla_deadline' => $verification->sla_deadline,
                ]);

            } catch (\Exception $e) {
                Log::error('MonitorVerificationSLAs: Failed to send SLA warning', [
                    'verification_id' => $verification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $warningsSent;
    }

    /**
     * Send SLA breach alerts for exceeded verifications
     */
    protected function sendSLABreachAlerts(): int
    {
        $alertsSent = 0;

        // Get verifications that need breach notifications
        $breachedVerifications = VerificationQueue::needsSLABreachNotification()
            ->with('verifiable')
            ->get();

        if ($breachedVerifications->isEmpty()) {
            return 0;
        }

        // Get admin users to notify
        $adminUsers = $this->getVerificationAdmins();

        if ($adminUsers->isEmpty()) {
            Log::warning('MonitorVerificationSLAs: No admin users found to notify about SLA breaches');
            return 0;
        }

        foreach ($breachedVerifications as $verification) {
            try {
                // Send notification to admin team
                Notification::send($adminUsers, new VerificationSLABreachedNotification($verification));

                // Mark as notified to prevent duplicate notifications
                $verification->update(['sla_breach_notified_at' => now()]);

                $alertsSent++;

                Log::warning('MonitorVerificationSLAs: SLA breach alert sent', [
                    'verification_id' => $verification->id,
                    'type' => $verification->verification_type,
                    'sla_deadline' => $verification->sla_deadline,
                    'hours_overdue' => abs($verification->sla_remaining_hours),
                ]);

            } catch (\Exception $e) {
                Log::error('MonitorVerificationSLAs: Failed to send SLA breach alert', [
                    'verification_id' => $verification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $alertsSent;
    }

    /**
     * Get admin users who should receive verification SLA notifications
     */
    protected function getVerificationAdmins()
    {
        return User::where('role', 'admin')
            ->where('status', 'active')
            ->where(function ($q) {
                // Super admins with full access
                $q->where('permission', 'full')
                  // Or admins with verification permission
                  ->orWhere('permissions', 'LIKE', '%verification%')
                  // Or admins with limited_access (they see dashboard)
                  ->orWhere('permissions', 'limited_access');
            })
            ->get();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('MonitorVerificationSLAs: Job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
