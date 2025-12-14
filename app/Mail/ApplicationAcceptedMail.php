<?php

namespace App\Mail;

use App\Models\ShiftApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationAcceptedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $application;

    public function __construct(ShiftApplication $application)
    {
        $this->application = $application;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🎉 Your Application Was Accepted!",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.applications.accepted',
            with: [
                'application' => $this->application,
                'shift' => $this->application->shift,
                'url' => route('worker.assignments.show', $this->application->shift->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
