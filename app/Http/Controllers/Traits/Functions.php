<?php

namespace App\Http\Controllers\Traits;

use App\Models\AdminSettings;

/**
 * Common utility functions used across controllers
 */
trait Functions
{
    /**
     * Get admin settings
     *
     * @return AdminSettings|null
     */
    protected function getSettings()
    {
        return AdminSettings::first();
    }

    /**
     * Format currency amount
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    protected function formatAmount($amount, $currency = 'USD')
    {
        $settings = $this->getSettings();
        $currencySymbol = $settings->currency_symbol ?? '$';

        return $currencySymbol . number_format($amount, 2);
    }

    /**
     * Convert amount from cents to dollars
     *
     * @param int $cents
     * @return float
     */
    protected function centsToAmount($cents)
    {
        return $cents / 100;
    }

    /**
     * Convert amount from dollars to cents
     *
     * @param float $amount
     * @return int
     */
    protected function amountToCents($amount)
    {
        return (int) round($amount * 100);
    }

    /**
     * Generate a unique transaction reference
     *
     * @param string $prefix
     * @return string
     */
    protected function generateTransactionRef($prefix = 'TXN')
    {
        return $prefix . '_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Check if user has reached rate limit
     *
     * @param string $key
     * @param int $maxAttempts
     * @param int $decayMinutes
     * @return bool
     */
    protected function isRateLimited($key, $maxAttempts = 5, $decayMinutes = 1)
    {
        $attempts = cache()->get($key, 0);

        if ($attempts >= $maxAttempts) {
            return true;
        }

        cache()->put($key, $attempts + 1, now()->addMinutes($decayMinutes));

        return false;
    }

    /**
     * Send JSON response
     *
     * @param bool $success
     * @param string|null $message
     * @param array $data
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonResponse($success, $message = null, $data = [], $status = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Validate file upload
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param array $allowedTypes
     * @param int $maxSizeKb
     * @return bool
     */
    protected function validateFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSizeKb = 5120)
    {
        if (!$file || !$file->isValid()) {
            return false;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            return false;
        }

        if ($file->getSize() > ($maxSizeKb * 1024)) {
            return false;
        }

        return true;
    }

    /**
     * Clean input string
     *
     * @param string $input
     * @return string
     */
    protected function cleanInput($input)
    {
        return trim(strip_tags($input));
    }

    /**
     * Generate random string
     *
     * @param int $length
     * @return string
     */
    protected function generateRandomString($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    protected function isAjax()
    {
        return request()->ajax() || request()->wantsJson();
    }

    /**
     * Get client IP address
     *
     * @return string|null
     */
    protected function getClientIp()
    {
        return request()->ip();
    }

    /**
     * Calculate percentage
     *
     * @param float $value
     * @param float $total
     * @return float
     */
    protected function calculatePercentage($value, $total)
    {
        if ($total == 0) {
            return 0;
        }

        return round(($value / $total) * 100, 2);
    }
}
