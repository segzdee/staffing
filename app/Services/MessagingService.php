<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\UserPhonePreference;
use Illuminate\Support\Facades\Log;

/**
 * COM-004: Unified Messaging Service
 *
 * Intelligently routes messages between SMS and WhatsApp based on:
 * - User preferences
 * - Message type and urgency
 * - Channel availability
 * - Quiet hours
 *
 * Acts as the primary interface for all outbound messaging.
 */
class MessagingService
{
    protected SmsService $smsService;

    protected WhatsAppService $whatsAppService;

    public function __construct(SmsService $smsService, WhatsAppService $whatsAppService)
    {
        $this->smsService = $smsService;
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Send an urgent alert to a user
     *
     * Urgent alerts bypass quiet hours and use the fastest available channel.
     */
    public function sendUrgentAlert(User $user, string $message): SmsLog
    {
        $preference = UserPhonePreference::getOrCreateForUser($user);
        $phone = $preference->full_phone_number;

        // For urgent alerts, try preferred channel first, then fallback
        $channel = $preference->getBestChannel(SmsLog::TYPE_URGENT_ALERT);

        // Don't check quiet hours for urgent alerts
        return $this->sendToChannel($channel, $phone, $message, $user, SmsLog::TYPE_URGENT_ALERT);
    }

    /**
     * Send a shift reminder to a user
     */
    public function sendShiftReminder(User $user, Shift $shift): SmsLog
    {
        $preference = UserPhonePreference::getOrCreateForUser($user);

        if (! $preference->canReceiveMessageType(SmsLog::TYPE_SHIFT_REMINDER)) {
            return $this->createSkippedLog($user, 'User opted out of transactional messages');
        }

        // Check quiet hours for non-urgent messages
        if ($preference->isInQuietHours()) {
            Log::info('Shift reminder delayed due to quiet hours', [
                'user_id' => $user->id,
                'shift_id' => $shift->id,
            ]);

            // Could queue for later, for now we'll skip
            return $this->createSkippedLog($user, 'User is in quiet hours');
        }

        $phone = $preference->full_phone_number;
        $channel = $preference->getBestChannel(SmsLog::TYPE_SHIFT_REMINDER);

        // Format shift reminder message
        $message = $this->formatShiftReminder($shift);

        // For WhatsApp, use template
        if ($channel === SmsLog::CHANNEL_WHATSAPP && $this->whatsAppService->isEnabled()) {
            return $this->whatsAppService->sendTemplate(
                $phone,
                'shift_reminder',
                [
                    '1' => $shift->title,
                    '2' => $shift->start_time->format('g:i A'),
                    '3' => $shift->location_address ?? 'the venue',
                ],
                $user,
                SmsLog::TYPE_SHIFT_REMINDER
            );
        }

        return $this->smsService->send($phone, $message, $user, SmsLog::TYPE_SHIFT_REMINDER);
    }

    /**
     * Send OTP verification code
     *
     * Always sent via SMS for maximum compatibility.
     */
    public function sendOTP(string $phone, string $code, ?User $user = null): SmsLog
    {
        // OTPs are always SMS for reliability
        return $this->smsService->sendOTP($phone, $code, $user);
    }

    /**
     * Send a transactional message to a user
     *
     * @param  string  $templateName  WhatsApp template name (if using WhatsApp)
     * @param  array  $templateParams  Template parameters
     * @param  string  $fallbackMessage  SMS message if WhatsApp unavailable
     */
    public function sendTransactional(
        User $user,
        string $templateName,
        array $templateParams = [],
        ?string $fallbackMessage = null
    ): SmsLog {
        $preference = UserPhonePreference::getOrCreateForUser($user);

        if (! $preference->canReceiveMessageType(SmsLog::TYPE_TRANSACTIONAL)) {
            return $this->createSkippedLog($user, 'User opted out of transactional messages');
        }

        if ($preference->isInQuietHours()) {
            return $this->createSkippedLog($user, 'User is in quiet hours');
        }

        $phone = $preference->full_phone_number;
        $channel = $preference->getBestChannel(SmsLog::TYPE_TRANSACTIONAL);

        if ($channel === SmsLog::CHANNEL_WHATSAPP && $this->whatsAppService->isEnabled()) {
            return $this->whatsAppService->sendTemplate(
                $phone,
                $templateName,
                $templateParams,
                $user,
                SmsLog::TYPE_TRANSACTIONAL
            );
        }

        // Build fallback message if not provided
        if (! $fallbackMessage) {
            $fallbackMessage = $this->buildFallbackMessage($templateName, $templateParams);
        }

        return $this->smsService->send($phone, $fallbackMessage, $user, SmsLog::TYPE_TRANSACTIONAL);
    }

    /**
     * Send a marketing message to a user
     *
     * Requires explicit opt-in and respects all preferences.
     */
    public function sendMarketing(
        User $user,
        string $templateName,
        array $templateParams = [],
        ?string $fallbackMessage = null
    ): ?SmsLog {
        $preference = UserPhonePreference::getOrCreateForUser($user);

        if (! $preference->marketing_opt_in) {
            Log::debug('Marketing message skipped - user not opted in', ['user_id' => $user->id]);

            return null;
        }

        if ($preference->isInQuietHours()) {
            Log::debug('Marketing message skipped - quiet hours', ['user_id' => $user->id]);

            return null;
        }

        $phone = $preference->full_phone_number;
        $channel = $preference->getBestChannel(SmsLog::TYPE_MARKETING);

        if ($channel === SmsLog::CHANNEL_WHATSAPP && $this->whatsAppService->isEnabled()) {
            return $this->whatsAppService->sendTemplate(
                $phone,
                $templateName,
                $templateParams,
                $user,
                SmsLog::TYPE_MARKETING
            );
        }

        if (! $fallbackMessage) {
            $fallbackMessage = $this->buildFallbackMessage($templateName, $templateParams);
        }

        return $this->smsService->send($phone, $fallbackMessage, $user, SmsLog::TYPE_MARKETING);
    }

    /**
     * Send to a specific channel
     */
    protected function sendToChannel(
        string $channel,
        string $phone,
        string $message,
        ?User $user,
        string $type
    ): SmsLog {
        if ($channel === SmsLog::CHANNEL_WHATSAPP && $this->whatsAppService->isEnabled()) {
            // For direct messages, need to use a template for initial contact
            // or send freeform if within 24-hour window
            return $this->whatsAppService->sendMessage($phone, $message, $user);
        }

        return $this->smsService->send($phone, $message, $user, $type);
    }

    /**
     * Notify worker of shift assignment
     */
    public function notifyShiftAssigned(User $worker, Shift $shift): SmsLog
    {
        return $this->sendTransactional(
            $worker,
            'shift_assigned',
            [
                '1' => $shift->title,
                '2' => $shift->shift_date->format('M j, Y'),
                '3' => $shift->start_time->format('g:i A'),
            ],
            "You've been assigned to {$shift->title} on {$shift->shift_date->format('M j')} at {$shift->start_time->format('g:i A')}!"
        );
    }

    /**
     * Notify worker of shift cancellation
     */
    public function notifyShiftCancelled(User $worker, Shift $shift, ?string $reason = null): SmsLog
    {
        $reasonText = $reason ? " Reason: {$reason}" : '';

        return $this->sendTransactional(
            $worker,
            'shift_cancelled',
            [
                '1' => $shift->title,
                '2' => $shift->shift_date->format('M j, Y'),
            ],
            "Your shift {$shift->title} on {$shift->shift_date->format('M j')} has been cancelled.{$reasonText}"
        );
    }

    /**
     * Notify business of worker check-in
     */
    public function notifyWorkerCheckedIn(User $business, User $worker, Shift $shift): SmsLog
    {
        return $this->sendTransactional(
            $business,
            'worker_checked_in',
            [
                '1' => $worker->name,
                '2' => $shift->title,
            ],
            "{$worker->name} has checked in for {$shift->title}."
        );
    }

    /**
     * Notify business of worker no-show
     */
    public function notifyWorkerNoShow(User $business, User $worker, Shift $shift): SmsLog
    {
        return $this->sendUrgentAlert(
            $business,
            "ALERT: {$worker->name} has not checked in for {$shift->title}. The shift started at {$shift->start_time->format('g:i A')}."
        );
    }

    /**
     * Notify worker of payment release
     */
    public function notifyPaymentReleased(User $worker, float $amount, string $currency = 'USD'): SmsLog
    {
        $formattedAmount = number_format($amount, 2);

        return $this->sendTransactional(
            $worker,
            'payment_released',
            [
                '1' => "{$currency} {$formattedAmount}",
            ],
            "Your payment of {$currency} {$formattedAmount} has been released and will arrive shortly!"
        );
    }

    /**
     * Send bulk messages (with rate limiting)
     *
     * @param  array  $recipients  Array of [user => User, template => string, params => array]
     * @param  int  $batchDelay  Milliseconds between batches
     */
    public function sendBulk(array $recipients, int $batchSize = 50, int $batchDelay = 1000): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'logs' => [],
        ];

        $batches = array_chunk($recipients, $batchSize);

        foreach ($batches as $index => $batch) {
            foreach ($batch as $recipient) {
                $user = $recipient['user'] ?? null;
                $template = $recipient['template'] ?? null;
                $params = $recipient['params'] ?? [];
                $fallback = $recipient['fallback'] ?? null;

                if (! $user || ! $template) {
                    $results['skipped']++;

                    continue;
                }

                try {
                    $log = $this->sendTransactional($user, $template, $params, $fallback);

                    if ($log->wasSent()) {
                        $results['sent']++;
                    } else {
                        $results['failed']++;
                    }

                    $results['logs'][] = $log;
                } catch (\Exception $e) {
                    $results['failed']++;
                    Log::error('Bulk send error', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Delay between batches (except for last batch)
            if ($index < count($batches) - 1) {
                usleep($batchDelay * 1000);
            }
        }

        return $results;
    }

    /**
     * Get user's messaging preferences
     */
    public function getUserPreferences(User $user): UserPhonePreference
    {
        return UserPhonePreference::getOrCreateForUser($user);
    }

    /**
     * Update user's preferred channel
     */
    public function updatePreferredChannel(User $user, string $channel): bool
    {
        $preference = UserPhonePreference::getOrCreateForUser($user);

        return $preference->setPreferredChannel($channel);
    }

    /**
     * Enable WhatsApp for user
     */
    public function enableWhatsApp(User $user, ?string $optInMessageId = null): bool
    {
        $preference = UserPhonePreference::getOrCreateForUser($user);

        return $preference->enableWhatsApp($optInMessageId);
    }

    /**
     * Disable WhatsApp for user
     */
    public function disableWhatsApp(User $user): bool
    {
        $preference = UserPhonePreference::getOrCreateForUser($user);

        return $preference->disableWhatsApp();
    }

    /**
     * Set user quiet hours
     */
    public function setQuietHours(User $user, string $start, string $end, string $timezone = 'UTC'): bool
    {
        $preference = UserPhonePreference::getOrCreateForUser($user);

        return $preference->setQuietHours($start, $end, $timezone);
    }

    /**
     * Get messaging statistics for a user
     */
    public function getUserStats(User $user): array
    {
        $logs = SmsLog::forUser($user->id)->get();

        return [
            'total_messages' => $logs->count(),
            'sms_count' => $logs->where('channel', SmsLog::CHANNEL_SMS)->count(),
            'whatsapp_count' => $logs->where('channel', SmsLog::CHANNEL_WHATSAPP)->count(),
            'delivered' => $logs->whereIn('status', [SmsLog::STATUS_DELIVERED, SmsLog::STATUS_READ])->count(),
            'failed' => $logs->where('status', SmsLog::STATUS_FAILED)->count(),
            'total_cost' => $logs->sum('cost'),
        ];
    }

    /**
     * Format shift reminder message
     */
    protected function formatShiftReminder(Shift $shift): string
    {
        $time = $shift->start_time->format('g:i A');
        $location = $shift->location_address ?? 'the venue';

        return "Reminder: Your shift \"{$shift->title}\" starts at {$time} at {$location}. Don't forget to check in!";
    }

    /**
     * Build fallback message from template name and params
     */
    protected function buildFallbackMessage(string $templateName, array $params): string
    {
        // Map template names to fallback message formats
        $templates = [
            'shift_assigned' => "You've been assigned to {1} on {2} at {3}.",
            'shift_cancelled' => 'Your shift {1} on {2} has been cancelled.',
            'shift_reminder' => 'Reminder: {1} starts at {2} at {3}.',
            'worker_checked_in' => '{1} has checked in for {2}.',
            'payment_released' => 'Your payment of {1} has been released!',
        ];

        $format = $templates[$templateName] ?? implode(' ', array_values($params));

        // Replace numbered placeholders
        foreach ($params as $key => $value) {
            $format = str_replace("{{$key}}", $value, $format);
        }

        return $format;
    }

    /**
     * Create a skipped log entry
     */
    protected function createSkippedLog(User $user, string $reason): SmsLog
    {
        return SmsLog::create([
            'user_id' => $user->id,
            'phone_number' => $user->phone ?? '',
            'channel' => SmsLog::CHANNEL_SMS,
            'type' => SmsLog::TYPE_TRANSACTIONAL,
            'content' => '',
            'provider' => 'skipped',
            'status' => SmsLog::STATUS_FAILED,
            'error_message' => $reason,
            'failed_at' => now(),
        ]);
    }
}
