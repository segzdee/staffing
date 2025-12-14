<?php

namespace App\Mail;

use App\Models\ShiftAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShiftReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $assignment;
    public $reminderType; // '24hr' or '2hr'

    public function __construct(ShiftAssignment $assignment, $reminderType = '24hr')
    {
        $this->assignment = $assignment;
        $this->reminderType = $reminderType;
    }

    public function envelope(): Envelope
    {
        $time = $this->reminderType === '24hr' ? '24 hours' : '2 hours';
        return new Envelope(
            subject: "⏰ Reminder: Your shift starts in {$time}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.shifts.reminder',
            with: [
                'assignment' => $this->assignment,
                'shift' => $this->assignment->shift,
                'reminderType' => $this->reminderType,
                'url' => route('worker.assignments.show', $this->assignment->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
