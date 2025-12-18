<?php

namespace App\Notifications;

use App\Models\BusinessRoster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * BIZ-005: Notification sent to workers when they are removed from a roster.
 */
class RemovedFromRosterNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BusinessRoster $roster;

    protected ?string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(BusinessRoster $roster, ?string $reason = null)
    {
        $this->roster = $roster;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $business = $this->roster->business;
        $appName = config('app.name', 'OvertimeStaff');

        $mail = (new MailMessage)
            ->subject("Removed from {$business->name}'s Roster")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been removed from **{$business->name}'s** **{$this->roster->name}** roster.");

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        $mail->line('')
            ->line('This means you will no longer receive priority notifications for shifts from this business, but you can still apply to any of their open shifts on the marketplace.')
            ->line('')
            ->line('If you believe this was done in error, please contact the business directly or our support team.')
            ->action('Browse Available Shifts', route('worker.market.index'))
            ->salutation("Best regards,\nThe {$appName} Team");

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        $business = $this->roster->business;

        return [
            'type' => 'removed_from_roster',
            'title' => "Removed from {$business->name}'s Roster",
            'message' => "You have been removed from {$business->name}'s {$this->roster->name} roster.".
                ($this->reason ? " Reason: {$this->reason}" : ''),
            'roster_id' => $this->roster->id,
            'roster_name' => $this->roster->name,
            'roster_type' => $this->roster->type,
            'business_id' => $business->id,
            'business_name' => $business->name,
            'reason' => $this->reason,
            'action_url' => route('worker.market.index'),
            'action_text' => 'Browse Shifts',
            'priority' => 'low',
            'icon' => 'user-minus',
            'color' => 'yellow',
        ];
    }
}
