<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "💬 New Message from {$this->message->sender->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.messages.new',
            with: [
                'message' => $this->message,
                'sender' => $this->message->sender,
                'conversation' => $this->message->conversation,
                'url' => route('messages.show', $this->message->conversation_id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
