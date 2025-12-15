<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to workers when their account is suspended.
 * WKR-008: Automated Suspension Triggers
 */
class WorkerSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $suspendedUntil;
    protected $reason;
    protected $days;

    /**
     * Create a new notification instance.
     *
     * @param \Carbon\Carbon $suspendedUntil
     * @param string $reason
     * @param int $days
     */
    public function __construct($suspendedUntil, string $reason, int $days)
    {
        $this->suspendedUntil = $suspendedUntil;
        $this->reason = $reason;
        $this->days = $days;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $suspendedUntilFormatted = $this->suspendedUntil->format('F j, Y \a\t g:i A');

        return (new MailMessage)
            ->error()
            ->subject('Account Suspended - Action Required')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your OvertimeStaff worker account has been temporarily suspended.')
            ->line("**Reason:** {$this->reason}")
            ->line("**Suspension Duration:** {$this->days} day(s)")
            ->line("**Suspension Ends:** {$suspendedUntilFormatted}")
            ->line('As a result of this suspension:')
            ->line('• All pending shift applications have been cancelled')
            ->line('• You cannot apply for new shifts until the suspension ends')
            ->line('• Your profile is temporarily hidden from businesses')
            ->line('Your account will be automatically reinstated on the date above.')
            ->line('**What you can do:**')
            ->line('1. Review our reliability and cancellation policies')
            ->line('2. Submit an appeal if you believe this suspension is unwarranted')
            ->line('3. Contact our support team for assistance')
            ->action('Submit Appeal', url('/worker/suspension/appeal'))
            ->line('We take reliability seriously to maintain trust between businesses and workers. If you have extenuating circumstances, please submit an appeal with supporting documentation.')
            ->line('Thank you for your understanding.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'account_suspended',
            'title' => 'Account Suspended',
            'message' => "Your account has been suspended for {$this->days} day(s). {$this->reason}",
            'reason' => $this->reason,
            'days' => $this->days,
            'suspended_until' => $this->suspendedUntil->toDateTimeString(),
            'action_url' => url('/worker/suspension/appeal'),
            'priority' => 'critical',
        ];
    }
}
