<?php

namespace App\Notifications;

use App\Models\BookingConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SL-004: Notification sent when a booking is fully confirmed.
 *
 * Sent to both workers and businesses when both parties
 * have confirmed the shift booking.
 */
class BookingConfirmedNotification extends Notification implements ShouldQueue
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
        return config('booking_confirmation.notification_channels.confirmed', ['database', 'mail']);
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

        $subject = "Booking Confirmed: {$shift->title}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Booking Confirmed!')
            ->line($isWorker
                ? 'Great news! Your shift booking has been confirmed by both you and the business.'
                : 'Great news! The shift booking has been confirmed by both you and the worker.');

        $message->line('')
            ->line('**Shift Details:**')
            ->line("- Title: {$shift->title}")
            ->line("- Date: {$shift->shift_date->format('l, M j, Y')}")
            ->line("- Time: {$shift->start_time->format('g:i A')} - {$shift->end_time->format('g:i A')}")
            ->line("- Location: {$shift->location_address}")
            ->line("- City: {$shift->location_city}, {$shift->location_state}");

        if ($isWorker) {
            $message->line('')
                ->line('**Business:** '.$this->confirmation->business->name)
                ->line('')
                ->line("**Confirmation Code:** {$this->confirmation->confirmation_code}")
                ->line('You may need to present this code when checking in.');

            // Add special instructions if available
            if ($shift->special_instructions) {
                $message->line('')
                    ->line('**Special Instructions:**')
                    ->line($shift->special_instructions);
            }

            if ($shift->dress_code) {
                $message->line('')
                    ->line("**Dress Code:** {$shift->dress_code}");
            }

            if ($shift->parking_info) {
                $message->line('')
                    ->line("**Parking:** {$shift->parking_info}");
            }
        } else {
            $message->line('')
                ->line('**Worker:** '.$this->confirmation->worker->name)
                ->line("**Confirmation Code:** {$this->confirmation->confirmation_code}");
        }

        $actionUrl = $isWorker
            ? url('/worker/shifts/'.$shift->id)
            : url('/business/shifts/'.$shift->id);

        $message->action('View Shift Details', $actionUrl)
            ->line('')
            ->line('We will send you reminders before the shift starts.');

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
        $shift = $this->confirmation->shift;

        return [
            'type' => 'booking_confirmed',
            'confirmation_id' => $this->confirmation->id,
            'confirmation_code' => $this->confirmation->confirmation_code,
            'shift_id' => $this->confirmation->shift_id,
            'shift_title' => $shift->title,
            'shift_date' => $shift->shift_date->toDateString(),
            'shift_start_time' => $shift->start_time->format('H:i'),
            'shift_end_time' => $shift->end_time->format('H:i'),
            'shift_location' => "{$shift->location_city}, {$shift->location_state}",
            'worker_id' => $this->confirmation->worker_id,
            'worker_name' => $isWorker ? null : $this->confirmation->worker->name,
            'business_id' => $this->confirmation->business_id,
            'business_name' => $isWorker ? $this->confirmation->business->name : null,
            'worker_confirmed_at' => $this->confirmation->worker_confirmed_at?->toDateTimeString(),
            'business_confirmed_at' => $this->confirmation->business_confirmed_at?->toDateTimeString(),
            'action_url' => $isWorker
                ? url('/worker/shifts/'.$shift->id)
                : url('/business/shifts/'.$shift->id),
        ];
    }
}
