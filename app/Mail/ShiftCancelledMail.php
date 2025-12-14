<?php

namespace App\Mail;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShiftCancelledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $shift;
    public $recipient;
    public $reason;

    public function __construct(Shift $shift, User $recipient, $reason = null)
    {
        $this->shift = $shift;
        $this->recipient = $recipient;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Shift Cancelled: {$this->shift->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.shifts.cancelled',
            with: [
                'shift' => $this->shift,
                'recipient' => $this->recipient,
                'reason' => $this->reason,
                'url' => route('shifts.index'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
