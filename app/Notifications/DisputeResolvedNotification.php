<?php

namespace App\Notifications;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DisputeResolvedNotification
 *
 * FIN-010: Notifies both parties when a dispute is resolved.
 *
 * Channels: Database, Mail
 */
class DisputeResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The resolved dispute.
     */
    protected Dispute $dispute;

    /**
     * The party being notified ('worker' or 'business').
     */
    protected string $partyType;

    /**
     * Create a new notification instance.
     */
    public function __construct(Dispute $dispute, string $partyType)
    {
        $this->dispute = $dispute;
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
        $isInFavor = $this->isInUserFavor();

        $message = (new MailMessage)
            ->subject("Dispute #{$this->dispute->id} - Resolution Notice")
            ->greeting("Hello {$notifiable->name},");

        if ($isInFavor) {
            $message->line('Good news! Your dispute has been resolved in your favor.');
        } elseif ($this->dispute->resolution === Dispute::RESOLUTION_SPLIT) {
            $message->line('Your dispute has been resolved with a split decision.');
        } else {
            $message->line('Your dispute has been reviewed and a resolution has been reached.');
        }

        $message
            ->line('')
            ->line('**Resolution Details:**')
            ->line("- Dispute ID: #{$this->dispute->id}")
            ->line("- Original Amount: {$this->dispute->formatted_disputed_amount}")
            ->line("- Resolution: **{$this->dispute->resolution_label}**")
            ->line("- Resolution Amount: {$this->dispute->formatted_resolution_amount}")
            ->line("- Resolved On: {$this->dispute->resolved_at->format('F j, Y')}");

        if ($this->dispute->resolution_notes) {
            $message
                ->line('')
                ->line('**Resolution Notes:**')
                ->line($this->dispute->resolution_notes);
        }

        // Add financial impact information
        if ($this->dispute->resolution_amount && $this->dispute->resolution_amount > 0) {
            $message->line('');

            if ($this->partyType === 'worker') {
                if ($this->dispute->resolution === Dispute::RESOLUTION_WORKER_FAVOR) {
                    $message->line("**Financial Impact:** You will receive \${$this->dispute->resolution_amount}. This amount will be credited to your account within ".config('disputes.resolution_processing_days', 3).' business days.');
                } elseif ($this->dispute->resolution === Dispute::RESOLUTION_SPLIT) {
                    $halfAmount = number_format($this->dispute->resolution_amount / 2, 2);
                    $message->line("**Financial Impact:** You will receive \${$halfAmount} as part of the split resolution.");
                }
            } else {
                if ($this->dispute->resolution === Dispute::RESOLUTION_BUSINESS_FAVOR) {
                    $message->line('**Financial Impact:** No additional payment required. The original payment stands.');
                } elseif ($this->dispute->resolution === Dispute::RESOLUTION_WORKER_FAVOR) {
                    $message->line("**Financial Impact:** An additional \${$this->dispute->resolution_amount} will be deducted and paid to the worker.");
                } elseif ($this->dispute->resolution === Dispute::RESOLUTION_SPLIT) {
                    $halfAmount = number_format($this->dispute->resolution_amount / 2, 2);
                    $message->line("**Financial Impact:** \${$halfAmount} will be deducted as part of the split resolution.");
                }
            }
        }

        $actionUrl = $this->partyType === 'worker'
            ? url("/worker/disputes/{$this->dispute->id}")
            : url("/business/disputes/{$this->dispute->id}");

        $message
            ->line('')
            ->action('View Resolution Details', $actionUrl)
            ->line('')
            ->line('If you have questions about this resolution, please contact support.');

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
            ? url("/worker/disputes/{$this->dispute->id}")
            : url("/business/disputes/{$this->dispute->id}");

        return [
            'type' => 'dispute_resolved',
            'dispute_id' => $this->dispute->id,
            'shift_id' => $this->dispute->shift_id,
            'resolution' => $this->dispute->resolution,
            'resolution_label' => $this->dispute->resolution_label,
            'resolution_amount' => $this->dispute->resolution_amount,
            'disputed_amount' => $this->dispute->disputed_amount,
            'is_in_favor' => $this->isInUserFavor(),
            'resolved_at' => $this->dispute->resolved_at->toDateTimeString(),
            'party_type' => $this->partyType,
            'action_url' => $actionUrl,
            'message' => "Dispute #{$this->dispute->id} resolved - {$this->dispute->resolution_label}",
        ];
    }

    /**
     * Check if resolution is in the user's favor.
     */
    private function isInUserFavor(): bool
    {
        if ($this->partyType === 'worker') {
            return in_array($this->dispute->resolution, [
                Dispute::RESOLUTION_WORKER_FAVOR,
                Dispute::RESOLUTION_SPLIT,
            ]);
        }

        return in_array($this->dispute->resolution, [
            Dispute::RESOLUTION_BUSINESS_FAVOR,
            Dispute::RESOLUTION_SPLIT,
        ]);
    }
}
