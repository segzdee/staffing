<?php

namespace App\Notifications;

use App\Models\AdminDisputeQueue;
use App\Models\PaymentAdjustment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DisputeResolutionNotification
 *
 * Notifies all parties when a dispute is resolved with outcome and adjustment details.
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 *
 * Channels: Database, Mail
 */
class DisputeResolutionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The resolved dispute.
     *
     * @var AdminDisputeQueue
     */
    protected $dispute;

    /**
     * The payment adjustment, if any.
     *
     * @var PaymentAdjustment|null
     */
    protected $adjustment;

    /**
     * Create a new notification instance.
     *
     * @param AdminDisputeQueue $dispute
     * @param PaymentAdjustment|null $adjustment
     * @return void
     */
    public function __construct(AdminDisputeQueue $dispute, ?PaymentAdjustment $adjustment = null)
    {
        $this->dispute = $dispute;
        $this->adjustment = $adjustment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $outcomeLabel = $this->dispute->getOutcomeLabel();
        $isInFavor = $this->isInUserFavor($notifiable);

        $mailMessage = (new MailMessage)
            ->subject("Dispute #{$this->dispute->id} Has Been Resolved")
            ->greeting("Dispute Resolution Notice");

        if ($isInFavor) {
            $mailMessage->line("Good news! Your dispute has been resolved in your favor.");
        } else {
            $mailMessage->line("Your dispute has been reviewed and a resolution has been reached.");
        }

        $mailMessage
            ->line("")
            ->line("**Dispute Details:**")
            ->line("- Dispute ID: #{$this->dispute->id}")
            ->line("- Filed: {$this->dispute->filed_at->format('M d, Y')}")
            ->line("- Resolved: {$this->dispute->resolved_at->format('M d, Y')}")
            ->line("")
            ->line("**Resolution:**")
            ->line("- Outcome: **{$outcomeLabel}**");

        if ($this->dispute->resolution_notes) {
            $mailMessage->line("- Notes: {$this->dispute->resolution_notes}");
        }

        // Add adjustment details if applicable
        if ($this->adjustment && $this->adjustment->amount > 0) {
            $formattedAmount = $this->adjustment->getFormattedAmount();
            $adjustmentType = $this->adjustment->getTypeLabel();

            $mailMessage
                ->line("")
                ->line("**Financial Adjustment:**")
                ->line("- Type: {$adjustmentType}")
                ->line("- Amount: **{$formattedAmount}**");

            if ($this->adjustment->applied_to === 'worker' && $notifiable->id === $this->dispute->worker_id) {
                $mailMessage->line("- This amount will be credited to your account.");
            } elseif ($this->adjustment->applied_to === 'business' && $notifiable->id === $this->dispute->business_id) {
                $mailMessage->line("- This amount will be refunded to your payment method.");
            } elseif ($this->adjustment->applied_to === 'both') {
                $mailMessage->line("- The amount has been split between both parties.");
            }
        }

        $mailMessage
            ->line("")
            ->action('View Resolution Details', $this->getActionUrl($notifiable))
            ->line("")
            ->line("If you have any questions about this resolution, please contact support.");

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $data = [
            'type' => 'dispute_resolution',
            'dispute_id' => $this->dispute->id,
            'resolution_outcome' => $this->dispute->resolution_outcome,
            'outcome_label' => $this->dispute->getOutcomeLabel(),
            'resolution_notes' => $this->dispute->resolution_notes,
            'resolved_at' => $this->dispute->resolved_at->toDateTimeString(),
            'is_in_favor' => $this->isInUserFavor($notifiable),
            'action_url' => $this->getActionUrl($notifiable),
        ];

        if ($this->adjustment) {
            $data['adjustment'] = [
                'id' => $this->adjustment->id,
                'type' => $this->adjustment->adjustment_type,
                'amount' => $this->adjustment->amount,
                'formatted_amount' => $this->adjustment->getFormattedAmount(),
                'applied_to' => $this->adjustment->applied_to,
            ];
        }

        return $data;
    }

    /**
     * Check if resolution is in the user's favor.
     *
     * @param mixed $notifiable
     * @return bool
     */
    protected function isInUserFavor($notifiable): bool
    {
        $outcome = $this->dispute->resolution_outcome;

        // Worker check
        if ($notifiable->id === $this->dispute->worker_id) {
            return $outcome === AdminDisputeQueue::OUTCOME_WORKER_FAVOR ||
                $outcome === AdminDisputeQueue::OUTCOME_SPLIT;
        }

        // Business check
        if ($notifiable->id === $this->dispute->business_id) {
            return $outcome === AdminDisputeQueue::OUTCOME_BUSINESS_FAVOR ||
                $outcome === AdminDisputeQueue::OUTCOME_SPLIT;
        }

        return false;
    }

    /**
     * Get the appropriate action URL based on user type.
     *
     * @param mixed $notifiable
     * @return string
     */
    protected function getActionUrl($notifiable): string
    {
        // Admin gets admin panel link
        if ($notifiable->role === 'admin') {
            return url("/panel/admin/disputes/{$this->dispute->id}");
        }

        // Worker gets worker portal link
        if ($notifiable->id === $this->dispute->worker_id) {
            return url("/worker/disputes/{$this->dispute->id}");
        }

        // Business gets business portal link
        if ($notifiable->id === $this->dispute->business_id) {
            return url("/business/disputes/{$this->dispute->id}");
        }

        // Default fallback
        return url("/disputes/{$this->dispute->id}");
    }
}
