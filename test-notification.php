<?php
/**
 * Quick test script to dispatch a NotificationCreated event
 * 
 * Usage: php test-notification.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get first user (or specify user ID)
$userId = $argv[1] ?? null;
if ($userId) {
    $user = \App\Models\User::find($userId);
} else {
    $user = \App\Models\User::first();
}

if (!$user) {
    echo "❌ No users found. Create a user first.\n";
    exit(1);
}

echo "📧 Creating test notification for user: {$user->name} (ID: {$user->id})\n";

// Create a test notification
$notification = \App\Models\ShiftNotification::create([
    'user_id' => $user->id,
    'type' => 'test',
    'title' => '🧪 Test Notification',
    'message' => 'This is a test notification sent at ' . now()->format('Y-m-d H:i:s'),
    'read' => false,
]);

echo "✅ Notification created (ID: {$notification->id})\n";

// Dispatch the event
event(new \App\Events\NotificationCreated($notification));

echo "📡 Event dispatched!\n";
echo "\n";
echo "📋 Next steps:\n";
echo "   1. Make sure Reverb server is running: php artisan reverb:start\n";
echo "   2. Open browser and login as user ID: {$user->id}\n";
echo "   3. Check browser console for WebSocket connection\n";
echo "   4. You should see a toast notification appear!\n";
echo "\n";
