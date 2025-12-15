<?php

namespace App\Notifications\Agency;

use App\Models\AgencyPerformanceNotification;
use App\Models\AgencyPerformanceScorecard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when agency fees are increased due to poor performance.
 * AGY-005: Agency Performance Notification System
 */
class PerformanceFeeIncreaseNotification extends Notification implements ShouldQueue
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
            ->subject('Notice: Commission Rate Increase Due to Performance')
            ->view('emails.agency.performance.fee-increase', [
                'agency' => $notifiable,
                'notification' => $this->performanceNotification,
                'scorecard' => $this->scorecard,
                'actionPlan' => $this->actionPlan,
                'metrics' => $this->performanceNotification->metrics_snapshot,
                'previousRate' => $this->performanceNotification->previous_commission_rate,
                'newRate' => $this->performanceNotification->new_commission_rate,
                'consecutiveRedWeeks' => $this->performanceNotification->consecutive_red_weeks,
                'acknowledgeUrl' => url("/agency/performance/notifications/{$this->performanceNotification->id}/acknowledge"),
                'dashboardUrl' => url('/agency/performance'),
                'billingUrl' => url('/agency/billing'),
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'performance_fee_increase',
            'title' => $this->performanceNotification->title,
            'message' => $this->performanceNotification->message,
            'notification_id' => $this->performanceNotification->id,
            'scorecard_id' => $this->scorecard->id,
            'status' => $this->scorecard->status,
            'previous_commission_rate' => $this->performanceNotification->previous_commission_rate,
            'new_commission_rate' => $this->performanceNotification->new_commission_rate,
            'metrics' => $this->performanceNotification->metrics_snapshot,
            'requires_acknowledgment' => true,
            'action_url' => url("/agency/performance/notifications/{$this->performanceNotification->id}"),
            'priority' => 'critical',
        ];
    }
}
