<?php

namespace App\Notifications;

use App\Models\BookingConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SL-004: Reminder notification for pending confirmations.
 *
 * Sent to workers or businesses when their confirmation
 * is approaching expiry and they haven't responded yet.
 */
class ConfirmationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The booking confirmation instance.
     */
    protected BookingConfirmation $confirmation;

    /**
     * The recipient type (worker or business).
     */
    protected string $recipientType;

    /**
     * Create a new notification instance.
     */
    public function __construct(BookingConfirmation $confirmation, string $recipientType)
    {
        $this->confirmation = $confirmation;
        $this->recipientType = $recipientType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        $channels = config('booking_confirmation.notification_channels.reminder', ['database', 'mail']);

        // Add push notification for urgent reminders (< 4 hours)
        if ($this->confirmation->hoursUntilExpiration() < 4) {
            if (! in_array('push', $channels)) {
                $channels[] = 'database'; // Ensure database is included for urgency
            }
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $this->confirmation->load(['shift', 'worker', 'business']);

        $shift = $this->confirmation->shift;
        $hoursRemaining = round($this->confirmation->hoursUntilExpiration(), 1);
        $isUrgent = $hoursRemaining < 4;

        $subject = $isUrgent
            ? "URGENT: Confirm Your Booking NOW - {$shift->title}"
            : "Reminder: Confirm Your Booking - {$shift->title}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($isUrgent ? 'Action Required Immediately!' : 'Reminder: Confirmation Needed');

        if ($isUrgent) {
            $message->line("Your booking confirmation expires in **{$hoursRemaining} hours**!")
                ->line('If you do not confirm, the booking will be cancelled automatically.');
        } else {
            $message->line('This is a reminder that you have a pending shift booking that requires your confirmation.')
                ->line("The confirmation expires in **{$hoursRemaining} hours**.");
        }

        $message->line('')
            ->line('**Shift Details:**')
            ->line("- Title: {$shift->title}")
            ->line("- Date: {$shift->shift_date->format('l, M j, Y')}")
            ->line("- Time: {$shift->start_time->format('g:i A')} - {$shift->end_time->format('g:i A')}")
            ->line("- Location: {$shift->location_city}, {$shift->location_state}");

        if ($this->recipientType === 'worker') {
            $message->line('')
                ->line('**Business:** '.$this->confirmation->business->name);
        } else {
            $message->line('')
                ->line('**Worker:** '.$this->confirmation->worker->name);
        }

        $message->line('')
            ->line("**Confirmation Code:** {$this->confirmation->confirmation_code}")
            ->line("**Expires:** {$this->confirmation->expires_at->format('M j, Y g:i A')}");

        $actionUrl = $this->recipientType === 'worker'
            ? url("/worker/confirmations/{$this->confirmation->id}")
            : url("/business/confirmations/{$this->confirmation->id}");

        $message->action('Confirm Now', $actionUrl);

        if ($isUrgent) {
            $message->line('')
                ->line('This is an automated reminder. If you cannot work this shift, please decline so we can find a replacement.');
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        $this->confirmation->load(['shift', 'worker', 'business']);
        $shift = $this->confirmation->shift;
        $hoursRemaining = round($this->confirmation->hoursUntilExpiration(), 1);

        return [
            'type' => 'confirmation_reminder',
            'confirmation_id' => $this->confirmation->id,
            'confirmation_code' => $this->confirmation->confirmation_code,
            'shift_id' => $this->confirmation->shift_id,
            'shift_title' => $shift->title,
            'shift_date' => $shift->shift_date->toDateString(),
            'shift_start_time' => $shift->start_time->format('H:i'),
            'recipient_type' => $this->recipientType,
            'hours_until_expiry' => $hoursRemaining,
            'is_urgent' => $hoursRemaining < 4,
            'expires_at' => $this->confirmation->expires_at->toDateTimeString(),
            'action_url' => $this->recipientType === 'worker'
                ? url("/worker/confirmations/{$this->confirmation->id}")
                : url("/business/confirmations/{$this->confirmation->id}"),
            'message' => $hoursRemaining < 4
                ? "URGENT: Your booking confirmation expires in {$hoursRemaining} hours!"
                : "Reminder: Your booking confirmation expires in {$hoursRemaining} hours.",
        ];
    }
}
