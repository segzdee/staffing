<?php

namespace App\Services;

use App\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Environment Update Service
 *
 * SECURITY: Whitelist-based environment variable updates with audit logging
 * Prevents arbitrary .env modifications
 */
class EnvironmentUpdateService
{
    /**
     * Whitelist of allowed environment variable keys
     * SECURITY: Only these keys can be updated via admin panel
     *
     * @var array<string>
     */
    protected const ALLOWED_KEYS = [
        // Application
        'APP_URL',
        'APP_NAME',
        'DEFAULT_LOCALE',
        'APP_DEBUG',
        'APP_ENV',

        // Storage/Filesystem
        'FILESYSTEM_DRIVER',
        'AWS_ACCESS_KEY_ID',
        'AWS_SECRET_ACCESS_KEY',
        'AWS_DEFAULT_REGION',
        'AWS_BUCKET',
        'DOS_ACCESS_KEY_ID',
        'DOS_SECRET_ACCESS_KEY',
        'DOS_DEFAULT_REGION',
        'DOS_BUCKET',
        'DOS_CDN',
        'WAS_ACCESS_KEY_ID',
        'WAS_SECRET_ACCESS_KEY',
        'WAS_DEFAULT_REGION',
        'WAS_BUCKET',
        'BACKBLAZE_ACCOUNT_ID',
        'BACKBLAZE_APP_KEY',
        'BACKBLAZE_BUCKET',
        'BACKBLAZE_BUCKET_ID',
        'BACKBLAZE_BUCKET_REGION',
        'VULTR_ACCESS_KEY',
        'VULTR_SECRET_KEY',
        'VULTR_REGION',
        'VULTR_BUCKET',

        // Mail
        'MAIL_MAILER',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',

        // Payment Gateways (handled separately in payment gateway controllers)
        'STRIPE_KEY',
        'STRIPE_SECRET',
        'STRIPE_WEBHOOK_SECRET',
        'FLW_PUBLIC_KEY',
        'FLW_SECRET_KEY',

        // Social Login
        'FACEBOOK_CLIENT_ID',
        'FACEBOOK_CLIENT_SECRET',
        'GOOGLE_CLIENT_ID',
        'GOOGLE_CLIENT_SECRET',
        'TWITTER_CLIENT_ID',
        'TWITTER_CLIENT_SECRET',

        // PWA
        'PWA_SHORT_NAME',
    ];

    /**
     * Update environment variables from request with whitelist validation.
     *
     * @param  array  $additionalAllowedKeys  Additional keys allowed for this specific update
     * @return array ['updated' => [], 'rejected' => [], 'errors' => []]
     */
    public function updateFromRequest(Request $request, array $additionalAllowedKeys = []): array
    {
        $allowedKeys = array_merge(self::ALLOWED_KEYS, $additionalAllowedKeys);
        $updated = [];
        $rejected = [];
        $errors = [];

        $input = $request->except(['_token', '_method']);

        foreach ($input as $key => $value) {
            // SECURITY: Only update whitelisted keys
            if (! in_array($key, $allowedKeys, true)) {
                $rejected[] = $key;
                Log::channel('admin')->warning('Environment variable update rejected (not whitelisted)', [
                    'admin_id' => auth()->id(),
                    'key' => $key,
                    'ip' => $request->ip(),
                ]);

                continue;
            }

            try {
                // SECURITY: Only update secret fields if new value provided
                if ($this->isSecretKey($key) && empty($value)) {
                    // Skip empty secret updates to preserve existing values
                    continue;
                }

                Helper::envUpdate($key, $value);
                $updated[] = $key;
            } catch (\Exception $e) {
                $errors[] = [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ];
                Log::channel('admin')->error('Environment variable update failed', [
                    'admin_id' => auth()->id(),
                    'key' => $key,
                    'error' => $e->getMessage(),
                    'ip' => $request->ip(),
                ]);
            }
        }

        // SECURITY: Audit log all environment updates
        if (! empty($updated)) {
            Log::channel('admin')->warning('Environment variables updated via admin panel', [
                'admin_id' => auth()->id(),
                'updated_keys' => $updated,
                'rejected_keys' => $rejected,
                'ip' => $request->ip(),
            ]);
        }

        return [
            'updated' => $updated,
            'rejected' => $rejected,
            'errors' => $errors,
        ];
    }

    /**
     * Check if a key is a secret that should be masked.
     */
    protected function isSecretKey(string $key): bool
    {
        $secretPatterns = [
            'SECRET',
            'PASSWORD',
            'KEY',
            'TOKEN',
            'APP_KEY',
        ];

        $upperKey = strtoupper($key);

        foreach ($secretPatterns as $pattern) {
            if (str_contains($upperKey, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get list of allowed keys for a specific context.
     *
     * @param  string  $context  Context name (storage, mail, social, etc.)
     */
    public function getAllowedKeysForContext(string $context): array
    {
        return match ($context) {
            'storage' => array_filter(self::ALLOWED_KEYS, fn ($key) => str_starts_with($key, 'FILESYSTEM_') || str_starts_with($key, 'AWS_') || str_starts_with($key, 'DOS_') || str_starts_with($key, 'WAS_') || str_starts_with($key, 'BACKBLAZE_') || str_starts_with($key, 'VULTR_')),
            'mail' => array_filter(self::ALLOWED_KEYS, fn ($key) => str_starts_with($key, 'MAIL_')),
            'social' => array_filter(self::ALLOWED_KEYS, fn ($key) => str_contains($key, 'CLIENT_ID') || str_contains($key, 'CLIENT_SECRET')),
            default => self::ALLOWED_KEYS,
        };
    }
}
