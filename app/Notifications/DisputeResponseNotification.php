<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DisputeResponseNotification
 *
 * FIN-010: Notifies worker when business responds to their dispute.
 *
 * Channels: Database, Mail
 */
class DisputeResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The dispute that received a response.
     */
    protected Dispute $dispute;

    /**
     * Create a new notification instance.
     */
    public function __construct(Dispute $dispute)
    {
        $this->dispute = $dispute;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('disputes.notifications.send_email', true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Business Response - Dispute #{$this->dispute->id}")
            ->greeting("Hello {$notifiable->name},")
            ->line('The business has responded to your dispute.')
            ->line('')
            ->line('**Dispute Details:**')
            ->line("- Dispute ID: #{$this->dispute->id}")
            ->line("- Type: {$this->dispute->type_label}")
            ->line("- Disputed Amount: {$this->dispute->formatted_disputed_amount}")
            ->line("- Business: {$this->dispute->business->name}")
            ->line('')
            ->line('**Business Response:**')
            ->line($this->truncateDescription($this->dispute->business_response))
            ->line('')
            ->line("**Current Status:** {$this->dispute->status_label}")
            ->line('')
            ->action('View Dispute', url("/worker/disputes/{$this->dispute->id}"))
            ->line('')
            ->line("You may submit additional evidence to support your case if you haven't already.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'dispute_response',
            'dispute_id' => $this->dispute->id,
            'shift_id' => $this->dispute->shift_id,
            'business_id' => $this->dispute->business_id,
            'business_name' => $this->dispute->business->name,
            'status' => $this->dispute->status,
            'action_url' => url("/worker/disputes/{$this->dispute->id}"),
            'message' => "Business has responded to your dispute #{$this->dispute->id}",
        ];
    }

    /**
     * Truncate description for display.
     */
    private function truncateDescription(?string $description, int $length = 300): string
    {
        if (! $description) {
            return 'No response text provided.';
        }

        if (strlen($description) <= $length) {
            return $description;
        }

        return substr($description, 0, $length).'...';
    }
}
