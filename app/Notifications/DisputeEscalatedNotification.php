<?php

namespace App\Notifications;

use App\Models\AdminDisputeQueue;
use App\Models\DisputeEscalation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DisputeEscalatedNotification
 *
 * Notifies senior admin when a dispute is escalated to them.
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 *
 * Channels: Database, Mail
 */
class DisputeEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The dispute that was escalated.
     *
     * @var AdminDisputeQueue
     */
    protected $dispute;

    /**
     * The escalation record.
     *
     * @var DisputeEscalation
     */
    protected $escalation;

    /**
     * Create a new notification instance.
     *
     * @param AdminDisputeQueue $dispute
     * @param DisputeEscalation $escalation
     * @return void
     */
    public function __construct(AdminDisputeQueue $dispute, DisputeEscalation $escalation)
    {
        $this->dispute = $dispute;
        $this->escalation = $escalation;
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
        $levelName = $this->escalation->getLevelName();
        $remainingHours = round($this->dispute->getRemainingHours(), 1);

        return (new MailMessage)
            ->subject("ESCALATED: Dispute #{$this->dispute->id} Requires Your Attention")
            ->priority(1)
            ->greeting("Dispute Escalation Alert")
            ->line("A dispute has been escalated to you for immediate attention.")
            ->line("")
            ->line("**Dispute Details:**")
            ->line("- Dispute ID: #{$this->dispute->id}")
            ->line("- Priority: **{$this->dispute->priority}** (upgraded)")
            ->line("- Escalation Level: {$levelName}")
            ->line("- Filed: {$this->dispute->filed_at->format('M d, Y g:ia')}")
            ->line("")
            ->line("**Escalation Reason:**")
            ->line($this->escalation->escalation_reason)
            ->line("")
            ->line("**Parties Involved:**")
            ->line("- Worker: " . ($this->dispute->worker->name ?? 'N/A'))
            ->line("- Business: " . ($this->dispute->business->name ?? 'N/A'))
            ->line("")
            ->line("**Dispute Reason:**")
            ->line(substr($this->dispute->dispute_reason, 0, 200) . (strlen($this->dispute->dispute_reason) > 200 ? '...' : ''))
            ->line("")
            ->line("**SLA Status:**")
            ->line("- Remaining Time: **{$remainingHours} hours**")
            ->line("- Original SLA has been breached")
            ->line("")
            ->action('Review Dispute Now', url("/panel/admin/disputes/{$this->dispute->id}"))
            ->line("")
            ->line("Please review and take action on this dispute as soon as possible.");
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
            'type' => 'dispute_escalated',
            'dispute_id' => $this->dispute->id,
            'escalation_id' => $this->escalation->id,
            'escalation_level' => $this->escalation->escalation_level,
            'escalation_reason' => $this->escalation->escalation_reason,
            'priority' => $this->dispute->priority,
            'worker_id' => $this->dispute->worker_id,
            'worker_name' => $this->dispute->worker->name ?? null,
            'business_id' => $this->dispute->business_id,
            'business_name' => $this->dispute->business->name ?? null,
            'escalated_from_admin_id' => $this->escalation->escalated_from_admin_id,
            'remaining_hours' => $this->dispute->getRemainingHours(),
            'action_url' => url("/panel/admin/disputes/{$this->dispute->id}"),
        ];
    }
}
