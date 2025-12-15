<?php

namespace App\Notifications;

use App\Models\WorkerConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to worker when business initiates hire intent.
 * BIZ-010: Direct Hire & Conversion
 */
class HireIntentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $conversion;

    public function __construct(WorkerConversion $conversion)
    {
        $this->conversion = $conversion;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $businessName = $this->conversion->business->name ?? 'A business';
        $fee = $this->conversion->conversion_fee_dollars;

        return (new MailMessage)
            ->subject('Direct Hire Opportunity from ' . $businessName)
            ->greeting("Hello {$notifiable->name},")
            ->line("{$businessName} would like to hire you directly through OvertimeStaff!")
            ->line("You have worked {$this->conversion->total_hours_worked} hours across {$this->conversion->total_shifts_completed} shifts for this business.")
            ->line("Conversion Fee: €{$fee} ({$this->conversion->conversion_fee_tier})")
            ->line("If you accept, the business will pay the conversion fee and you will transition to direct employment with them.")
            ->line("Notes from business: " . ($this->conversion->hire_intent_notes ?? 'None'))
            ->action('Review Offer', url('/worker/conversions/' . $this->conversion->id))
            ->line('Please review and respond to this offer at your earliest convenience.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'hire_intent',
            'title' => 'Direct Hire Opportunity',
            'message' => "{$this->conversion->business->name} wants to hire you directly!",
            'conversion_id' => $this->conversion->id,
            'business_id' => $this->conversion->business_id,
            'business_name' => $this->conversion->business->name,
            'conversion_fee' => $this->conversion->conversion_fee_dollars,
            'action_url' => url('/worker/conversions/' . $this->conversion->id),
            'priority' => 'high',
        ];
    }
}
