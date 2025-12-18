<?php

namespace App\Notifications;

use App\Models\VenueSafetyFlag;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SAF-004: Venue Safety Flag Notification
 *
 * Notification sent to business owners when a safety concern is flagged
 * at one of their venues. Includes details of the concern and deadline
 * for response.
 */
class VenueSafetyFlagNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The safety flag that triggered this notification.
     */
    protected VenueSafetyFlag $flag;

    /**
     * Create a new notification instance.
     */
    public function __construct(VenueSafetyFlag $flag)
    {
        $this->flag = $flag;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $venue = $this->flag->venue;
        $severityLabel = $this->flag->severity_label;
        $flagTypeLabel = $this->flag->flag_type_label;

        $message = (new MailMessage)
            ->subject("[Safety Alert] {$flagTypeLabel} reported at {$venue->name}")
            ->greeting('Safety Concern Reported')
            ->line("A safety concern has been reported at your venue **{$venue->name}**.")
            ->line('')
            ->line('**Concern Details:**')
            ->line("- **Type:** {$flagTypeLabel}")
            ->line("- **Severity:** {$severityLabel}")
            ->line("- **Reported:** {$this->flag->created_at->format('F j, Y g:i A')}")
            ->line('')
            ->line('**Description:**')
            ->line($this->truncateDescription($this->flag->description));

        // Add severity-specific messaging
        if (in_array($this->flag->severity, [VenueSafetyFlag::SEVERITY_HIGH, VenueSafetyFlag::SEVERITY_CRITICAL])) {
            $message->error()
                ->line('')
                ->line('**This is a high-priority concern that requires immediate attention.**');
        }

        // Add response deadline
        if ($this->flag->business_response_due) {
            $message->line('')
                ->line("**Response Required By:** {$this->flag->business_response_due->format('F j, Y g:i A')}")
                ->line('Please review this concern and provide a response within 48 hours.');
        }

        $message->action('View Details & Respond', url("/business/venues/{$venue->id}/safety"))
            ->line('')
            ->line('Taking worker safety concerns seriously helps maintain your venue\'s reputation and ensures a safe working environment.')
            ->line('')
            ->line('If you believe this report was made in error, please contact our support team.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'venue_safety_flag',
            'flag_id' => $this->flag->id,
            'venue_id' => $this->flag->venue_id,
            'venue_name' => $this->flag->venue->name,
            'flag_type' => $this->flag->flag_type,
            'flag_type_label' => $this->flag->flag_type_label,
            'severity' => $this->flag->severity,
            'severity_label' => $this->flag->severity_label,
            'description_preview' => $this->truncateDescription($this->flag->description, 100),
            'response_due' => $this->flag->business_response_due?->toIso8601String(),
            'created_at' => $this->flag->created_at->toIso8601String(),
            'message' => sprintf(
                '%s safety concern reported at %s: %s',
                ucfirst($this->flag->severity),
                $this->flag->venue->name,
                $this->flag->flag_type_label
            ),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'venue_safety_flag';
    }

    /**
     * Truncate description for preview.
     */
    protected function truncateDescription(string $description, int $length = 300): string
    {
        if (strlen($description) <= $length) {
            return $description;
        }

        return substr($description, 0, $length).'...';
    }
}
