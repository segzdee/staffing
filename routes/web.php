<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| OvertimeStaff Web Routes - Clean Minimal Setup
|--------------------------------------------------------------------------
|
| This is a clean routes file for OvertimeStaff local development.
| Old Paxpally routes backed up to: routes/web.php.paxpally.backup
|
*/

// ============================================================================
// PUBLIC ROUTES - Working
// ============================================================================

// Homepage
Route::get('/', function() {
    $users = collect(); // Empty collection for layout compatibility
    return view('welcome', compact('users'));
})->name('home');

// Public Marketing Pages
Route::get('/features', function() {
    return view('public.features');
})->name('features');

// ============================================================================
// DEV ROUTES - Local/Development Only
// ============================================================================
if (app()->environment('local', 'development')) {
    Route::get('/dev/login/{type}', [App\Http\Controllers\Dev\DevLoginController::class, 'login'])
        ->name('dev.login')
        ->where('type', 'worker|business|agency|agent|admin');
    
    Route::match(['get', 'post'], '/dev/credentials', [App\Http\Controllers\Dev\DevLoginController::class, 'showCredentials'])
        ->name('dev.credentials');
}

Route::get('/pricing', function() {
    return view('public.pricing');
})->name('pricing');

Route::get('/about', function() {
    return view('public.about');
})->name('about');

Route::get('/contact', function() {
    return view('public.contact');
})->name('contact');

Route::get('/terms', function() {
    return view('public.terms');
})->name('terms');

Route::get('/privacy', function() {
    return view('public.privacy');
})->name('privacy');

// Contact Form Submission
Route::post('/contact', [App\Http\Controllers\HomeController::class, 'submitContact'])->name('contact.submit');

// Redirect /home to /
Route::get('home', function() {
    return redirect('/');
});

// Clear Cache (Development Only - Protected)
if (app()->environment('local', 'development')) {
    Route::get('/clear-cache', function() {
        Artisan::call('optimize:clear');
        return redirect()->back()->with('success', 'Cache cleared successfully!');
    })->middleware(['auth', 'admin'])->name('cache.clear');
}

// ============================================================================
// AUTHENTICATION ROUTES - Laravel Default
// ============================================================================
Auth::routes();

// ============================================================================
// LIVE MARKET API ROUTES - Throttled public endpoints
// ============================================================================
Route::prefix('api/market')->middleware('throttle:60,1')->group(function() {
    // Public market shifts endpoint
    Route::get('/', [App\Http\Controllers\LiveMarketController::class, 'index'])->name('api.market.index');

    // Demo activity simulation
    Route::get('/simulate', [App\Http\Controllers\LiveMarketController::class, 'simulateActivity'])->name('api.market.simulate');
});

// ============================================================================
// API ROUTES FOR AI AGENTS - Phase 6
// ============================================================================
// Note: API routes are in routes/api.php under 'agent' prefix
// Example: POST /api/agent/shifts, GET /api/agent/workers/search

// ============================================================================
// AUTHENTICATED USER ROUTES - OvertimeStaff Shift Marketplace
// ============================================================================
Route::middleware(['auth'])->group(function() {

    // Dashboard - redirect based on user type
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // ===== LIVE MARKET AUTH ROUTES (Worker & Agency) =====
    // Worker apply and instant claim
    Route::post('/shifts/{shift}/apply', [App\Http\Controllers\LiveMarketController::class, 'apply'])->name('market.apply')->middleware('worker');
    Route::post('/shifts/{shift}/claim', [App\Http\Controllers\LiveMarketController::class, 'instantClaim'])->name('market.claim')->middleware('worker');

    // Agency assign worker to shift
    Route::post('/shifts/{shift}/assign', [App\Http\Controllers\LiveMarketController::class, 'agencyAssign'])->name('market.assign')->middleware('agency');

    // ===== WORKER ROUTES =====
    Route::prefix('worker')->name('worker.')->middleware('worker')->group(function() {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Worker\DashboardController::class, 'index'])->name('dashboard');

        // Assignments
        Route::get('assignments', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'myAssignments'])->name('assignments');
        Route::get('assignments/{id}', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'showAssignment'])->name('assignments.show');
        Route::post('assignments/{id}/check-in', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'checkIn'])->name('assignments.checkIn');
        Route::post('assignments/{id}/check-out', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'checkOut'])->name('assignments.checkOut');

        // Applications
        Route::get('applications', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'myApplications'])->name('applications');
        Route::post('applications/apply/{shift_id}', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'apply'])->name('applications.apply');
        Route::delete('applications/{id}/withdraw', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'withdraw'])->name('applications.withdraw');

        // Calendar & Availability
        Route::get('calendar', [App\Http\Controllers\CalendarController::class, 'index'])->name('calendar');
        Route::get('calendar/data', [App\Http\Controllers\CalendarController::class, 'getCalendarData'])->name('calendar.data');
        Route::post('availability', [App\Http\Controllers\Worker\AvailabilityBroadcastController::class, 'store'])->name('availability.store');
        Route::post('availability/{id}/cancel', [App\Http\Controllers\Worker\AvailabilityBroadcastController::class, 'cancel'])->name('availability.cancel');
        Route::post('availability/{id}/extend', [App\Http\Controllers\Worker\AvailabilityBroadcastController::class, 'extend'])->name('availability.extend');

        // Blackout Dates
        Route::post('blackouts', [App\Http\Controllers\CalendarController::class, 'storeBlackout'])->name('blackouts.store');
        Route::delete('blackouts/{id}', [App\Http\Controllers\CalendarController::class, 'deleteBlackout'])->name('blackouts.delete');

        // Shift Swaps
        Route::get('swaps', [App\Http\Controllers\Shift\ShiftSwapController::class, 'index'])->name('swaps.index');
        Route::get('swaps/my', [App\Http\Controllers\Shift\ShiftSwapController::class, 'mySwaps'])->name('swaps.my');
        Route::get('swaps/create/{assignment_id}', [App\Http\Controllers\Shift\ShiftSwapController::class, 'create'])->name('swaps.create');
        Route::post('swaps/{assignment_id}/offer', [App\Http\Controllers\Shift\ShiftSwapController::class, 'store'])->name('swaps.offer');
        Route::get('swaps/{id}', [App\Http\Controllers\Shift\ShiftSwapController::class, 'show'])->name('swaps.show');
        Route::post('swaps/{id}/accept', [App\Http\Controllers\Shift\ShiftSwapController::class, 'accept'])->name('swaps.accept');
        Route::delete('swaps/{id}/cancel', [App\Http\Controllers\Shift\ShiftSwapController::class, 'cancel'])->name('swaps.cancel');
        Route::delete('swaps/{id}/withdraw', [App\Http\Controllers\Shift\ShiftSwapController::class, 'withdrawAcceptance'])->name('swaps.withdraw');

        // Profile & Badges
        Route::get('profile', [App\Http\Controllers\Worker\DashboardController::class, 'profile'])->name('profile');
        Route::get('profile/badges', [App\Http\Controllers\Worker\DashboardController::class, 'badges'])->name('profile.badges');

        // Ratings
        Route::get('shifts/{assignment}/rate', [App\Http\Controllers\RatingController::class, 'createWorkerRating'])->name('shifts.rate');
        Route::post('shifts/{assignment}/rate', [App\Http\Controllers\RatingController::class, 'storeWorkerRating'])->name('shifts.rate.store');

        // Recommended Shifts
        Route::get('recommended', [App\Http\Controllers\Shift\ShiftController::class, 'recommended'])->name('recommended');
    });

    // ===== BUSINESS ROUTES =====
    Route::prefix('business')->name('business.')->middleware('business')->group(function() {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Business\DashboardController::class, 'index'])->name('dashboard');

        // Shifts Management
        Route::get('shifts', [App\Http\Controllers\Business\ShiftManagementController::class, 'myShifts'])->name('shifts.index');
        Route::get('shifts/{id}', [App\Http\Controllers\Business\ShiftManagementController::class, 'show'])->name('shifts.show');
        Route::get('shifts/{id}/edit', [App\Http\Controllers\Shift\ShiftController::class, 'edit'])->name('shifts.edit');
        Route::post('shifts/{id}/duplicate', [App\Http\Controllers\Shift\ShiftController::class, 'duplicate'])->name('shifts.duplicate');
        Route::delete('shifts/{id}/cancel', [App\Http\Controllers\Shift\ShiftController::class, 'cancel'])->name('shifts.cancel');

        // Applications Management
        Route::get('shifts/{id}/applications', [App\Http\Controllers\Business\ShiftManagementController::class, 'viewApplications'])->name('shifts.applications');
        Route::post('applications/{id}/assign', [App\Http\Controllers\Business\ShiftManagementController::class, 'assignWorker'])->name('shifts.assignWorker');
        Route::delete('applications/{id}/unassign', [App\Http\Controllers\Business\ShiftManagementController::class, 'unassignWorker'])->name('shifts.unassignWorker');
        Route::post('applications/{id}/reject', [App\Http\Controllers\Business\ShiftManagementController::class, 'rejectApplication'])->name('applications.reject');

        // Shift Templates
        Route::get('templates', [App\Http\Controllers\Shift\ShiftTemplateController::class, 'index'])->name('templates.index');
        Route::post('templates', [App\Http\Controllers\Shift\ShiftTemplateController::class, 'store'])->name('templates.store');
        Route::post('templates/{id}/create-shifts', [App\Http\Controllers\Shift\ShiftTemplateController::class, 'createBulkShifts'])->name('templates.createShifts');
        Route::post('templates/{id}/duplicate', [App\Http\Controllers\Shift\ShiftTemplateController::class, 'duplicate'])->name('templates.duplicate');
        Route::post('templates/{id}/activate', [App\Http\Controllers\Shift\ShiftTemplateController::class, 'activate'])->name('templates.activate');
        Route::post('templates/{id}/deactivate', [App\Http\Controllers\Shift\ShiftTemplateController::class, 'deactivate'])->name('templates.deactivate');
        Route::delete('templates/{id}', [App\Http\Controllers\Shift\ShiftTemplateController::class, 'destroy'])->name('templates.delete');

        // Available Workers
        Route::get('available-workers', [App\Http\Controllers\Business\AvailableWorkersController::class, 'index'])->name('available-workers');
        Route::post('invite-worker', [App\Http\Controllers\Business\AvailableWorkersController::class, 'inviteWorker'])->name('invite-worker');

        // Analytics
        Route::get('analytics', [App\Http\Controllers\Business\ShiftManagementController::class, 'analytics'])->name('analytics');

        // Shift Swaps Management
        Route::get('swaps', [App\Http\Controllers\Shift\ShiftSwapController::class, 'businessSwaps'])->name('swaps.index');
        Route::post('swaps/{id}/approve', [App\Http\Controllers\Shift\ShiftSwapController::class, 'approve'])->name('swaps.approve');
        Route::post('swaps/{id}/reject', [App\Http\Controllers\Shift\ShiftSwapController::class, 'reject'])->name('swaps.reject');

        // Ratings
        Route::get('shifts/{assignment}/rate', [App\Http\Controllers\RatingController::class, 'createBusinessRating'])->name('shifts.rate');
        Route::post('shifts/{assignment}/rate', [App\Http\Controllers\RatingController::class, 'storeBusinessRating'])->name('shifts.rate.store');

        // Profile
        Route::get('profile', [App\Http\Controllers\Business\DashboardController::class, 'profile'])->name('profile');
    });

    // ===== GENERIC SHIFT ROUTES (Both Workers & Businesses) =====
    // Public shift browsing (all authenticated users)
    Route::get('shifts', [App\Http\Controllers\Shift\ShiftController::class, 'index'])->name('shifts.index');

    // Shift creation/editing (Business & Agency only - protected by middleware in controller)
    // IMPORTANT: Must come BEFORE shifts/{id} route
    Route::get('shifts/create', [App\Http\Controllers\Shift\ShiftController::class, 'create'])->name('shifts.create');
    Route::post('shifts', [App\Http\Controllers\Shift\ShiftController::class, 'store'])->name('shifts.store');

    // Show specific shift - must be last to avoid catching /create
    Route::get('shifts/{id}', [App\Http\Controllers\Shift\ShiftController::class, 'show'])->name('shifts.show');
    // Note: Edit/update routes are in /business/shifts/* with business middleware

    // ===== RATING ROUTES =====
    Route::post('ratings/{rating}/respond', [App\Http\Controllers\RatingController::class, 'respond'])->name('ratings.respond');

    // ===== MESSAGING ROUTES (Worker-Business Communication) =====
    Route::get('messages', [App\Http\Controllers\MessagesController::class, 'index'])->name('messages.index');
    Route::get('messages/{id}', [App\Http\Controllers\MessagesController::class, 'show'])->name('messages.show');
    Route::get('messages/business/{business_id}', [App\Http\Controllers\MessagesController::class, 'createWithBusiness'])->name('messages.business');
    Route::get('messages/worker/{worker_id}', [App\Http\Controllers\MessagesController::class, 'createWithWorker'])->name('messages.worker');
    Route::post('messages/send', [App\Http\Controllers\MessagesController::class, 'send'])->name('messages.send');
    Route::post('messages/{id}/archive', [App\Http\Controllers\MessagesController::class, 'archive'])->name('messages.archive');
    Route::post('messages/{id}/restore', [App\Http\Controllers\MessagesController::class, 'restore'])->name('messages.restore');
    Route::get('messages/unread/count', [App\Http\Controllers\MessagesController::class, 'unreadCount'])->name('messages.unread');

    // ===== SETTINGS ROUTES (All User Types) =====
    Route::get('settings', [App\Http\Controllers\User\SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/profile', [App\Http\Controllers\User\SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('settings/password', [App\Http\Controllers\User\SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::put('settings/notifications', [App\Http\Controllers\User\SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::delete('settings/account', [App\Http\Controllers\User\SettingsController::class, 'deleteAccount'])->name('settings.account.delete');

    // ===== NOTIFICATIONS ROUTES =====
    Route::get('notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::delete('notifications/{id}', [App\Http\Controllers\NotificationController::class, 'delete'])->name('notifications.delete');

    // ===== PROFILE COMPLETION & ONBOARDING ROUTES =====
    // Worker profile completion
    Route::get('/worker/profile/complete', function() {
        return redirect()->route('worker.profile')->with('info', 'Please complete your profile to continue.');
    })->name('worker.profile.complete');

    // Business profile completion
    Route::get('/business/profile/complete', function() {
        return redirect()->route('business.profile')->with('info', 'Please complete your business profile to continue.');
    })->name('business.profile.complete');

    // Business payment setup
    Route::get('/business/payment/setup', function() {
        return redirect()->route('settings.index')->with('info', 'Please configure your payment settings.');
    })->name('business.payment.setup');

    // Agency profile completion
    Route::get('/agency/profile/complete', function() {
        return redirect()->route('agency.dashboard')->with('info', 'Please complete your agency profile.');
    })->name('agency.profile.complete');

    // Agency verification pending
    Route::get('/agency/verification/pending', function() {
        return view('onboarding.verification-pending');
    })->name('agency.verification.pending');

    // User referrals page
    Route::get('/referrals', function() {
        return view('users.referrals');
    })->name('referrals');

    // Worker earnings page
    Route::get('/worker/earnings', function() {
        return view('worker.earnings');
    })->middleware('worker')->name('worker.earnings');

    // ===== AI AGENT ROUTES =====
    Route::prefix('agent')->name('agent.')->middleware('auth')->group(function() {
        Route::get('/dashboard', [App\Http\Controllers\Agent\DashboardController::class, 'index'])->name('dashboard');
    });

    // ===== AGENCY ROUTES (Optional - if enabled) =====
    Route::prefix('agency')->name('agency.')->middleware('agency')->group(function() {
        // Dashboard
        Route::get('/dashboard', [App\Http\Controllers\Agency\DashboardController::class, 'index'])->name('dashboard');

        // Clients Management (Live Market)
        Route::resource('clients', App\Http\Controllers\Agency\AgencyClientController::class);
        Route::get('clients/{client}/post-shift', [App\Http\Controllers\Agency\AgencyClientController::class, 'postShiftFor'])->name('clients.post-shift');
        Route::post('clients/{client}/shifts', [App\Http\Controllers\Agency\AgencyClientController::class, 'storeShift'])->name('clients.shifts.store');

        // Workers Management
        Route::get('workers', [App\Http\Controllers\Agency\ShiftManagementController::class, 'workers'])->name('workers.index');
        Route::post('workers/add', [App\Http\Controllers\Agency\ShiftManagementController::class, 'addWorker'])->name('workers.add');
        Route::delete('workers/{id}/remove', [App\Http\Controllers\Agency\ShiftManagementController::class, 'removeWorker'])->name('workers.remove');

        // Shifts Browsing & Assignment
        Route::get('shifts/browse', [App\Http\Controllers\Agency\ShiftManagementController::class, 'browseShifts'])->name('shifts.browse');
        Route::get('shifts/{id}', [App\Http\Controllers\Agency\ShiftManagementController::class, 'viewShift'])->name('shifts.view');
        Route::post('shifts/assign', [App\Http\Controllers\Agency\ShiftManagementController::class, 'assignWorker'])->name('shifts.assign');

        // Assignments & Placements
        Route::get('assignments', [App\Http\Controllers\Agency\ShiftManagementController::class, 'assignments'])->name('assignments');
        Route::get('placements', [App\Http\Controllers\Agency\ShiftManagementController::class, 'assignments'])->name('placements.index');
        Route::get('placements/create', [App\Http\Controllers\Agency\ShiftManagementController::class, 'createPlacement'])->name('placements.create');

        // Commission Tracking
        Route::get('commissions', [App\Http\Controllers\Agency\ShiftManagementController::class, 'commissions'])->name('commissions');

        // Analytics & Reports
        Route::get('analytics', [App\Http\Controllers\Agency\ShiftManagementController::class, 'analytics'])->name('analytics');
        Route::get('reports', [App\Http\Controllers\Agency\ShiftManagementController::class, 'analytics'])->name('reports');
        Route::get('shifts/available', [App\Http\Controllers\Agency\ShiftManagementController::class, 'browseShifts'])->name('shifts.available');
    });
});

// ============================================================================
// ADMIN ROUTES
// ============================================================================
Route::prefix('panel/admin')->middleware(['auth', 'admin'])->name('admin.')->group(function() {
    Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'admin'])->name('dashboard'); // Method is 'admin' not 'dashboard'
    Route::get('dashboard', [App\Http\Controllers\Admin\AdminController::class, 'admin']); // Alias for /panel/admin/dashboard
    Route::get('shifts', [App\Http\Controllers\Admin\ShiftManagementController::class, 'index'])->name('shifts.index');
    Route::get('users', [App\Http\Controllers\Admin\AdminController::class, 'users'])->name('users');
    Route::get('disputes', [App\Http\Controllers\Admin\ShiftPaymentController::class, 'disputes'])->name('disputes');
    Route::post('workers/{id}/verify', [App\Http\Controllers\Admin\AdminController::class, 'verifyWorker'])->name('workers.verify');
    Route::post('businesses/{id}/verify', [App\Http\Controllers\Admin\AdminController::class, 'verifyBusiness'])->name('businesses.verify');
});

// ============================================================================
// DEVELOPMENT INFO & DATABASE TESTING (Development Only)
// ============================================================================
if (app()->environment('local', 'development', 'testing')) {
    Route::prefix('dev')->group(function() {
        Route::get('/info', function() {
            return response()->json([
                'app' => config('app.name'),
                'env' => config('app.env'),
                'database' => [
                    'connection' => config('database.default'),
                    'host' => config('database.connections.mysql.host'),
                    'database' => config('database.connections.mysql.database'),
                ],
                'tables_migrated' => 28,
                'status' => 'ready',
            ]);
        })->name('dev.info');

        // Test database connection and user count
        Route::get('/db-test', function() {
            try {
                $userCount = \App\Models\User::count();
                $workers = \App\Models\User::where('user_type', 'worker')->count();
                $businesses = \App\Models\User::where('user_type', 'business')->count();

                return response()->json([
                    'database_connection' => 'OK',
                    'total_users' => $userCount,
                    'workers' => $workers,
                    'businesses' => $businesses,
                    'message' => $userCount === 0 ? 'No users found. Run: php artisan db:seed --class=OvertimeStaffSeeder' : 'Database has users'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'database_connection' => 'FAILED',
                    'error' => $e->getMessage()
                ], 500);
            }
        })->name('dev.db-test');

        // Create a test worker user (for quick testing)
        Route::get('/create-test-user', function() {
            try {
                // Check if user already exists
                $existing = \App\Models\User::where('email', 'test@example.com')->first();
                if ($existing) {
                    return response()->json([
                        'message' => 'Test user already exists',
                        'email' => 'test@example.com',
                        'password' => 'password'
                    ]);
                }

                // Create user
                $user = \App\Models\User::create([
                    'name' => 'Test Worker',
                    'email' => 'test@example.com',
                    'password' => \Hash::make('password'),
                    'user_type' => 'worker',
                    'role' => 'user',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);

                // Create worker profile
                \App\Models\WorkerProfile::create([
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'message' => 'Test user created successfully',
                    'email' => 'test@example.com',
                    'password' => 'password',
                    'user_id' => $user->id
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage()
                ], 500);
            }
        })->name('dev.create-test-user');
    });
}

// ============================================================================
// ERROR PAGE TEST ROUTES (Development Only)
// ============================================================================
if (app()->environment('local', 'development', 'testing')) {
    Route::get('/test-401', function() {
        abort(401, 'Unauthorized test');
    })->name('test.401');

    Route::get('/test-419', function() {
        abort(419, 'Page expired test');
    })->name('test.419');

    Route::get('/test-429', function() {
        abort(429, 'Too many requests test');
    })->name('test.429');

    Route::get('/test-403', function() {
        abort(403, 'Forbidden test');
    })->name('test.403');

    Route::get('/test-404', function() {
        abort(404, 'Not found test');
    })->name('test.404');

    Route::get('/test-500', function() {
        abort(500, 'Server error test');
    })->name('test.500');
}
