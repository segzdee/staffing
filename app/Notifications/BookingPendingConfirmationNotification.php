<?php

namespace App\Notifications;

use App\Models\BookingConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SL-004: Notification sent when a booking requires confirmation.
 *
 * Sent to both workers and businesses when a shift booking
 * is created and awaiting confirmation from both parties.
 */
class BookingPendingConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The booking confirmation instance.
     */
    protected BookingConfirmation $confirmation;

    /**
     * Create a new notification instance.
     */
    public function __construct(BookingConfirmation $confirmation)
    {
        $this->confirmation = $confirmation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return config('booking_confirmation.notification_channels.pending', ['database', 'mail']);
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
        $isWorker = $notifiable->id === $this->confirmation->worker_id;

        $subject = $isWorker
            ? "Action Required: Confirm Your Shift Booking - {$shift->title}"
            : "New Booking: Worker Confirmation Required - {$shift->title}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($isWorker ? 'Confirm Your Booking' : 'New Shift Booking')
            ->line($isWorker
                ? 'You have been selected for a shift and need to confirm your attendance.'
                : 'A worker has been assigned to your shift and both parties need to confirm.');

        $message->line('')
            ->line('**Shift Details:**')
            ->line("- Title: {$shift->title}")
            ->line("- Date: {$shift->shift_date->format('l, M j, Y')}")
            ->line("- Time: {$shift->start_time->format('g:i A')} - {$shift->end_time->format('g:i A')}")
            ->line("- Location: {$shift->location_city}, {$shift->location_state}");

        if ($isWorker) {
            $message->line('')
                ->line('**Business:** '.$this->confirmation->business->name);
        } else {
            $message->line('')
                ->line('**Worker:** '.$this->confirmation->worker->name);
        }

        $message->line('')
            ->line("**Confirmation Code:** {$this->confirmation->confirmation_code}")
            ->line('')
            ->line("**Expires:** {$this->confirmation->expires_at->format('M j, Y g:i A')} ({$this->confirmation->hoursUntilExpiration()} hours remaining)");

        $actionUrl = $isWorker
            ? url("/worker/confirmations/{$this->confirmation->id}")
            : url("/business/confirmations/{$this->confirmation->id}");

        $message->action('Confirm Booking', $actionUrl)
            ->line('')
            ->line('Please confirm as soon as possible. Unconfirmed bookings will expire automatically.')
            ->line('')
            ->line('If you cannot work this shift, please decline so we can find another worker.');

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
        $isWorker = $notifiable->id === $this->confirmation->worker_id;

        return [
            'type' => 'booking_pending_confirmation',
            'confirmation_id' => $this->confirmation->id,
            'confirmation_code' => $this->confirmation->confirmation_code,
            'shift_id' => $this->confirmation->shift_id,
            'shift_title' => $this->confirmation->shift->title,
            'shift_date' => $this->confirmation->shift->shift_date->toDateString(),
            'shift_start_time' => $this->confirmation->shift->start_time->format('H:i'),
            'shift_end_time' => $this->confirmation->shift->end_time->format('H:i'),
            'worker_id' => $this->confirmation->worker_id,
            'worker_name' => $isWorker ? null : $this->confirmation->worker->name,
            'business_id' => $this->confirmation->business_id,
            'business_name' => $isWorker ? $this->confirmation->business->name : null,
            'expires_at' => $this->confirmation->expires_at->toDateTimeString(),
            'hours_until_expiry' => $this->confirmation->hoursUntilExpiration(),
            'action_url' => $isWorker
                ? url("/worker/confirmations/{$this->confirmation->id}")
                : url("/business/confirmations/{$this->confirmation->id}"),
            'requires_action_from' => $isWorker ? 'worker' : 'business',
        ];
    }
}
