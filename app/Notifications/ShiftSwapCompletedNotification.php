<?php

namespace App\Notifications;

use App\Models\ShiftSwap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shift Swap Completed Notification
 * Sent to all parties when a shift swap is successfully processed
 */
class ShiftSwapCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ShiftSwap $shiftSwap;

    protected string $recipientRole;

    /**
     * Create a new notification instance.
     *
     * @param  string  $recipientRole  'offerer', 'accepter', or 'business'
     */
    public function __construct(ShiftSwap $shiftSwap, string $recipientRole = 'offerer')
    {
        $this->shiftSwap = $shiftSwap;
        $this->recipientRole = $recipientRole;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $shift = $this->shiftSwap->assignment?->shift;
        $shiftTitle = $shift?->title ?? 'the shift';
        $shiftDate = $shift?->shift_date ?? 'scheduled date';

        $message = (new MailMessage)
            ->subject('Shift Swap Completed Successfully');

        if ($this->recipientRole === 'offerer') {
            $accepterName = $this->shiftSwap->receivingWorker?->name ?? 'Another worker';
            $message->greeting('Shift Swap Complete!')
                ->line('Your shift swap request has been completed successfully.')
                ->line("{$accepterName} will now work {$shiftTitle} on {$shiftDate}.")
                ->line('You are no longer assigned to this shift.');
        } elseif ($this->recipientRole === 'accepter') {
            $message->greeting('You\'ve Been Assigned!')
                ->line('The shift swap has been completed. You are now assigned to:')
                ->line("**{$shiftTitle}** on {$shiftDate}")
                ->line('Please review the shift details and ensure you can attend.');
        } else {
            $offererName = $this->shiftSwap->offeringWorker?->name ?? 'A worker';
            $accepterName = $this->shiftSwap->receivingWorker?->name ?? 'another worker';
            $message->greeting('Shift Swap Completed')
                ->line("A shift swap has been completed for {$shiftTitle}.")
                ->line("{$offererName} has been replaced by {$accepterName}.");
        }

        return $message
            ->action('View Shift Details', url("/shifts/{$shift?->id}"))
            ->line('If you have any questions, please contact support.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $shift = $this->shiftSwap->assignment?->shift;

        return [
            'type' => 'shift_swap_completed',
            'title' => 'Shift Swap Completed',
            'message' => $this->getArrayMessage(),
            'swap_id' => $this->shiftSwap->id,
            'shift_id' => $shift?->id,
            'shift_title' => $shift?->title,
            'shift_date' => $shift?->shift_date,
            'offering_worker_id' => $this->shiftSwap->offering_worker_id,
            'receiving_worker_id' => $this->shiftSwap->receiving_worker_id,
            'recipient_role' => $this->recipientRole,
            'action_url' => url("/shifts/{$shift?->id}"),
        ];
    }

    /**
     * Get the message for the array representation.
     */
    protected function getArrayMessage(): string
    {
        $shift = $this->shiftSwap->assignment?->shift;
        $shiftTitle = $shift?->title ?? 'the shift';

        return match ($this->recipientRole) {
            'offerer' => "Your shift swap for {$shiftTitle} has been completed. You are no longer assigned.",
            'accepter' => "You are now assigned to {$shiftTitle} following a successful shift swap.",
            default => "A shift swap has been completed for {$shiftTitle}.",
        };
    }
}
