<?php

namespace App\Jobs;

use App\Models\AgencyPerformanceNotification;
use App\Models\User;
use App\Notifications\Agency\AdminReviewRequiredNotification;
use App\Notifications\Agency\EscalationJobFailedNotification;
use App\Notifications\Agency\EscalationSummaryNotification;
use App\Notifications\Agency\PerformanceEscalationNotification;
use App\Services\AgencyPerformanceNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * EscalateUnacknowledgedNotifications Job
 *
 * Runs daily to check for unacknowledged notifications and escalate them.
 *
 * TASK: AGY-005 Performance Notification System - Escalation Workflow
 *
 * Schedule: Every day at 9:00 AM
 * Command: php artisan schedule:run
 * Kernel entry: $schedule->job(new EscalateUnacknowledgedNotifications)->daily()->at('09:00');
 *
 * Escalation workflow:
 * - If agency doesn't acknowledge within 48 hours, send follow-up and escalate
 * - After 7 days with no acknowledgment, require admin review
 * - Continue sending follow-ups every 24 hours until resolved
 */
class EscalateUnacknowledgedNotifications implements ShouldQueue
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
    public $backoff = 60;

    /**
     * Execute the job.
     */
    public function handle(AgencyPerformanceNotificationService $notificationService): void
    {
        Log::info("Starting escalation check for unacknowledged notifications", [
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            $summary = [
                'first_escalations' => 0,
                'follow_ups_sent' => 0,
                'admin_reviews_required' => 0,
                'total_processed' => 0,
                'errors' => 0,
            ];

            // Process notifications pending escalation (past due date)
            $pendingEscalation = AgencyPerformanceNotification::where('requires_acknowledgment', true)
                ->where('acknowledged', false)
                ->where('escalation_due_at', '<=', now())
                ->orderBy('severity', 'desc')
                ->orderBy('escalation_due_at', 'asc')
                ->get();

            Log::info("Found notifications pending escalation", [
                'count' => $pendingEscalation->count(),
            ]);

            foreach ($pendingEscalation as $notification) {
                $summary['total_processed']++;

                try {
                    $this->processEscalation($notification, $summary);
                } catch (\Exception $e) {
                    $summary['errors']++;
                    Log::error("Failed to process escalation for notification", [
                        'notification_id' => $notification->id,
                        'agency_id' => $notification->agency_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Also check for notifications that need scheduled follow-ups
            $this->processScheduledFollowUps($summary);

            Log::info("Escalation processing completed", $summary);

            // Notify admins if there were significant escalations
            if ($summary['admin_reviews_required'] > 0 || $summary['first_escalations'] > 3) {
                $this->notifyAdminsOfEscalations($summary);
            }
        } catch (\Exception $e) {
            Log::error("Escalation job failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process escalation for a single notification.
     */
    protected function processEscalation(AgencyPerformanceNotification $notification, array &$summary): void
    {
        $daysSinceSent = $notification->sent_at
            ? $notification->sent_at->diffInDays(now())
            : $notification->created_at->diffInDays(now());

        // Determine escalation action based on time and current level
        if ($notification->escalation_level === 0) {
            // First escalation - send follow-up to agency and mark as escalated
            $this->performFirstEscalation($notification);
            $summary['first_escalations']++;
        } elseif ($daysSinceSent >= AgencyPerformanceNotificationService::ADMIN_REVIEW_DAYS
            && !$notification->admin_reviewed) {
            // After 7 days, require admin review
            $this->requireAdminReview($notification);
            $summary['admin_reviews_required']++;
        } else {
            // Send follow-up reminder
            $this->sendFollowUp($notification);
            $summary['follow_ups_sent']++;
        }
    }

    /**
     * Perform first escalation.
     */
    protected function performFirstEscalation(AgencyPerformanceNotification $notification): void
    {
        // Find an admin to escalate to
        $admin = User::where('role', 'admin')->first();

        $notification->escalate(
            $admin?->id ?? 0,
            'Agency failed to acknowledge notification within 48 hours'
        );

        // Send follow-up notification to agency
        $agency = $notification->agency;
        if ($agency) {
            $agency->notify(new PerformanceEscalationNotification($notification));
        }

        // Update next follow-up time
        $notification->update([
            'next_follow_up_at' => now()->addHours(24),
        ]);

        Log::warning("Notification escalated - first level", [
            'notification_id' => $notification->id,
            'agency_id' => $notification->agency_id,
            'notification_type' => $notification->notification_type,
        ]);
    }

    /**
     * Send follow-up reminder.
     */
    protected function sendFollowUp(AgencyPerformanceNotification $notification): void
    {
        $notification->recordFollowUp();

        // Send follow-up to agency
        $agency = $notification->agency;
        if ($agency) {
            $agency->notify(new PerformanceEscalationNotification($notification, true));
        }

        Log::info("Follow-up sent for unacknowledged notification", [
            'notification_id' => $notification->id,
            'agency_id' => $notification->agency_id,
            'follow_up_count' => $notification->follow_up_count,
        ]);
    }

    /**
     * Require admin review.
     */
    protected function requireAdminReview(AgencyPerformanceNotification $notification): void
    {
        // Create admin review notification record
        $adminNotification = AgencyPerformanceNotification::createForAgency(
            $notification->agency_id,
            AgencyPerformanceNotification::TYPE_ADMIN_REVIEW,
            'Admin Review Required: Unresponsive Agency',
            "Agency has not responded to {$notification->type_display} after 7+ days. Manual review required.",
            [
                'scorecard_id' => $notification->scorecard_id,
                'requires_acknowledgment' => false,
                'severity' => AgencyPerformanceNotification::SEVERITY_CRITICAL,
            ]
        );

        // Escalate original notification to higher level
        $admin = User::where('role', 'admin')->first();
        $notification->escalate(
            $admin?->id ?? 0,
            'Agency unresponsive for 7+ days - admin review required'
        );

        // Notify all admins
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new AdminReviewRequiredNotification($notification));

        Log::critical("Admin review required for agency performance notification", [
            'original_notification_id' => $notification->id,
            'admin_notification_id' => $adminNotification->id,
            'agency_id' => $notification->agency_id,
            'days_unacknowledged' => $notification->created_at->diffInDays(now()),
        ]);
    }

    /**
     * Process scheduled follow-ups.
     */
    protected function processScheduledFollowUps(array &$summary): void
    {
        // Get notifications that have scheduled follow-ups due
        $dueFollowUps = AgencyPerformanceNotification::where('requires_acknowledgment', true)
            ->where('acknowledged', false)
            ->where('escalated', true)
            ->where('next_follow_up_at', '<=', now())
            ->where('follow_up_count', '<', 7) // Cap at 7 follow-ups
            ->get();

        foreach ($dueFollowUps as $notification) {
            try {
                $this->sendFollowUp($notification);
                $summary['follow_ups_sent']++;
            } catch (\Exception $e) {
                $summary['errors']++;
                Log::error("Failed to send scheduled follow-up", [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify admins of escalation summary.
     */
    protected function notifyAdminsOfEscalations(array $summary): void
    {
        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        foreach ($admins as $admin) {
            $admin->notify(new EscalationSummaryNotification($summary));
        }

        Log::info("Admins notified of escalation summary", [
            'admin_count' => $admins->count(),
            'summary' => $summary,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("Escalation job failed after all retries", [
            'error' => $exception->getMessage(),
        ]);

        // Notify admins of failure
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new EscalationJobFailedNotification($exception->getMessage()));
        }
    }
}
