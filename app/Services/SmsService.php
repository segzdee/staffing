<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * COM-004: Enhanced SMS Service
 *
 * Handles SMS messaging with support for:
 * - Multiple providers (Twilio, Vonage, MessageBird, AWS SNS)
 * - GSM-7 and Unicode character encoding detection
 * - Segment counting and cost estimation
 * - Delivery status webhooks
 * - Rate limiting and retry handling
 */
class SmsService
{
    /**
     * GSM-7 Basic Character Set (128 characters)
     * Characters that count as 1 unit in GSM encoding
     */
    protected const GSM7_BASIC = '@£$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà';

    /**
     * GSM-7 Extended Characters (requires escape sequence, counts as 2 units)
     */
    protected const GSM7_EXTENDED = '|^€{}[]~\\';

    /**
     * Character limits per segment
     */
    protected const GSM7_SINGLE_SEGMENT = 160;

    protected const GSM7_MULTI_SEGMENT = 153;  // 7 chars reserved for UDH

    protected const UNICODE_SINGLE_SEGMENT = 70;

    protected const UNICODE_MULTI_SEGMENT = 67;  // 3 chars reserved for UDH

    /**
     * Default cost per segment by provider (in USD)
     */
    protected const COST_PER_SEGMENT = [
        'twilio' => 0.0079,
        'vonage' => 0.0068,
        'messagebird' => 0.0065,
        'sns' => 0.00645,
    ];

    /**
     * Get the configured provider
     */
    protected function getProvider(): string
    {
        return config('services.sms.provider', 'twilio');
    }

    /**
     * Send an SMS message
     *
     * @param  string  $phone  Phone number in E.164 format
     * @param  string  $message  Message content
     * @param  User|null  $user  Associated user (for logging)
     * @param  string  $type  Message type for categorization
     */
    public function send(
        string $phone,
        string $message,
        ?User $user = null,
        string $type = SmsLog::TYPE_TRANSACTIONAL
    ): SmsLog {
        $phone = $this->normalizePhone($phone);
        $encoding = $this->detectEncoding($message);
        $segments = $this->countSegments($message);
        $estimatedCost = $this->estimateCost($segments);

        // Create log entry
        $log = SmsLog::create([
            'user_id' => $user?->id,
            'phone_number' => $phone,
            'channel' => SmsLog::CHANNEL_SMS,
            'type' => $type,
            'content' => $message,
            'provider' => $this->getProvider(),
            'status' => SmsLog::STATUS_PENDING,
            'segments' => $segments,
            'cost' => $estimatedCost,
        ]);

        try {
            $response = $this->sendViaProvider($phone, $message);

            if ($response['success']) {
                $log->markSent($response['message_id'] ?? null);

                // Update cost if provider returned actual cost
                if (isset($response['cost'])) {
                    $log->setCost($response['cost'], $response['currency'] ?? 'USD');
                }

                Log::info('SMS sent', [
                    'phone' => $this->maskPhone($phone),
                    'segments' => $segments,
                    'encoding' => $encoding,
                ]);
            } else {
                $log->markFailed(
                    $response['error'] ?? 'Unknown error',
                    $response['error_code'] ?? null
                );

                Log::error('SMS send failed', [
                    'phone' => $this->maskPhone($phone),
                    'error' => $response['error'] ?? 'Unknown',
                ]);
            }
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage(), 'EXCEPTION');

            Log::error('SMS exception', [
                'phone' => $this->maskPhone($phone),
                'error' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Send OTP code
     */
    public function sendOTP(string $phone, string $code, ?User $user = null): SmsLog
    {
        $message = $this->formatOTPMessage($code);

        return $this->send($phone, $message, $user, SmsLog::TYPE_OTP);
    }

    /**
     * Send shift reminder
     */
    public function sendShiftReminder(string $phone, array $shiftData, ?User $user = null): SmsLog
    {
        $message = $this->formatShiftReminderMessage($shiftData);

        return $this->send($phone, $message, $user, SmsLog::TYPE_SHIFT_REMINDER);
    }

    /**
     * Send urgent alert
     */
    public function sendUrgentAlert(string $phone, string $message, ?User $user = null): SmsLog
    {
        return $this->send($phone, $message, $user, SmsLog::TYPE_URGENT_ALERT);
    }

    /**
     * Detect if message requires Unicode encoding
     *
     * @return string 'gsm7' or 'unicode'
     */
    public function detectEncoding(string $message): string
    {
        $allGsmChars = self::GSM7_BASIC.self::GSM7_EXTENDED;

        for ($i = 0; $i < mb_strlen($message); $i++) {
            $char = mb_substr($message, $i, 1);
            if (mb_strpos($allGsmChars, $char) === false) {
                return 'unicode';
            }
        }

        return 'gsm7';
    }

    /**
     * Count the number of characters considering GSM encoding
     *
     * Extended GSM characters count as 2 units
     */
    public function countGsmCharacters(string $message): int
    {
        $count = 0;

        for ($i = 0; $i < mb_strlen($message); $i++) {
            $char = mb_substr($message, $i, 1);

            if (mb_strpos(self::GSM7_EXTENDED, $char) !== false) {
                $count += 2; // Extended characters use escape sequence
            } else {
                $count += 1;
            }
        }

        return $count;
    }

    /**
     * Count the number of segments required for a message
     */
    public function countSegments(string $message): int
    {
        $encoding = $this->detectEncoding($message);

        if ($encoding === 'unicode') {
            $charCount = mb_strlen($message);

            if ($charCount <= self::UNICODE_SINGLE_SEGMENT) {
                return 1;
            }

            return (int) ceil($charCount / self::UNICODE_MULTI_SEGMENT);
        }

        // GSM-7 encoding
        $charCount = $this->countGsmCharacters($message);

        if ($charCount <= self::GSM7_SINGLE_SEGMENT) {
            return 1;
        }

        return (int) ceil($charCount / self::GSM7_MULTI_SEGMENT);
    }

    /**
     * Get the maximum character count for single segment
     */
    public function getMaxSingleSegmentLength(string $encoding = 'gsm7'): int
    {
        return $encoding === 'unicode'
            ? self::UNICODE_SINGLE_SEGMENT
            : self::GSM7_SINGLE_SEGMENT;
    }

    /**
     * Estimate cost for sending a message
     */
    public function estimateCost(int $segments, ?string $provider = null): float
    {
        $provider = $provider ?? $this->getProvider();
        $costPerSegment = self::COST_PER_SEGMENT[$provider] ?? 0.01;

        return round($segments * $costPerSegment, 4);
    }

    /**
     * Get message info (encoding, segments, cost estimate)
     */
    public function analyzeMessage(string $message): array
    {
        $encoding = $this->detectEncoding($message);
        $segments = $this->countSegments($message);

        return [
            'encoding' => $encoding,
            'char_count' => $encoding === 'unicode'
                ? mb_strlen($message)
                : $this->countGsmCharacters($message),
            'segments' => $segments,
            'max_single_segment' => $this->getMaxSingleSegmentLength($encoding),
            'estimated_cost' => $this->estimateCost($segments),
            'provider' => $this->getProvider(),
        ];
    }

    /**
     * Send via configured provider
     */
    protected function sendViaProvider(string $phone, string $message): array
    {
        $provider = $this->getProvider();

        return match ($provider) {
            'twilio' => $this->sendViaTwilio($phone, $message),
            'vonage', 'nexmo' => $this->sendViaVonage($phone, $message),
            'messagebird' => $this->sendViaMessageBird($phone, $message),
            'sns' => $this->sendViaSNS($phone, $message),
            'log' => $this->sendViaLog($phone, $message),
            default => ['success' => false, 'error' => "Unknown provider: {$provider}"],
        };
    }

    /**
     * Send via Twilio
     */
    protected function sendViaTwilio(string $phone, string $message): array
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (! $sid || ! $token || ! $from) {
            return ['success' => false, 'error' => 'Twilio credentials not configured'];
        }

        try {
            $twilio = new \Twilio\Rest\Client($sid, $token);

            $twilioMessage = $twilio->messages->create($phone, [
                'from' => $from,
                'body' => $message,
            ]);

            return [
                'success' => true,
                'message_id' => $twilioMessage->sid,
                'cost' => (float) $twilioMessage->price * -1, // Twilio returns negative
                'currency' => $twilioMessage->priceUnit ?? 'USD',
            ];
        } catch (\Twilio\Exceptions\TwilioException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }

    /**
     * Send via Vonage (Nexmo)
     */
    protected function sendViaVonage(string $phone, string $message): array
    {
        $apiKey = config('services.vonage.key');
        $apiSecret = config('services.vonage.secret');
        $from = config('services.vonage.from', 'OvertimeStaff');

        if (! $apiKey || ! $apiSecret) {
            return ['success' => false, 'error' => 'Vonage credentials not configured'];
        }

        try {
            $basic = new \Vonage\Client\Credentials\Basic($apiKey, $apiSecret);
            $client = new \Vonage\Client($basic);

            $response = $client->sms()->send(
                new \Vonage\SMS\Message\SMS($phone, $from, $message)
            );

            $sentMessage = $response->current();

            if ($sentMessage->getStatus() == 0) {
                return [
                    'success' => true,
                    'message_id' => $sentMessage->getMessageId(),
                    'cost' => (float) $sentMessage->getMessagePrice(),
                ];
            }

            return [
                'success' => false,
                'error' => $sentMessage->getErrorText() ?? 'Vonage error',
                'error_code' => $sentMessage->getStatus(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'VONAGE_ERROR',
            ];
        }
    }

    /**
     * Send via MessageBird
     */
    protected function sendViaMessageBird(string $phone, string $message): array
    {
        $apiKey = config('services.messagebird.key');
        $originator = config('services.messagebird.from', 'OvertimeStaff');

        if (! $apiKey) {
            return ['success' => false, 'error' => 'MessageBird credentials not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "AccessKey {$apiKey}",
            ])->post('https://rest.messagebird.com/messages', [
                'recipients' => [$phone],
                'originator' => $originator,
                'body' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'message_id' => $data['id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('errors.0.description') ?? 'MessageBird error',
                'error_code' => $response->json('errors.0.code'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'MESSAGEBIRD_ERROR',
            ];
        }
    }

    /**
     * Send via AWS SNS
     */
    protected function sendViaSNS(string $phone, string $message): array
    {
        try {
            $sns = new \Aws\Sns\SnsClient([
                'region' => config('services.ses.region', 'us-east-1'),
                'version' => 'latest',
            ]);

            $result = $sns->publish([
                'Message' => $message,
                'PhoneNumber' => $phone,
                'MessageAttributes' => [
                    'AWS.SNS.SMS.SMSType' => [
                        'DataType' => 'String',
                        'StringValue' => 'Transactional',
                    ],
                ],
            ]);

            return [
                'success' => true,
                'message_id' => $result['MessageId'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'SNS_ERROR',
            ];
        }
    }

    /**
     * Send via Log (development mode)
     */
    protected function sendViaLog(string $phone, string $message): array
    {
        Log::info('SMS (dev mode)', [
            'phone' => $phone,
            'message' => $message,
            'encoding' => $this->detectEncoding($message),
            'segments' => $this->countSegments($message),
        ]);

        return [
            'success' => true,
            'message_id' => 'LOG_'.uniqid(),
        ];
    }

    /**
     * Handle delivery status webhook
     */
    public function handleDeliveryWebhook(string $provider, array $payload): void
    {
        match ($provider) {
            'twilio' => $this->handleTwilioWebhook($payload),
            'vonage' => $this->handleVonageWebhook($payload),
            'messagebird' => $this->handleMessageBirdWebhook($payload),
            default => Log::warning('Unknown SMS webhook provider', ['provider' => $provider]),
        };
    }

    /**
     * Handle Twilio status callback
     */
    protected function handleTwilioWebhook(array $payload): void
    {
        $messageId = $payload['MessageSid'] ?? null;
        $status = $payload['MessageStatus'] ?? null;

        if (! $messageId) {
            return;
        }

        $log = SmsLog::findByProviderMessageId($messageId);

        if (! $log) {
            return;
        }

        match ($status) {
            'delivered' => $log->markDelivered(),
            'failed', 'undelivered' => $log->markFailed(
                $payload['ErrorMessage'] ?? 'Delivery failed',
                $payload['ErrorCode'] ?? null
            ),
            default => Log::debug('Twilio status', ['status' => $status]),
        };

        // Update cost if provided
        if (isset($payload['Price'])) {
            $log->setCost(abs((float) $payload['Price']), $payload['PriceUnit'] ?? 'USD');
        }
    }

    /**
     * Handle Vonage DLR webhook
     */
    protected function handleVonageWebhook(array $payload): void
    {
        $messageId = $payload['messageId'] ?? null;
        $status = $payload['status'] ?? null;

        if (! $messageId) {
            return;
        }

        $log = SmsLog::findByProviderMessageId($messageId);

        if (! $log) {
            return;
        }

        match ($status) {
            'delivered' => $log->markDelivered(),
            'failed', 'rejected', 'expired' => $log->markFailed(
                $payload['err-code'] ?? 'Delivery failed',
                $status
            ),
            default => null,
        };

        if (isset($payload['price'])) {
            $log->setCost((float) $payload['price']);
        }
    }

    /**
     * Handle MessageBird status webhook
     */
    protected function handleMessageBirdWebhook(array $payload): void
    {
        $messageId = $payload['id'] ?? null;
        $status = $payload['status'] ?? null;

        if (! $messageId) {
            return;
        }

        $log = SmsLog::findByProviderMessageId($messageId);

        if (! $log) {
            return;
        }

        match ($status) {
            'delivered' => $log->markDelivered(),
            'delivery_failed' => $log->markFailed(
                $payload['statusReason'] ?? 'Delivery failed'
            ),
            default => null,
        };
    }

    /**
     * Format OTP message
     */
    protected function formatOTPMessage(string $code): string
    {
        return "Your OvertimeStaff verification code is: {$code}. This code expires in 10 minutes.";
    }

    /**
     * Format shift reminder message
     */
    protected function formatShiftReminderMessage(array $shiftData): string
    {
        $title = $shiftData['title'] ?? 'Your shift';
        $time = $shiftData['time'] ?? 'soon';
        $location = $shiftData['location'] ?? '';

        $message = "Reminder: {$title} starts at {$time}";

        if ($location) {
            $message .= " at {$location}";
        }

        return $message.'. Don\'t forget to check in!';
    }

    /**
     * Normalize phone number to E.164 format
     */
    protected function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters except leading +
        $normalized = preg_replace('/[^\d+]/', '', $phone);

        // Ensure it has + prefix
        if (! str_starts_with($normalized, '+')) {
            // Assume US number if no country code
            if (strlen($normalized) === 10) {
                $normalized = '+1'.$normalized;
            } else {
                $normalized = '+'.$normalized;
            }
        }

        return $normalized;
    }

    /**
     * Mask phone number for logging
     */
    protected function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone);
        $length = strlen($digits);

        if ($length <= 4) {
            return $digits;
        }

        return substr($digits, 0, 3).str_repeat('*', $length - 5).substr($digits, -2);
    }

    /**
     * Validate phone number format
     */
    public function validatePhoneNumber(string $phone): bool
    {
        $normalized = preg_replace('/[^\d+]/', '', $phone);
        $digits = ltrim($normalized, '+');

        return strlen($digits) >= 10 && strlen($digits) <= 15;
    }

    /**
     * Retry failed messages
     *
     * @return int Number of messages retried
     */
    public function retryFailed(int $limit = 50): int
    {
        $messages = SmsLog::retryable()
            ->sms()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $retried = 0;

        foreach ($messages as $message) {
            if ($message->prepareForRetry()) {
                $this->send(
                    $message->phone_number,
                    $message->content,
                    $message->user,
                    $message->type
                );
                $retried++;
            }
        }

        return $retried;
    }

    /**
     * Get statistics for a date range
     */
    public function getStats(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        return array_merge(
            SmsLog::getCostStats($startDate, $endDate),
            SmsLog::getDeliveryStats($startDate, $endDate)
        );
    }
}
