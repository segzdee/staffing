<?php

namespace App\Notifications\Agency;

use App\Models\AgencyPerformanceNotification;
use App\Models\AgencyPerformanceScorecard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when agency status improves from red to yellow/green.
 * AGY-005: Agency Performance Notification System
 */
class PerformanceImprovementNotification extends Notification implements ShouldQueue
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
            ->success()
            ->subject('Great News: Your Performance Has Improved!')
            ->view('emails.agency.performance.improvement', [
                'agency' => $notifiable,
                'notification' => $this->performanceNotification,
                'scorecard' => $this->scorecard,
                'metrics' => $this->performanceNotification->metrics_snapshot,
                'previousStatus' => $this->performanceNotification->previous_status,
                'currentStatus' => $this->performanceNotification->status_at_notification,
                'trend' => $this->scorecard->getTrend(),
                'dashboardUrl' => url('/agency/performance'),
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'performance_improvement',
            'title' => $this->performanceNotification->title,
            'message' => $this->performanceNotification->message,
            'notification_id' => $this->performanceNotification->id,
            'scorecard_id' => $this->scorecard->id,
            'previous_status' => $this->performanceNotification->previous_status,
            'current_status' => $this->performanceNotification->status_at_notification,
            'metrics' => $this->performanceNotification->metrics_snapshot,
            'requires_acknowledgment' => false,
            'action_url' => url('/agency/performance'),
            'priority' => 'info',
        ];
    }
}
