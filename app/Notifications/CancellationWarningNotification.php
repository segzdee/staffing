<?php

namespace App\Notifications;

use App\Models\BusinessProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CancellationWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $businessProfile;
    protected $metrics;
    protected $warningType;

    /**
     * Create a new notification instance.
     */
    public function __construct(BusinessProfile $businessProfile, $metrics, $warningType)
    {
        $this->businessProfile = $businessProfile;
        $this->metrics = $metrics;
        $this->warningType = $warningType;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = ['database'];

        // Send email for all types except dashboard-only
        if ($this->warningType !== 'dashboard') {
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

        // Set appropriate level
        if ($this->warningType === 'credit_suspended') {
            $mail->error();
        } else {
            $mail->level('warning');
        }

        $mail->line($this->getMessage());

        $mail->line("**Cancellation Metrics (Last 30 Days):**")
            ->line("- Total Cancellations: {$this->metrics['cancellations_30_days']}")
            ->line("- Late Cancellations: {$this->metrics['late_cancellations_30_days']}")
            ->line("- Cancellation Rate: {$this->metrics['cancellation_rate']}%");

        if ($this->warningType === 'credit_suspended') {
            $mail->line("")
                ->line("**Action Taken:**")
                ->line("Your account credit has been temporarily suspended until your cancellation rate improves.")
                ->line("")
                ->line("To reinstate your account, you will need to:")
                ->line("1. Reduce your cancellation rate below 15%")
                ->line("2. Demonstrate consistent, reliable shift fulfillment")
                ->line("3. Contact our support team for review");
        } else {
            $mail->line("")
                ->line("**Recommended Actions:**")
                ->line("1. Review your shift posting practices")
                ->line("2. Ensure adequate notice for any necessary cancellations")
                ->line("3. Consider posting shifts further in advance")
                ->line("4. Improve internal planning to minimize last-minute changes");
        }

        $mail->action('View Cancellation History', url('/business/analytics'))
            ->line('Maintaining a low cancellation rate helps build trust with workers and ensures better shift coverage.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'cancellation_warning',
            'warning_type' => $this->warningType,
            'business_profile_id' => $this->businessProfile->id,
            'business_name' => $this->businessProfile->business_name,
            'metrics' => $this->metrics,
            'message' => $this->getMessage(),
            'actions_required' => $this->getActionsRequired(),
        ];
    }

    /**
     * Get subject line based on warning type.
     */
    protected function getSubject()
    {
        switch ($this->warningType) {
            case 'credit_suspended':
                return 'URGENT: Account Credit Suspended Due to High Cancellation Rate';
            case 'rate_warning':
                return 'Warning: High Cancellation Rate Detected';
            case 'email':
                return 'Cancellation Pattern Alert';
            case 'dashboard':
                return 'Multiple Late Cancellations Detected';
            default:
                return 'Cancellation Warning';
        }
    }

    /**
     * Get main message based on warning type.
     */
    protected function getMessage()
    {
        $rate = $this->metrics['cancellation_rate'];
        $lateCancellations = $this->metrics['late_cancellations_30_days'];

        switch ($this->warningType) {
            case 'credit_suspended':
                return "Your account credit has been suspended due to a cancellation rate of {$rate}%, which exceeds our acceptable threshold of 25%. This high rate negatively impacts workers and the platform's reliability.";
            case 'rate_warning':
                return "Your cancellation rate of {$rate}% has exceeded our warning threshold of 15%. If this rate reaches 25%, your account credit may be suspended.";
            case 'email':
                return "We've noticed you have {$lateCancellations} late cancellations (less than 24 hours notice) in the last 30 days. We wanted to bring this to your attention to help you maintain good standing on our platform.";
            case 'dashboard':
                return "You have {$lateCancellations} late cancellations in the last 30 days. Multiple late cancellations may result in increased escrow requirements and other account restrictions.";
            default:
                return "Your cancellation pattern requires attention.";
        }
    }

    /**
     * Get actions required based on warning type.
     */
    protected function getActionsRequired()
    {
        if ($this->warningType === 'credit_suspended') {
            return [
                'Reduce cancellation rate below 15%',
                'Contact support for account review',
                'Demonstrate reliable shift fulfillment',
            ];
        }

        if ($this->businessProfile->requires_increased_escrow) {
            return [
                'Increased escrow may be required for new shifts',
                'Reduce late cancellations',
                'Provide adequate notice for cancellations',
            ];
        }

        return [
            'Minimize last-minute cancellations',
            'Provide at least 24 hours notice',
            'Review shift planning processes',
        ];
    }
}
