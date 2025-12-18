<?php

namespace App\Notifications;

use App\Models\WorkerTier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * WKR-007: Worker Tier Upgrade Notification
 *
 * Notifies workers when they have been upgraded to a higher tier.
 */
class WorkerTierUpgradeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected WorkerTier $newTier;

    protected ?WorkerTier $previousTier;

    protected array $metrics;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        WorkerTier $newTier,
        ?WorkerTier $previousTier = null,
        array $metrics = []
    ) {
        $this->newTier = $newTier;
        $this->previousTier = $previousTier;
        $this->metrics = $metrics;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Congratulations! You've been upgraded to {$this->newTier->name} tier!")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Great news! Your hard work has paid off. You've been upgraded to the **{$this->newTier->name}** tier!");

        if ($this->previousTier) {
            $mail->line("You've moved up from {$this->previousTier->name} to {$this->newTier->name}.");
        }

        // Add achievement metrics
        if (! empty($this->metrics)) {
            $mail->line('')
                ->line('**Your achievements:**')
                ->line("- Shifts completed: {$this->metrics['shifts_completed']}")
                ->line('- Average rating: '.number_format($this->metrics['rating'], 2))
                ->line("- Hours worked: {$this->metrics['hours_worked']}")
                ->line("- Months active: {$this->metrics['months_active']}");
        }

        // Add benefits
        $benefits = $this->newTier->getAllBenefits();
        if (! empty($benefits)) {
            $mail->line('')
                ->line("**Your {$this->newTier->name} tier benefits:**");

            foreach ($benefits as $benefit) {
                $mail->line("- {$benefit}");
            }
        }

        $mail->line('')
            ->line('Keep up the great work and continue climbing the career ladder!')
            ->action('View Your Profile', url('/worker/profile'))
            ->line('Thank you for being a valued member of our community!');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'worker_tier_upgrade',
            'tier_id' => $this->newTier->id,
            'tier_name' => $this->newTier->name,
            'tier_slug' => $this->newTier->slug,
            'tier_level' => $this->newTier->level,
            'tier_badge_color' => $this->newTier->badge_color,
            'previous_tier_id' => $this->previousTier?->id,
            'previous_tier_name' => $this->previousTier?->name,
            'metrics' => $this->metrics,
            'benefits' => $this->newTier->getAllBenefits(),
            'message' => $this->getDatabaseMessage(),
            'icon' => 'arrow-up-circle',
            'color' => 'green',
        ];
    }

    /**
     * Get the database message.
     */
    protected function getDatabaseMessage(): string
    {
        if ($this->previousTier) {
            return "Congratulations! You've been upgraded from {$this->previousTier->name} to {$this->newTier->name} tier!";
        }

        return "Welcome to the {$this->newTier->name} tier!";
    }
}
