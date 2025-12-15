<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Notification sent to senior admins when appeals have been pending for too long.
 * FIN-006: Worker Penalty Appeal Notifications - Escalation Workflow
 */
class SeniorAdminAppealEscalationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Collection $appeals;
    protected int $daysOverdue;

    /**
     * Create a new notification instance.
     *
     * @param Collection $appeals
     * @param int $daysOverdue
     */
    public function __construct(Collection $appeals, int $daysOverdue = 7)
    {
        $this->appeals = $appeals;
        $this->daysOverdue = $daysOverdue;
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
        $appealCount = $this->appeals->count();
        $totalPenaltyAmount = $this->appeals->sum(function ($appeal) {
            return $appeal->penalty->penalty_amount ?? 0;
        });

        $mail = (new MailMessage)
            ->subject("ESCALATION: {$appealCount} Appeal(s) Pending > {$this->daysOverdue} Days")
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$appealCount} penalty appeal(s)** have been pending for more than **{$this->daysOverdue} days** and require immediate attention.")
            ->line('')
            ->line("**Total Pending Penalty Amount:** \$" . number_format($totalPenaltyAmount, 2))
            ->line('')
            ->line('**Appeals Requiring Attention:**');

        // List up to 10 appeals in the email
        foreach ($this->appeals->take(10) as $appeal) {
            $workerName = $appeal->worker->name ?? 'Unknown Worker';
            $penaltyType = ucfirst(str_replace('_', ' ', $appeal->penalty->penalty_type ?? 'Unknown'));
            $amount = number_format($appeal->penalty->penalty_amount ?? 0, 2);
            $submittedDays = $appeal->submitted_at ? $appeal->submitted_at->diffInDays(now()) : 'N/A';

            $mail->line("- **#APL-{$appeal->id}**: {$workerName} - {$penaltyType} (\${$amount}) - Pending {$submittedDays} days");
        }

        if ($appealCount > 10) {
            $mail->line("- ... and " . ($appealCount - 10) . " more");
        }

        $mail->line('')
            ->line('**Impact of Delayed Reviews:**')
            ->line('- Workers are unable to receive clear resolution')
            ->line('- Penalties remain on hold, affecting financial reporting')
            ->line('- May result in worker dissatisfaction and complaints')
            ->line('')
            ->action('Review Pending Appeals', url('/admin/appeals?status=pending'))
            ->line('Please prioritize these appeals for review.');

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
        $appealIds = $this->appeals->pluck('id')->toArray();
        $totalAmount = $this->appeals->sum(function ($appeal) {
            return $appeal->penalty->penalty_amount ?? 0;
        });

        return [
            'type' => 'appeal_escalation',
            'title' => 'Appeal Escalation Alert',
            'message' => "{$this->appeals->count()} appeal(s) have been pending for more than {$this->daysOverdue} days and require immediate review.",
            'appeal_count' => $this->appeals->count(),
            'appeal_ids' => $appealIds,
            'days_overdue' => $this->daysOverdue,
            'total_penalty_amount' => $totalAmount,
            'action_url' => url('/admin/appeals?status=pending'),
            'priority' => 'urgent',
        ];
    }
}
