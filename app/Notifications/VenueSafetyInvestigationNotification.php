<?php

namespace App\Notifications;

use App\Models\Venue;
use App\Services\VenueSafetyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SAF-004: Venue Safety Investigation Notification
 *
 * Notification sent to admins when a venue requires safety investigation
 * due to low safety scores, multiple flags, or critical severity reports.
 */
class VenueSafetyInvestigationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The venue that requires investigation.
     */
    protected Venue $venue;

    /**
     * Create a new notification instance.
     */
    public function __construct(Venue $venue)
    {
        $this->venue = $venue;
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
        $this->venue->load(['businessProfile', 'safetyFlags' => function ($query) {
            $query->open()->orderBy('severity', 'desc');
        }]);

        $businessName = $this->venue->businessProfile?->company_name ?? 'Unknown Business';
        $openFlags = $this->venue->safetyFlags->count();
        $criticalFlags = $this->venue->safetyFlags->where('severity', 'critical')->count();

        $message = (new MailMessage)
            ->subject("[Action Required] Safety Investigation Needed - {$this->venue->name}")
            ->error()
            ->greeting('Safety Investigation Required')
            ->line('A venue requires immediate safety investigation.')
            ->line('')
            ->line('**Venue Information:**')
            ->line("- **Name:** {$this->venue->name}")
            ->line("- **Business:** {$businessName}")
            ->line("- **Address:** {$this->venue->full_address}")
            ->line("- **Current Status:** {$this->venue->safety_status_label}")
            ->line('')
            ->line('**Safety Metrics:**')
            ->line('- **Safety Score:** '.($this->venue->safety_score ? number_format($this->venue->safety_score, 2).'/5' : 'No ratings'))
            ->line("- **Active Flags:** {$openFlags}")
            ->line("- **Critical Flags:** {$criticalFlags}")
            ->line("- **Total Ratings:** {$this->venue->safety_ratings_count}");

        // Explain why investigation was triggered
        $triggers = $this->getInvestigationTriggers();
        if (! empty($triggers)) {
            $message->line('')
                ->line('**Investigation Triggered Because:**');
            foreach ($triggers as $trigger) {
                $message->line("- {$trigger}");
            }
        }

        // List critical flags if any
        $criticalFlagsList = $this->venue->safetyFlags->where('severity', 'critical');
        if ($criticalFlagsList->isNotEmpty()) {
            $message->line('')
                ->line('**Critical Safety Flags:**');
            foreach ($criticalFlagsList->take(3) as $flag) {
                $message->line("- {$flag->flag_type_label}: ".substr($flag->description, 0, 100).'...');
            }
        }

        $message->action('View Venue Safety Details', url("/admin/safety/venues/{$this->venue->id}"))
            ->line('')
            ->line('Please review this venue and take appropriate action.')
            ->line('Consider restricting the venue if safety cannot be verified.');

        return $message;
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
            'type' => 'venue_safety_investigation',
            'venue_id' => $this->venue->id,
            'venue_name' => $this->venue->name,
            'business_name' => $this->venue->businessProfile?->company_name,
            'safety_score' => $this->venue->safety_score,
            'safety_status' => $this->venue->safety_status,
            'active_flags' => $this->venue->active_safety_flags,
            'triggers' => $this->getInvestigationTriggers(),
            'created_at' => now()->toIso8601String(),
            'message' => sprintf(
                'Safety investigation required for %s (Score: %s, Flags: %d)',
                $this->venue->name,
                $this->venue->safety_score ? number_format($this->venue->safety_score, 1) : 'N/A',
                $this->venue->active_safety_flags
            ),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'venue_safety_investigation';
    }

    /**
     * Determine why the investigation was triggered.
     */
    protected function getInvestigationTriggers(): array
    {
        $triggers = [];

        // Check safety score
        if ($this->venue->safety_score !== null && $this->venue->safety_score < VenueSafetyService::SAFETY_SCORE_THRESHOLD) {
            $triggers[] = sprintf(
                'Safety score (%.2f) is below threshold (%.2f)',
                $this->venue->safety_score,
                VenueSafetyService::SAFETY_SCORE_THRESHOLD
            );
        }

        // Check for critical flags
        $criticalCount = $this->venue->safetyFlags()
            ->open()
            ->critical()
            ->count();
        if ($criticalCount > 0) {
            $triggers[] = "{$criticalCount} critical severity flag(s) reported";
        }

        // Check for multiple recent flags
        $recentFlags = $this->venue->safetyFlags()
            ->open()
            ->where('created_at', '>=', now()->subDays(VenueSafetyService::FLAGS_THRESHOLD_DAYS))
            ->count();
        if ($recentFlags >= VenueSafetyService::FLAGS_THRESHOLD_COUNT) {
            $triggers[] = sprintf(
                '%d flags reported in the last %d days (threshold: %d)',
                $recentFlags,
                VenueSafetyService::FLAGS_THRESHOLD_DAYS,
                VenueSafetyService::FLAGS_THRESHOLD_COUNT
            );
        }

        return $triggers;
    }
}
