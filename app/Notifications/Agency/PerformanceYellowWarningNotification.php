<?php

namespace App\Notifications\Agency;

use App\Models\AgencyPerformanceNotification;
use App\Models\AgencyPerformanceScorecard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when agency status becomes yellow.
 * AGY-005: Agency Performance Notification System
 */
class PerformanceYellowWarningNotification extends Notification implements ShouldQueue
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
            ->subject('Performance Warning: Action Required Within 2 Weeks')
            ->view('emails.agency.performance.yellow-warning', [
                'agency' => $notifiable,
                'notification' => $this->performanceNotification,
                'scorecard' => $this->scorecard,
                'actionPlan' => $this->actionPlan,
                'metrics' => $this->performanceNotification->metrics_snapshot,
                'deadline' => $this->performanceNotification->improvement_deadline,
                'acknowledgeUrl' => url("/agency/performance/notifications/{$this->performanceNotification->id}/acknowledge"),
                'dashboardUrl' => url('/agency/performance'),
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'performance_yellow_warning',
            'title' => $this->performanceNotification->title,
            'message' => $this->performanceNotification->message,
            'notification_id' => $this->performanceNotification->id,
            'scorecard_id' => $this->scorecard->id,
            'status' => $this->scorecard->status,
            'metrics' => $this->performanceNotification->metrics_snapshot,
            'improvement_deadline' => $this->performanceNotification->improvement_deadline?->toDateString(),
            'requires_acknowledgment' => true,
            'action_url' => url("/agency/performance/notifications/{$this->performanceNotification->id}"),
            'priority' => 'warning',
        ];
    }
}
