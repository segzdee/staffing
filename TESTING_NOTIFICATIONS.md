# Testing Real-time Notifications

## Setup Verification

### ✅ 1. Reverb Keys Generated
- REVERB_APP_ID: `982262`
- REVERB_APP_KEY: `qbkaewaad7gauyd4nldo`
- REVERB_APP_SECRET: `hahdpj6mpco1qqpr8i7l`
- BROADCAST_DRIVER: `reverb`
- REVERB_HOST: `localhost`
- REVERB_PORT: `8080`
- REVERB_SCHEME: `http`

### ✅ 2. Reverb Server
Start the server:
```bash
php artisan reverb:start
```

The server should be running on `http://localhost:8080`

### ✅ 3. Assets Compiled
Run:
```bash
npm run build
```

This compiles:
- `resources/js/bootstrap.js` (with Echo configuration)
- `resources/js/notifications.js` (toast functions)
- `resources/css/toast-notifications.css` (toast styles)

---

## Testing Methods

### Method 1: Create a Shift Notification (Recommended)

1. **Login as a user**
2. **Create or update a shift** that triggers a notification
3. **Check browser console** for WebSocket connection
4. **Verify toast appears** in top-right corner
5. **Check notification badge** updates

### Method 2: Use Tinker to Dispatch Events

```bash
php artisan tinker
```

Then run:
```php
// Create a test notification
$user = \App\Models\User::first();
$notification = \App\Models\ShiftNotification::create([
    'user_id' => $user->id,
    'type' => 'test',
    'title' => 'Test Notification',
    'message' => 'This is a test notification',
    'read' => false,
]);

// Dispatch the event
event(new \App\Events\NotificationCreated($notification));
```

### Method 3: Test via API Endpoint

```bash
# Get unread count (requires authentication)
curl -X GET http://localhost:8000/api/notifications/unread-count \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Method 4: Browser Console Testing

Open browser console and run:

```javascript
// Test toast function
showToast({
    title: 'Test Notification',
    message: 'This is a test message',
    type: 'success'
});

// Test badge update
updateNotificationBadge();
```

---

## Verification Checklist

### Frontend
- [ ] Toast container appears in top-right corner
- [ ] Toast shows correct icon (✅ ❌ ⚠️ ℹ️)
- [ ] Toast auto-dismisses after 5 seconds
- [ ] Toast can be dismissed by clicking
- [ ] Toast animations work smoothly
- [ ] Mobile responsive (test on mobile device)
- [ ] Dark mode styling works (if applicable)

### WebSocket Connection
- [ ] Browser console shows Echo connection
- [ ] No WebSocket errors in console
- [ ] Connection status shows "connected"
- [ ] Private channel subscription successful

### Events
- [ ] `NotificationCreated` event triggers toast
- [ ] `message.new` event triggers toast
- [ ] `application.status.changed` event triggers toast
- [ ] Badge updates when notification received

### API Endpoint
- [ ] `/api/notifications/unread-count` returns correct count
- [ ] Endpoint requires authentication
- [ ] Returns JSON: `{ "count": 5 }`

---

## Troubleshooting

### Reverb Server Not Starting
```bash
# Check if port 8080 is available
lsof -i :8080

# Kill process if needed
kill -9 <PID>

# Start Reverb again
php artisan reverb:start
```

### WebSocket Connection Failed
1. Check `.env` has correct Reverb keys
2. Verify Reverb server is running
3. Check browser console for errors
4. Verify `BROADCAST_DRIVER=reverb` in `.env`
5. Clear config cache: `php artisan config:clear`

### Toast Not Appearing
1. Check browser console for JavaScript errors
2. Verify `notifications.js` is loaded
3. Check `window.userId` is set
4. Verify `showToast` function exists: `typeof showToast`
5. Check CSS is loaded: Inspect element for `.toast-container`

### Badge Not Updating
1. Check API endpoint is accessible
2. Verify authentication token is valid
3. Check browser console for AJAX errors
4. Verify badge element exists in DOM
5. Test manually: `updateNotificationBadge()`

---

## Expected Behavior

### When Notification is Created:
1. **Event Dispatched**: `NotificationCreated` event fires
2. **WebSocket Broadcast**: Event sent to `user.{id}` channel
3. **Echo Receives**: Frontend Echo listener catches event
4. **Toast Appears**: `showToast()` displays notification
5. **Badge Updates**: `updateNotificationBadge()` fetches new count
6. **Badge Shows**: Unread count displayed in navbar

### Toast Appearance:
- **Position**: Top-right corner (mobile: full width)
- **Animation**: Slides in from right
- **Duration**: 5 seconds (auto-dismiss)
- **Dismissal**: Click anywhere or close button
- **Types**: Success (green), Error (red), Warning (yellow), Info (blue)

---

## Production Checklist

Before deploying to production:

- [ ] Change `REVERB_SCHEME` to `https`
- [ ] Update `REVERB_HOST` to production domain
- [ ] Use secure WebSocket (wss://)
- [ ] Set up process manager (Supervisor/PM2) for Reverb
- [ ] Configure firewall rules for port 8080
- [ ] Set up Redis for scaling (if needed)
- [ ] Test with multiple concurrent users
- [ ] Monitor WebSocket connections
- [ ] Set up error logging for Reverb

---

## Quick Test Script

Save this as `test-notification.php` in project root:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::first();
if (!$user) {
    echo "No users found. Create a user first.\n";
    exit(1);
}

$notification = \App\Models\ShiftNotification::create([
    'user_id' => $user->id,
    'type' => 'test',
    'title' => 'Test Notification',
    'message' => 'This is a test notification sent at ' . now(),
    'read' => false,
]);

event(new \App\Events\NotificationCreated($notification));

echo "Notification created and event dispatched!\n";
echo "User ID: {$user->id}\n";
echo "Check your browser if logged in as this user.\n";
```

Run: `php test-notification.php`
