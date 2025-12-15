<?php

namespace App\Notifications;

use App\Models\AdminDisputeQueue;
use App\Services\DisputeEscalationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * DisputeSLABreachWarningNotification
 *
 * Warns assigned admin when dispute is approaching SLA breach (80% of time elapsed).
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 *
 * Channels: Database, Mail
 */
class DisputeSLABreachWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The dispute approaching SLA breach.
     *
     * @var AdminDisputeQueue
     */
    protected $dispute;

    /**
     * Create a new notification instance.
     *
     * @param AdminDisputeQueue $dispute
     * @return void
     */
    public function __construct(AdminDisputeQueue $dispute)
    {
        $this->dispute = $dispute;
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
        $remainingHours = round($this->dispute->getRemainingHours(), 1);
        $percentage = round($this->dispute->getSLAPercentage());
        $deadline = $this->dispute->getSLADeadline();

        $slaThreshold = DisputeEscalationService::SLA_THRESHOLDS[$this->dispute->priority] ?? 120;
        $slaDays = $slaThreshold / 24;

        return (new MailMessage)
            ->subject("WARNING: Dispute #{$this->dispute->id} Approaching SLA Breach")
            ->priority(2)
            ->greeting("SLA Breach Warning")
            ->line("A dispute assigned to you is approaching its SLA deadline.")
            ->line("")
            ->line("**Dispute Details:**")
            ->line("- Dispute ID: #{$this->dispute->id}")
            ->line("- Priority: {$this->dispute->priority}")
            ->line("- Status: {$this->dispute->status}")
            ->line("- Filed: {$this->dispute->filed_at->format('M d, Y g:ia')}")
            ->line("")
            ->line("**SLA Warning:**")
            ->line("- SLA Threshold: {$slaDays} day(s) ({$slaThreshold} hours)")
            ->line("- Time Elapsed: **{$percentage}%**")
            ->line("- Remaining Time: **{$remainingHours} hours**")
            ->line("- Deadline: {$deadline->format('M d, Y g:ia')}")
            ->line("")
            ->line("**Parties Involved:**")
            ->line("- Worker: " . ($this->dispute->worker->name ?? 'N/A'))
            ->line("- Business: " . ($this->dispute->business->name ?? 'N/A'))
            ->line("")
            ->line("**Dispute Reason:**")
            ->line(substr($this->dispute->dispute_reason, 0, 200) . (strlen($this->dispute->dispute_reason) > 200 ? '...' : ''))
            ->line("")
            ->action('Review Dispute Now', url("/panel/admin/disputes/{$this->dispute->id}"))
            ->line("")
            ->line("If this dispute is not resolved before the deadline, it will be automatically escalated to a senior admin.");
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
            'type' => 'dispute_sla_warning',
            'dispute_id' => $this->dispute->id,
            'priority' => $this->dispute->priority,
            'status' => $this->dispute->status,
            'sla_percentage' => $this->dispute->getSLAPercentage(),
            'remaining_hours' => $this->dispute->getRemainingHours(),
            'deadline' => $this->dispute->getSLADeadline()->toDateTimeString(),
            'worker_id' => $this->dispute->worker_id,
            'worker_name' => $this->dispute->worker->name ?? null,
            'business_id' => $this->dispute->business_id,
            'business_name' => $this->dispute->business->name ?? null,
            'action_url' => url("/panel/admin/disputes/{$this->dispute->id}"),
        ];
    }
}
