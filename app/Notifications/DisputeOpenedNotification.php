<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DisputeOpenedNotification
 *
 * FIN-010: Notifies business when a worker opens a dispute.
 *
 * Channels: Database, Mail
 */
class DisputeOpenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The dispute that was opened.
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
        $responseDeadline = now()->addDays(config('disputes.business_response_days', 3));

        return (new MailMessage)
            ->subject("Dispute Filed - Shift #{$this->dispute->shift_id}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A worker has filed a dispute regarding a shift at your business.')
            ->line('')
            ->line('**Dispute Details:**')
            ->line("- Dispute ID: #{$this->dispute->id}")
            ->line("- Type: {$this->dispute->type_label}")
            ->line("- Disputed Amount: {$this->dispute->formatted_disputed_amount}")
            ->line("- Shift: {$this->dispute->shift->title}")
            ->line("- Worker: {$this->dispute->worker->name}")
            ->line('')
            ->line("**Worker's Description:**")
            ->line($this->truncateDescription($this->dispute->worker_description))
            ->line('')
            ->line("**Please respond by: {$responseDeadline->format('F j, Y')}**")
            ->line('')
            ->action('Respond to Dispute', url("/business/disputes/{$this->dispute->id}"))
            ->line('')
            ->line("Failure to respond within the deadline may result in the dispute being resolved in the worker's favor.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'dispute_opened',
            'dispute_id' => $this->dispute->id,
            'shift_id' => $this->dispute->shift_id,
            'worker_id' => $this->dispute->worker_id,
            'worker_name' => $this->dispute->worker->name,
            'dispute_type' => $this->dispute->type,
            'disputed_amount' => $this->dispute->disputed_amount,
            'description' => $this->truncateDescription($this->dispute->worker_description, 100),
            'action_url' => url("/business/disputes/{$this->dispute->id}"),
            'message' => "A dispute has been filed for shift #{$this->dispute->shift_id} - {$this->dispute->formatted_disputed_amount}",
        ];
    }

    /**
     * Truncate description for display.
     */
    private function truncateDescription(string $description, int $length = 300): string
    {
        if (strlen($description) <= $length) {
            return $description;
        }

        return substr($description, 0, $length).'...';
    }
}
