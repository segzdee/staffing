<?php

namespace App\Notifications;

use App\Models\BusinessProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $businessProfile;
    protected $budgetOverview;
    protected $alerts;
    protected $alertLevel;

    /**
     * Create a new notification instance.
     */
    public function __construct(BusinessProfile $businessProfile, $budgetOverview, $alerts, $alertLevel)
    {
        $this->businessProfile = $businessProfile;
        $this->budgetOverview = $budgetOverview;
        $this->alerts = $alerts;
        $this->alertLevel = $alertLevel;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = ['database'];

        // Send email for warning and critical alerts
        if (in_array($this->alertLevel, ['warning', 'critical'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting("Hello {$notifiable->name},");

        if ($this->alertLevel === 'critical') {
            $mail->error();
        } elseif ($this->alertLevel === 'warning') {
            $mail->level('warning');
        }

        $mail->line($this->getMessage());

        $mail->line("**Budget Overview:**")
            ->line("- Monthly Budget: $" . number_format($this->budgetOverview['monthly_budget_dollars'], 2))
            ->line("- Current Spend: $" . number_format($this->budgetOverview['current_spend_dollars'], 2))
            ->line("- Remaining: $" . number_format($this->budgetOverview['remaining_budget_dollars'], 2))
            ->line("- Utilization: {$this->budgetOverview['utilization_percentage']}%");

        if (count($this->alerts) > 0) {
            $mail->line("")->line("**Active Alerts:**");
            foreach ($this->alerts as $alert) {
                $mail->line("- {$alert['message']}");
            }
        }

        $mail->action('View Analytics Dashboard', url('/business/analytics'))
            ->line('Please review your spending and adjust your budget or shift posting accordingly.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'budget_alert',
            'level' => $this->alertLevel,
            'business_profile_id' => $this->businessProfile->id,
            'business_name' => $this->businessProfile->business_name,
            'budget_overview' => $this->budgetOverview,
            'alerts' => $this->alerts,
            'message' => $this->getMessage(),
        ];
    }

    /**
     * Get subject line based on alert level.
     */
    protected function getSubject()
    {
        switch ($this->alertLevel) {
            case 'critical':
                return 'URGENT: Monthly Budget Exceeded - Action Required';
            case 'warning':
                return 'Warning: High Budget Utilization';
            case 'info':
                return 'Budget Alert: 75% Threshold Reached';
            default:
                return 'Budget Alert';
        }
    }

    /**
     * Get main message based on alert level.
     */
    protected function getMessage()
    {
        $utilization = $this->budgetOverview['utilization_percentage'];

        switch ($this->alertLevel) {
            case 'critical':
                return "Your monthly budget has been reached or exceeded ({$utilization}% utilization). Please review your spending immediately to avoid any disruption to your shift operations.";
            case 'warning':
                return "Your monthly budget utilization is at {$utilization}%, which is approaching your limit. Please review your spending patterns and consider adjusting your budget or shift postings.";
            case 'info':
                return "Your monthly budget utilization has reached {$utilization}%. This is a courtesy notification to help you track your spending.";
            default:
                return "Your budget requires attention.";
        }
    }
}
