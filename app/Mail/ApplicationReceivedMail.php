<?php

namespace App\Mail;

use App\Models\ShiftApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationReceivedMail extends Mailable implements ShouldQueue
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
            subject: "New Application Received for {$this->application->shift->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.applications.received',
            with: [
                'application' => $this->application,
                'shift' => $this->application->shift,
                'worker' => $this->application->worker,
                'url' => route('business.shifts.applications', $this->application->shift->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
