<?php

namespace App\Notifications;

use App\Models\VerificationQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * VerificationSLABreachedNotification - ADM-001
 *
 * Sent to verification admin team when a verification request
 * has exceeded its SLA deadline (breached status).
 */
class VerificationSLABreachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected VerificationQueue $verification;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationQueue $verification)
    {
        $this->verification = $verification;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationType = $this->getVerificationTypeLabel();
        $deadline = $this->verification->sla_deadline?->format('M j, Y g:i A');
        $hoursOverdue = abs(round($this->verification->sla_remaining_hours ?? 0, 1));

        return (new MailMessage)
            ->subject("URGENT: SLA Breached - {$verificationType} Verification Overdue")
            ->error()
            ->greeting("Urgent Action Required")
            ->line("A verification request has exceeded its SLA deadline and requires immediate processing.")
            ->line("")
            ->line("**Verification Details:**")
            ->line("- Type: {$verificationType}")
            ->line("- Verification ID: #{$this->verification->id}")
            ->line("- SLA Deadline Was: {$deadline}")
            ->line("- Hours Overdue: {$hoursOverdue}")
            ->line("")
            ->line("This verification is now in breach of its SLA target. Please process immediately.")
            ->action('Process Now', url('/panel/admin/verification-queue/' . $this->verification->id))
            ->line('Continued SLA breaches may affect platform compliance metrics.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_sla_breached',
            'level' => 'critical',
            'verification_id' => $this->verification->id,
            'verification_type' => $this->verification->verification_type,
            'sla_deadline' => $this->verification->sla_deadline?->toISOString(),
            'hours_overdue' => abs($this->verification->sla_remaining_hours ?? 0),
            'message' => 'URGENT: ' . $this->getVerificationTypeLabel() . ' verification #' . $this->verification->id . ' has breached SLA',
            'url' => '/panel/admin/verification-queue/' . $this->verification->id,
        ];
    }

    /**
     * Get human-readable verification type label
     */
    protected function getVerificationTypeLabel(): string
    {
        $labels = [
            'identity' => 'Worker Identity',
            'background_check' => 'Background Check',
            'certification' => 'Certification',
            'business_license' => 'Business License',
            'agency' => 'Agency',
        ];

        return $labels[$this->verification->verification_type] ?? ucfirst($this->verification->verification_type);
    }
}
