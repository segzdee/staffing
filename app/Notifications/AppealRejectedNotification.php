<?php

namespace App\Notifications;

use App\Models\PenaltyAppeal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to workers when their penalty appeal is rejected.
 * FIN-006: Worker Penalty Appeal Notifications
 */
class AppealRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected PenaltyAppeal $appeal;

    /**
     * Create a new notification instance.
     *
     * @param PenaltyAppeal $appeal
     */
    public function __construct(PenaltyAppeal $appeal)
    {
        $this->appeal = $appeal;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $penalty = $this->appeal->penalty;
        $penaltyAmount = number_format($penalty->penalty_amount ?? 0, 2);
        $dueDate = $penalty->due_date ? $penalty->due_date->format('F j, Y') : 'Not specified';

        return (new MailMessage)
            ->subject('Appeal Decision - Penalty Upheld')
            ->greeting("Hello {$notifiable->name},")
            ->line('We have reviewed your penalty appeal and unfortunately, we were unable to approve it.')
            ->line('')
            ->line("**Penalty Type:** " . ucfirst(str_replace('_', ' ', $penalty->penalty_type ?? 'Unknown')))
            ->line("**Penalty Amount:** \${$penaltyAmount}")
            ->line("**Due Date:** {$dueDate}")
            ->line('')
            ->line("**Decision Reason:**")
            ->line($this->appeal->decision_reason ?? 'No reason provided.')
            ->line('')
            ->line('**What happens next:**')
            ->line('- The original penalty remains in effect')
            ->line("- Payment of \${$penaltyAmount} is due by {$dueDate}")
            ->line('- The penalty will be deducted from your next shift payment if not paid')
            ->line('')
            ->line('If you believe there has been an error or have additional evidence to support your case, please contact our support team.')
            ->action('View Appeal Details', url("/worker/appeals/{$this->appeal->id}"))
            ->line('We understand this may not be the outcome you hoped for. We encourage you to review our policies to avoid future penalties.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        $penalty = $this->appeal->penalty;

        return [
            'type' => 'appeal_rejected',
            'title' => 'Appeal Rejected',
            'message' => 'Your penalty appeal has been reviewed and rejected. The original penalty remains in effect.',
            'appeal_id' => $this->appeal->id,
            'penalty_id' => $penalty->id ?? null,
            'penalty_type' => $penalty->penalty_type ?? null,
            'penalty_amount' => $penalty->penalty_amount ?? 0,
            'due_date' => $penalty->due_date?->toDateString(),
            'decision_reason' => $this->appeal->decision_reason,
            'reviewed_at' => $this->appeal->reviewed_at?->toDateTimeString(),
            'action_url' => url("/worker/appeals/{$this->appeal->id}"),
            'priority' => 'high',
        ];
    }
}
