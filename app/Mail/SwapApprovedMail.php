<?php

namespace App\Mail;

use App\Models\ShiftSwap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SwapApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $swap;

    public function __construct(ShiftSwap $swap)
    {
        $this->swap = $swap;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "✅ Shift Swap Approved!",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.swaps.approved',
            with: [
                'swap' => $this->swap,
                'shift' => $this->swap->shift,
                'offeringWorker' => $this->swap->offeringWorker,
                'receivingWorker' => $this->swap->receivingWorker,
                'url' => route('worker.swaps.show', $this->swap->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
