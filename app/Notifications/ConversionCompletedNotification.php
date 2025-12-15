<?php

namespace App\Notifications;

use App\Models\WorkerConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConversionCompletedNotification extends Notification implements ShouldQueue
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
        $isWorker = $notifiable->id === $this->conversion->worker_id;
        $otherParty = $isWorker
            ? $this->conversion->business->name
            : $this->conversion->worker->name;

        $message = (new MailMessage)
            ->subject('Direct Hire Conversion Completed')
            ->greeting("Hello {$notifiable->name},")
            ->line("The direct hire conversion has been completed successfully!");

        if ($isWorker) {
            $message->line("You have been hired directly by {$otherParty}.")
                ->line("Congratulations on your new position!")
                ->line("Note: You are now under a 6-month non-solicitation agreement, expiring on {$this->conversion->non_solicitation_expires_at->format('F j, Y')}.");
        } else {
            $message->line("You have successfully hired {$otherParty} directly.")
                ->line("Payment of €{$this->conversion->conversion_fee_dollars} has been processed.")
                ->line("Non-solicitation period: 6 months (expires {$this->conversion->non_solicitation_expires_at->format('F j, Y')})");
        }

        $message->action('View Details', url(
            $isWorker
                ? '/worker/conversions/' . $this->conversion->id
                : '/business/conversions/' . $this->conversion->id
        ))
            ->line('Thank you for using OvertimeStaff!');

        return $message;
    }

    public function toArray($notifiable)
    {
        $isWorker = $notifiable->id === $this->conversion->worker_id;

        return [
            'type' => 'conversion_completed',
            'title' => 'Direct Hire Completed',
            'message' => 'Direct hire conversion has been completed successfully.',
            'conversion_id' => $this->conversion->id,
            'action_url' => url(
                $isWorker
                    ? '/worker/conversions/' . $this->conversion->id
                    : '/business/conversions/' . $this->conversion->id
            ),
            'priority' => 'high',
        ];
    }
}
