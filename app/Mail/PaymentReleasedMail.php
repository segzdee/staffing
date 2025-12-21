<?php

namespace App\Mail;

use App\Models\ShiftPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReleasedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $payment;

    public function __construct(ShiftPayment $payment)
    {
        $this->payment = $payment;
    }

    public function envelope(): Envelope
    {
        // Handle both Money objects and numeric values
        $amount = $this->payment->amount_net;
        $amountValue = $amount instanceof \Money\Money ? $amount->getAmount() / 100 : (float) $amount;

        return new Envelope(
            subject: 'Payment Released - $'.number_format($amountValue, 2),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payments.released',
            with: [
                'payment' => $this->payment,
                'shift' => $this->payment->assignment->shift,
                'url' => route('worker.earnings'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
