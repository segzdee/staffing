<?php

namespace App\Notifications;

use App\Models\ShiftAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to businesses when a worker cancels a shift.
 * SL-010: Worker Cancellation Logic
 */
class WorkerCancelledShiftNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $assignment;
    protected $hoursNotice;
    protected $isExcused;

    /**
     * Create a new notification instance.
     *
     * @param ShiftAssignment $assignment
     * @param float $hoursNotice
     * @param bool $isExcused
     */
    public function __construct(ShiftAssignment $assignment, float $hoursNotice, bool $isExcused = false)
    {
        $this->assignment = $assignment;
        $this->hoursNotice = $hoursNotice;
        $this->isExcused = $isExcused;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $workerName = $this->assignment->worker->name;
        $shiftTitle = $this->assignment->shift->title;
        $shiftDate = $this->assignment->shift->start_time->format('F j, Y');
        $shiftTime = $this->assignment->shift->start_time->format('g:i A');

        $message = (new MailMessage)
            ->subject("Worker Cancelled: {$shiftTitle} on {$shiftDate}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Worker {$workerName} has cancelled their assignment for your shift:");

        if ($this->hoursNotice < 24) {
            $message->error();
        }

        $message->line("Shift: {$shiftTitle}")
            ->line("Date: {$shiftDate} at {$shiftTime}")
            ->line("Notice given: " . $this->formatHoursNotice($this->hoursNotice));

        if ($this->isExcused) {
            $message->line("Cancellation Type: Excused (pending review)")
                ->line("Reason: " . ($this->assignment->cancellation_reason ?? 'Not provided'));
        } else {
            $message->line("Cancellation Type: Standard cancellation");
        }

        $message->line("This shift has been re-opened for applications. Workers matching your requirements will be notified.")
            ->action('View Shift & Find Replacement', url('/business/shifts/' . $this->assignment->shift_id));

        if ($this->hoursNotice < 12) {
            $message->line("URGENT: This is a last-minute cancellation. We recommend enabling instant booking to fill this position quickly.");
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
            'type' => 'worker_cancelled_shift',
            'title' => 'Worker Cancelled Shift',
            'message' => "{$this->assignment->worker->name} cancelled shift: {$this->assignment->shift->title}",
            'assignment_id' => $this->assignment->id,
            'shift_id' => $this->assignment->shift_id,
            'worker_id' => $this->assignment->worker_id,
            'worker_name' => $this->assignment->worker->name,
            'hours_notice' => round($this->hoursNotice, 1),
            'is_excused' => $this->isExcused,
            'shift_start' => $this->assignment->shift->start_time->toDateTimeString(),
            'action_url' => url('/business/shifts/' . $this->assignment->shift_id),
            'priority' => $this->hoursNotice < 24 ? 'high' : 'medium',
        ];
    }

    /**
     * Format hours notice into readable string.
     *
     * @param float $hours
     * @return string
     */
    protected function formatHoursNotice($hours)
    {
        if ($hours < 0) {
            return 'No-show';
        } elseif ($hours < 1) {
            return 'Less than 1 hour';
        } elseif ($hours < 24) {
            return round($hours, 1) . ' hours';
        } else {
            $days = floor($hours / 24);
            return $days . ' day' . ($days > 1 ? 's' : '');
        }
    }
}
