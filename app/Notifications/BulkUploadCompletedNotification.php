<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when bulk shift upload is completed.
 * BIZ-005: Bulk Shift Posting
 */
class BulkUploadCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $results;

    /**
     * Create a new notification instance.
     *
     * @param array $results
     */
    public function __construct(array $results)
    {
        $this->results = $results;
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
            ->subject('Bulk Shift Upload Completed')
            ->greeting("Hello {$notifiable->name},")
            ->line('Your bulk shift upload has been processed successfully.');

        $message->line("Total rows processed: {$this->results['total_rows']}")
            ->line("Successful: {$this->results['successful']}")
            ->line("Failed: {$this->results['failed']}");

        if ($this->results['successful'] > 0) {
            $message->line("{$this->results['successful']} shift(s) have been created and are ready for review.");
        }

        if ($this->results['failed'] > 0) {
            $message->line("Note: {$this->results['failed']} shift(s) could not be created due to errors. Please review the error details below:");

            foreach (array_slice($this->results['errors'], 0, 5) as $error) {
                $message->line("• Row {$error['row']}: {$error['error']}");
            }

            if (count($this->results['errors']) > 5) {
                $remaining = count($this->results['errors']) - 5;
                $message->line("... and {$remaining} more error(s)");
            }
        }

        $message->action('View Results', url('/business/shifts/bulk-upload/results/' . $this->results['batch_id']))
            ->line('Thank you for using OvertimeStaff!');

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
            'type' => 'bulk_upload_completed',
            'title' => 'Bulk Upload Completed',
            'message' => "Bulk upload processed: {$this->results['successful']} successful, {$this->results['failed']} failed",
            'batch_id' => $this->results['batch_id'],
            'total_rows' => $this->results['total_rows'],
            'successful' => $this->results['successful'],
            'failed' => $this->results['failed'],
            'action_url' => url('/business/shifts/bulk-upload/results/' . $this->results['batch_id']),
            'priority' => 'medium',
        ];
    }
}
