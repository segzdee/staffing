<?php

namespace App\Notifications;

use App\Models\SuspensionAppeal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * WKR-009: Notification sent to worker when their appeal is reviewed.
 */
class AppealDecisionNotification extends Notification implements ShouldQueue
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
        return config('suspensions.notifications.channels', ['mail', 'database']);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isApproved = $this->appeal->isApproved();

        $message = (new MailMessage)
            ->subject($isApproved ? 'Appeal Approved - Suspension Overturned' : 'Appeal Decision - Review Complete')
            ->greeting($isApproved ? 'Good News!' : 'Appeal Review Complete');

        if ($isApproved) {
            $message
                ->line('Your appeal has been reviewed and approved.')
                ->line('Your suspension has been overturned and your account has been reinstated.')
                ->line('You can now access all platform features and apply for shifts again.');

            if ($this->appeal->review_notes) {
                $message->line('Reviewer Notes: '.$this->appeal->review_notes);
            }

            $message->action('Browse Available Shifts', route('dashboard.staff.marketplace'));
        } else {
            $message
                ->line('Your appeal has been reviewed.')
                ->line('After careful consideration, your appeal has been denied and your suspension remains in effect.');

            if ($this->appeal->review_notes) {
                $message->line('Reviewer Notes: '.$this->appeal->review_notes);
            }

            $suspension = $this->appeal->suspension;
            if ($suspension->ends_at) {
                $message->line('Your suspension will be lifted on: '.$suspension->ends_at->format('F j, Y \a\t g:i A'));
            }

            $message->action('View Suspension Details', route('worker.suspensions.show', $suspension));
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
        $isApproved = $this->appeal->isApproved();

        return [
            'type' => 'appeal_decision',
            'appeal_id' => $this->appeal->id,
            'suspension_id' => $this->appeal->suspension_id,
            'decision' => $this->appeal->status,
            'is_approved' => $isApproved,
            'review_notes' => $this->appeal->review_notes,
            'message' => $isApproved
                ? 'Your appeal has been approved and your suspension has been overturned.'
                : 'Your appeal has been reviewed and denied. Your suspension remains in effect.',
        ];
    }
}
