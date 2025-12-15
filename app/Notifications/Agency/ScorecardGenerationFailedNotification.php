<?php

namespace App\Notifications\Agency;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to admins when scorecard generation fails.
 * AGY-005: Agency Performance Notification System
 */
class ScorecardGenerationFailedNotification extends Notification implements ShouldQueue
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
            ->subject('CRITICAL: Agency Scorecard Generation Failed')
            ->greeting('System Alert')
            ->line('The weekly agency scorecard generation job has **failed after all retry attempts**.')
            ->line('This means agency performance notifications will not be sent this week.')
            ->line('---')
            ->line('**Error Message:**')
            ->line($this->errorMessage)
            ->line('---')
            ->line('**Required Actions:**')
            ->line('1. Check the application logs for detailed error information')
            ->line('2. Investigate and resolve the underlying issue')
            ->line('3. Manually trigger scorecard generation if needed')
            ->line('4. Monitor for successful completion')
            ->action('View System Logs', url('/admin/logs'))
            ->line('This requires immediate attention to ensure agency performance tracking continues.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'scorecard_generation_failed',
            'title' => 'Agency Scorecard Generation Failed',
            'error_message' => $this->errorMessage,
            'failed_at' => now()->toDateTimeString(),
            'action_url' => url('/admin/logs'),
            'priority' => 'critical',
        ];
    }
}
