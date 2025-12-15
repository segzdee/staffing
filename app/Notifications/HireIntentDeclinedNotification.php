<?php

namespace App\Notifications;

use App\Models\WorkerConversion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HireIntentDeclinedNotification extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject('Hire Intent Declined - ' . $workerName)
            ->greeting("Hello {$notifiable->name},")
            ->line("{$workerName} has declined your direct hire offer.")
            ->line("Worker's notes: " . ($this->conversion->worker_response_notes ?? 'None provided'))
            ->line("The worker can continue working shifts for your business through the OvertimeStaff platform.")
            ->action('View Dashboard', url('/business/conversions'))
            ->line('Thank you for your understanding.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'hire_intent_declined',
            'title' => 'Hire Intent Declined',
            'message' => "{$this->conversion->worker->name} declined your direct hire offer.",
            'conversion_id' => $this->conversion->id,
            'worker_id' => $this->conversion->worker_id,
            'worker_name' => $this->conversion->worker->name,
            'action_url' => url('/business/conversions'),
            'priority' => 'medium',
        ];
    }
}
