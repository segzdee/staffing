<?php

namespace App\Notifications\Agency;

use App\Models\AgencyPerformanceNotification;
use App\Models\AgencyPerformanceScorecard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when agency status becomes red.
 * AGY-005: Agency Performance Notification System
 */
class PerformanceRedAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected AgencyPerformanceNotification $performanceNotification;
    protected AgencyPerformanceScorecard $scorecard;
    protected array $actionPlan;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        AgencyPerformanceNotification $performanceNotification,
        AgencyPerformanceScorecard $scorecard,
        array $actionPlan
    ) {
        $this->performanceNotification = $performanceNotification;
        $this->scorecard = $scorecard;
        $this->actionPlan = $actionPlan;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('URGENT: Critical Performance Alert - Action Required Within 1 Week')
            ->view('emails.agency.performance.red-alert', [
                'agency' => $notifiable,
                'notification' => $this->performanceNotification,
                'scorecard' => $this->scorecard,
                'actionPlan' => $this->actionPlan,
                'metrics' => $this->performanceNotification->metrics_snapshot,
                'deadline' => $this->performanceNotification->improvement_deadline,
                'consecutiveRedWeeks' => $this->performanceNotification->consecutive_red_weeks,
                'acknowledgeUrl' => url("/agency/performance/notifications/{$this->performanceNotification->id}/acknowledge"),
                'dashboardUrl' => url('/agency/performance'),
                'supportUrl' => url('/support/contact'),
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'performance_red_alert',
            'title' => $this->performanceNotification->title,
            'message' => $this->performanceNotification->message,
            'notification_id' => $this->performanceNotification->id,
            'scorecard_id' => $this->scorecard->id,
            'status' => $this->scorecard->status,
            'metrics' => $this->performanceNotification->metrics_snapshot,
            'improvement_deadline' => $this->performanceNotification->improvement_deadline?->toDateString(),
            'consecutive_red_weeks' => $this->performanceNotification->consecutive_red_weeks,
            'requires_acknowledgment' => true,
            'action_url' => url("/agency/performance/notifications/{$this->performanceNotification->id}"),
            'priority' => 'critical',
        ];
    }
}
