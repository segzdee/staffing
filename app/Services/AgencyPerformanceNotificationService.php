<?php

namespace App\Services;

use App\Models\AgencyPerformanceNotification;
use App\Models\AgencyPerformanceScorecard;
use App\Models\AgencyProfile;
use App\Models\User;
use App\Notifications\Agency\PerformanceYellowWarningNotification;
use App\Notifications\Agency\PerformanceRedAlertNotification;
use App\Notifications\Agency\PerformanceFeeIncreaseNotification;
use App\Notifications\Agency\PerformanceSuspensionNotification;
use App\Notifications\Agency\PerformanceImprovementNotification;
use App\Notifications\Agency\AdminReviewRequiredNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * AgencyPerformanceNotificationService
 *
 * Handles the logic for determining, sending, and tracking agency performance notifications.
 * AGY-005: Agency Performance Notification System
 *
 * Features:
 * - Determines appropriate notification type based on status changes
 * - Prevents duplicate notifications
 * - Generates action plans and recommendations
 * - Tracks notification history
 * - Handles escalation workflow
 */
class AgencyPerformanceNotificationService
{
    // Improvement deadlines (in days)
    const YELLOW_IMPROVEMENT_DEADLINE_DAYS = 14; // 2 weeks
    const RED_IMPROVEMENT_DEADLINE_DAYS = 7;     // 1 week

    // Escalation thresholds
    const ESCALATION_HOURS = 48;
    const ADMIN_REVIEW_DAYS = 7;

    /**
     * Process notifications for a newly generated scorecard.
     *
     * @param AgencyPerformanceScorecard $scorecard
     * @return array Summary of notifications sent
     */
    public function processScorecard(AgencyPerformanceScorecard $scorecard): array
    {
        $summary = [
            'scorecard_id' => $scorecard->id,
            'agency_id' => $scorecard->agency_id,
            'current_status' => $scorecard->status,
            'notifications_sent' => [],
            'skipped' => [],
        ];

        // Get previous scorecard to determine status change
        $previousScorecard = $this->getPreviousScorecard($scorecard);
        $previousStatus = $previousScorecard?->status ?? 'green';

        // Determine what notification type to send
        $notificationType = $this->determineNotificationType($scorecard, $previousStatus);

        if (!$notificationType) {
            $summary['skipped'][] = 'No notification required for current status transition';
            Log::info("No notification required for agency", [
                'agency_id' => $scorecard->agency_id,
                'current_status' => $scorecard->status,
                'previous_status' => $previousStatus,
            ]);
            return $summary;
        }

        // Check if we should send this notification
        if (!$this->shouldSendNotification($scorecard->agency_id, $notificationType, $scorecard->id)) {
            $summary['skipped'][] = "Duplicate {$notificationType} notification prevented";
            Log::info("Duplicate notification prevented", [
                'agency_id' => $scorecard->agency_id,
                'type' => $notificationType,
            ]);
            return $summary;
        }

        // Get consecutive status counts
        $consecutiveCounts = $this->getConsecutiveStatusCounts($scorecard->agency_id, $scorecard->period_end);

        // Generate action plan
        $actionPlan = $this->generateActionPlan($scorecard, $notificationType);

        // Create and send notification
        $result = $this->sendNotification(
            $scorecard,
            $notificationType,
            $previousStatus,
            $consecutiveCounts,
            $actionPlan
        );

        if ($result) {
            $summary['notifications_sent'][] = [
                'type' => $notificationType,
                'notification_id' => $result->id,
            ];
        }

        return $summary;
    }

    /**
     * Determine the appropriate notification type based on status transition.
     *
     * @param AgencyPerformanceScorecard $scorecard
     * @param string $previousStatus
     * @return string|null
     */
    public function determineNotificationType(AgencyPerformanceScorecard $scorecard, string $previousStatus): ?string
    {
        $currentStatus = $scorecard->status;

        // Check for suspension (3+ consecutive red)
        if ($scorecard->sanction_type === 'suspension') {
            return AgencyPerformanceNotification::TYPE_SUSPENSION;
        }

        // Check for fee increase
        if ($scorecard->sanction_type === 'fee_increase') {
            return AgencyPerformanceNotification::TYPE_FEE_INCREASE;
        }

        // Status improvements
        if ($this->isImprovement($previousStatus, $currentStatus)) {
            return AgencyPerformanceNotification::TYPE_IMPROVEMENT;
        }

        // Status degradations
        if ($currentStatus === 'red' && $previousStatus !== 'red') {
            return AgencyPerformanceNotification::TYPE_RED_ALERT;
        }

        if ($currentStatus === 'yellow' && $previousStatus === 'green') {
            return AgencyPerformanceNotification::TYPE_YELLOW_WARNING;
        }

        // Sustained poor performance (still red/yellow)
        if ($currentStatus === 'red' && $previousStatus === 'red') {
            // Check consecutive count to determine if we need another notification
            $consecutiveRed = $this->getConsecutiveRedCount($scorecard->agency_id, $scorecard->period_end);
            if ($consecutiveRed === 2) {
                return AgencyPerformanceNotification::TYPE_FEE_INCREASE;
            }
        }

        return null;
    }

    /**
     * Check if a notification should be sent (avoid duplicates).
     *
     * @param int $agencyId
     * @param string $notificationType
     * @param int|null $scorecardId
     * @return bool
     */
    public function shouldSendNotification(int $agencyId, string $notificationType, ?int $scorecardId = null): bool
    {
        // Check for recent duplicate of same type
        $recentNotification = AgencyPerformanceNotification::where('agency_id', $agencyId)
            ->where('notification_type', $notificationType)
            ->where('created_at', '>=', now()->subDays(7))
            ->first();

        if ($recentNotification) {
            // Allow if it's for a different scorecard
            if ($scorecardId && $recentNotification->scorecard_id !== $scorecardId) {
                return true;
            }
            return false;
        }

        // Improvement notifications can always be sent if status actually improved
        if ($notificationType === AgencyPerformanceNotification::TYPE_IMPROVEMENT) {
            return true;
        }

        return true;
    }

    /**
     * Track that a notification was sent.
     *
     * @param AgencyPerformanceNotification $notification
     * @return void
     */
    public function trackNotificationSent(AgencyPerformanceNotification $notification): void
    {
        $notification->markAsSent();

        Log::info("Agency performance notification sent", [
            'notification_id' => $notification->id,
            'agency_id' => $notification->agency_id,
            'type' => $notification->notification_type,
            'severity' => $notification->severity,
        ]);
    }

    /**
     * Generate action plan based on scorecard metrics.
     *
     * @param AgencyPerformanceScorecard $scorecard
     * @param string $notificationType
     * @return array
     */
    public function generateActionPlan(AgencyPerformanceScorecard $scorecard, string $notificationType): array
    {
        $actionItems = [];
        $failedMetrics = $scorecard->getFailedMetrics();

        foreach ($failedMetrics as $metric) {
            $actionItems = array_merge($actionItems, $this->getActionsForMetric($metric));
        }

        // Add type-specific actions
        $actionItems = array_merge($actionItems, $this->getTypeSpecificActions($notificationType));

        return [
            'items' => $actionItems,
            'priority' => $this->calculatePriority($failedMetrics),
            'estimated_improvement_time' => $this->estimateImprovementTime($failedMetrics),
            'support_resources' => $this->getSupportResources($notificationType),
        ];
    }

    /**
     * Get improvement actions for a specific failed metric.
     *
     * @param array $metric
     * @return array
     */
    protected function getActionsForMetric(array $metric): array
    {
        $actions = [];

        switch ($metric['metric']) {
            case 'Fill Rate':
                $actions[] = 'Review worker availability and ensure adequate coverage for all shifts';
                $actions[] = 'Expand your worker pool by onboarding additional qualified workers';
                $actions[] = 'Implement better shift notification systems to improve response times';
                if ($metric['severity'] === 'critical') {
                    $actions[] = 'Consider declining shifts you cannot reliably fill';
                }
                break;

            case 'No-Show Rate':
                $actions[] = 'Implement stricter worker confirmation requirements (24-hour confirmation)';
                $actions[] = 'Add automated shift reminders via SMS and email';
                $actions[] = 'Review and address workers with high no-show rates';
                $actions[] = 'Consider adding backup workers for critical shifts';
                break;

            case 'Average Worker Rating':
                $actions[] = 'Review feedback from businesses to identify common issues';
                $actions[] = 'Provide additional training to underperforming workers';
                $actions[] = 'Implement quality check-ins during shifts';
                $actions[] = 'Remove consistently low-rated workers from your pool';
                break;

            case 'Complaint Rate':
                $actions[] = 'Investigate recent complaints and identify root causes';
                $actions[] = 'Improve worker vetting and screening processes';
                $actions[] = 'Implement feedback loops to catch issues early';
                $actions[] = 'Provide customer service training to workers';
                break;
        }

        return $actions;
    }

    /**
     * Get notification-type-specific actions.
     *
     * @param string $type
     * @return array
     */
    protected function getTypeSpecificActions(string $type): array
    {
        return match ($type) {
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING => [
                'Review this scorecard within 48 hours and acknowledge receipt',
                'Create an improvement plan addressing the flagged metrics',
                'Schedule a check-in with your account manager if needed',
            ],
            AgencyPerformanceNotification::TYPE_RED_ALERT => [
                'URGENT: Acknowledge this alert within 24 hours',
                'Submit a detailed improvement action plan within 48 hours',
                'Consider temporarily reducing shift acceptance until metrics improve',
                'Contact support immediately if you need assistance',
            ],
            AgencyPerformanceNotification::TYPE_FEE_INCREASE => [
                'Understand that the fee increase is effective immediately',
                'Focus on improving metrics to return to standard rates',
                'Contact billing support if you have questions about the increase',
            ],
            AgencyPerformanceNotification::TYPE_SUSPENSION => [
                'Understand that you cannot accept new shifts during suspension',
                'Prepare an appeal with evidence of improvement measures if applicable',
                'Complete any in-progress shifts professionally',
                'Contact support to discuss reinstatement requirements',
            ],
            default => [],
        };
    }

    /**
     * Send the appropriate notification.
     *
     * @param AgencyPerformanceScorecard $scorecard
     * @param string $type
     * @param string $previousStatus
     * @param array $consecutiveCounts
     * @param array $actionPlan
     * @return AgencyPerformanceNotification|null
     */
    protected function sendNotification(
        AgencyPerformanceScorecard $scorecard,
        string $type,
        string $previousStatus,
        array $consecutiveCounts,
        array $actionPlan
    ): ?AgencyPerformanceNotification {
        $agency = User::find($scorecard->agency_id);

        if (!$agency) {
            Log::error("Agency not found for notification", ['agency_id' => $scorecard->agency_id]);
            return null;
        }

        // Determine improvement deadline
        $improvementDeadline = $this->calculateImprovementDeadline($type);

        // Get fee information if applicable
        $feeInfo = $this->getFeeInformation($scorecard);

        // Create notification record
        $notification = AgencyPerformanceNotification::createForAgency(
            $scorecard->agency_id,
            $type,
            $this->getNotificationTitle($type),
            $this->getNotificationMessage($type, $scorecard),
            [
                'scorecard_id' => $scorecard->id,
                'status_at_notification' => $scorecard->status,
                'previous_status' => $previousStatus,
                'metrics_snapshot' => $this->createMetricsSnapshot($scorecard),
                'action_items' => $actionPlan['items'],
                'improvement_deadline' => $improvementDeadline,
                'consecutive_yellow_weeks' => $consecutiveCounts['yellow'],
                'consecutive_red_weeks' => $consecutiveCounts['red'],
                'previous_commission_rate' => $feeInfo['previous_rate'],
                'new_commission_rate' => $feeInfo['new_rate'],
            ]
        );

        // Send the actual notification via Laravel's notification system
        try {
            $notificationClass = $this->getNotificationClass($type);
            $agency->notify(new $notificationClass($notification, $scorecard, $actionPlan));

            $this->trackNotificationSent($notification);

            Log::info("Performance notification sent successfully", [
                'notification_id' => $notification->id,
                'type' => $type,
                'agency_id' => $agency->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send performance notification", [
                'notification_id' => $notification->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }

        return $notification;
    }

    /**
     * Get the notification class for a type.
     *
     * @param string $type
     * @return string
     */
    protected function getNotificationClass(string $type): string
    {
        return match ($type) {
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING => PerformanceYellowWarningNotification::class,
            AgencyPerformanceNotification::TYPE_RED_ALERT => PerformanceRedAlertNotification::class,
            AgencyPerformanceNotification::TYPE_FEE_INCREASE => PerformanceFeeIncreaseNotification::class,
            AgencyPerformanceNotification::TYPE_SUSPENSION => PerformanceSuspensionNotification::class,
            AgencyPerformanceNotification::TYPE_IMPROVEMENT => PerformanceImprovementNotification::class,
            default => throw new \InvalidArgumentException("Unknown notification type: {$type}"),
        };
    }

    /**
     * Get notification title.
     *
     * @param string $type
     * @return string
     */
    protected function getNotificationTitle(string $type): string
    {
        return match ($type) {
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING => 'Performance Warning: Action Required',
            AgencyPerformanceNotification::TYPE_RED_ALERT => 'URGENT: Critical Performance Alert',
            AgencyPerformanceNotification::TYPE_FEE_INCREASE => 'Notice: Commission Rate Increase',
            AgencyPerformanceNotification::TYPE_SUSPENSION => 'Account Suspended: Immediate Action Required',
            AgencyPerformanceNotification::TYPE_IMPROVEMENT => 'Congratulations: Performance Improved',
            AgencyPerformanceNotification::TYPE_ESCALATION => 'Escalation: Unacknowledged Notification',
            AgencyPerformanceNotification::TYPE_ADMIN_REVIEW => 'Admin Review Required',
            default => 'Performance Notification',
        };
    }

    /**
     * Get notification message.
     *
     * @param string $type
     * @param AgencyPerformanceScorecard $scorecard
     * @return string
     */
    protected function getNotificationMessage(string $type, AgencyPerformanceScorecard $scorecard): string
    {
        $periodStr = $scorecard->period_start->format('M j') . ' - ' . $scorecard->period_end->format('M j, Y');

        return match ($type) {
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING =>
                "Your agency performance for {$periodStr} has fallen below target thresholds. " .
                "Please review the metrics below and take action to improve within 2 weeks.",

            AgencyPerformanceNotification::TYPE_RED_ALERT =>
                "URGENT: Your agency performance for {$periodStr} is critically below acceptable levels. " .
                "Immediate action is required within 1 week to avoid further consequences.",

            AgencyPerformanceNotification::TYPE_FEE_INCREASE =>
                "Due to sustained poor performance, your commission rate has been increased. " .
                "Please review your metrics and take immediate steps to improve.",

            AgencyPerformanceNotification::TYPE_SUSPENSION =>
                "Your agency account has been suspended due to 3 consecutive weeks of critical performance issues. " .
                "You cannot accept new shifts until your account is reinstated.",

            AgencyPerformanceNotification::TYPE_IMPROVEMENT =>
                "Great news! Your agency performance for {$periodStr} has improved. " .
                "Keep up the excellent work to maintain your good standing.",

            default => "Please review your performance scorecard for {$periodStr}.",
        };
    }

    /**
     * Calculate improvement deadline based on notification type.
     *
     * @param string $type
     * @return \Carbon\Carbon|null
     */
    protected function calculateImprovementDeadline(string $type): ?\Carbon\Carbon
    {
        return match ($type) {
            AgencyPerformanceNotification::TYPE_YELLOW_WARNING =>
                now()->addDays(self::YELLOW_IMPROVEMENT_DEADLINE_DAYS),
            AgencyPerformanceNotification::TYPE_RED_ALERT =>
                now()->addDays(self::RED_IMPROVEMENT_DEADLINE_DAYS),
            default => null,
        };
    }

    /**
     * Create metrics snapshot.
     *
     * @param AgencyPerformanceScorecard $scorecard
     * @return array
     */
    protected function createMetricsSnapshot(AgencyPerformanceScorecard $scorecard): array
    {
        return [
            'fill_rate' => [
                'actual' => $scorecard->fill_rate,
                'target' => $scorecard->target_fill_rate,
                'variance' => round($scorecard->fill_rate - $scorecard->target_fill_rate, 2),
            ],
            'no_show_rate' => [
                'actual' => $scorecard->no_show_rate,
                'target' => $scorecard->target_no_show_rate,
                'variance' => round($scorecard->no_show_rate - $scorecard->target_no_show_rate, 2),
            ],
            'average_rating' => [
                'actual' => $scorecard->average_worker_rating,
                'target' => $scorecard->target_average_rating,
                'variance' => round($scorecard->average_worker_rating - $scorecard->target_average_rating, 2),
            ],
            'complaint_rate' => [
                'actual' => $scorecard->complaint_rate,
                'target' => $scorecard->target_complaint_rate,
                'variance' => round($scorecard->complaint_rate - $scorecard->target_complaint_rate, 2),
            ],
            'total_shifts' => $scorecard->total_shifts_assigned,
            'shifts_filled' => $scorecard->shifts_filled,
            'no_shows' => $scorecard->no_shows,
            'complaints' => $scorecard->complaints_received,
            'trend' => $scorecard->getTrend(),
        ];
    }

    /**
     * Get fee information.
     *
     * @param AgencyPerformanceScorecard $scorecard
     * @return array
     */
    protected function getFeeInformation(AgencyPerformanceScorecard $scorecard): array
    {
        $agencyProfile = AgencyProfile::where('user_id', $scorecard->agency_id)->first();

        return [
            'previous_rate' => $scorecard->sanction_type === 'fee_increase'
                ? max(0, $agencyProfile?->commission_rate - 2.00)
                : null,
            'new_rate' => $scorecard->sanction_type === 'fee_increase'
                ? $agencyProfile?->commission_rate
                : null,
        ];
    }

    /**
     * Check if current status is an improvement.
     *
     * @param string $previousStatus
     * @param string $currentStatus
     * @return bool
     */
    protected function isImprovement(string $previousStatus, string $currentStatus): bool
    {
        $statusOrder = ['green' => 3, 'yellow' => 2, 'red' => 1];

        return $statusOrder[$currentStatus] > $statusOrder[$previousStatus];
    }

    /**
     * Get the previous scorecard for an agency.
     *
     * @param AgencyPerformanceScorecard $currentScorecard
     * @return AgencyPerformanceScorecard|null
     */
    protected function getPreviousScorecard(AgencyPerformanceScorecard $currentScorecard): ?AgencyPerformanceScorecard
    {
        return AgencyPerformanceScorecard::where('agency_id', $currentScorecard->agency_id)
            ->where('period_end', '<', $currentScorecard->period_start)
            ->orderBy('period_end', 'desc')
            ->first();
    }

    /**
     * Get consecutive status counts.
     *
     * @param int $agencyId
     * @param \Carbon\Carbon $beforeDate
     * @return array
     */
    public function getConsecutiveStatusCounts(int $agencyId, $beforeDate): array
    {
        $scorecards = AgencyPerformanceScorecard::where('agency_id', $agencyId)
            ->where('period_end', '<=', $beforeDate)
            ->orderBy('period_end', 'desc')
            ->limit(10)
            ->get();

        $consecutiveRed = 0;
        $consecutiveYellow = 0;

        foreach ($scorecards as $scorecard) {
            if ($scorecard->status === 'red') {
                // Stop counting red if we already started counting yellow
                if ($consecutiveYellow > 0) {
                    break;
                }
                $consecutiveRed++;
            } elseif ($scorecard->status === 'yellow') {
                // Stop counting yellow if we already started counting red
                if ($consecutiveRed > 0) {
                    break;
                }
                $consecutiveYellow++;
            } else {
                break;
            }
        }

        return [
            'red' => $consecutiveRed,
            'yellow' => $consecutiveYellow,
        ];
    }

    /**
     * Get consecutive red count.
     *
     * @param int $agencyId
     * @param \Carbon\Carbon $beforeDate
     * @return int
     */
    protected function getConsecutiveRedCount(int $agencyId, $beforeDate): int
    {
        return $this->getConsecutiveStatusCounts($agencyId, $beforeDate)['red'];
    }

    /**
     * Calculate priority based on failed metrics.
     *
     * @param array $failedMetrics
     * @return string
     */
    protected function calculatePriority(array $failedMetrics): string
    {
        $criticalCount = collect($failedMetrics)->where('severity', 'critical')->count();

        if ($criticalCount >= 2) {
            return 'critical';
        } elseif ($criticalCount === 1) {
            return 'high';
        } elseif (count($failedMetrics) > 0) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Estimate improvement time based on failed metrics.
     *
     * @param array $failedMetrics
     * @return string
     */
    protected function estimateImprovementTime(array $failedMetrics): string
    {
        $criticalCount = collect($failedMetrics)->where('severity', 'critical')->count();

        if ($criticalCount >= 2) {
            return '4-6 weeks with focused effort';
        } elseif ($criticalCount === 1) {
            return '2-4 weeks with consistent improvement';
        } elseif (count($failedMetrics) > 0) {
            return '1-2 weeks with attention to details';
        }

        return 'No significant improvement needed';
    }

    /**
     * Get support resources based on notification type.
     *
     * @param string $type
     * @return array
     */
    protected function getSupportResources(string $type): array
    {
        $resources = [
            'help_center' => url('/help/agency-performance'),
            'account_manager' => 'Contact your dedicated account manager',
            'support_email' => config('mail.support_address', 'support@overtimestaff.com'),
        ];

        if (in_array($type, [
            AgencyPerformanceNotification::TYPE_RED_ALERT,
            AgencyPerformanceNotification::TYPE_SUSPENSION,
        ])) {
            $resources['emergency_support'] = config('app.emergency_support_phone', '1-800-XXX-XXXX');
            $resources['appeal_form'] = url('/agency/performance/appeal');
        }

        return $resources;
    }

    /**
     * Process escalation for unacknowledged notifications.
     *
     * @return array Summary of escalations
     */
    public function processEscalations(): array
    {
        $summary = [
            'escalated' => 0,
            'admin_review_required' => 0,
            'follow_ups_sent' => 0,
        ];

        // Get notifications pending escalation
        $pendingEscalation = AgencyPerformanceNotification::pendingEscalation()->get();

        foreach ($pendingEscalation as $notification) {
            if ($notification->escalation_level === 0) {
                // First escalation - send follow-up and escalate
                $this->escalateNotification($notification);
                $summary['escalated']++;
            } elseif ($notification->created_at->diffInDays(now()) >= self::ADMIN_REVIEW_DAYS) {
                // After 7 days, require admin review
                $this->requestAdminReview($notification);
                $summary['admin_review_required']++;
            } else {
                // Send follow-up
                $notification->recordFollowUp();
                $summary['follow_ups_sent']++;
            }
        }

        return $summary;
    }

    /**
     * Escalate a notification.
     *
     * @param AgencyPerformanceNotification $notification
     * @return void
     */
    protected function escalateNotification(AgencyPerformanceNotification $notification): void
    {
        // Find an admin to escalate to
        $admin = User::where('role', 'admin')->first();

        if ($admin) {
            $notification->escalate(
                $admin->id,
                'Agency failed to acknowledge notification within 48 hours'
            );

            // Send escalation notification to admin
            $admin->notify(new AdminReviewRequiredNotification($notification));
        }

        Log::warning("Performance notification escalated", [
            'notification_id' => $notification->id,
            'agency_id' => $notification->agency_id,
            'escalation_level' => $notification->escalation_level,
        ]);
    }

    /**
     * Request admin review for a notification.
     *
     * @param AgencyPerformanceNotification $notification
     * @return void
     */
    protected function requestAdminReview(AgencyPerformanceNotification $notification): void
    {
        // Create admin review notification
        $adminNotification = AgencyPerformanceNotification::createForAgency(
            $notification->agency_id,
            AgencyPerformanceNotification::TYPE_ADMIN_REVIEW,
            'Admin Review Required: Unresponsive Agency',
            "Agency has not responded to critical performance notification after 7 days. Manual review required.",
            [
                'scorecard_id' => $notification->scorecard_id,
                'requires_acknowledgment' => false,
            ]
        );

        // Notify all admins
        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new AdminReviewRequiredNotification($notification));

        Log::critical("Admin review required for agency performance", [
            'original_notification_id' => $notification->id,
            'admin_notification_id' => $adminNotification->id,
            'agency_id' => $notification->agency_id,
        ]);
    }

    /**
     * Get notification history for an agency.
     *
     * @param int $agencyId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotificationHistory(int $agencyId, int $limit = 20)
    {
        return AgencyPerformanceNotification::forAgency($agencyId)
            ->with(['scorecard'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get unacknowledged notifications for an agency.
     *
     * @param int $agencyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnacknowledgedNotifications(int $agencyId)
    {
        return AgencyPerformanceNotification::forAgency($agencyId)
            ->unacknowledged()
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Acknowledge a notification.
     *
     * @param int $notificationId
     * @param int $userId
     * @param string|null $notes
     * @return AgencyPerformanceNotification
     */
    public function acknowledgeNotification(int $notificationId, int $userId, ?string $notes = null): AgencyPerformanceNotification
    {
        $notification = AgencyPerformanceNotification::findOrFail($notificationId);
        $notification->acknowledge($userId, $notes);

        Log::info("Performance notification acknowledged", [
            'notification_id' => $notificationId,
            'acknowledged_by' => $userId,
        ]);

        return $notification;
    }

    /**
     * Get agencies with recent alerts for admin dashboard.
     *
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAgenciesWithRecentAlerts(int $days = 7)
    {
        return AgencyPerformanceNotification::whereIn('notification_type', [
                AgencyPerformanceNotification::TYPE_RED_ALERT,
                AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
                AgencyPerformanceNotification::TYPE_SUSPENSION,
            ])
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['agency', 'agency.agencyProfile', 'scorecard'])
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('agency_id');
    }

    /**
     * Get notification response rate statistics.
     *
     * @param int $days
     * @return array
     */
    public function getResponseRateStatistics(int $days = 30): array
    {
        $notifications = AgencyPerformanceNotification::where('requires_acknowledgment', true)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        $total = $notifications->count();
        $acknowledged = $notifications->where('acknowledged', true)->count();
        $escalated = $notifications->where('escalated', true)->count();

        $avgResponseTime = $notifications
            ->where('acknowledged', true)
            ->filter(fn($n) => $n->acknowledged_at && $n->sent_at)
            ->avg(fn($n) => $n->sent_at->diffInHours($n->acknowledged_at));

        return [
            'total_notifications' => $total,
            'acknowledged' => $acknowledged,
            'acknowledgment_rate' => $total > 0 ? round(($acknowledged / $total) * 100, 1) : 0,
            'escalated' => $escalated,
            'escalation_rate' => $total > 0 ? round(($escalated / $total) * 100, 1) : 0,
            'avg_response_time_hours' => round($avgResponseTime ?? 0, 1),
        ];
    }

    /**
     * Track improvement after warnings.
     *
     * @param int $agencyId
     * @return array
     */
    public function trackImprovementAfterWarning(int $agencyId): array
    {
        $lastWarning = AgencyPerformanceNotification::forAgency($agencyId)
            ->whereIn('notification_type', [
                AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
                AgencyPerformanceNotification::TYPE_RED_ALERT,
            ])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastWarning || !$lastWarning->scorecard) {
            return ['status' => 'no_warning_found'];
        }

        // Get scorecards since warning
        $scorecardsSinceWarning = AgencyPerformanceScorecard::where('agency_id', $agencyId)
            ->where('period_start', '>', $lastWarning->scorecard->period_end)
            ->orderBy('period_start', 'asc')
            ->get();

        if ($scorecardsSinceWarning->isEmpty()) {
            return [
                'status' => 'pending',
                'warning_date' => $lastWarning->created_at,
                'warning_type' => $lastWarning->notification_type,
                'days_since_warning' => $lastWarning->created_at->diffInDays(now()),
            ];
        }

        $latestScorecard = $scorecardsSinceWarning->last();
        $warningScorecard = $lastWarning->scorecard;

        return [
            'status' => $this->isImprovement($warningScorecard->status, $latestScorecard->status) ? 'improved' : 'not_improved',
            'warning_date' => $lastWarning->created_at,
            'warning_type' => $lastWarning->notification_type,
            'warning_status' => $warningScorecard->status,
            'current_status' => $latestScorecard->status,
            'weeks_since_warning' => $scorecardsSinceWarning->count(),
            'metrics_change' => [
                'fill_rate' => round($latestScorecard->fill_rate - $warningScorecard->fill_rate, 2),
                'no_show_rate' => round($latestScorecard->no_show_rate - $warningScorecard->no_show_rate, 2),
                'rating' => round($latestScorecard->average_worker_rating - $warningScorecard->average_worker_rating, 2),
                'complaint_rate' => round($latestScorecard->complaint_rate - $warningScorecard->complaint_rate, 2),
            ],
        ];
    }
}
