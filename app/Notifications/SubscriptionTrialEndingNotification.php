<?php

namespace App\Notifications;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Subscription Trial Ending Notification
 * Sent when a subscription trial is about to end
 */
class SubscriptionTrialEndingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Subscription $subscription;

    protected ?int $trialEndTimestamp;

    /**
     * Create a new notification instance.
     */
    public function __construct(Subscription $subscription, ?int $trialEndTimestamp = null)
    {
        $this->subscription = $subscription;
        $this->trialEndTimestamp = $trialEndTimestamp;
    }

    /**
     * Get the notification's delivery channels.
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
        $planName = $this->subscription->plan?->name ?? 'your plan';
        $trialEnd = $this->trialEndTimestamp
            ? Carbon::createFromTimestamp($this->trialEndTimestamp)->format('F j, Y')
            : ($this->subscription->trial_ends_at?->format('F j, Y') ?? 'soon');

        return (new MailMessage)
            ->subject('Your Trial Ends Soon')
            ->greeting('Trial Period Ending')
            ->line("Your free trial for {$planName} will end on {$trialEnd}.")
            ->line('**What happens next:**')
            ->line('- Your payment method will be charged automatically')
            ->line('- You will continue to have full access to all features')
            ->line('- You can cancel anytime before the trial ends')
            ->line('**Not ready to continue?**')
            ->line('You can cancel your subscription before the trial ends to avoid being charged.')
            ->action('Manage Subscription', url('/settings/subscription'))
            ->line('Thank you for trying our platform!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $trialEnd = $this->trialEndTimestamp
            ? Carbon::createFromTimestamp($this->trialEndTimestamp)->toIso8601String()
            : $this->subscription->trial_ends_at?->toIso8601String();

        return [
            'type' => 'subscription_trial_ending',
            'title' => 'Trial Ending Soon',
            'message' => 'Your free trial is ending soon. Your payment method will be charged automatically.',
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan?->name,
            'trial_ends_at' => $trialEnd,
            'action_url' => url('/settings/subscription'),
            'action_text' => 'Manage Subscription',
        ];
    }
}
