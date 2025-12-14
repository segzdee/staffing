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

class ShiftCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $shift;
    public $worker;
    public $matchScore;

    /**
     * Create a new message instance.
     */
    public function __construct(Shift $shift, User $worker, $matchScore = null)
    {
        $this->shift = $shift;
        $this->worker = $worker;
        $this->matchScore = $matchScore;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Shift Available: {$this->shift->title}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.shifts.created',
            with: [
                'shift' => $this->shift,
                'worker' => $this->worker,
                'matchScore' => $this->matchScore,
                'url' => route('shifts.show', $this->shift->id),
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
