<?php

namespace App\Services;

use App\Models\User;
use App\Models\OnboardingStep;
use App\Models\OnboardingProgress;
use App\Models\OnboardingReminder;
use App\Models\OnboardingEvent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * OnboardingReminderService
 *
 * Handles scheduling and sending of onboarding reminders to users.
 * Manages email, push, and in-app notifications for onboarding progress.
 */
class OnboardingReminderService
{
    protected OnboardingProgressService $progressService;

    // Reminder timing configuration (in hours after signup)
    const TIMING_WELCOME = 0;           // Immediate
    const TIMING_FIRST_STEP = 24;       // 24 hours after signup
    const TIMING_INACTIVITY_1 = 48;     // 48 hours after last activity
    const TIMING_INACTIVITY_2 = 168;    // 7 days after signup
    const TIMING_SUPPORT_OFFER = 72;    // 3 days if stuck on a step

    // Maximum reminders per user
    const MAX_REMINDERS_PER_USER = 10;
    const MAX_REMINDERS_PER_STEP = 3;

    public function __construct(OnboardingProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Schedule all initial reminders for a new user
     */
    public function scheduleReminders(User $user): array
    {
        $scheduledReminders = [];

        // Schedule welcome reminder (immediate or slight delay)
        $scheduledReminders[] = $this->scheduleWelcomeReminder($user);

        // Schedule first step reminder (24 hours if not started)
        $scheduledReminders[] = $this->scheduleFirstStepReminder($user);

        // Schedule inactivity reminders
        $scheduledReminders[] = $this->scheduleInactivityReminder($user, self::TIMING_INACTIVITY_1);
        $scheduledReminders[] = $this->scheduleInactivityReminder($user, self::TIMING_INACTIVITY_2);

        return array_filter($scheduledReminders);
    }

    /**
     * Schedule welcome reminder
     */
    protected function scheduleWelcomeReminder(User $user): ?OnboardingReminder
    {
        // Check if already scheduled
        if (OnboardingReminder::hasPendingReminderOfType($user->id, OnboardingReminder::TYPE_WELCOME)) {
            return null;
        }

        $content = OnboardingReminder::getReminderContent(
            OnboardingReminder::TYPE_WELCOME,
            $user
        );

        return OnboardingReminder::schedule(
            $user->id,
            OnboardingReminder::TYPE_WELCOME,
            now()->addMinutes(5), // Short delay to allow page load
            OnboardingReminder::CHANNEL_EMAIL,
            null,
            [
                'subject' => $content['subject'],
                'message' => $content['message'],
                'user_name' => $user->first_name,
                'dashboard_url' => route('dashboard'),
            ]
        );
    }

    /**
     * Schedule first step reminder
     */
    protected function scheduleFirstStepReminder(User $user): ?OnboardingReminder
    {
        if (OnboardingReminder::hasPendingReminderOfType($user->id, OnboardingReminder::TYPE_FIRST_STEP)) {
            return null;
        }

        $content = OnboardingReminder::getReminderContent(
            OnboardingReminder::TYPE_FIRST_STEP,
            $user
        );

        return OnboardingReminder::schedule(
            $user->id,
            OnboardingReminder::TYPE_FIRST_STEP,
            now()->addHours(self::TIMING_FIRST_STEP),
            OnboardingReminder::CHANNEL_EMAIL,
            null,
            [
                'subject' => $content['subject'],
                'message' => $content['message'],
                'user_name' => $user->first_name,
            ]
        );
    }

    /**
     * Schedule inactivity reminder
     */
    protected function scheduleInactivityReminder(User $user, int $hoursAfterSignup): ?OnboardingReminder
    {
        $scheduledAt = $user->created_at->addHours($hoursAfterSignup);

        // Don't schedule if time has already passed
        if ($scheduledAt <= now()) {
            return null;
        }

        $content = OnboardingReminder::getReminderContent(
            OnboardingReminder::TYPE_INACTIVITY,
            $user
        );

        return OnboardingReminder::schedule(
            $user->id,
            OnboardingReminder::TYPE_INACTIVITY,
            $scheduledAt,
            OnboardingReminder::CHANNEL_EMAIL,
            null,
            [
                'subject' => $content['subject'],
                'message' => $content['message'],
                'user_name' => $user->first_name,
                'hours_after_signup' => $hoursAfterSignup,
            ]
        );
    }

    /**
     * Send reminder for a specific incomplete step
     */
    public function sendStepReminder(User $user, OnboardingStep $step): bool
    {
        // Check reminder limits
        $existingCount = OnboardingReminder::forUser($user->id)
            ->forStep($step->step_id)
            ->sent()
            ->count();

        if ($existingCount >= self::MAX_REMINDERS_PER_STEP) {
            Log::info("Max step reminders reached for user {$user->id}, step {$step->step_id}");
            return false;
        }

        // Check total reminders for user
        $totalReminders = OnboardingReminder::forUser($user->id)->sent()->count();
        if ($totalReminders >= self::MAX_REMINDERS_PER_USER) {
            Log::info("Max total reminders reached for user {$user->id}");
            return false;
        }

        $content = OnboardingReminder::getReminderContent(
            OnboardingReminder::TYPE_INCOMPLETE_STEP,
            $user,
            $step
        );

        $reminder = OnboardingReminder::schedule(
            $user->id,
            OnboardingReminder::TYPE_INCOMPLETE_STEP,
            now(),
            OnboardingReminder::CHANNEL_EMAIL,
            $step->step_id,
            [
                'subject' => $content['subject'],
                'message' => $content['message'],
                'user_name' => $user->first_name,
                'step_name' => $step->name,
                'step_url' => $step->getRouteUrl(),
            ]
        );

        return $this->sendReminder($reminder);
    }

    /**
     * Send inactivity reminder after N days of inactivity
     */
    public function sendInactivityReminder(User $user, int $daysInactive): bool
    {
        // Check if already completed onboarding
        if ($user->onboarding_completed) {
            return false;
        }

        // Check reminder limits
        $recentInactivityReminders = OnboardingReminder::forUser($user->id)
            ->ofType(OnboardingReminder::TYPE_INACTIVITY)
            ->where('sent_at', '>=', now()->subDays(7))
            ->count();

        if ($recentInactivityReminders >= 2) {
            return false; // Max 2 inactivity reminders per week
        }

        $content = OnboardingReminder::getReminderContent(
            OnboardingReminder::TYPE_INACTIVITY,
            $user
        );

        $reminder = OnboardingReminder::schedule(
            $user->id,
            OnboardingReminder::TYPE_INACTIVITY,
            now(),
            OnboardingReminder::CHANNEL_EMAIL,
            null,
            [
                'subject' => $content['subject'],
                'message' => $content['message'],
                'user_name' => $user->first_name,
                'days_inactive' => $daysInactive,
            ]
        );

        return $this->sendReminder($reminder);
    }

    /**
     * Send completion celebration
     */
    public function sendCompletionCelebration(User $user): bool
    {
        // Only send if not already sent
        $alreadySent = OnboardingReminder::forUser($user->id)
            ->ofType(OnboardingReminder::TYPE_CELEBRATION)
            ->sent()
            ->exists();

        if ($alreadySent) {
            return false;
        }

        $content = OnboardingReminder::getReminderContent(
            OnboardingReminder::TYPE_CELEBRATION,
            $user
        );

        $reminder = OnboardingReminder::schedule(
            $user->id,
            OnboardingReminder::TYPE_CELEBRATION,
            now(),
            OnboardingReminder::CHANNEL_EMAIL,
            null,
            [
                'subject' => $content['subject'],
                'message' => $content['message'],
                'user_name' => $user->first_name,
                'dashboard_url' => route('dashboard'),
            ]
        );

        // Also send in-app notification
        $this->createInAppNotification($user, 'celebration', [
            'title' => 'Congratulations!',
            'message' => 'Your account is now fully activated. Start exploring!',
            'show_confetti' => true,
        ]);

        // Cancel any remaining onboarding reminders
        OnboardingReminder::cancelAllForUser($user->id, 'Onboarding completed');

        return $this->sendReminder($reminder);
    }

    /**
     * Send milestone notification (e.g., 50% complete)
     */
    public function sendMilestoneNotification(User $user, int $progressPercentage): bool
    {
        $milestones = [25, 50, 75];

        if (!in_array($progressPercentage, $milestones)) {
            return false;
        }

        // Check if already sent for this milestone
        $alreadySent = OnboardingReminder::forUser($user->id)
            ->ofType(OnboardingReminder::TYPE_MILESTONE)
            ->whereJsonContains('template_data->milestone', $progressPercentage)
            ->sent()
            ->exists();

        if ($alreadySent) {
            return false;
        }

        $content = OnboardingReminder::getReminderContent(
            OnboardingReminder::TYPE_MILESTONE,
            $user
        );

        $reminder = OnboardingReminder::schedule(
            $user->id,
            OnboardingReminder::TYPE_MILESTONE,
            now(),
            OnboardingReminder::CHANNEL_IN_APP, // Milestone as in-app only
            null,
            [
                'subject' => $content['subject'],
                'message' => "You're {$progressPercentage}% done with your setup!",
                'milestone' => $progressPercentage,
            ]
        );

        return $this->sendReminder($reminder);
    }

    /**
     * Send support offer for stuck users
     */
    public function sendSupportOffer(User $user, OnboardingStep $stuckStep): bool
    {
        $content = OnboardingReminder::getReminderContent(
            OnboardingReminder::TYPE_SUPPORT_OFFER,
            $user,
            $stuckStep
        );

        $reminder = OnboardingReminder::schedule(
            $user->id,
            OnboardingReminder::TYPE_SUPPORT_OFFER,
            now(),
            OnboardingReminder::CHANNEL_EMAIL,
            $stuckStep->step_id,
            [
                'subject' => $content['subject'],
                'message' => $content['message'],
                'user_name' => $user->first_name,
                'step_name' => $stuckStep->name,
                'support_url' => route('contact'),
            ]
        );

        return $this->sendReminder($reminder);
    }

    /**
     * Process and send a reminder
     */
    protected function sendReminder(OnboardingReminder $reminder): bool
    {
        try {
            $user = $reminder->user;

            // Check suppression conditions
            if ($this->shouldSuppress($reminder)) {
                $reminder->suppress('User preferences or conditions');
                return false;
            }

            switch ($reminder->channel) {
                case OnboardingReminder::CHANNEL_EMAIL:
                    $this->sendEmailReminder($reminder);
                    break;

                case OnboardingReminder::CHANNEL_PUSH:
                    $this->sendPushReminder($reminder);
                    break;

                case OnboardingReminder::CHANNEL_SMS:
                    $this->sendSmsReminder($reminder);
                    break;

                case OnboardingReminder::CHANNEL_IN_APP:
                    $this->sendInAppReminder($reminder);
                    break;
            }

            $reminder->markAsSent();

            // Log the event
            OnboardingEvent::log(
                $user->id,
                OnboardingEvent::EVENT_REMINDER_SENT,
                $reminder->step_id,
                [
                    'reminder_type' => $reminder->reminder_type,
                    'channel' => $reminder->channel,
                    'tracking_id' => $reminder->tracking_id,
                ]
            );

            // Update progress record reminder count
            if ($reminder->step_id) {
                $step = OnboardingStep::findByStepId($reminder->step_id);
                if ($step) {
                    $progress = OnboardingProgress::where('user_id', $user->id)
                        ->where('onboarding_step_id', $step->id)
                        ->first();
                    $progress?->recordReminderSent();
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send onboarding reminder: {$e->getMessage()}", [
                'reminder_id' => $reminder->id,
                'user_id' => $reminder->user_id,
                'type' => $reminder->reminder_type,
            ]);

            $reminder->markAsFailed($e->getMessage());
            return false;
        }
    }

    /**
     * Send email reminder
     */
    protected function sendEmailReminder(OnboardingReminder $reminder): void
    {
        $user = $reminder->user;
        $data = $reminder->template_data ?? [];

        // In production, use actual Mail facade
        // Mail::to($user->email)->send(new OnboardingReminderMail($reminder));

        Log::info("Sending onboarding email reminder", [
            'user_id' => $user->id,
            'email' => $user->email,
            'type' => $reminder->reminder_type,
            'subject' => $data['subject'] ?? 'Onboarding Reminder',
        ]);
    }

    /**
     * Send push notification reminder
     */
    protected function sendPushReminder(OnboardingReminder $reminder): void
    {
        $user = $reminder->user;

        if (!$user->device_token) {
            throw new \Exception('User has no device token');
        }

        // In production, use FCM or other push service
        Log::info("Sending onboarding push notification", [
            'user_id' => $user->id,
            'type' => $reminder->reminder_type,
        ]);
    }

    /**
     * Send SMS reminder
     */
    protected function sendSmsReminder(OnboardingReminder $reminder): void
    {
        // In production, use Twilio or other SMS service
        Log::info("Sending onboarding SMS reminder", [
            'user_id' => $reminder->user_id,
            'type' => $reminder->reminder_type,
        ]);
    }

    /**
     * Send in-app reminder
     */
    protected function sendInAppReminder(OnboardingReminder $reminder): void
    {
        $this->createInAppNotification(
            $reminder->user,
            $reminder->reminder_type,
            $reminder->template_data ?? []
        );
    }

    /**
     * Create in-app notification
     */
    protected function createInAppNotification(User $user, string $type, array $data): void
    {
        // Create notification using the Notifications model
        try {
            \App\Models\Notifications::create([
                'destination' => $user->id,
                'type' => "onboarding_{$type}",
                'target' => $data['step_url'] ?? route('dashboard'),
                'message' => $data['message'] ?? 'Continue your onboarding',
                'read' => false,
            ]);
        } catch (\Exception $e) {
            Log::warning("Could not create in-app notification: {$e->getMessage()}");
        }
    }

    /**
     * Check if reminder should be suppressed
     */
    protected function shouldSuppress(OnboardingReminder $reminder): bool
    {
        $user = $reminder->user;

        // Don't send if onboarding already completed
        if ($user->onboarding_completed) {
            return true;
        }

        // Don't send if user has unsubscribed from this type
        $preferences = json_decode($user->notification_preferences ?? '{}', true);
        if (isset($preferences['onboarding_reminders']) && !$preferences['onboarding_reminders']) {
            return true;
        }

        // Don't send if step is already completed
        if ($reminder->step_id) {
            $step = OnboardingStep::findByStepId($reminder->step_id);
            if ($step) {
                $progress = OnboardingProgress::where('user_id', $user->id)
                    ->where('onboarding_step_id', $step->id)
                    ->first();
                if ($progress?->isCompleted()) {
                    return true;
                }
            }
        }

        // Don't send first step reminder if user has started
        if ($reminder->reminder_type === OnboardingReminder::TYPE_FIRST_STEP) {
            $hasStarted = OnboardingProgress::forUser($user->id)
                ->where('status', '!=', 'pending')
                ->exists();
            if ($hasStarted) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process all pending reminders (called by scheduler)
     */
    public function processPendingReminders(): array
    {
        $reminders = OnboardingReminder::getPendingReminders(100);
        $results = [
            'processed' => 0,
            'sent' => 0,
            'suppressed' => 0,
            'failed' => 0,
        ];

        foreach ($reminders as $reminder) {
            $results['processed']++;

            if ($this->shouldSuppress($reminder)) {
                $reminder->suppress('Auto-suppressed during processing');
                $results['suppressed']++;
                continue;
            }

            $sent = $this->sendReminder($reminder);
            if ($sent) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Find users stuck on a step and send support offers
     */
    public function processStuckUsers(int $stuckDays = 3): array
    {
        $results = ['checked' => 0, 'reminders_sent' => 0];

        // Find users who have been stuck on a step for X days
        $stuckProgress = OnboardingProgress::where('status', 'in_progress')
            ->where('started_at', '<', now()->subDays($stuckDays))
            ->whereNull('completed_at')
            ->with(['user', 'step'])
            ->get();

        foreach ($stuckProgress as $progress) {
            $results['checked']++;

            if (!$progress->user || !$progress->step) {
                continue;
            }

            // Check if already sent support offer for this step recently
            $recentOffer = OnboardingReminder::forUser($progress->user_id)
                ->forStep($progress->step->step_id)
                ->ofType(OnboardingReminder::TYPE_SUPPORT_OFFER)
                ->where('scheduled_at', '>=', now()->subDays(7))
                ->exists();

            if ($recentOffer) {
                continue;
            }

            $sent = $this->sendSupportOffer($progress->user, $progress->step);
            if ($sent) {
                $results['reminders_sent']++;
            }
        }

        return $results;
    }
}
