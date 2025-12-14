<?php

namespace App\Listeners;

use App\Events\PaymentReleased;
use App\Mail\PaymentReleasedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyPaymentReleased implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentReleased $event): void
    {
        $payment = $event->payment;
        $worker = $payment->worker;

        try {
            Mail::to($worker->email)->send(new PaymentReleasedMail($payment));
        } catch (\Exception $e) {
            \Log::error("Failed to send payment notification to worker {$worker->id}: " . $e->getMessage());
        }
    }
}
