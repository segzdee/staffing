<?php

namespace App\Notifications;

use App\Models\WorkerTier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * WKR-007: Worker Tier Downgrade Warning Notification
 *
 * Notifies workers when their tier has been downgraded.
 * Includes guidance on how to regain their previous tier.
 */
class WorkerTierDowngradeWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected WorkerTier $newTier;

    protected WorkerTier $previousTier;

    protected array $metrics;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        WorkerTier $newTier,
        WorkerTier $previousTier,
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
            ->subject("Your tier status has changed to {$this->newTier->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("We wanted to let you know that your tier status has been adjusted from **{$this->previousTier->name}** to **{$this->newTier->name}** based on our monthly review.");

        $mail->line('')
            ->line('This change reflects our tier requirements which are reviewed periodically to ensure fairness for all workers.');

        // Show current metrics
        if (! empty($this->metrics)) {
            $mail->line('')
                ->line('**Your current metrics:**')
                ->line("- Shifts completed: {$this->metrics['shifts_completed']}")
                ->line('- Average rating: '.number_format($this->metrics['rating'], 2))
                ->line("- Hours worked: {$this->metrics['hours_worked']}")
                ->line("- Months active: {$this->metrics['months_active']}");
        }

        // Show what's needed to get back to previous tier
        $mail->line('')
            ->line("**How to regain {$this->previousTier->name} tier:**")
            ->line("- Minimum shifts: {$this->previousTier->min_shifts_completed}")
            ->line('- Minimum rating: '.number_format($this->previousTier->min_rating, 2))
            ->line("- Minimum hours: {$this->previousTier->min_hours_worked}")
            ->line("- Minimum months active: {$this->previousTier->min_months_active}");

        // Show current tier benefits they still have
        $benefits = $this->newTier->getAllBenefits();
        if (! empty($benefits)) {
            $mail->line('')
                ->line("**Your current {$this->newTier->name} tier benefits:**");

            foreach ($benefits as $benefit) {
                $mail->line("- {$benefit}");
            }
        }

        $mail->line('')
            ->line("Don't worry - you can climb back up! Complete more shifts and maintain a high rating to regain your previous status.")
            ->action('Browse Available Shifts', url('/shifts'))
            ->line('We believe in you and look forward to seeing your progress!');

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
            'type' => 'worker_tier_downgrade',
            'tier_id' => $this->newTier->id,
            'tier_name' => $this->newTier->name,
            'tier_slug' => $this->newTier->slug,
            'tier_level' => $this->newTier->level,
            'tier_badge_color' => $this->newTier->badge_color,
            'previous_tier_id' => $this->previousTier->id,
            'previous_tier_name' => $this->previousTier->name,
            'previous_tier_level' => $this->previousTier->level,
            'metrics' => $this->metrics,
            'recovery_requirements' => [
                'shifts' => $this->previousTier->min_shifts_completed,
                'rating' => $this->previousTier->min_rating,
                'hours' => $this->previousTier->min_hours_worked,
                'months' => $this->previousTier->min_months_active,
            ],
            'message' => $this->getDatabaseMessage(),
            'icon' => 'arrow-down-circle',
            'color' => 'yellow',
        ];
    }

    /**
     * Get the database message.
     */
    protected function getDatabaseMessage(): string
    {
        return "Your tier has changed from {$this->previousTier->name} to {$this->newTier->name}. Complete more shifts to climb back up!";
    }
}
