<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when bulk shift upload fails.
 * BIZ-005: Bulk Shift Posting
 */
class BulkUploadFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $batchId;
    protected $error;

    /**
     * Create a new notification instance.
     *
     * @param string $batchId
     * @param string $error
     */
    public function __construct(string $batchId, string $error)
    {
        $this->batchId = $batchId;
        $this->error = $error;
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
        return (new MailMessage)
            ->error()
            ->subject('Bulk Shift Upload Failed')
            ->greeting("Hello {$notifiable->name},")
            ->line('Unfortunately, your bulk shift upload encountered an error and could not be completed.')
            ->line("Batch ID: {$this->batchId}")
            ->line("Error: {$this->error}")
            ->line('Please try the following:')
            ->line('1. Check that your CSV file is properly formatted')
            ->line('2. Ensure all required fields are filled in')
            ->line('3. Verify that dates and times are in the correct format')
            ->action('Download Template', url('/business/shifts/bulk-upload/template'))
            ->line('If the problem persists, please contact our support team with your batch ID.');
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
            'type' => 'bulk_upload_failed',
            'title' => 'Bulk Upload Failed',
            'message' => 'Your bulk shift upload failed. Please review the error and try again.',
            'batch_id' => $this->batchId,
            'error' => $this->error,
            'action_url' => url('/business/shifts/bulk-upload'),
            'priority' => 'high',
        ];
    }
}
