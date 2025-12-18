<?php

namespace App\Notifications;

use App\Models\FraudSignal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FIN-015: Fraud Alert Notification
 *
 * Notification sent to admins when a high-severity fraud signal is detected.
 */
class FraudAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The fraud signal that triggered this notification.
     */
    protected FraudSignal $signal;

    /**
     * Create a new notification instance.
     */
    public function __construct(FraudSignal $signal)
    {
        $this->signal = $signal;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $user = $this->signal->user;

        return (new MailMessage)
            ->subject('[FRAUD ALERT] High-Severity Signal Detected - '.$this->signal->signal_code)
            ->error()
            ->greeting('Fraud Alert!')
            ->line('A high-severity fraud signal has been detected that requires your attention.')
            ->line('')
            ->line('**Signal Details:**')
            ->line('- **Type:** '.$this->signal->type_label)
            ->line('- **Code:** '.$this->signal->signal_code)
            ->line('- **Severity:** '.$this->signal->severity.'/10 ('.$this->signal->severity_label.')')
            ->line('- **Description:** '.$this->signal->code_description)
            ->line('')
            ->line('**User Details:**')
            ->line('- **Name:** '.($user->name ?? 'Unknown'))
            ->line('- **Email:** '.($user->email ?? 'Unknown'))
            ->line('- **User ID:** '.$this->signal->user_id)
            ->line('')
            ->line('**Detection Details:**')
            ->line('- **IP Address:** '.($this->signal->ip_address ?? 'Unknown'))
            ->line('- **Detected At:** '.$this->signal->created_at->format('Y-m-d H:i:s T'))
            ->action('Review Signal', url('/admin/fraud/signals?user_id='.$this->signal->user_id))
            ->line('')
            ->line('Please investigate this activity promptly.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'fraud_alert',
            'signal_id' => $this->signal->id,
            'signal_type' => $this->signal->signal_type,
            'signal_code' => $this->signal->signal_code,
            'severity' => $this->signal->severity,
            'user_id' => $this->signal->user_id,
            'user_name' => $this->signal->user->name ?? null,
            'user_email' => $this->signal->user->email ?? null,
            'ip_address' => $this->signal->ip_address,
            'created_at' => $this->signal->created_at->toIso8601String(),
            'message' => sprintf(
                'Fraud signal detected: %s (Severity: %d/10) for user %s',
                $this->signal->code_description,
                $this->signal->severity,
                $this->signal->user->email ?? 'Unknown'
            ),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'fraud_alert';
    }
}
