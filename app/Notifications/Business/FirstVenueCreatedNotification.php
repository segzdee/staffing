<?php

namespace App\Notifications\Business;

use App\Models\Venue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * First Venue Created Notification
 * BIZ-REG-006: Sent when a business creates their first venue
 */
class FirstVenueCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Venue $venue;

    /**
     * Create a new notification instance.
     */
    public function __construct(Venue $venue)
    {
        $this->venue = $venue;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Congratulations! Your First Venue is Ready')
            ->greeting('Great news!')
            ->line("You've successfully created your first venue: {$this->venue->name}")
            ->line('You can now start posting shifts and finding qualified workers.')
            ->line('**Next steps:**')
            ->line('1. Set up operating hours for your venue')
            ->line('2. Add parking and entrance instructions for workers')
            ->line('3. Post your first shift')
            ->action('View Your Venue', url("/business/venues/{$this->venue->id}"))
            ->line('Need help? Our support team is here to assist you.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'first_venue_created',
            'title' => 'First Venue Created!',
            'message' => "Congratulations! You've created your first venue: {$this->venue->name}. You can now start posting shifts.",
            'venue_id' => $this->venue->id,
            'venue_name' => $this->venue->name,
            'action_url' => url("/business/venues/{$this->venue->id}"),
            'action_text' => 'View Venue',
        ];
    }
}
