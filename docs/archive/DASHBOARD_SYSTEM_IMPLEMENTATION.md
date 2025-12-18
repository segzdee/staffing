# Unified Dashboard System - Implementation Guide

## Overview

A comprehensive, monochrome dashboard layout system has been implemented for all five user types (Worker, Business, Agency, Admin, AI Agent) in OvertimeStaff.

---

## Files Created/Modified

### Configuration
- **`config/dashboard.php`** - Role-based navigation configuration for all 5 user types

### Layouts
- **`resources/views/layouts/dashboard.blade.php`** - Unified dashboard layout (replaced old version)

### Components
- **`resources/views/components/dashboard/widget-card.blade.php`** - Standard card/widget container
- **`resources/views/components/dashboard/empty-state.blade.php`** - Empty state for lists
- **`resources/views/components/dashboard/quick-action.blade.php`** - Action button component

---

## Layout Features

### 1. **Collapsible Sidebar**
- Fixed left sidebar (264px width)
- Role-based navigation from `config/dashboard.php`
- User info section with avatar and role badge
- Active state highlighting (gray-900 background)
- Badge support for notification counts
- Optional "Quick Actions" section at bottom
- Mobile responsive with overlay

### 2. **Top Header**
- Sticky header with search bar
- Notification bell with badge indicator
- Messages icon with unread count
- User dropdown menu:
  - Profile link
  - Settings link
  - Logout button (POST form)

### 3. **Welcome Section**
- Personalized greeting with user name
- Role-specific subtitle
- Optional onboarding progress bar (shows if `$onboardingProgress < 100`)

### 4. **Metrics Grid**
- Responsive 4-column grid (collapses to 2x2 on tablet, stacks on mobile)
- Unified card styling:
  - White background
  - Gray border
  - Icon in top-right
  - Large number display
  - Optional subtitle
  - Optional trend indicator (up/down arrow with percentage)

### 5. **Content Area**
- Max-width container (7xl)
- Consistent padding across breakpoints
- Flash message support (success, error, validation)

### 6. **Form Styling**
- `.form-input` class: h-12, rounded-lg, gray-900 focus ring
- `.form-label` class: text-sm, font-medium
- `.form-error` class: text-sm, red-600

### 7. **Color Scheme (Monochrome)**
- **Primary**: gray-900
- **Text**: gray-600, gray-900
- **Backgrounds**: gray-50 (page), gray-100 (hover), white (cards)
- **Borders**: gray-200
- **No blue/purple/green gradients** - all removed

---

## Configuration Structure

### `config/dashboard.php`

```php
return [
    'navigation' => [
        'worker' => [...],      // Worker navigation items
        'business' => [...],    // Business navigation items
        'agency' => [...],      // Agency navigation items
        'admin' => [...],       // Admin navigation items
        'ai_agent' => [...],    // AI Agent navigation items
    ],
    'roles' => [
        'worker' => [
            'name' => 'Worker',
            'badge' => 'Shift Worker',
            'color' => 'gray-700',
        ],
        // ... other roles
    ],
];
```

**Navigation Item Structure**:
```php
[
    'icon' => 'M3 12l2-2...', // SVG path
    'label' => 'Dashboard',
    'route' => 'dashboard',
    'active' => ['dashboard', 'dashboard.index'], // Routes that highlight this nav item
    'badge' => 'pending_verifications', // Optional: variable name for badge count
]
```

---

## Component Usage

### Widget Card

```blade
<x-dashboard.widget-card
    title="Recent Shifts"
    :action="route('shifts.index')"
    actionLabel="View all"
    icon="M21 13.255A23.931..."
>
    <!-- Your content here -->
    @foreach($shifts as $shift)
        <div class="p-4 bg-gray-50 rounded-lg mb-3">
            <!-- Shift content -->
        </div>
    @endforeach
</x-dashboard.widget-card>
```

### Empty State

```blade
<x-dashboard.empty-state
    icon="M8 7V3m8 4V3..."
    title="No shifts found"
    description="Start browsing available shifts to get started."
    :actionUrl="route('shifts.index')"
    actionLabel="Browse Shifts"
/>
```

### Quick Action Button

```blade
<x-dashboard.quick-action
    :href="route('shifts.create')"
    icon="M12 4v16m8-8H4"
    variant="primary"
>
    Post New Shift
</x-dashboard.quick-action>

<x-dashboard.quick-action
    :href="route('workers.index')"
    icon="M17 20h5..."
    variant="secondary"
>
    Find Workers
</x-dashboard.quick-action>
```

---

## Dashboard View Implementation Pattern

### Basic Structure

```blade
@extends('layouts.dashboard')

@section('title', 'Worker Dashboard')

@section('page-title', 'Welcome back, {{ auth()->user()->name }}!')
@section('page-subtitle', 'Ready to find your next shift')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main content (2 columns) -->
    <div class="lg:col-span-2 space-y-6">
        <x-dashboard.widget-card
            title="Upcoming Shifts"
            :action="route('worker.assignments')"
        >
            <!-- Content -->
        </x-dashboard.widget-card>
    </div>

    <!-- Sidebar (1 column) -->
    <div class="space-y-6">
        <x-dashboard.widget-card title="Quick Actions">
            <div class="space-y-3">
                <x-dashboard.quick-action
                    :href="route('shifts.index')"
                    variant="primary"
                >
                    Browse Shifts
                </x-dashboard.quick-action>
            </div>
        </x-dashboard.widget-card>
    </div>
</div>
@endsection
```

### Passing Metrics to Layout

```php
// In your controller
return view('worker.dashboard', [
    'metrics' => [
        [
            'label' => 'This Week',
            'value' => $weekShifts,
            'subtitle' => 'Scheduled shifts',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'trend' => 15, // Optional: positive/negative percentage
        ],
        [
            'label' => 'Total Earned',
            'value' => '$'.number_format($earnings, 2),
            'subtitle' => 'All time',
            'icon' => 'M12 8c-1.657 0-3 .895-3 2...',
        ],
        // ... 2 more metrics for 4-column grid
    ],
    'onboardingProgress' => 75, // Optional: shows progress bar if < 100
    'unreadNotifications' => 3, // For notification badge
    'unreadMessages' => 5, // For messages badge
]);
```

### Adding Quick Actions (Optional)

```php
return view('business.dashboard', [
    'quickActions' => [
        [
            'label' => 'Post Shift',
            'url' => route('shifts.create'),
            'icon' => 'M12 4v16m8-8H4',
        ],
        [
            'label' => 'Find Workers',
            'url' => route('business.available-workers'),
            'icon' => 'M17 20h5v-2a3 3...',
        ],
    ],
]);
```

---

## Responsive Breakpoints

- **Mobile**: < 640px (sm) - Sidebar hidden, hamburger menu
- **Tablet**: 640px - 1024px (sm-lg) - 2x2 metric grid
- **Desktop**: ≥ 1024px (lg) - Sidebar visible, 4-column grid

---

## Migration Checklist

To migrate an existing dashboard to the new system:

### Step 1: Update Layout
Change from:
```blade
@extends('layouts.authenticated')
```
To:
```blade
@extends('layouts.dashboard')
```

### Step 2: Remove Old Navigation
Delete:
```blade
@section('sidebar-nav')
    <!-- old navigation -->
@endsection
```

### Step 3: Update Page Title
Change from:
```blade
@section('page-title', 'Dashboard')
```
To:
```blade
@section('page-title', 'Welcome back, {{ auth()->user()->name }}!')
@section('page-subtitle', 'Your role-specific message')
```

### Step 4: Remove Colored Gradients
Replace:
```blade
<div class="bg-gradient-to-r from-blue-500 to-blue-600 ...">
```
With:
```blade
<!-- Remove entirely or use monochrome -->
<div class="bg-white border border-gray-200 rounded-lg ...">
```

### Step 5: Update Metric Cards
Replace custom metric cards with the unified structure passed from controller (see "Passing Metrics" above).

### Step 6: Wrap Content in Components
Replace custom cards with `<x-dashboard.widget-card>` components.

### Step 7: Update Controller
Add `$metrics`, `$onboardingProgress`, `$unreadNotifications`, `$unreadMessages` to view data.

---

## Controller Pattern

### Example: `WorkerDashboardController.php`

```php
<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Calculate metrics
        $weekShifts = $user->assignedShifts()
            ->whereBetween('shift_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $totalEarnings = $user->payments()->sum('amount') / 100;

        $completedShifts = $user->assignedShifts()
            ->where('status', 'completed')
            ->count();

        // Get data for widgets
        $upcomingShifts = $user->assignedShifts()
            ->where('shift_date', '>=', now())
            ->orderBy('shift_date')
            ->limit(5)
            ->get();

        $recentApplications = $user->applications()
            ->latest()
            ->limit(3)
            ->get();

        // Profile completeness
        $profileCompleteness = $this->calculateProfileCompleteness($user);

        return view('worker.dashboard', [
            'metrics' => [
                [
                    'label' => 'This Week',
                    'value' => $weekShifts,
                    'subtitle' => 'Scheduled shifts',
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
                [
                    'label' => 'Upcoming',
                    'value' => $upcomingShifts->count(),
                    'subtitle' => 'Next 7 days',
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                ],
                [
                    'label' => 'Completed',
                    'value' => $completedShifts,
                    'subtitle' => 'Total shifts',
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
                [
                    'label' => 'Total Earned',
                    'value' => '$'.number_format($totalEarnings, 2),
                    'subtitle' => 'All time',
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
            ],
            'upcomingShifts' => $upcomingShifts,
            'recentApplications' => $recentApplications,
            'onboardingProgress' => $profileCompleteness,
            'unreadNotifications' => $user->unreadNotifications()->count(),
            'unreadMessages' => $user->unreadMessages()->count(),
        ]);
    }

    private function calculateProfileCompleteness($user)
    {
        $fields = ['name', 'email', 'phone', 'bio', 'skills'];
        $filled = collect($fields)->filter(fn($field) => !empty($user->$field))->count();
        return round(($filled / count($fields)) * 100);
    }
}
```

---

## Testing Checklist

- [ ] Sidebar displays correct navigation for each user type
- [ ] Active navigation item is highlighted
- [ ] Badge counts appear on nav items (if applicable)
- [ ] Hamburger menu works on mobile
- [ ] Sidebar closes when clicking overlay on mobile
- [ ] Search bar is visible on desktop, hidden on mobile
- [ ] Notifications badge shows unread count
- [ ] Messages badge shows unread count
- [ ] User dropdown works correctly
- [ ] Logout POST form works
- [ ] Metrics grid displays correctly (4 → 2 → 1 column)
- [ ] Onboarding progress shows when < 100%
- [ ] Flash messages display correctly
- [ ] All form inputs use unified styling
- [ ] No blue/purple/green gradients remain
- [ ] All dashboards use monochrome theme

---

## Color Reference

### Primary Colors
- **Gray-50**: `#F9FAFB` - Page background
- **Gray-100**: `#F3F4F6` - Hover states, secondary backgrounds
- **Gray-200**: `#E5E7EB` - Borders
- **Gray-600**: `#4B5563` - Secondary text, icons
- **Gray-700**: `#374151` - Labels
- **Gray-900**: `#111827` - Primary text, active states, buttons

### Status Colors (Minimal Use)
- **Green**: Success messages, positive trends
- **Red**: Errors, negative trends, delete actions
- **Yellow/Amber**: Warnings (use sparingly)

---

## Next Steps

1. ✅ Create `config/dashboard.php` with all navigation configs
2. ✅ Create unified `layouts/dashboard.blade.php`
3. ✅ Create reusable components (widget-card, empty-state, quick-action)
4. ⏳ Update Worker dashboard controller and view
5. ⏳ Update Business dashboard controller and view
6. ⏳ Update Agency dashboard controller and view
7. ⏳ Update Admin dashboard controller and view
8. ⏳ Update AI Agent dashboard controller and view
9. ⏳ Test all dashboards across different screen sizes
10. ⏳ Remove old `layouts/authenticated.blade.php` if no longer used

---

## Support

All navigation configuration is centralized in `config/dashboard.php`. To add/remove/modify navigation items, edit this file instead of individual views.

The layout automatically handles:
- Role-based navigation
- Active state detection
- Badge rendering
- Responsive behavior
- Monochrome styling

Simply pass the correct data from your controller and the layout handles the rest!
