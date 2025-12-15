<?php

namespace Database\Factories;

use App\Models\AgencyPerformanceNotification;
use App\Models\AgencyPerformanceScorecard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for AgencyPerformanceNotification model.
 * AGY-005: Agency Performance Notification System
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgencyPerformanceNotification>
 */
class AgencyPerformanceNotificationFactory extends Factory
{
    protected $model = AgencyPerformanceNotification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => User::factory()->create(['user_type' => 'agency'])->id,
            'scorecard_id' => null,
            'notification_type' => AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
            'severity' => AgencyPerformanceNotification::SEVERITY_WARNING,
            'status_at_notification' => 'yellow',
            'previous_status' => 'green',
            'title' => 'Performance Warning: Action Required',
            'message' => 'Your agency performance has fallen below target thresholds.',
            'metrics_snapshot' => [
                'fill_rate' => ['actual' => 85.0, 'target' => 90.0, 'variance' => -5.0],
                'no_show_rate' => ['actual' => 4.0, 'target' => 3.0, 'variance' => 1.0],
                'average_rating' => ['actual' => 4.2, 'target' => 4.3, 'variance' => -0.1],
                'complaint_rate' => ['actual' => 2.5, 'target' => 2.0, 'variance' => 0.5],
            ],
            'action_items' => [
                'Review worker availability and ensure adequate coverage',
                'Implement better shift notification systems',
                'Address workers with high no-show rates',
            ],
            'improvement_deadline' => now()->addDays(14),
            'consecutive_yellow_weeks' => 1,
            'consecutive_red_weeks' => 0,
            'previous_commission_rate' => null,
            'new_commission_rate' => null,
            'sent_at' => now(),
            'sent_via' => 'email',
            'email_delivered' => true,
            'email_delivered_at' => now(),
            'requires_acknowledgment' => true,
            'acknowledged' => false,
            'acknowledged_at' => null,
            'acknowledged_by' => null,
            'acknowledgment_notes' => null,
            'escalated' => false,
            'escalated_at' => null,
            'escalated_to' => null,
            'escalation_reason' => null,
            'escalation_level' => 0,
            'escalation_due_at' => now()->addHours(48),
            'admin_reviewed' => false,
            'admin_reviewed_at' => null,
            'admin_reviewed_by' => null,
            'admin_notes' => null,
            'admin_decision' => null,
            'appealed' => false,
            'appealed_at' => null,
            'appeal_reason' => null,
            'appeal_status' => null,
            'appeal_response' => null,
            'appeal_resolved_at' => null,
            'follow_up_count' => 0,
            'last_follow_up_at' => null,
            'next_follow_up_at' => null,
        ];
    }

    /**
     * Create a yellow warning notification.
     */
    public function yellowWarning(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => AgencyPerformanceNotification::TYPE_YELLOW_WARNING,
            'severity' => AgencyPerformanceNotification::SEVERITY_WARNING,
            'status_at_notification' => 'yellow',
            'previous_status' => 'green',
            'title' => 'Performance Warning: Action Required',
            'improvement_deadline' => now()->addDays(14),
        ]);
    }

    /**
     * Create a red alert notification.
     */
    public function redAlert(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => AgencyPerformanceNotification::TYPE_RED_ALERT,
            'severity' => AgencyPerformanceNotification::SEVERITY_CRITICAL,
            'status_at_notification' => 'red',
            'previous_status' => 'yellow',
            'title' => 'URGENT: Critical Performance Alert',
            'improvement_deadline' => now()->addDays(7),
        ]);
    }

    /**
     * Create a fee increase notification.
     */
    public function feeIncrease(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => AgencyPerformanceNotification::TYPE_FEE_INCREASE,
            'severity' => AgencyPerformanceNotification::SEVERITY_CRITICAL,
            'status_at_notification' => 'red',
            'previous_status' => 'red',
            'title' => 'Notice: Commission Rate Increase',
            'previous_commission_rate' => 10.00,
            'new_commission_rate' => 12.00,
            'consecutive_red_weeks' => 2,
        ]);
    }

    /**
     * Create a suspension notification.
     */
    public function suspension(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => AgencyPerformanceNotification::TYPE_SUSPENSION,
            'severity' => AgencyPerformanceNotification::SEVERITY_CRITICAL,
            'status_at_notification' => 'red',
            'previous_status' => 'red',
            'title' => 'Account Suspended: Immediate Action Required',
            'consecutive_red_weeks' => 3,
        ]);
    }

    /**
     * Create an improvement notification.
     */
    public function improvement(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_type' => AgencyPerformanceNotification::TYPE_IMPROVEMENT,
            'severity' => AgencyPerformanceNotification::SEVERITY_INFO,
            'status_at_notification' => 'yellow',
            'previous_status' => 'red',
            'title' => 'Congratulations: Performance Improved',
            'requires_acknowledgment' => false,
            'improvement_deadline' => null,
        ]);
    }

    /**
     * Create an acknowledged notification.
     */
    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $attributes['agency_id'],
            'acknowledgment_notes' => 'We acknowledge this notification and will take corrective action.',
        ]);
    }

    /**
     * Create an escalated notification.
     */
    public function escalated(): static
    {
        return $this->state(fn (array $attributes) => [
            'escalated' => true,
            'escalated_at' => now(),
            'escalated_to' => User::factory()->create(['role' => 'admin'])->id,
            'escalation_reason' => 'Agency failed to acknowledge within 48 hours',
            'escalation_level' => 1,
        ]);
    }

    /**
     * Create a notification with pending appeal.
     */
    public function withAppeal(): static
    {
        return $this->state(fn (array $attributes) => [
            'appealed' => true,
            'appealed_at' => now(),
            'appeal_reason' => 'Extenuating circumstances due to severe weather affecting operations.',
            'appeal_status' => AgencyPerformanceNotification::APPEAL_PENDING,
        ]);
    }

    /**
     * Create a notification pending escalation.
     */
    public function pendingEscalation(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_acknowledgment' => true,
            'acknowledged' => false,
            'escalated' => false,
            'escalation_due_at' => now()->subHours(1),
        ]);
    }

    /**
     * Create notification for specific agency.
     */
    public function forAgency(User $agency): static
    {
        return $this->state(fn (array $attributes) => [
            'agency_id' => $agency->id,
        ]);
    }

    /**
     * Create notification with scorecard.
     */
    public function withScorecard(AgencyPerformanceScorecard $scorecard): static
    {
        return $this->state(fn (array $attributes) => [
            'scorecard_id' => $scorecard->id,
            'agency_id' => $scorecard->agency_id,
        ]);
    }
}
