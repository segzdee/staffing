<?php

namespace App\Notifications\Agency;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to admins when the escalation job fails.
 * AGY-005: Agency Performance Notification System
 */
class EscalationJobFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $errorMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('CRITICAL: Escalation Job Failed')
            ->greeting('System Alert')
            ->line('The daily escalation job has failed after all retry attempts.')
            ->line('Unacknowledged notifications may not be properly escalated today.')
            ->line('---')
            ->line("**Error:** {$this->errorMessage}")
            ->action('View System Logs', url('/admin/logs'))
            ->line('Please investigate and resolve.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'escalation_job_failed',
            'title' => 'Escalation Job Failed',
            'error_message' => $this->errorMessage,
            'failed_at' => now()->toDateTimeString(),
            'priority' => 'critical',
        ];
    }
}
