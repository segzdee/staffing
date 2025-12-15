<?php

namespace App\Notifications;

use App\Models\ShiftAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to workers when a no-show is recorded.
 * SL-010: Worker Cancellation Logic
 */
class NoShowRecordedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $assignment;

    /**
     * Create a new notification instance.
     *
     * @param ShiftAssignment $assignment
     */
    public function __construct(ShiftAssignment $assignment)
    {
        $this->assignment = $assignment;
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
        $shiftTitle = $this->assignment->shift->title;
        $shiftDate = $this->assignment->shift->start_time->format('F j, Y');
        $shiftTime = $this->assignment->shift->start_time->format('g:i A');

        return (new MailMessage)
            ->error()
            ->subject('No-Show Recorded - Reliability Score Impact')
            ->greeting("Hello {$notifiable->name},")
            ->line('A no-show has been recorded for the following shift:')
            ->line("Shift: {$shiftTitle}")
            ->line("Date: {$shiftDate} at {$shiftTime}")
            ->line("Business: {$this->assignment->shift->business->name}")
            ->line('IMPACT ON YOUR ACCOUNT:')
            ->line('• Your reliability score has been reduced by 40 points')
            ->line('• This may affect your eligibility for future shifts')
            ->line('• Multiple no-shows may result in account suspension')
            ->line('If you believe this no-show was recorded in error:')
            ->line('1. Contact the business directly to resolve the issue')
            ->line('2. Submit a dispute with supporting evidence')
            ->line('3. Contact our support team for assistance')
            ->action('Submit Dispute', url('/worker/shifts/' . $this->assignment->id . '/dispute'))
            ->line('Please remember to always cancel shifts through the platform if you cannot attend. This helps businesses find replacement workers and maintains your reliability score.');
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
            'type' => 'no_show_recorded',
            'title' => 'No-Show Recorded',
            'message' => "No-show recorded for shift: {$this->assignment->shift->title}",
            'assignment_id' => $this->assignment->id,
            'shift_id' => $this->assignment->shift_id,
            'shift_title' => $this->assignment->shift->title,
            'shift_date' => $this->assignment->shift->start_time->toDateString(),
            'reliability_impact' => -40,
            'action_url' => url('/worker/shifts/' . $this->assignment->id . '/dispute'),
            'priority' => 'critical',
        ];
    }
}
