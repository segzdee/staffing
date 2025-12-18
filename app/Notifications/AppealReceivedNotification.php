<?php

namespace App\Notifications;

use App\Models\SuspensionAppeal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * WKR-009: Notification sent to admins when a worker submits an appeal.
 */
class AppealReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SuspensionAppeal $appeal
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return config('suspensions.notifications.admin_channels', ['mail', 'database']);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $suspension = $this->appeal->suspension;
        $worker = $suspension->worker;

        return (new MailMessage)
            ->subject('New Suspension Appeal Submitted')
            ->greeting('New Appeal Requires Review')
            ->line('A worker has submitted an appeal for their suspension.')
            ->line('Worker: '.$worker->name.' ('.$worker->email.')')
            ->line('Suspension Type: '.$suspension->getTypeLabel())
            ->line('Reason Category: '.$suspension->getReasonCategoryLabel())
            ->line('Appeal submitted: '.$this->appeal->created_at->format('F j, Y \a\t g:i A'))
            ->action('Review Appeal', route('admin.suspensions.appeals.review', $this->appeal))
            ->line('Please review this appeal within the SLA timeframe.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'appeal_received',
            'appeal_id' => $this->appeal->id,
            'suspension_id' => $this->appeal->suspension_id,
            'worker_id' => $this->appeal->user_id,
            'worker_name' => $this->appeal->worker->name,
            'message' => 'New suspension appeal from '.$this->appeal->worker->name.' requires review.',
        ];
    }
}
