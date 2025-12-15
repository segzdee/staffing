<?php

namespace App\Notifications;

use App\Models\PenaltyAppeal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to workers when their penalty appeal is submitted and under review.
 * FIN-006: Worker Penalty Appeal Notifications
 */
class AppealUnderReviewNotification extends Notification implements ShouldQueue
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
        $submittedAt = $this->appeal->submitted_at ? $this->appeal->submitted_at->format('F j, Y \a\t g:i A') : 'Just now';

        return (new MailMessage)
            ->subject('Appeal Received - Under Review')
            ->greeting("Hello {$notifiable->name},")
            ->line('We have received your penalty appeal and it is now under review.')
            ->line('')
            ->line("**Appeal Reference:** #APL-{$this->appeal->id}")
            ->line("**Submitted:** {$submittedAt}")
            ->line("**Penalty Type:** " . ucfirst(str_replace('_', ' ', $penalty->penalty_type ?? 'Unknown')))
            ->line("**Penalty Amount:** \${$penaltyAmount}")
            ->line('')
            ->line('**What happens next:**')
            ->line('1. Our team will review your appeal and any evidence provided')
            ->line('2. We may contact you if additional information is needed')
            ->line('3. You will receive a notification once a decision has been made')
            ->line('4. Most appeals are reviewed within 3-5 business days')
            ->line('')
            ->line('While your appeal is under review, the penalty will be placed on hold and will not be deducted from your earnings.')
            ->line('')
            ->line('You can add additional evidence to your appeal at any time before a decision is made.')
            ->action('View Appeal Status', url("/worker/appeals/{$this->appeal->id}"))
            ->line('Thank you for your patience.');
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
            'type' => 'appeal_under_review',
            'title' => 'Appeal Under Review',
            'message' => "Your appeal for the {$penalty->penalty_type} penalty has been submitted and is under review.",
            'appeal_id' => $this->appeal->id,
            'penalty_id' => $penalty->id ?? null,
            'penalty_type' => $penalty->penalty_type ?? null,
            'penalty_amount' => $penalty->penalty_amount ?? 0,
            'submitted_at' => $this->appeal->submitted_at?->toDateTimeString(),
            'action_url' => url("/worker/appeals/{$this->appeal->id}"),
            'priority' => 'normal',
        ];
    }
}
