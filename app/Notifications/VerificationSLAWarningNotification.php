<?php

namespace App\Notifications;

use App\Models\VerificationQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * VerificationSLAWarningNotification - ADM-001
 *
 * Sent to verification admin team when a verification request
 * reaches 80% of its SLA time (at_risk status).
 */
class VerificationSLAWarningNotification extends Notification implements ShouldQueue
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
        $remainingTime = $this->verification->sla_remaining_time;
        $deadline = $this->verification->sla_deadline?->format('M j, Y g:i A');

        return (new MailMessage)
            ->subject("SLA Warning: {$verificationType} Verification Approaching Deadline")
            ->level('warning')
            ->greeting("Attention Required")
            ->line("A verification request is approaching its SLA deadline and requires immediate attention.")
            ->line("")
            ->line("**Verification Details:**")
            ->line("- Type: {$verificationType}")
            ->line("- Verification ID: #{$this->verification->id}")
            ->line("- Deadline: {$deadline}")
            ->line("- Time Remaining: {$remainingTime}")
            ->line("")
            ->line("This verification has passed 80% of its allotted processing time.")
            ->action('Review Verification', url('/panel/admin/verification-queue/' . $this->verification->id))
            ->line('Please review and process this verification as soon as possible to maintain SLA compliance.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_sla_warning',
            'verification_id' => $this->verification->id,
            'verification_type' => $this->verification->verification_type,
            'sla_deadline' => $this->verification->sla_deadline?->toISOString(),
            'sla_remaining_hours' => $this->verification->sla_remaining_hours,
            'message' => $this->getVerificationTypeLabel() . ' verification #' . $this->verification->id . ' is approaching SLA deadline',
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
