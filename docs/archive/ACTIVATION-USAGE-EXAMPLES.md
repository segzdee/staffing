# Business Activation - Usage Examples

## Controller Usage

### Check Activation Status in Controllers
```php
use App\Http\Controllers\Business\ActivationController;

class MyController extends Controller
{
    protected ActivationController $activationController;

    public function __construct(ActivationController $activationController)
    {
        $this->activationController = $activationController;
    }

    public function someAction(Request $request)
    {
        $business = $request->user()->businessProfile;

        // Get full activation status
        $status = $this->activationController->checkActivationRequirements($business);

        if (!$status['can_post_shifts']) {
            return response()->json([
                'error' => 'Account not activated',
                'completion' => $status['completion_percentage'],
                'next_step' => $status['next_step'],
            ], 403);
        }

        // Proceed with action...
    }
}
```

## Model Usage

### Check Activation in Business Logic
```php
$business = BusinessProfile::find($id);

// Quick checks
if ($business->isActivated()) {
    // Business is activated
}

if ($business->canPostShifts()) {
    // Can post shifts (activated + good standing + no blocks)
}

// Get completion percentage
$percentage = $business->getActivationCompletionPercentage();

if ($business->hasMetAllActivationRequirements()) {
    // All requirements met, ready for activation
}
```

### Update Activation Cache
```php
$requirementsStatus = [
    'email_verified' => ['met' => true],
    'profile_complete' => ['met' => true],
    'kyb_verified' => ['met' => false],
    'insurance_verified' => ['met' => false],
    'venue_created' => ['met' => true],
    'payment_verified' => ['met' => true],
];

$business->updateActivationStatus($requirementsStatus);
// Automatically calculates completion_percentage and stores in DB
```

### Get Cached Status
```php
// Returns null if cache expired (>1 hour old)
$cached = $business->getCachedActivationStatus();

if ($cached) {
    $percentage = $cached['completion_percentage'];
    $requirements = $cached['requirements_status'];
}
```

### Manage Blocked Reasons
```php
// Add a blocked reason
$business->addActivationBlockedReason(
    'payment_failed',
    'Last payment attempt failed',
    'critical'
);

// Clear all blocked reasons
$business->clearActivationBlockedReasons();
```

## Route Protection Examples

### Protect Individual Routes
```php
// In routes/web.php or routes/api.php
Route::post('/business/shifts', [ShiftController::class, 'store'])
    ->middleware(['auth', 'business', 'business.activated']);
```

### Protect Route Groups
```php
Route::middleware(['auth', 'business', 'business.activated'])->group(function() {
    Route::post('/shifts', [ShiftController::class, 'store']);
    Route::post('/templates', [TemplateController::class, 'store']);
    Route::post('/bulk-shifts', [BulkController::class, 'create']);
});
```

### Conditional Middleware
```php
Route::post('/shifts', [ShiftController::class, 'store'])
    ->middleware(['auth', 'business'])
    ->middleware('business.activated:except,draft'); // Custom conditional
```

## API Integration Examples

### JavaScript/Vue Frontend
```javascript
// Check if business can post shifts
async function checkCanPostShifts() {
    try {
        const response = await fetch('/api/business/activation/can-post-shifts', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.can_post) {
            // Show "Post Shift" button
        } else {
            // Show "Complete Activation" banner
            showActivationBanner(data.completion_percentage, data.next_step);
        }
    } catch (error) {
        console.error('Failed to check activation:', error);
    }
}

// Get full activation status
async function getActivationStatus() {
    const response = await fetch('/api/business/activation/status', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        }
    });

    const result = await response.json();
    const status = result.data;

    return {
        isActivated: status.is_activated,
        canActivate: status.can_activate,
        requirements: status.requirements,
        nextStep: status.next_step,
        completionPercentage: status.completion_percentage
    };
}

// Activate account
async function activateAccount() {
    try {
        const response = await fetch('/api/business/activation/activate', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            // Show success message
            showSuccessModal('Your account is now activated!');
            // Redirect to shift posting
            window.location.href = '/shifts/create';
        } else {
            // Show requirements that are not met
            showRequirementsModal(result.data.requirements);
        }
    } catch (error) {
        console.error('Failed to activate:', error);
    }
}
```

### React Example
```jsx
import { useState, useEffect } from 'react';

function ActivationGate({ children }) {
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        checkActivation();
    }, []);

    const checkActivation = async () => {
        const response = await fetch('/api/business/activation/can-post-shifts', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
            }
        });
        const data = await response.json();
        setStatus(data);
        setLoading(false);
    };

    if (loading) return <LoadingSpinner />;

    if (!status.can_post) {
        return (
            <ActivationRequired
                completionPercentage={status.completion_percentage}
                nextStep={status.next_step}
                requirements={status.requirements}
            />
        );
    }

    return children;
}

// Usage
<ActivationGate>
    <ShiftPostingForm />
</ActivationGate>
```

## Blade Template Examples

### Check Activation Status
```blade
@if($business->isActivated())
    <a href="{{ route('shifts.create') }}" class="btn btn-primary">
        Post New Shift
    </a>
@else
    <a href="{{ route('business.activation.status') }}" class="btn btn-warning">
        Complete Activation ({{ $business->getActivationCompletionPercentage() }}%)
    </a>
@endif
```

### Show Requirements Checklist
```blade
@php
    $activationController = app(\App\Http\Controllers\Business\ActivationController::class);
    $status = $activationController->checkActivationRequirements($business);
@endphp

<div class="activation-checklist">
    <h3>Activation Progress: {{ $status['completion_percentage'] }}%</h3>

    <div class="progress mb-4">
        <div class="progress-bar" style="width: {{ $status['completion_percentage'] }}%"></div>
    </div>

    @foreach($status['requirements'] as $key => $requirement)
        <div class="requirement-item {{ $requirement['met'] ? 'complete' : 'incomplete' }}">
            <i class="icon {{ $requirement['met'] ? 'check-circle' : 'circle' }}"></i>
            <div class="requirement-details">
                <h4>{{ $requirement['label'] }}</h4>
                <p>{{ $requirement['description'] }}</p>

                @if(!$requirement['met'])
                    <a href="{{ $requirement['action_url'] }}" class="btn btn-sm btn-primary">
                        {{ $requirement['action_text'] }}
                    </a>
                @endif
            </div>
        </div>
    @endforeach

    @if($status['can_activate'])
        <form action="{{ route('api.business.activation.activate') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success btn-lg">
                Activate My Account
            </button>
        </form>
    @endif
</div>
```

## Service Integration

### In FirstShiftWizardService
```php
use App\Http\Controllers\Business\ActivationController;

class FirstShiftWizardService
{
    protected ActivationController $activationController;

    public function __construct(ActivationController $activationController)
    {
        $this->activationController = $activationController;
    }

    public function canStartWizard(BusinessProfile $business): array
    {
        $status = $this->activationController->checkActivationRequirements($business);

        if (!$status['can_post_shifts']) {
            return [
                'allowed' => false,
                'reason' => 'activation_required',
                'completion' => $status['completion_percentage'],
                'next_step' => $status['next_step'],
            ];
        }

        return ['allowed' => true];
    }
}
```

## Job/Command Examples

### Check Activation in Background Jobs
```php
use App\Http\Controllers\Business\ActivationController;

class SendActivationReminders extends Command
{
    protected ActivationController $activationController;

    public function handle()
    {
        $businesses = BusinessProfile::whereHas('onboarding', function($q) {
            $q->where('is_activated', false);
        })->get();

        foreach ($businesses as $business) {
            $status = $this->activationController->checkActivationRequirements($business);

            if ($status['completion_percentage'] > 50 && $status['completion_percentage'] < 100) {
                // Send reminder email
                $business->user->notify(new ActivationReminderNotification($status));
            }
        }
    }
}
```

## Testing Examples

### Feature Test
```php
use App\Models\BusinessProfile;
use App\Models\User;

public function test_activation_status_returns_correct_data()
{
    $user = User::factory()->business()->create();
    $business = $user->businessProfile;

    $response = $this->actingAs($user)
        ->getJson('/api/business/activation/status');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'is_activated',
                'can_activate',
                'can_post_shifts',
                'completion_percentage',
                'requirements',
                'next_step',
            ]
        ]);
}

public function test_middleware_blocks_unactivated_business()
{
    $user = User::factory()->business()->create();

    // Try to post shift without activation
    $response = $this->actingAs($user)
        ->post('/shifts', ['title' => 'Test Shift']);

    $response->assertStatus(403);
}

public function test_activation_succeeds_when_requirements_met()
{
    $user = User::factory()->business()->create();
    $business = $user->businessProfile;

    // Set up all requirements
    $this->setupAllRequirements($business);

    $response = $this->actingAs($user)
        ->postJson('/api/business/activation/activate');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'is_activated' => true,
                'can_post_shifts' => true,
            ]
        ]);
}
```

## Webhook Integration

### Notify External Systems on Activation
```php
public function activateAccount(Request $request): JsonResponse
{
    // ... activation logic ...

    if ($activated) {
        // Trigger webhook
        event(new BusinessActivated($business));

        // Or direct webhook call
        Http::post(config('services.webhook.url'), [
            'event' => 'business.activated',
            'business_id' => $business->id,
            'activated_at' => now()->toIso8601String(),
        ]);
    }

    // ... return response ...
}
```

## Admin Panel Integration

### Admin Override Activation
```php
// In AdminController
public function forceActivateBusiness($businessId)
{
    $business = BusinessProfile::findOrFail($businessId);

    DB::transaction(function() use ($business) {
        $onboarding = $business->onboarding;

        if (!$onboarding) {
            $onboarding = BusinessOnboarding::create([
                'business_profile_id' => $business->id,
                'user_id' => $business->user_id,
            ]);
        }

        $onboarding->update([
            'is_activated' => true,
            'activated_at' => now(),
            'status' => 'completed',
        ]);

        $business->update([
            'can_post_shifts' => true,
            'account_in_good_standing' => true,
        ]);
    });

    Log::info('Business manually activated by admin', [
        'business_id' => $businessId,
        'admin_id' => auth()->id(),
    ]);

    return redirect()->back()->with('success', 'Business activated');
}
```

## Notification Examples

### Send Activation Complete Notification
```php
// In ActivationController after successful activation
use App\Notifications\BusinessActivatedNotification;

$business->user->notify(new BusinessActivatedNotification($business));
```

### Create Notification Class
```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BusinessActivatedNotification extends Notification
{
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Business Account is Now Active!')
            ->greeting('Congratulations!')
            ->line('Your business account has been fully activated.')
            ->line('You can now post shifts and access all platform features.')
            ->action('Post Your First Shift', route('shifts.create'))
            ->line('Thank you for completing the activation process!');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'business_activated',
            'message' => 'Your account is now active and ready to post shifts',
            'action_url' => route('shifts.create'),
        ];
    }
}
```
