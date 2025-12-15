<?php

namespace App\Notifications;

use App\Models\Shift;
use App\Models\UrgentShiftRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * UrgentShiftNotification
 *
 * Notifies agencies of urgent shifts requiring immediate attention.
 *
 * TASK: AGY-004 Urgent Fill Routing
 *
 * Channels: Database, Mail, SMS (optional)
 * SLA: 30-minute response time
 */
class UrgentShiftNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The urgent shift request.
     *
     * @var UrgentShiftRequest
     */
    protected $urgentRequest;

    /**
     * The shift details.
     *
     * @var Shift
     */
    protected $shift;

    /**
     * Create a new notification instance.
     *
     * @param UrgentShiftRequest $urgentRequest
     * @param Shift $shift
     * @return void
     */
    public function __construct(UrgentShiftRequest $urgentRequest, Shift $shift)
    {
        $this->urgentRequest = $urgentRequest;
        $this->shift = $shift;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = ['database', 'mail'];

        // Add SMS for critical urgency (< 2 hours)
        if ($this->urgentRequest->hours_until_shift < 2) {
            // $channels[] = 'nexmo'; // Or 'twilio'
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $hoursUntilShift = $this->urgentRequest->hours_until_shift;
        $fillPercentage = $this->urgentRequest->fill_percentage;
        $spotsRemaining = $this->shift->required_workers - $this->shift->filled_workers;

        $urgencyEmoji = $hoursUntilShift < 2 ? '🔴' : '🟡';
        $subject = "{$urgencyEmoji} URGENT: Shift needs immediate attention";

        $message = (new MailMessage)
            ->subject($subject)
            ->priority(1) // High priority
            ->greeting("Urgent Shift Alert")
            ->line("A shift requires immediate agency support:")
            ->line("")
            ->line("**Shift Details:**")
            ->line("- Title: {$this->shift->title}")
            ->line("- Location: {$this->shift->location_city}, {$this->shift->location_state}")
            ->line("- Start Time: {$this->shift->start_time->format('D, M j @ g:ia')}")
            ->line("- Time Until Shift: **{$hoursUntilShift} hours**")
            ->line("")
            ->line("**Urgency Details:**")
            ->line("- Spots Remaining: **{$spotsRemaining}** of {$this->shift->required_workers}")
            ->line("- Fill Rate: {$fillPercentage}%")
            ->line("- Reason: " . $this->getUrgencyReasonText())
            ->line("")
            ->line("**Commission:** " . $this->getCommissionText())
            ->line("")
            ->action('View Shift & Respond', $this->getShiftUrl())
            ->line("")
            ->line("**⏰ Response SLA: 30 minutes**")
            ->line("Please respond as soon as possible to help fill this shift.");

        if ($hoursUntilShift < 2) {
            $message->line("")
                ->line("⚠️ **CRITICAL:** This shift starts in less than 2 hours!");
        }

        return $message;
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
            'type' => 'urgent_shift',
            'urgent_request_id' => $this->urgentRequest->id,
            'shift_id' => $this->shift->id,
            'shift_title' => $this->shift->title,
            'shift_start_time' => $this->shift->start_time->toDateTimeString(),
            'hours_until_shift' => $this->urgentRequest->hours_until_shift,
            'fill_percentage' => $this->urgentRequest->fill_percentage,
            'spots_remaining' => $this->shift->required_workers - $this->shift->filled_workers,
            'urgency_reason' => $this->urgentRequest->urgency_reason,
            'sla_deadline' => $this->urgentRequest->sla_deadline ? $this->urgentRequest->sla_deadline->toDateTimeString() : null,
            'location' => [
                'city' => $this->shift->location_city,
                'state' => $this->shift->location_state,
                'lat' => $this->shift->location_lat,
                'lng' => $this->shift->location_lng,
            ],
            'pay_rate' => $this->shift->final_rate ?? $this->shift->base_rate,
            'action_url' => $this->getShiftUrl(),
        ];
    }

    /**
     * Get human-readable urgency reason.
     *
     * @return string
     */
    protected function getUrgencyReasonText()
    {
        return match ($this->urgentRequest->urgency_reason) {
            'time_constraint' => 'Shift starting in less than 4 hours',
            'low_fill_rate' => 'Fill rate below 80% with shift approaching',
            'cancellation' => 'Worker cancellation created urgent gap',
            default => 'Requires immediate attention',
        };
    }

    /**
     * Get commission information text.
     *
     * @return string
     */
    protected function getCommissionText()
    {
        $agencyProfile = $this->urgentRequest->shift->postedByAgency?->agencyProfile;

        if ($agencyProfile && $agencyProfile->urgent_fill_enabled) {
            $multiplier = $agencyProfile->urgent_fill_commission_multiplier ?? 1.5;
            $baseRate = $agencyProfile->commission_rate ?? 15;
            $urgentRate = $baseRate * $multiplier;

            return "**{$urgentRate}%** (Urgent Fill Bonus: {$multiplier}x)";
        }

        return "Standard commission rate applies";
    }

    /**
     * Get URL to view shift details.
     *
     * @return string
     */
    protected function getShiftUrl()
    {
        return url("/agency/urgent-shifts/{$this->urgentRequest->id}");
    }
}
