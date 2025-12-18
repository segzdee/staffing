<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * EvidenceDeadlineNotification
 *
 * FIN-010: Reminds parties about upcoming evidence submission deadline.
 *
 * Channels: Database, Mail
 */
class EvidenceDeadlineNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The dispute with approaching deadline.
     */
    protected Dispute $dispute;

    /**
     * Hours until deadline.
     */
    protected int $hoursRemaining;

    /**
     * The party being notified ('worker' or 'business').
     */
    protected string $partyType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Dispute $dispute, int $hoursRemaining, string $partyType)
    {
        $this->dispute = $dispute;
        $this->hoursRemaining = $hoursRemaining;
        $this->partyType = $partyType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('disputes.notifications.send_email', true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->hoursRemaining <= 12 ? 'URGENT: ' : '';
        $deadlineFormatted = $this->dispute->evidence_deadline->format('F j, Y \a\t g:i A');

        $message = (new MailMessage)
            ->subject("{$urgency}Evidence Deadline Approaching - Dispute #{$this->dispute->id}");

        if ($this->hoursRemaining <= 12) {
            $message->error();
        }

        $message
            ->greeting("Hello {$notifiable->name},")
            ->line('This is a reminder that the evidence submission deadline for your dispute is approaching.')
            ->line('')
            ->line("**Time Remaining: {$this->hoursRemaining} hours**")
            ->line("**Deadline: {$deadlineFormatted}**")
            ->line('')
            ->line('**Dispute Details:**')
            ->line("- Dispute ID: #{$this->dispute->id}")
            ->line("- Type: {$this->dispute->type_label}")
            ->line("- Amount: {$this->dispute->formatted_disputed_amount}");

        if ($this->partyType === 'worker') {
            $message
                ->line('')
                ->line('You have not yet submitted evidence to support your claim.')
                ->action('Submit Evidence', url("/worker/disputes/{$this->dispute->id}/evidence"));
        } else {
            $message
                ->line('')
                ->line('You have not yet submitted evidence to support your response.')
                ->action('Submit Evidence', url("/business/disputes/{$this->dispute->id}/evidence"));
        }

        $message
            ->line('')
            ->line('Failure to submit evidence before the deadline may impact the resolution of your dispute.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $actionUrl = $this->partyType === 'worker'
            ? url("/worker/disputes/{$this->dispute->id}/evidence")
            : url("/business/disputes/{$this->dispute->id}/evidence");

        return [
            'type' => 'evidence_deadline_reminder',
            'dispute_id' => $this->dispute->id,
            'hours_remaining' => $this->hoursRemaining,
            'deadline' => $this->dispute->evidence_deadline->toDateTimeString(),
            'is_urgent' => $this->hoursRemaining <= 12,
            'party_type' => $this->partyType,
            'action_url' => $actionUrl,
            'message' => "Evidence deadline in {$this->hoursRemaining} hours for dispute #{$this->dispute->id}",
        ];
    }
}
