<?php

namespace App\Mail;

use App\Models\ShiftAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShiftCompletedMail extends Mailable implements ShouldQueue
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
            subject: "✅ Shift Completed - Payment Processing",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.shifts.completed',
            with: [
                'assignment' => $this->assignment,
                'shift' => $this->assignment->shift,
                'payment' => $this->assignment->payment,
                'url' => route('worker.earnings'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
