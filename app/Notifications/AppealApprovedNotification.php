<?php

namespace App\Notifications;

use App\Models\PenaltyAppeal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to workers when their penalty appeal is approved.
 * FIN-006: Worker Penalty Appeal Notifications
 */
class AppealApprovedNotification extends Notification implements ShouldQueue
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
        $isFullWaiver = $this->appeal->adjusted_amount === null || $this->appeal->adjusted_amount == 0;
        $originalAmount = number_format($penalty->penalty_amount ?? 0, 2);

        $mail = (new MailMessage)
            ->subject('Appeal Approved - Penalty Decision')
            ->greeting("Hello {$notifiable->name},")
            ->line('Great news! Your penalty appeal has been approved.')
            ->line('')
            ->line("**Penalty Type:** " . ucfirst(str_replace('_', ' ', $penalty->penalty_type ?? 'Unknown')))
            ->line("**Original Penalty Amount:** \${$originalAmount}");

        if ($isFullWaiver) {
            $mail->line('**Decision:** Full penalty waiver')
                ->line('The entire penalty has been waived and removed from your account.');
        } else {
            $adjustedAmount = number_format($this->appeal->adjusted_amount, 2);
            $mail->line("**Adjusted Amount:** \${$adjustedAmount}")
                ->line('The penalty has been reduced based on your appeal.');
        }

        $mail->line('')
            ->line("**Decision Reason:**")
            ->line($this->appeal->decision_reason ?? 'No reason provided.')
            ->line('')
            ->action('View Appeal Details', url("/worker/appeals/{$this->appeal->id}"))
            ->line('Thank you for your patience during the review process.');

        return $mail;
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
        $isFullWaiver = $this->appeal->adjusted_amount === null || $this->appeal->adjusted_amount == 0;

        return [
            'type' => 'appeal_approved',
            'title' => 'Appeal Approved',
            'message' => $isFullWaiver
                ? 'Your penalty appeal has been approved with a full waiver.'
                : 'Your penalty appeal has been approved with a reduced penalty amount.',
            'appeal_id' => $this->appeal->id,
            'penalty_id' => $penalty->id ?? null,
            'penalty_type' => $penalty->penalty_type ?? null,
            'original_amount' => $penalty->penalty_amount ?? 0,
            'adjusted_amount' => $this->appeal->adjusted_amount,
            'is_full_waiver' => $isFullWaiver,
            'decision_reason' => $this->appeal->decision_reason,
            'reviewed_at' => $this->appeal->reviewed_at?->toDateTimeString(),
            'action_url' => url("/worker/appeals/{$this->appeal->id}"),
            'priority' => 'high',
        ];
    }
}
