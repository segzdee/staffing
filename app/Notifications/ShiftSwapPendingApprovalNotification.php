<?php

namespace App\Notifications;

use App\Models\ShiftSwap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shift Swap Pending Approval Notification
 * Sent to business when a shift swap needs their approval
 */
class ShiftSwapPendingApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ShiftSwap $shiftSwap;

    /**
     * Create a new notification instance.
     */
    public function __construct(ShiftSwap $shiftSwap)
    {
        $this->shiftSwap = $shiftSwap;
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
        $shiftTitle = $shift?->title ?? 'a shift';
        $shiftDate = $shift?->shift_date ?? 'scheduled date';
        $offererName = $this->shiftSwap->offeringWorker?->name ?? 'A worker';
        $accepterName = $this->shiftSwap->receivingWorker?->name ?? 'another worker';

        return (new MailMessage)
            ->subject('Shift Swap Request - Approval Required')
            ->greeting('Shift Swap Request')
            ->line('A shift swap request needs your approval.')
            ->line("**Shift:** {$shiftTitle} on {$shiftDate}")
            ->line("**Current worker:** {$offererName}")
            ->line("**Proposed replacement:** {$accepterName}")
            ->line('Please review and approve or reject this swap request.')
            ->action('Review Swap Request', url("/business/shift-swaps/{$this->shiftSwap->id}"))
            ->line('The swap will only be processed after your approval.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $shift = $this->shiftSwap->assignment?->shift;

        return [
            'type' => 'shift_swap_pending_approval',
            'title' => 'Shift Swap Needs Approval',
            'message' => "A shift swap request for {$shift?->title} needs your approval.",
            'swap_id' => $this->shiftSwap->id,
            'shift_id' => $shift?->id,
            'shift_title' => $shift?->title,
            'shift_date' => $shift?->shift_date,
            'offering_worker_id' => $this->shiftSwap->offering_worker_id,
            'offering_worker_name' => $this->shiftSwap->offeringWorker?->name,
            'receiving_worker_id' => $this->shiftSwap->receiving_worker_id,
            'receiving_worker_name' => $this->shiftSwap->receivingWorker?->name,
            'action_url' => url("/business/shift-swaps/{$this->shiftSwap->id}"),
            'action_text' => 'Review Request',
        ];
    }
}
