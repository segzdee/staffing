<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "✅ Account Verification Approved!",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verification.approved',
            with: [
                'user' => $this->user,
                'url' => route('dashboard'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
