<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $reason;

    public function __construct(User $user, $reason = null)
    {
        $this->user = $user;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Account Verification Update",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verification.rejected',
            with: [
                'user' => $this->user,
                'reason' => $this->reason,
                'url' => route('verification.apply'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
