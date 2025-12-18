<?php

namespace App\Notifications;

use App\Models\VolumeDiscountTier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FIN-001: Tier Progress Notification
 *
 * Notifies businesses when their volume discount tier changes
 * (either upgraded or downgraded) and provides information about
 * their progress toward the next tier.
 */
class TierProgressNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected VolumeDiscountTier $tier;

    protected string $changeType;

    protected array $progressInfo;

    /**
     * Create a new notification instance.
     *
     * @param  string  $changeType  'upgraded', 'downgraded', or 'progress'
     */
    public function __construct(
        VolumeDiscountTier $tier,
        string $changeType,
        array $progressInfo = []
    ) {
        $this->tier = $tier;
        $this->changeType = $changeType;
        $this->progressInfo = $progressInfo;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Send email for tier upgrades and downgrades
        if (in_array($this->changeType, ['upgraded', 'downgraded'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting("Hello {$notifiable->name},");

        if ($this->changeType === 'upgraded') {
            $mail->line($this->getUpgradeMessage());

            // Add tier benefits
            $mail->line('')
                ->line("**Your {$this->tier->name} Tier Benefits:**")
                ->line("- Platform Fee: {$this->tier->fee_display}")
                ->line("- Savings: {$this->tier->savings_description}");

            if ($this->tier->benefits && count($this->tier->benefits) > 0) {
                foreach ($this->tier->benefits as $benefit) {
                    $mail->line("- {$benefit}");
                }
            }

            $mail->line('')
                ->line('Thank you for your continued partnership!');
        } elseif ($this->changeType === 'downgraded') {
            $mail->line($this->getDowngradeMessage());

            $mail->line('')
                ->line("**Your Current Tier: {$this->tier->name}**")
                ->line("- Platform Fee: {$this->tier->fee_display}")
                ->line("- Shift Range: {$this->tier->shift_range}");

            // Show how to get back to the previous tier
            if (! empty($this->progressInfo['next_tier_name'])) {
                $mail->line('')
                    ->line('**How to Reach the Next Tier:**')
                    ->line("Post {$this->progressInfo['shifts_needed']} more shifts this month to qualify for {$this->progressInfo['next_tier_name']} and reduce your platform fee to {$this->progressInfo['next_tier_fee']}%.");
            }
        } else {
            // Progress notification
            $mail->line($this->getProgressMessage());

            if (! empty($this->progressInfo['next_tier_name'])) {
                $mail->line('')
                    ->line("**Progress to {$this->progressInfo['next_tier_name']} Tier:**")
                    ->line("- Current Shifts: {$this->progressInfo['current_shifts']}")
                    ->line("- Shifts Needed: {$this->progressInfo['shifts_needed']}")
                    ->line("- Progress: {$this->progressInfo['progress_percent']}%");

                if (! empty($this->progressInfo['potential_savings'])) {
                    $mail->line('- Potential Monthly Savings: $'.number_format($this->progressInfo['potential_savings'], 2));
                }
            }
        }

        $mail->action('View Your Tier Status', url('/business/analytics'))
            ->line('Questions? Contact our support team anytime.');

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
            'type' => 'tier_progress',
            'change_type' => $this->changeType,
            'tier_id' => $this->tier->id,
            'tier_name' => $this->tier->name,
            'tier_slug' => $this->tier->slug,
            'platform_fee_percent' => $this->tier->platform_fee_percent,
            'discount_percentage' => $this->tier->discount_percentage,
            'benefits' => $this->tier->benefits,
            'progress_info' => $this->progressInfo,
            'message' => $this->getDatabaseMessage(),
            'icon' => $this->getIcon(),
            'color' => $this->tier->badge_color ?? $this->getDefaultColor(),
        ];
    }

    /**
     * Get the subject line based on change type.
     */
    protected function getSubject(): string
    {
        return match ($this->changeType) {
            'upgraded' => "Congratulations! You've been upgraded to {$this->tier->name} tier",
            'downgraded' => "Your volume tier has changed to {$this->tier->name}",
            default => "Update on your {$this->tier->name} tier progress",
        };
    }

    /**
     * Get the upgrade message.
     */
    protected function getUpgradeMessage(): string
    {
        $savings = $this->tier->discount_percentage;

        return "Great news! Based on your posting activity, you've been upgraded to our **{$this->tier->name}** tier. ".
            "This means you'll now enjoy a **{$savings}% discount** on platform fees!";
    }

    /**
     * Get the downgrade message.
     */
    protected function getDowngradeMessage(): string
    {
        return "Your monthly shift volume has changed, which means your tier has been adjusted to **{$this->tier->name}**. ".
            "Don't worry - you can easily earn back a higher tier by posting more shifts!";
    }

    /**
     * Get the progress message.
     */
    protected function getProgressMessage(): string
    {
        if (empty($this->progressInfo['shifts_needed'])) {
            return "You're at our highest tier - **{$this->tier->name}**! Keep up the great work.";
        }

        $shiftsNeeded = $this->progressInfo['shifts_needed'];
        $nextTier = $this->progressInfo['next_tier_name'] ?? 'the next tier';

        return "You're making great progress! Post just **{$shiftsNeeded} more shifts** this month to reach **{$nextTier}** and unlock even lower fees.";
    }

    /**
     * Get the database message for quick display.
     */
    protected function getDatabaseMessage(): string
    {
        return match ($this->changeType) {
            'upgraded' => "You've been upgraded to {$this->tier->name} tier! Enjoy {$this->tier->discount_percentage}% off platform fees.",
            'downgraded' => "Your tier has changed to {$this->tier->name}. Post more shifts to upgrade!",
            default => "You're {$this->progressInfo['progress_percent']}% toward {$this->progressInfo['next_tier_name']} tier.",
        };
    }

    /**
     * Get the icon based on change type.
     */
    protected function getIcon(): string
    {
        return match ($this->changeType) {
            'upgraded' => 'arrow-up-circle',
            'downgraded' => 'arrow-down-circle',
            default => 'chart-line',
        };
    }

    /**
     * Get the default color based on change type.
     */
    protected function getDefaultColor(): string
    {
        return match ($this->changeType) {
            'upgraded' => 'green',
            'downgraded' => 'yellow',
            default => 'blue',
        };
    }
}
