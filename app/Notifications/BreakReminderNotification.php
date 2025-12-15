<?php

namespace App\Notifications;

use App\Models\ShiftAssignment;
use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SL-006: Break Reminder Notification
 *
 * Sent to workers who have been working for 6+ hours and need to take a break
 * to comply with labor regulations.
 */
class BreakReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $assignment;
    protected $shift;
    protected $compliance;

    /**
     * Create a new notification instance.
     *
     * @param ShiftAssignment $assignment
     * @param Shift $shift
     * @param array $compliance Compliance data from checkBreakCompliance()
     */
    public function __construct(ShiftAssignment $assignment, Shift $shift, array $compliance)
    {
        $this->assignment = $assignment;
        $this->shift = $shift;
        $this->compliance = $compliance;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Send via database (in-app) and mail
        // Could also add SMS channel for urgent reminders
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
        $businessName = $this->shift->business->businessProfile->business_name
            ?? $this->shift->business->name
            ?? 'Your employer';

        $hoursWorked = $this->compliance['hours_worked'];
        $requiredMinutes = $this->compliance['required_minutes'];
        $breakTaken = $this->compliance['break_taken'];
        $breakMinutes = $this->compliance['break_minutes'] ?? 0;

        $subject = $breakTaken
            ? 'Additional Break Required'
            : 'Break Reminder - Action Required';

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been working for {$hoursWorked} hours on your shift at {$businessName}.");

        if ($breakTaken && $breakMinutes < $requiredMinutes) {
            // Worker took a break but it wasn't long enough
            $additionalMinutes = $requiredMinutes - $breakMinutes;
            $message->line("You have taken a {$breakMinutes}-minute break, but the minimum required break is {$requiredMinutes} minutes.")
                ->line("Please take an additional {$additionalMinutes}-minute break to comply with labor regulations.");
        } else {
            // Worker hasn't taken any break
            $message->line("According to labor regulations, you are required to take a {$requiredMinutes}-minute break.")
                ->line("Please clock out for your break as soon as possible.");
        }

        $message->line('**Why is this important?**')
            ->line('- Breaks are legally mandated to ensure worker safety and wellbeing')
            ->line('- Proper breaks help prevent fatigue and maintain productivity')
            ->line('- Non-compliance may result in penalties for both you and your employer');

        // Add urgency indicator
        if ($hoursWorked >= 7) {
            $message->line('')
                ->line('**URGENT:** You have been working for ' . $hoursWorked . ' hours. Please take your break immediately.');
        }

        return $message->line('Thank you for your cooperation.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $businessName = $this->shift->business->businessProfile->business_name
            ?? $this->shift->business->name
            ?? 'Employer';

        return [
            'type' => 'break_reminder',
            'assignment_id' => $this->assignment->id,
            'shift_id' => $this->shift->id,
            'shift_title' => $this->shift->title,
            'business_name' => $businessName,
            'hours_worked' => $this->compliance['hours_worked'],
            'required_minutes' => $this->compliance['required_minutes'],
            'break_taken' => $this->compliance['break_taken'],
            'break_minutes' => $this->compliance['break_minutes'] ?? 0,
            'urgency' => $this->getUrgencyLevel(),
            'message' => $this->getShortMessage(),
        ];
    }

    /**
     * Get urgency level based on hours worked.
     *
     * @return string
     */
    protected function getUrgencyLevel(): string
    {
        $hoursWorked = $this->compliance['hours_worked'];

        if ($hoursWorked >= 8) {
            return 'critical';
        } elseif ($hoursWorked >= 7) {
            return 'high';
        } elseif ($hoursWorked >= 6.5) {
            return 'medium';
        }

        return 'normal';
    }

    /**
     * Get short message for in-app notification.
     *
     * @return string
     */
    protected function getShortMessage(): string
    {
        $hoursWorked = $this->compliance['hours_worked'];
        $requiredMinutes = $this->compliance['required_minutes'];
        $breakTaken = $this->compliance['break_taken'];

        if ($breakTaken) {
            $breakMinutes = $this->compliance['break_minutes'] ?? 0;
            $additionalMinutes = $requiredMinutes - $breakMinutes;
            return "You need to take an additional {$additionalMinutes}-minute break. You've worked {$hoursWorked} hours.";
        }

        return "You've worked {$hoursWorked} hours. Please take your {$requiredMinutes}-minute break now.";
    }

    /**
     * Get notification icon based on urgency.
     *
     * @return string
     */
    public function getIcon(): string
    {
        return match($this->getUrgencyLevel()) {
            'critical' => 'exclamation-circle',
            'high' => 'exclamation-triangle',
            'medium' => 'clock',
            default => 'information-circle',
        };
    }

    /**
     * Get notification color based on urgency.
     *
     * @return string
     */
    public function getColor(): string
    {
        return match($this->getUrgencyLevel()) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            default => 'blue',
        };
    }
}
