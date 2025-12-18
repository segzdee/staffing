<?php

namespace App\Services;

use App\Models\PushNotificationLog;
use App\Models\PushNotificationToken;
use App\Models\User;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * COM-002: Push Notification Service
 *
 * Handles push notification delivery via FCM (Firebase Cloud Messaging),
 * APNs (Apple Push Notification Service), and Web Push.
 *
 * FCM is the primary integration as it handles both Android and iOS.
 */
class PushNotificationService
{
    /**
     * Cache key for FCM access token.
     */
    protected const FCM_TOKEN_CACHE_KEY = 'fcm_access_token';

    /**
     * FCM token scope.
     */
    protected const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /**
     * Register a push notification token for a user.
     *
     * @param  array<string, mixed>  $deviceInfo
     */
    public function registerToken(
        User $user,
        string $token,
        string $platform = 'fcm',
        array $deviceInfo = []
    ): PushNotificationToken {
        // Check if we've exceeded max tokens for this user
        $maxTokens = config('firebase.tokens.max_per_user', 10);
        $existingCount = PushNotificationToken::forUser($user->id)->active()->count();

        if ($existingCount >= $maxTokens) {
            // Remove oldest token to make room
            PushNotificationToken::forUser($user->id)
                ->active()
                ->oldest()
                ->first()
                ?->deactivate();
        }

        // Create or update the token
        $pushToken = PushNotificationToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $token,
            ],
            [
                'platform' => $platform,
                'device_id' => $deviceInfo['device_id'] ?? null,
                'device_name' => $deviceInfo['device_name'] ?? null,
                'device_model' => $deviceInfo['device_model'] ?? null,
                'app_version' => $deviceInfo['app_version'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        Log::info('Push notification token registered', [
            'user_id' => $user->id,
            'platform' => $platform,
            'token_id' => $pushToken->id,
        ]);

        return $pushToken;
    }

    /**
     * Remove a push notification token.
     */
    public function removeToken(string $token): void
    {
        $deleted = PushNotificationToken::where('token', $token)->delete();

        if ($deleted) {
            Log::info('Push notification token removed', ['token_hash' => substr(md5($token), 0, 8)]);
        }
    }

    /**
     * Deactivate a push notification token (soft removal).
     */
    public function deactivateToken(string $token): void
    {
        $pushToken = PushNotificationToken::where('token', $token)->first();

        if ($pushToken) {
            $pushToken->deactivate();
            Log::info('Push notification token deactivated', ['token_id' => $pushToken->id]);
        }
    }

    /**
     * Send a push notification to a user (all their active tokens).
     *
     * @param  array<string, mixed>  $data
     */
    public function send(
        User $user,
        string $title,
        string $body,
        array $data = []
    ): ?PushNotificationLog {
        $tokens = PushNotificationToken::forUser($user->id)
            ->active()
            ->get();

        if ($tokens->isEmpty()) {
            Log::debug('No active push tokens for user', ['user_id' => $user->id]);

            return null;
        }

        $lastLog = null;

        foreach ($tokens as $token) {
            $lastLog = $this->sendToToken($token, $title, $body, $data);
        }

        return $lastLog;
    }

    /**
     * Send a push notification to a specific token.
     *
     * @param  array<string, mixed>  $data
     */
    public function sendToToken(
        PushNotificationToken $token,
        string $title,
        string $body,
        array $data = []
    ): PushNotificationLog {
        // Create log entry
        $log = PushNotificationLog::create([
            'user_id' => $token->user_id,
            'token_id' => $token->id,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'platform' => $token->platform,
            'status' => PushNotificationLog::STATUS_PENDING,
        ]);

        try {
            // Route to appropriate platform handler
            $result = match ($token->platform) {
                PushNotificationToken::PLATFORM_FCM => $this->sendViaFCM($token, $title, $body, $data),
                PushNotificationToken::PLATFORM_APNS => $this->sendViaAPNs($token, $title, $body, $data),
                PushNotificationToken::PLATFORM_WEB => $this->sendViaWebPush($token, $title, $body, $data),
                default => throw new \InvalidArgumentException("Unsupported platform: {$token->platform}"),
            };

            if ($result['success']) {
                $log->markAsSent($result['message_id'] ?? 'unknown');
                $token->markAsUsed();

                if (config('firebase.logging.log_success', false)) {
                    Log::info('Push notification sent', [
                        'log_id' => $log->id,
                        'user_id' => $token->user_id,
                        'platform' => $token->platform,
                    ]);
                }
            } else {
                $log->markAsFailed($result['error'] ?? 'Unknown error');
                $this->handleSendFailure($token, $result['error'] ?? 'Unknown error');
            }

        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            $this->handleSendFailure($token, $e->getMessage());

            Log::error('Push notification failed', [
                'log_id' => $log->id,
                'user_id' => $token->user_id,
                'error' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Send bulk push notifications to multiple users.
     *
     * @param  Collection<int, User>  $users
     * @param  array<string, mixed>  $data
     * @return int Number of notifications sent successfully
     */
    public function sendBulk(
        Collection $users,
        string $title,
        string $body,
        array $data = []
    ): int {
        $successCount = 0;

        // Get all active tokens for these users
        $userIds = $users->pluck('id')->toArray();
        $tokens = PushNotificationToken::whereIn('user_id', $userIds)
            ->active()
            ->get();

        // Group tokens by user for rate limiting
        $tokensByUser = $tokens->groupBy('user_id');

        foreach ($tokensByUser as $userId => $userTokens) {
            foreach ($userTokens as $token) {
                $log = $this->sendToToken($token, $title, $body, $data);
                if ($log->wasSent()) {
                    $successCount++;
                }
            }
        }

        Log::info('Bulk push notifications sent', [
            'total_users' => $users->count(),
            'total_tokens' => $tokens->count(),
            'successful' => $successCount,
        ]);

        return $successCount;
    }

    /**
     * Send notification via Firebase Cloud Messaging (FCM) HTTP v1 API.
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message_id?: string, error?: string}
     */
    public function sendViaFCM(
        PushNotificationToken $token,
        string $title,
        string $body,
        array $data = []
    ): array {
        if (! config('firebase.fcm.enabled', true)) {
            return ['success' => false, 'error' => 'FCM is disabled'];
        }

        $accessToken = $this->getFCMAccessToken();
        if (! $accessToken) {
            return ['success' => false, 'error' => 'Failed to obtain FCM access token'];
        }

        $projectId = config('firebase.project_id');
        if (! $projectId) {
            return ['success' => false, 'error' => 'Firebase project ID not configured'];
        }

        // Build the FCM message
        $message = [
            'message' => [
                'token' => $token->token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ],
        ];

        // Add data payload if provided
        if (! empty($data)) {
            // FCM requires all data values to be strings
            $message['message']['data'] = array_map('strval', $data);
        }

        // Add Android-specific configuration
        $message['message']['android'] = [
            'priority' => config('firebase.fcm.android.priority', 'high'),
            'notification' => [
                'channel_id' => config('firebase.fcm.android.default_channel_id', 'overtimestaff_default'),
                'sound' => config('firebase.fcm.default_sound', 'default'),
                'click_action' => config('firebase.fcm.android.click_action', 'FLUTTER_NOTIFICATION_CLICK'),
            ],
        ];

        // Add APNs-specific configuration (for iOS through FCM)
        $message['message']['apns'] = [
            'payload' => [
                'aps' => [
                    'alert' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'sound' => config('firebase.fcm.apns.sound', 'default'),
                    'content-available' => config('firebase.fcm.apns.content_available', true) ? 1 : 0,
                ],
            ],
        ];

        // Add webpush-specific configuration
        $message['message']['webpush'] = [
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => config('firebase.fcm.webpush.icon'),
                'badge' => config('firebase.fcm.webpush.badge'),
            ],
        ];

        $url = str_replace('{project}', $projectId, config('firebase.fcm.api_url'));

        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post($url, $message);

            if ($response->successful()) {
                $responseData = $response->json();

                return [
                    'success' => true,
                    'message_id' => $responseData['name'] ?? 'unknown',
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? $response->body();

            // Check for invalid token errors
            if ($this->isInvalidTokenError($errorData)) {
                $this->handleInvalidToken($token);
            }

            return [
                'success' => false,
                'error' => "FCM Error ({$response->status()}): {$errorMessage}",
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "FCM Exception: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Send notification via APNs (direct, not through FCM).
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message_id?: string, error?: string}
     */
    public function sendViaAPNs(
        PushNotificationToken $token,
        string $title,
        string $body,
        array $data = []
    ): array {
        if (! config('firebase.apns.enabled', false)) {
            // Fall back to FCM for iOS
            return $this->sendViaFCM($token, $title, $body, $data);
        }

        // Direct APNs implementation
        // This requires the APNs authentication key (.p8 file)
        $bundleId = config('firebase.apns.bundle_id');
        $keyFile = config('firebase.apns.key_file');
        $keyId = config('firebase.apns.key_id');
        $teamId = config('firebase.apns.team_id');

        if (! $bundleId || ! $keyId || ! $teamId) {
            return ['success' => false, 'error' => 'APNs not properly configured'];
        }

        if (! file_exists($keyFile)) {
            return ['success' => false, 'error' => 'APNs key file not found'];
        }

        try {
            // Build APNs JWT token
            $jwt = $this->buildAPNsJWT($keyFile, $keyId, $teamId);

            // Build payload
            $payload = [
                'aps' => [
                    'alert' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'sound' => 'default',
                    'badge' => 1,
                ],
            ];

            if (! empty($data)) {
                $payload = array_merge($payload, $data);
            }

            // Determine APNs endpoint
            $environment = config('firebase.apns.environment', 'production');
            $apnsHost = $environment === 'production'
                ? 'api.push.apple.com'
                : 'api.sandbox.push.apple.com';

            $response = Http::withHeaders([
                'authorization' => "bearer {$jwt}",
                'apns-topic' => $bundleId,
                'apns-push-type' => 'alert',
                'apns-priority' => '10',
            ])
                ->timeout(30)
                ->post("https://{$apnsHost}:443/3/device/{$token->token}", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->header('apns-id') ?? uniqid('apns_'),
                ];
            }

            return [
                'success' => false,
                'error' => "APNs Error ({$response->status()}): {$response->body()}",
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "APNs Exception: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Send notification via Web Push.
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message_id?: string, error?: string}
     */
    public function sendViaWebPush(
        PushNotificationToken $token,
        string $title,
        string $body,
        array $data = []
    ): array {
        if (! config('firebase.webpush.enabled', true)) {
            return ['success' => false, 'error' => 'Web Push is disabled'];
        }

        // For web push, we can use FCM if the token is a FCM token
        // Otherwise, implement VAPID-based web push
        $vapidPublicKey = config('firebase.webpush.vapid.public_key');
        $vapidPrivateKey = config('firebase.webpush.vapid.private_key');

        if (! $vapidPublicKey || ! $vapidPrivateKey) {
            // Fall back to FCM for web
            return $this->sendViaFCM($token, $title, $body, $data);
        }

        // Web Push implementation using VAPID
        try {
            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'icon' => config('firebase.fcm.webpush.icon'),
                'badge' => config('firebase.fcm.webpush.badge'),
                'data' => $data,
            ]);

            // The token for web push is typically a JSON object containing
            // endpoint, keys.p256dh, and keys.auth
            $subscription = json_decode($token->token, true);

            if (! $subscription || ! isset($subscription['endpoint'])) {
                // It's a regular FCM token, use FCM
                return $this->sendViaFCM($token, $title, $body, $data);
            }

            // Use web-push library if available
            // For now, fall back to FCM
            return $this->sendViaFCM($token, $title, $body, $data);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Web Push Exception: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Handle delivery receipt from FCM.
     */
    public function handleDeliveryReceipt(string $messageId, string $status): void
    {
        $log = PushNotificationLog::byMessageId($messageId)->first();

        if (! $log) {
            Log::warning('Delivery receipt for unknown message', ['message_id' => $messageId]);

            return;
        }

        match ($status) {
            'delivered' => $log->markAsDelivered(),
            'clicked', 'opened' => $log->markAsClicked(),
            'failed' => $log->markAsFailed('Delivery failed'),
            default => null,
        };

        Log::debug('Push notification delivery receipt processed', [
            'message_id' => $messageId,
            'status' => $status,
        ]);
    }

    /**
     * Clean up inactive tokens (not used within configured days).
     *
     * @return int Number of tokens removed
     */
    public function cleanupInactiveTokens(): int
    {
        $inactiveDays = config('firebase.tokens.inactive_days', 90);

        $count = PushNotificationToken::notUsedSince($inactiveDays)->delete();

        Log::info('Cleaned up inactive push tokens', [
            'count' => $count,
            'inactive_days' => $inactiveDays,
        ]);

        return $count;
    }

    /**
     * Get FCM access token (cached).
     */
    protected function getFCMAccessToken(): ?string
    {
        return Cache::remember(self::FCM_TOKEN_CACHE_KEY, 3500, function () {
            return $this->generateFCMAccessToken();
        });
    }

    /**
     * Generate a new FCM access token using service account credentials.
     */
    protected function generateFCMAccessToken(): ?string
    {
        $credentialsFile = config('firebase.credentials.file');

        if (! $credentialsFile || ! file_exists($credentialsFile)) {
            Log::error('Firebase credentials file not found', ['path' => $credentialsFile]);

            return null;
        }

        try {
            $credentials = new ServiceAccountCredentials(
                [self::FCM_SCOPE],
                $credentialsFile
            );

            $token = $credentials->fetchAuthToken();

            return $token['access_token'] ?? null;

        } catch (\Exception $e) {
            Log::error('Failed to generate FCM access token', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build APNs JWT for authentication.
     */
    protected function buildAPNsJWT(string $keyFile, string $keyId, string $teamId): string
    {
        $key = file_get_contents($keyFile);

        $header = [
            'alg' => 'ES256',
            'kid' => $keyId,
        ];

        $claims = [
            'iss' => $teamId,
            'iat' => time(),
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $claimsEncoded = $this->base64UrlEncode(json_encode($claims));

        $signature = '';
        $privateKey = openssl_pkey_get_private($key);
        openssl_sign("{$headerEncoded}.{$claimsEncoded}", $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$headerEncoded}.{$claimsEncoded}.{$signatureEncoded}";
    }

    /**
     * Base64 URL encode.
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Check if FCM error indicates an invalid token.
     *
     * @param  array<string, mixed>  $errorData
     */
    protected function isInvalidTokenError(array $errorData): bool
    {
        $errorCode = $errorData['error']['details'][0]['errorCode'] ?? '';

        return in_array($errorCode, [
            'UNREGISTERED',
            'INVALID_ARGUMENT',
            'NOT_FOUND',
        ]);
    }

    /**
     * Handle invalid token (deactivate it).
     */
    protected function handleInvalidToken(PushNotificationToken $token): void
    {
        if (config('firebase.tokens.auto_remove_invalid', true)) {
            $token->deactivate();
            Log::info('Invalid push token deactivated', ['token_id' => $token->id]);
        }
    }

    /**
     * Handle send failure.
     */
    protected function handleSendFailure(PushNotificationToken $token, string $error): void
    {
        if (config('firebase.logging.log_failures', true)) {
            Log::warning('Push notification send failure', [
                'token_id' => $token->id,
                'user_id' => $token->user_id,
                'platform' => $token->platform,
                'error' => $error,
            ]);
        }
    }

    /**
     * Get notification statistics for a user.
     *
     * @return array<string, mixed>
     */
    public function getUserStats(User $user): array
    {
        $logs = PushNotificationLog::forUser($user->id);

        return [
            'total_sent' => $logs->clone()->whereIn('status', ['sent', 'delivered', 'clicked'])->count(),
            'total_failed' => $logs->clone()->failed()->count(),
            'total_clicked' => $logs->clone()->clicked()->count(),
            'active_tokens' => PushNotificationToken::forUser($user->id)->active()->count(),
        ];
    }

    /**
     * Send a test notification to a user.
     */
    public function sendTest(User $user): ?PushNotificationLog
    {
        return $this->send(
            $user,
            'Test Notification',
            'This is a test notification from OvertimeStaff.',
            ['type' => 'test', 'timestamp' => now()->toIso8601String()]
        );
    }
}
