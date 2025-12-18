<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * COM-003: Templated Email Mailable
 *
 * A generic mailable that uses pre-rendered HTML/text content from email templates.
 */
class TemplatedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $emailSubject;

    public string $bodyHtml;

    public string $bodyText;

    public ?int $logId;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $subject,
        string $bodyHtml,
        string $bodyText,
        ?int $logId = null
    ) {
        $this->emailSubject = $subject;
        $this->bodyHtml = $bodyHtml;
        $this->bodyText = $bodyText;
        $this->logId = $logId;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('email_templates.from_email', config('mail.from.address')),
            replyTo: config('email_templates.reply_to'),
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.templated',
            text: 'emails.templated-text',
            with: [
                'bodyHtml' => $this->bodyHtml,
                'bodyText' => $this->bodyText,
                'logId' => $this->logId,
                'trackOpens' => config('email_templates.track_opens', true),
                'trackClicks' => config('email_templates.track_clicks', true),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
