<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * BulkVerificationCompletedNotification - ADM-001
 *
 * Sent to verification admin team when a bulk verification
 * operation (approve/reject) has been completed.
 */
class BulkVerificationCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected User $admin;
    protected string $action;
    protected array $results;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $admin, string $action, array $results)
    {
        $this->admin = $admin;
        $this->action = $action;
        $this->results = $results;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        // Only send database notification for bulk operations
        // to avoid email overload
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $actionPast = $this->action === 'approve' ? 'approved' : 'rejected';
        $total = $this->results['success'] + $this->results['failed'];

        return (new MailMessage)
            ->subject("Bulk Verification {$actionPast}: {$this->results['success']}/{$total} processed")
            ->greeting("Bulk Operation Completed")
            ->line("{$this->admin->name} has performed a bulk {$this->action} operation on the verification queue.")
            ->line("")
            ->line("**Results:**")
            ->line("- Successfully {$actionPast}: {$this->results['success']}")
            ->line("- Failed: {$this->results['failed']}")
            ->action('View Verification Queue', url('/panel/admin/verification-queue'))
            ->line('Review the verification queue for any items that may still need attention.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $actionPast = $this->action === 'approve' ? 'approved' : 'rejected';

        return [
            'type' => 'bulk_verification_completed',
            'action' => $this->action,
            'admin_id' => $this->admin->id,
            'admin_name' => $this->admin->name,
            'success_count' => $this->results['success'],
            'failed_count' => $this->results['failed'],
            'processed_ids' => $this->results[$this->action . 'd_ids'] ?? [],
            'message' => "{$this->admin->name} {$actionPast} {$this->results['success']} verification(s)",
            'url' => '/panel/admin/verification-queue',
        ];
    }
}
