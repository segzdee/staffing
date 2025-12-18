<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * COM-004: WhatsApp Business API Service
 *
 * Handles sending WhatsApp messages through the official Business API.
 * Supports template messages (required for initiating conversations),
 * freeform messages (for replies within 24-hour window),
 * template synchronization with Meta, and webhook handling.
 */
class WhatsAppService
{
    /**
     * WhatsApp Cloud API base URL
     */
    protected const API_BASE_URL = 'https://graph.facebook.com/v18.0';

    /**
     * Get the configured provider
     */
    protected function getProvider(): string
    {
        return config('whatsapp.provider', 'meta');
    }

    /**
     * Check if WhatsApp is enabled
     */
    public function isEnabled(): bool
    {
        return config('whatsapp.enabled', false);
    }

    /**
     * Send a template message
     *
     * @param  string  $phone  Phone number in E.164 format
     * @param  string  $templateName  Template name
     * @param  array  $params  Template parameters
     * @param  User|null  $user  Associated user (for logging)
     * @param  string  $messageType  Message type for logging
     */
    public function sendTemplate(
        string $phone,
        string $templateName,
        array $params = [],
        ?User $user = null,
        string $messageType = SmsLog::TYPE_TRANSACTIONAL
    ): SmsLog {
        $template = WhatsAppTemplate::findByName($templateName);

        if (! $template) {
            Log::error('WhatsApp template not found', ['template' => $templateName]);

            return $this->createFailedLog(
                $phone,
                $user,
                $messageType,
                "Template not found: {$templateName}",
                $templateName
            );
        }

        if (! $template->isUsable()) {
            Log::error('WhatsApp template not usable', [
                'template' => $templateName,
                'status' => $template->status,
                'active' => $template->is_active,
            ]);

            return $this->createFailedLog(
                $phone,
                $user,
                $messageType,
                "Template not approved or inactive: {$templateName}",
                $templateName
            );
        }

        // Create log entry
        $log = SmsLog::create([
            'user_id' => $user?->id,
            'phone_number' => $this->normalizePhone($phone),
            'channel' => SmsLog::CHANNEL_WHATSAPP,
            'type' => $messageType,
            'content' => $template->render($params),
            'template_id' => $template->template_id,
            'template_params' => $params,
            'provider' => $this->getProvider(),
            'status' => SmsLog::STATUS_PENDING,
        ]);

        try {
            $response = $this->sendViaProvider($phone, $template, $params);

            if ($response['success']) {
                $log->markSent($response['message_id'] ?? null);

                if (isset($response['cost'])) {
                    $log->setCost($response['cost']);
                }

                Log::info('WhatsApp template message sent', [
                    'phone' => $this->maskPhone($phone),
                    'template' => $templateName,
                    'message_id' => $response['message_id'] ?? null,
                ]);
            } else {
                $log->markFailed(
                    $response['error'] ?? 'Unknown error',
                    $response['error_code'] ?? null
                );

                Log::error('WhatsApp send failed', [
                    'phone' => $this->maskPhone($phone),
                    'template' => $templateName,
                    'error' => $response['error'] ?? 'Unknown',
                ]);
            }
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage(), 'EXCEPTION');

            Log::error('WhatsApp exception', [
                'phone' => $this->maskPhone($phone),
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Send a freeform message (only within 24-hour customer service window)
     *
     * @param  string  $phone  Phone number
     * @param  string  $message  Message content
     * @param  User|null  $user  Associated user
     */
    public function sendMessage(string $phone, string $message, ?User $user = null): SmsLog
    {
        $log = SmsLog::create([
            'user_id' => $user?->id,
            'phone_number' => $this->normalizePhone($phone),
            'channel' => SmsLog::CHANNEL_WHATSAPP,
            'type' => SmsLog::TYPE_TRANSACTIONAL,
            'content' => $message,
            'provider' => $this->getProvider(),
            'status' => SmsLog::STATUS_PENDING,
        ]);

        try {
            $response = $this->sendFreeformMessage($phone, $message);

            if ($response['success']) {
                $log->markSent($response['message_id'] ?? null);
            } else {
                $log->markFailed(
                    $response['error'] ?? 'Unknown error',
                    $response['error_code'] ?? null
                );
            }
        } catch (\Exception $e) {
            $log->markFailed($e->getMessage(), 'EXCEPTION');
        }

        return $log;
    }

    /**
     * Get all templates from database
     */
    public function getTemplates(): Collection
    {
        return WhatsAppTemplate::orderBy('name')->get();
    }

    /**
     * Get usable templates
     */
    public function getUsableTemplates(): Collection
    {
        return WhatsAppTemplate::usable()->orderBy('name')->get();
    }

    /**
     * Sync templates from Meta Business API
     *
     * @return int Number of templates synced
     */
    public function syncTemplatesFromMeta(): int
    {
        if ($this->getProvider() !== 'meta') {
            Log::warning('Template sync only available for Meta provider');

            return 0;
        }

        $businessId = config('whatsapp.meta.business_id');
        $accessToken = config('whatsapp.meta.access_token');

        if (! $businessId || ! $accessToken) {
            Log::error('Meta WhatsApp credentials not configured');

            return 0;
        }

        try {
            $response = Http::withToken($accessToken)
                ->get(self::API_BASE_URL."/{$businessId}/message_templates");

            if (! $response->successful()) {
                Log::error('Failed to fetch templates from Meta', [
                    'status' => $response->status(),
                    'error' => $response->json('error'),
                ]);

                return 0;
            }

            $templates = $response->json('data', []);
            $syncedCount = 0;

            foreach ($templates as $templateData) {
                $this->syncTemplate($templateData);
                $syncedCount++;
            }

            Log::info('WhatsApp templates synced from Meta', ['count' => $syncedCount]);

            return $syncedCount;
        } catch (\Exception $e) {
            Log::error('Exception syncing templates', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Handle incoming webhook payload
     */
    public function handleWebhook(array $payload): void
    {
        $object = $payload['object'] ?? null;

        if ($object !== 'whatsapp_business_account') {
            Log::debug('Ignoring non-WhatsApp webhook', ['object' => $object]);

            return;
        }

        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $field = $change['field'] ?? null;
                $value = $change['value'] ?? [];

                if ($field === 'messages') {
                    $this->handleMessageWebhook($value);
                } elseif ($field === 'message_template_status_update') {
                    $this->handleTemplateStatusUpdate($value);
                }
            }
        }
    }

    /**
     * Mark a message as delivered by provider message ID
     */
    public function markAsDelivered(string $messageId): void
    {
        $log = SmsLog::findByProviderMessageId($messageId);

        if ($log) {
            $log->markDelivered();
            Log::info('WhatsApp message marked delivered', ['message_id' => $messageId]);
        }
    }

    /**
     * Mark a message as read by provider message ID
     */
    public function markAsRead(string $messageId): void
    {
        $log = SmsLog::findByProviderMessageId($messageId);

        if ($log) {
            $log->markRead();
            Log::info('WhatsApp message marked read', ['message_id' => $messageId]);
        }
    }

    /**
     * Validate phone number format for WhatsApp
     */
    public function validatePhoneNumber(string $phone): bool
    {
        // Remove all non-numeric characters except leading +
        $normalized = preg_replace('/[^\d+]/', '', $phone);

        // Must have at least 10 digits (excluding +)
        $digits = ltrim($normalized, '+');

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return false;
        }

        // Must be E.164 format (starts with country code)
        // Simple validation - more thorough validation would use libphonenumber
        return preg_match('/^\+?[1-9]\d{9,14}$/', $normalized) === 1;
    }

    /**
     * Send message via configured provider
     */
    protected function sendViaProvider(string $phone, WhatsAppTemplate $template, array $params): array
    {
        $provider = $this->getProvider();

        return match ($provider) {
            'meta' => $this->sendViaMeta($phone, $template, $params),
            'twilio' => $this->sendViaTwilio($phone, $template, $params),
            'messagebird' => $this->sendViaMessageBird($phone, $template, $params),
            default => ['success' => false, 'error' => "Unknown provider: {$provider}"],
        };
    }

    /**
     * Send via Meta/Facebook WhatsApp Cloud API
     */
    protected function sendViaMeta(string $phone, WhatsAppTemplate $template, array $params): array
    {
        $phoneNumberId = config('whatsapp.meta.phone_number_id');
        $accessToken = config('whatsapp.meta.access_token');

        if (! $phoneNumberId || ! $accessToken) {
            return ['success' => false, 'error' => 'Meta WhatsApp credentials not configured'];
        }

        $payload = $template->buildApiPayload($phone, $params);

        try {
            $response = Http::withToken($accessToken)
                ->post(self::API_BASE_URL."/{$phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                ];
            }

            $error = $response->json('error', []);

            return [
                'success' => false,
                'error' => $error['message'] ?? 'API request failed',
                'error_code' => $error['code'] ?? $response->status(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'error_code' => 'EXCEPTION'];
        }
    }

    /**
     * Send via Twilio WhatsApp
     */
    protected function sendViaTwilio(string $phone, WhatsAppTemplate $template, array $params): array
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('whatsapp.twilio.from');

        if (! $sid || ! $token || ! $from) {
            return ['success' => false, 'error' => 'Twilio credentials not configured'];
        }

        try {
            $twilio = new \Twilio\Rest\Client($sid, $token);

            // Twilio uses content templates differently
            $contentSid = $template->template_id;
            $contentVariables = json_encode(array_combine(
                array_map(fn ($i) => (string) $i, range(1, count($params))),
                array_values($params)
            ));

            $message = $twilio->messages->create(
                "whatsapp:{$this->normalizePhone($phone)}",
                [
                    'from' => "whatsapp:{$from}",
                    'contentSid' => $contentSid,
                    'contentVariables' => $contentVariables,
                ]
            );

            return [
                'success' => true,
                'message_id' => $message->sid,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'error_code' => 'TWILIO_ERROR'];
        }
    }

    /**
     * Send via MessageBird
     */
    protected function sendViaMessageBird(string $phone, WhatsAppTemplate $template, array $params): array
    {
        $apiKey = config('whatsapp.messagebird.api_key');
        $channelId = config('whatsapp.messagebird.channel_id');

        if (! $apiKey || ! $channelId) {
            return ['success' => false, 'error' => 'MessageBird credentials not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "AccessKey {$apiKey}",
            ])->post('https://conversations.messagebird.com/v1/send', [
                'to' => $this->normalizePhone($phone),
                'from' => $channelId,
                'type' => 'hsm',
                'content' => [
                    'hsm' => [
                        'namespace' => config('whatsapp.messagebird.namespace'),
                        'templateName' => $template->name,
                        'language' => [
                            'policy' => 'deterministic',
                            'code' => $template->language,
                        ],
                        'params' => array_map(fn ($p) => ['default' => $p], $params),
                    ],
                ],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('id'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('errors.0.description') ?? 'MessageBird error',
                'error_code' => $response->json('errors.0.code'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'error_code' => 'MESSAGEBIRD_ERROR'];
        }
    }

    /**
     * Send freeform message (within 24-hour window)
     */
    protected function sendFreeformMessage(string $phone, string $message): array
    {
        $provider = $this->getProvider();

        if ($provider === 'meta') {
            $phoneNumberId = config('whatsapp.meta.phone_number_id');
            $accessToken = config('whatsapp.meta.access_token');

            $response = Http::withToken($accessToken)
                ->post(self::API_BASE_URL."/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $this->normalizePhone($phone),
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('messages.0.id'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error.message') ?? 'API error',
                'error_code' => $response->json('error.code'),
            ];
        }

        return ['success' => false, 'error' => 'Freeform messages not supported for this provider'];
    }

    /**
     * Handle message status webhooks
     */
    protected function handleMessageWebhook(array $value): void
    {
        $statuses = $value['statuses'] ?? [];

        foreach ($statuses as $status) {
            $messageId = $status['id'] ?? null;
            $statusType = $status['status'] ?? null;

            if (! $messageId) {
                continue;
            }

            match ($statusType) {
                'sent' => Log::debug('WhatsApp message sent', ['id' => $messageId]),
                'delivered' => $this->markAsDelivered($messageId),
                'read' => $this->markAsRead($messageId),
                'failed' => $this->handleFailedStatus($messageId, $status),
                default => Log::debug('Unknown WhatsApp status', ['status' => $statusType]),
            };
        }

        // Handle incoming messages (for replies)
        $messages = $value['messages'] ?? [];
        foreach ($messages as $msg) {
            Log::info('Incoming WhatsApp message', [
                'from' => $msg['from'] ?? 'unknown',
                'type' => $msg['type'] ?? 'unknown',
            ]);
            // Could dispatch event or queue job for handling incoming messages
        }
    }

    /**
     * Handle template status update webhook
     */
    protected function handleTemplateStatusUpdate(array $value): void
    {
        $templateId = $value['message_template_id'] ?? null;
        $status = $value['event'] ?? null;
        $reason = $value['reason'] ?? null;

        if (! $templateId) {
            return;
        }

        $template = WhatsAppTemplate::findByTemplateId($templateId);

        if (! $template) {
            Log::warning('Template status update for unknown template', ['id' => $templateId]);

            return;
        }

        match ($status) {
            'APPROVED' => $template->markApproved(),
            'REJECTED' => $template->markRejected($reason ?? 'Rejected by Meta'),
            default => Log::info('Template status update', ['status' => $status]),
        };
    }

    /**
     * Handle failed message status
     */
    protected function handleFailedStatus(string $messageId, array $status): void
    {
        $log = SmsLog::findByProviderMessageId($messageId);

        if ($log) {
            $errors = $status['errors'] ?? [];
            $errorMessage = $errors[0]['title'] ?? 'Message failed';
            $errorCode = $errors[0]['code'] ?? null;

            $log->markFailed($errorMessage, $errorCode);

            Log::warning('WhatsApp message failed', [
                'message_id' => $messageId,
                'error' => $errorMessage,
                'code' => $errorCode,
            ]);
        }
    }

    /**
     * Sync a single template from Meta data
     */
    protected function syncTemplate(array $data): WhatsAppTemplate
    {
        $template = WhatsAppTemplate::updateOrCreate(
            ['template_id' => $data['id']],
            [
                'name' => $data['name'],
                'language' => $data['language'] ?? 'en',
                'category' => strtolower($data['category'] ?? 'utility'),
                'status' => strtolower($data['status'] ?? 'pending'),
                'content' => $this->extractTemplateContent($data),
                'header' => $this->extractTemplateHeader($data),
                'buttons' => $this->extractTemplateButtons($data),
                'last_synced_at' => now(),
            ]
        );

        if ($template->status === 'approved' && ! $template->approved_at) {
            $template->update(['approved_at' => now()]);
        }

        return $template;
    }

    /**
     * Extract body content from template components
     */
    protected function extractTemplateContent(array $data): string
    {
        $components = $data['components'] ?? [];

        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                return $component['text'] ?? '';
            }
        }

        return '';
    }

    /**
     * Extract header from template components
     */
    protected function extractTemplateHeader(array $data): ?array
    {
        $components = $data['components'] ?? [];

        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'HEADER') {
                return [
                    'type' => strtolower($component['format'] ?? 'text'),
                    'text' => $component['text'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Extract buttons from template components
     */
    protected function extractTemplateButtons(array $data): ?array
    {
        $components = $data['components'] ?? [];

        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'BUTTONS') {
                return $component['buttons'] ?? null;
            }
        }

        return null;
    }

    /**
     * Create a failed log entry
     */
    protected function createFailedLog(
        string $phone,
        ?User $user,
        string $type,
        string $error,
        ?string $templateId = null
    ): SmsLog {
        return SmsLog::create([
            'user_id' => $user?->id,
            'phone_number' => $this->normalizePhone($phone),
            'channel' => SmsLog::CHANNEL_WHATSAPP,
            'type' => $type,
            'content' => '',
            'template_id' => $templateId,
            'provider' => $this->getProvider(),
            'status' => SmsLog::STATUS_FAILED,
            'error_message' => $error,
            'failed_at' => now(),
        ]);
    }

    /**
     * Normalize phone number to E.164 without + prefix
     */
    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/[^\d]/', '', $phone);
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
     * Verify webhook signature (for Meta webhooks)
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $appSecret = config('whatsapp.meta.app_secret');

        if (! $appSecret) {
            return false;
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify webhook challenge (for webhook setup)
     */
    public function verifyWebhookChallenge(string $mode, string $token, string $challenge): ?string
    {
        $verifyToken = config('whatsapp.meta.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $challenge;
        }

        return null;
    }
}
