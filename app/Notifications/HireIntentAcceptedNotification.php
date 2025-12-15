<?php

namespace App\Notifications;

use App\Models\WorkerConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HireIntentAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $conversion;

    public function __construct(WorkerConversion $conversion)
    {
        $this->conversion = $conversion;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $workerName = $this->conversion->worker->name ?? 'The worker';
        $fee = $this->conversion->conversion_fee_dollars;

        return (new MailMessage)
            ->subject('Hire Intent Accepted - ' . $workerName)
            ->greeting("Hello {$notifiable->name},")
            ->line("Great news! {$workerName} has accepted your direct hire offer.")
            ->line("To complete the conversion, please proceed with payment:")
            ->line("Conversion Fee: €{$fee}")
            ->line("Once payment is processed, the 6-month non-solicitation period will begin and you can transition the worker to direct employment.")
            ->action('Complete Payment', url('/business/conversions/' . $this->conversion->id . '/payment'))
            ->line('Thank you for using OvertimeStaff!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'hire_intent_accepted',
            'title' => 'Hire Intent Accepted',
            'message' => "{$this->conversion->worker->name} accepted your direct hire offer.",
            'conversion_id' => $this->conversion->id,
            'worker_id' => $this->conversion->worker_id,
            'worker_name' => $this->conversion->worker->name,
            'action_url' => url('/business/conversions/' . $this->conversion->id . '/payment'),
            'priority' => 'high',
        ];
    }
}
