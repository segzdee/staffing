<?php

namespace App\Mail;

use App\Models\ShiftAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShiftAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $assignment;

    public function __construct(ShiftAssignment $assignment)
    {
        $this->assignment = $assignment;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "✅ You've Been Assigned to a Shift!",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.shifts.assigned',
            with: [
                'assignment' => $this->assignment,
                'shift' => $this->assignment->shift,
                'worker' => $this->assignment->worker,
                'url' => route('worker.assignments.show', $this->assignment->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
