<?php

namespace App\Notifications\Agency;

use App\Models\AgencyPerformanceNotification;
use App\Models\AgencyPerformanceScorecard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when agency is suspended for 3 consecutive red scorecards.
 * AGY-005: Agency Performance Notification System
 */
class PerformanceSuspensionNotification extends Notification implements ShouldQueue
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
            ->subject('Account Suspended: Immediate Action Required')
            ->view('emails.agency.performance.suspension', [
                'agency' => $notifiable,
                'notification' => $this->performanceNotification,
                'scorecard' => $this->scorecard,
                'actionPlan' => $this->actionPlan,
                'metrics' => $this->performanceNotification->metrics_snapshot,
                'consecutiveRedWeeks' => $this->performanceNotification->consecutive_red_weeks,
                'appealUrl' => url('/agency/performance/appeal'),
                'supportUrl' => url('/support/contact'),
                'recoveryRequirements' => $this->getRecoveryRequirements(),
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'performance_suspension',
            'title' => $this->performanceNotification->title,
            'message' => $this->performanceNotification->message,
            'notification_id' => $this->performanceNotification->id,
            'scorecard_id' => $this->scorecard->id,
            'status' => 'suspended',
            'metrics' => $this->performanceNotification->metrics_snapshot,
            'consecutive_red_weeks' => $this->performanceNotification->consecutive_red_weeks,
            'requires_acknowledgment' => true,
            'action_url' => url('/agency/performance/appeal'),
            'priority' => 'critical',
        ];
    }

    /**
     * Get recovery requirements.
     */
    protected function getRecoveryRequirements(): array
    {
        return [
            'Submit a detailed improvement action plan',
            'Complete all pending shift assignments professionally',
            'Acknowledge all outstanding performance notifications',
            'Schedule a review call with your account manager',
            'Demonstrate commitment to meeting performance targets',
        ];
    }
}
