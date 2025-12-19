<?php

namespace App\Notifications;

use App\Models\AvailabilityBroadcast;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Worker Availability Broadcast Notification
 * Sent to businesses when a worker broadcasts their availability
 */
class WorkerAvailabilityBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected AvailabilityBroadcast $broadcast;

    protected User $worker;

    /**
     * Create a new notification instance.
     */
    public function __construct(AvailabilityBroadcast $broadcast, User $worker)
    {
        $this->broadcast = $broadcast;
        $this->worker = $worker;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $workerName = $this->worker->name ?? 'A qualified worker';
        $availableFrom = $this->broadcast->available_from?->format('M j, g:ia') ?? 'soon';
        $availableTo = $this->broadcast->available_to?->format('M j, g:ia') ?? 'later';

        return (new MailMessage)
            ->subject('Worker Available for Shifts')
            ->greeting('Worker Availability Alert')
            ->line("{$workerName} is available to work!")
            ->line("**Availability:** {$availableFrom} - {$availableTo}")
            ->line($this->broadcast->message ? "**Message:** {$this->broadcast->message}" : '')
            ->action('View Worker Profile', url("/business/workers/{$this->worker->id}"))
            ->line('Invite this worker to your upcoming shifts.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'worker_availability_broadcast',
            'title' => 'Worker Available',
            'message' => "{$this->worker->name} is available from {$this->broadcast->available_from?->format('M j')} to {$this->broadcast->available_to?->format('M j')}",
            'broadcast_id' => $this->broadcast->id,
            'worker_id' => $this->worker->id,
            'worker_name' => $this->worker->name,
            'available_from' => $this->broadcast->available_from?->toIso8601String(),
            'available_to' => $this->broadcast->available_to?->toIso8601String(),
            'industries' => $this->broadcast->industries,
            'action_url' => url("/business/workers/{$this->worker->id}"),
            'action_text' => 'View Worker',
        ];
    }
}
