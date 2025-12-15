<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to workers when their suspension is lifted.
 * WKR-008: Automated Suspension Triggers
 */
class WorkerReinstatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $note;

    /**
     * Create a new notification instance.
     *
     * @param string|null $note
     */
    public function __construct(?string $note = null)
    {
        $this->note = $note;
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
        $message = (new MailMessage)
            ->success()
            ->subject('Account Reinstated - Welcome Back!')
            ->greeting("Hello {$notifiable->name},")
            ->line('Great news! Your OvertimeStaff worker account has been reinstated.')
            ->line('You now have full access to:')
            ->line('• Browse and apply for available shifts')
            ->line('• Accept shift invitations from businesses')
            ->line('• Update your profile and availability')
            ->line('• Communicate with businesses');

        if ($this->note) {
            $message->line("**Note:** {$this->note}");
        }

        $message->line('**Moving Forward:**')
            ->line('To maintain your account in good standing:')
            ->line('• Arrive on time for all shifts')
            ->line('• Provide at least 24 hours notice if you need to cancel')
            ->line('• Complete all assigned shifts')
            ->line('• Respond promptly to shift confirmations')
            ->action('Browse Available Shifts', url('/worker/shifts/available'))
            ->line('We look forward to seeing you back on the platform. Thank you for your commitment to reliability!');

        return $message;
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
            'type' => 'account_reinstated',
            'title' => 'Account Reinstated',
            'message' => 'Your account has been reinstated. You can now apply for shifts again.',
            'note' => $this->note,
            'action_url' => url('/worker/shifts/available'),
            'priority' => 'high',
        ];
    }
}
