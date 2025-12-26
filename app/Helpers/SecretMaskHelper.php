<?php

namespace App\Helpers;

/**
 * Helper for masking secrets in views
 * SECURITY: Never display actual secret values
 */
class SecretMaskHelper
{
    /**
     * Mask a secret value for display
     * Returns masked version if value exists, empty string if not set
     *
     * @param  string|null  $value
     * @param  int  $visibleChars  Number of characters to show at the end
     * @return string
     */
    public static function mask(?string $value, int $visibleChars = 4): string
    {
        if (empty($value)) {
            return '';
        }

        $length = strlen($value);

        if ($length <= $visibleChars) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - $visibleChars).substr($value, -$visibleChars);
    }

    /**
     * Check if a value should be masked (is a secret)
     *
     * @param  string  $key
     * @return bool
     */
    public static function shouldMask(string $key): bool
    {
        $secretPatterns = [
            'secret',
            'password',
            'key',
            'token',
            'api_key',
            'webhook_secret',
            'private_key',
            'access_token',
        ];

        $lowerKey = strtolower($key);

        foreach ($secretPatterns as $pattern) {
            if (str_contains($lowerKey, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
