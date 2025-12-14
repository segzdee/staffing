<?php

namespace App\Mail;

use App\Models\ShiftApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationRejectedMail extends Mailable implements ShouldQueue
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
            subject: "Application Update: {$this->application->shift->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.applications.rejected',
            with: [
                'application' => $this->application,
                'shift' => $this->application->shift,
                'url' => route('shifts.show', $this->application->shift->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
