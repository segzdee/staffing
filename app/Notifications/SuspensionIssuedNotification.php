<?php

namespace App\Notifications;

use App\Models\WorkerSuspension;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * WKR-009: Notification sent when a worker receives a suspension.
 */
class SuspensionIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public WorkerSuspension $suspension
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return config('suspensions.notifications.channels', ['mail', 'database']);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Account Suspension Notice')
            ->greeting('Important Notice')
            ->line('Your account has been suspended from the OvertimeStaff platform.');

        // Add reason if configured to show
        if (config('suspensions.visibility.show_reason', true)) {
            $message->line('Reason: '.$this->suspension->getReasonCategoryLabel());
            $message->line('Details: '.$this->suspension->reason_details);
        }

        // Add suspension type
        $message->line('Suspension Type: '.$this->suspension->getTypeLabel());

        // Add end date if applicable
        if ($this->suspension->ends_at && config('suspensions.visibility.show_end_date', true)) {
            $message->line('Your suspension will be lifted on: '.$this->suspension->ends_at->format('F j, Y \a\t g:i A'));
        } elseif (! $this->suspension->ends_at) {
            $message->line('This suspension is indefinite and requires review.');
        }

        // Add appeal information
        if ($this->suspension->canBeAppealed()) {
            $appealDays = config('suspensions.appeal_window_days', 7);
            $message->line("You have {$appealDays} days to submit an appeal if you believe this suspension was issued in error.");
            $message->action('Submit Appeal', route('worker.suspensions.appeal', $this->suspension));
        }

        $message->line('If you have any questions, please contact our support team.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'suspension_issued',
            'suspension_id' => $this->suspension->id,
            'suspension_type' => $this->suspension->type,
            'reason_category' => $this->suspension->reason_category,
            'ends_at' => $this->suspension->ends_at?->toIso8601String(),
            'can_appeal' => $this->suspension->canBeAppealed(),
            'message' => 'Your account has been suspended: '.$this->suspension->getReasonCategoryLabel(),
        ];
    }
}
