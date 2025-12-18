<?php

namespace App\Notifications;

use App\Models\BookingConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SL-004: Notification sent when a booking is declined.
 *
 * Sent to the other party when either the worker or business
 * declines the booking confirmation.
 */
class BookingDeclinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The booking confirmation instance.
     */
    protected BookingConfirmation $confirmation;

    /**
     * Who declined the booking (worker or business).
     */
    protected string $declinedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(BookingConfirmation $confirmation, string $declinedBy)
    {
        $this->confirmation = $confirmation;
        $this->declinedBy = $declinedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return config('booking_confirmation.notification_channels.declined', ['database', 'mail']);
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
        $isToWorker = $notifiable->id === $this->confirmation->worker_id;

        $subject = "Booking Declined: {$shift->title}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Booking Declined');

        if ($isToWorker) {
            // Notifying worker that business declined
            $message->line('Unfortunately, the business has declined your shift booking.');
        } else {
            // Notifying business that worker declined
            $message->line('The worker has declined the shift booking.');
        }

        $message->line('')
            ->line('**Shift Details:**')
            ->line("- Title: {$shift->title}")
            ->line("- Date: {$shift->shift_date->format('l, M j, Y')}")
            ->line("- Time: {$shift->start_time->format('g:i A')} - {$shift->end_time->format('g:i A')}")
            ->line("- Location: {$shift->location_city}, {$shift->location_state}");

        // Include reason if provided
        if ($this->confirmation->decline_reason) {
            $message->line('')
                ->line('**Reason provided:**')
                ->line($this->confirmation->decline_reason);
        }

        if ($isToWorker) {
            // Encourage worker to find other shifts
            $message->line('')
                ->line('Don\'t worry! There are plenty of other shifts available.')
                ->action('Browse Available Shifts', url('/worker/shifts/available'));
        } else {
            // Encourage business to find replacement
            $message->line('')
                ->line('We will process the next available worker or you can manually select a replacement.')
                ->action('View Shift Applications', url("/business/shifts/{$shift->id}/applications"));
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
        $isToWorker = $notifiable->id === $this->confirmation->worker_id;

        return [
            'type' => 'booking_declined',
            'confirmation_id' => $this->confirmation->id,
            'shift_id' => $this->confirmation->shift_id,
            'shift_title' => $shift->title,
            'shift_date' => $shift->shift_date->toDateString(),
            'declined_by' => $this->declinedBy,
            'declined_at' => $this->confirmation->declined_at?->toDateTimeString(),
            'decline_reason' => $this->confirmation->decline_reason,
            'worker_id' => $this->confirmation->worker_id,
            'worker_name' => $isToWorker ? null : $this->confirmation->worker->name,
            'business_id' => $this->confirmation->business_id,
            'business_name' => $isToWorker ? $this->confirmation->business->name : null,
            'action_url' => $isToWorker
                ? url('/worker/shifts/available')
                : url("/business/shifts/{$shift->id}/applications"),
            'message' => $isToWorker
                ? 'The business has declined your booking for this shift.'
                : 'The worker has declined this shift booking.',
        ];
    }
}
