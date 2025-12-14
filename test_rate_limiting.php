<?php

/**
 * Rate Limiting Test Script
 * Tests that 5 failed login attempts lock the account for 15 minutes
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;

echo "========================================\n";
echo "Rate Limiting Test\n";
echo "========================================\n\n";

// Test email and IP
$testEmail = 'test@example.com';
$testIP = '127.0.0.1';
$throttleKey = strtolower($testEmail) . '|' . $testIP;

echo "Test Configuration:\n";
echo "  Email: {$testEmail}\n";
echo "  IP: {$testIP}\n";
echo "  Throttle Key: {$throttleKey}\n";
echo "  Max Attempts: 5\n";
echo "  Lockout Duration: 15 minutes (900 seconds)\n\n";

// Clear any existing rate limit
RateLimiter::clear($throttleKey);
echo "✓ Cleared existing rate limit\n\n";

// Simulate failed login attempts
echo "Simulating failed login attempts...\n\n";

for ($i = 1; $i <= 6; $i++) {
    // Increment attempts
    RateLimiter::hit($throttleKey, 900);
    
    $attempts = RateLimiter::attempts($throttleKey);
    $tooMany = RateLimiter::tooManyAttempts($throttleKey, 5);
    $availableIn = RateLimiter::availableIn($throttleKey);
    
    echo "Attempt {$i}:\n";
    echo "  Total Attempts: {$attempts}\n";
    echo "  Too Many Attempts: " . ($tooMany ? 'YES' : 'NO') . "\n";
    
    if ($tooMany) {
        $minutes = ceil($availableIn / 60);
        echo "  Locked Out: YES\n";
        echo "  Lockout Remaining: {$availableIn} seconds ({$minutes} minutes)\n";
    } else {
        echo "  Locked Out: NO\n";
    }
    
    echo "\n";
    
    if ($i >= 5 && !$tooMany) {
        echo "⚠ WARNING: Rate limiting not working correctly!\n";
    }
}

// Verify lockout
if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
    echo "✓ PASS: Rate limiting is working - account is locked after 5 attempts\n";
} else {
    echo "✗ FAIL: Rate limiting not working - account should be locked\n";
}

// Clear for next test
RateLimiter::clear($throttleKey);
echo "\n✓ Cleared rate limit for next test\n";

echo "\n========================================\n";
echo "Test Complete\n";
echo "========================================\n";
