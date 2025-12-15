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
        ->where('type', 'worker|business|agency|admin');
    
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

// ============================================================================
// PUBLIC WORKER PROFILES - WKR-010 (No Authentication Required)
// ============================================================================
Route::get('/profile/{username}', [App\Http\Controllers\PublicProfileController::class, 'show'])->name('profile.public');
Route::get('/profile/{username}/portfolio/{itemId}', [App\Http\Controllers\PublicProfileController::class, 'portfolioItem'])->name('profile.portfolio');
Route::get('/workers', [App\Http\Controllers\PublicProfileController::class, 'searchWorkers'])->name('workers.search');
Route::get('/api/featured-workers', [App\Http\Controllers\PublicProfileController::class, 'featuredWorkers'])->name('api.featured-workers');

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
// TEAM INVITATION ROUTES - Public (BIZ-003)
// ============================================================================
Route::get('/team/invitation/{token}', [App\Http\Controllers\Business\TeamController::class, 'acceptInvitation'])
    ->name('team.invitation.accept');

Route::get('/team/dashboard', [App\Http\Controllers\Business\TeamController::class, 'dashboard'])
    ->middleware('auth')
    ->name('business.team.dashboard');

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
        // Live Market View
        Route::get('market', [App\Http\Controllers\LiveMarketController::class, 'marketView'])->name('market');

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
        Route::put('profile', [App\Http\Controllers\Worker\DashboardController::class, 'updateProfile'])->name('profile.update');
        Route::get('profile/badges', [App\Http\Controllers\Worker\DashboardController::class, 'badges'])->name('profile.badges');

        // Ratings
        Route::get('shifts/{assignment}/rate', [App\Http\Controllers\RatingController::class, 'createWorkerRating'])->name('shifts.rate');
        Route::post('shifts/{assignment}/rate', [App\Http\Controllers\RatingController::class, 'storeWorkerRating'])->name('shifts.rate.store');

        // Recommended Shifts
        Route::get('recommended', [App\Http\Controllers\Shift\ShiftController::class, 'recommended'])->name('recommended');

        // Quick Apply (alias for applications.apply - used in recommended shifts view)
        Route::post('apply/{shift_id}', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'apply'])->name('apply');

        // WKR-010: Portfolio & Showcase
        Route::prefix('portfolio')->name('portfolio.')->group(function() {
            Route::get('/', [App\Http\Controllers\Worker\PortfolioController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Worker\PortfolioController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Worker\PortfolioController::class, 'store'])->name('store');
            Route::get('/{portfolioItem}', [App\Http\Controllers\Worker\PortfolioController::class, 'show'])->name('show');
            Route::get('/{portfolioItem}/edit', [App\Http\Controllers\Worker\PortfolioController::class, 'edit'])->name('edit');
            Route::put('/{portfolioItem}', [App\Http\Controllers\Worker\PortfolioController::class, 'update'])->name('update');
            Route::delete('/{portfolioItem}', [App\Http\Controllers\Worker\PortfolioController::class, 'destroy'])->name('destroy');
            Route::put('/reorder', [App\Http\Controllers\Worker\PortfolioController::class, 'reorder'])->name('reorder');
            Route::post('/{portfolioItem}/featured', [App\Http\Controllers\Worker\PortfolioController::class, 'setFeatured'])->name('featured');
            Route::delete('/{portfolioItem}/featured', [App\Http\Controllers\Worker\PortfolioController::class, 'removeFeatured'])->name('featured.remove');
            Route::get('/analytics', [App\Http\Controllers\Worker\PortfolioController::class, 'analytics'])->name('analytics');
            Route::get('/preview', [App\Http\Controllers\Worker\PortfolioController::class, 'publicPreview'])->name('preview');
            Route::put('/visibility', [App\Http\Controllers\Worker\PortfolioController::class, 'toggleVisibility'])->name('visibility');
        });

        // WKR-010: Featured Status
        Route::get('profile/featured', [App\Http\Controllers\Worker\PortfolioController::class, 'showFeatured'])->name('profile.featured');
        Route::post('profile/featured', [App\Http\Controllers\Worker\PortfolioController::class, 'purchaseFeatured'])->name('profile.featured.purchase');

        // FIN-006: Penalties & Appeals
        Route::get('penalties', [App\Http\Controllers\Worker\AppealController::class, 'index'])->name('penalties.index');
        Route::get('appeals/create/{penaltyId}', [App\Http\Controllers\Worker\AppealController::class, 'create'])->name('appeals.create');
        Route::post('appeals/{penaltyId}', [App\Http\Controllers\Worker\AppealController::class, 'store'])->name('appeals.store');
        Route::get('appeals/{id}', [App\Http\Controllers\Worker\AppealController::class, 'show'])->name('appeals.show');
        Route::get('appeals/{id}/edit', [App\Http\Controllers\Worker\AppealController::class, 'edit'])->name('appeals.edit');
        Route::put('appeals/{id}', [App\Http\Controllers\Worker\AppealController::class, 'update'])->name('appeals.update');
        Route::post('appeals/{id}/evidence', [App\Http\Controllers\Worker\AppealController::class, 'addEvidence'])->name('appeals.evidence');
        Route::delete('appeals/{id}/evidence', [App\Http\Controllers\Worker\AppealController::class, 'removeEvidence'])->name('appeals.evidence.remove');
    });

    // ===== BUSINESS ROUTES =====
    Route::prefix('business')->name('business.')->middleware('business')->group(function() {
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

        // Analytics & Spend Monitoring (BIZ-008)
        Route::get('analytics', [App\Http\Controllers\Business\AnalyticsController::class, 'index'])->name('analytics');
        Route::get('analytics/trend-data', [App\Http\Controllers\Business\AnalyticsController::class, 'getTrendData'])->name('analytics.trend');
        Route::get('analytics/spend-by-role', [App\Http\Controllers\Business\AnalyticsController::class, 'getSpendByRole'])->name('analytics.role');
        Route::get('analytics/venue-comparison', [App\Http\Controllers\Business\AnalyticsController::class, 'getVenueComparison'])->name('analytics.venue');
        Route::get('analytics/budget-alerts', [App\Http\Controllers\Business\AnalyticsController::class, 'getBudgetAlerts'])->name('analytics.alerts');
        Route::get('analytics/cancellation-history', [App\Http\Controllers\Business\AnalyticsController::class, 'getCancellationHistory'])->name('analytics.cancellations');
        Route::get('analytics/export-pdf', [App\Http\Controllers\Business\AnalyticsController::class, 'exportPDF'])->name('analytics.export-pdf');
        Route::get('analytics/export-csv', [App\Http\Controllers\Business\AnalyticsController::class, 'exportCSV'])->name('analytics.export-csv');
        Route::get('analytics/export-excel', [App\Http\Controllers\Business\AnalyticsController::class, 'exportExcel'])->name('analytics.export-excel');

        // Shift Swaps Management
        Route::get('swaps', [App\Http\Controllers\Shift\ShiftSwapController::class, 'businessSwaps'])->name('swaps.index');
        Route::post('swaps/{id}/approve', [App\Http\Controllers\Shift\ShiftSwapController::class, 'approve'])->name('swaps.approve');
        Route::post('swaps/{id}/reject', [App\Http\Controllers\Shift\ShiftSwapController::class, 'reject'])->name('swaps.reject');

        // Ratings
        Route::get('shifts/{assignment}/rate', [App\Http\Controllers\RatingController::class, 'createBusinessRating'])->name('shifts.rate');
        Route::post('shifts/{assignment}/rate', [App\Http\Controllers\RatingController::class, 'storeBusinessRating'])->name('shifts.rate.store');

        // Profile
        Route::get('profile', [App\Http\Controllers\Business\DashboardController::class, 'profile'])->name('profile');

        // Team Management (BIZ-003)
        Route::prefix('team')->name('team.')->group(function() {
            Route::get('/', [App\Http\Controllers\Business\TeamController::class, 'index'])->name('index');
            Route::get('create', [App\Http\Controllers\Business\TeamController::class, 'create'])->name('create');
            Route::post('invite', [App\Http\Controllers\Business\TeamController::class, 'invite'])->name('invite');
            Route::get('{id}', [App\Http\Controllers\Business\TeamController::class, 'show'])->name('show');
            Route::get('{id}/edit', [App\Http\Controllers\Business\TeamController::class, 'edit'])->name('edit');
            Route::put('{id}', [App\Http\Controllers\Business\TeamController::class, 'update'])->name('update');
            Route::post('{id}/resend', [App\Http\Controllers\Business\TeamController::class, 'resendInvitation'])->name('resend');
            Route::post('{id}/suspend', [App\Http\Controllers\Business\TeamController::class, 'suspend'])->name('suspend');
            Route::post('{id}/reactivate', [App\Http\Controllers\Business\TeamController::class, 'reactivate'])->name('reactivate');
            Route::delete('{id}', [App\Http\Controllers\Business\TeamController::class, 'destroy'])->name('destroy');
        });

        // Alias for shifts.create (used in email templates)
        Route::get('shifts/create', function() {
            return redirect()->route('shifts.create');
        })->name('shifts.create');
    });

    // ===== GENERIC SHIFT ROUTES (Both Workers & Businesses) =====
    // Public shift browsing (all authenticated users)
    Route::get('shifts', [App\Http\Controllers\Shift\ShiftController::class, 'index'])->name('shifts.index');

    // Shift creation/editing (Business & Agency only - protected by middleware in controller)
    // IMPORTANT: Must come BEFORE shifts/{id} route
    Route::get('shifts/create', [App\Http\Controllers\Shift\ShiftController::class, 'create'])->name('shifts.create');
    Route::post('shifts', [App\Http\Controllers\Shift\ShiftController::class, 'store'])->name('shifts.store');
    Route::put('shifts/{id}', [App\Http\Controllers\Shift\ShiftController::class, 'update'])->name('shifts.update');

    // Show specific shift - must be last to avoid catching /create
    Route::get('shifts/{id}', [App\Http\Controllers\Shift\ShiftController::class, 'show'])->name('shifts.show');

    // Alias routes for backwards compatibility with views using alternate naming
    Route::get('shift/create', function() {
        return redirect()->route('shifts.create');
    })->name('shift.create');

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
    Route::get('/worker/profile/complete', [App\Http\Controllers\Worker\OnboardingController::class, 'completeProfile'])
        ->name('worker.profile.complete');

    // Business profile completion
    Route::get('/business/profile/complete', [App\Http\Controllers\Business\OnboardingController::class, 'completeProfile'])
        ->name('business.profile.complete');

    // Business payment setup
    Route::get('/business/payment/setup', [App\Http\Controllers\Business\OnboardingController::class, 'setupPayment'])
        ->name('business.payment.setup');

    // Agency profile completion
    Route::get('/agency/profile/complete', [App\Http\Controllers\Agency\OnboardingController::class, 'completeProfile'])
        ->name('agency.profile.complete');

    // Agency verification pending
    Route::get('/agency/verification/pending', [App\Http\Controllers\Agency\OnboardingController::class, 'verificationPending'])
        ->name('agency.verification.pending');

    // User referrals page
    Route::get('/referrals', function() {
        return view('users.referrals');
    })->name('referrals');

    // Worker earnings page
    Route::get('/worker/earnings', function() {
        return view('worker.earnings');
    })->middleware('worker')->name('worker.earnings');

    // ===== AGENCY ROUTES (Optional - if enabled) =====
    Route::prefix('agency')->name('agency.')->middleware('agency')->group(function() {
        // Profile Management
        Route::get('profile', [App\Http\Controllers\Agency\ProfileController::class, 'show'])->name('profile');
        Route::get('profile/edit', [App\Http\Controllers\Agency\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [App\Http\Controllers\Agency\ProfileController::class, 'update'])->name('profile.update');

        // Clients Management (Live Market)
        Route::resource('clients', App\Http\Controllers\Agency\AgencyClientController::class);
        Route::get('clients/{client}/post-shift', [App\Http\Controllers\Agency\AgencyClientController::class, 'postShiftFor'])->name('clients.post-shift');
        Route::post('clients/{client}/shifts', [App\Http\Controllers\Agency\AgencyClientController::class, 'storeShift'])->name('clients.shifts.store');

        // Workers Management
        Route::get('workers', [App\Http\Controllers\Agency\ShiftManagementController::class, 'workers'])->name('workers.index');
        Route::get('workers/add', [App\Http\Controllers\Agency\ShiftManagementController::class, 'showAddWorkerForm'])->name('workers.add');
        Route::post('workers/add', [App\Http\Controllers\Agency\ShiftManagementController::class, 'addWorker'])->name('workers.store');
        Route::delete('workers/{id}/remove', [App\Http\Controllers\Agency\ShiftManagementController::class, 'removeWorker'])->name('workers.remove');

        // Shifts Management & Browsing
        Route::get('shifts', [App\Http\Controllers\Agency\ShiftManagementController::class, 'index'])->name('shifts.index');
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

        // ===== STRIPE CONNECT ROUTES (AGY-003) =====
        Route::prefix('stripe')->name('stripe.')->group(function() {
            Route::get('onboarding', [App\Http\Controllers\Agency\StripeConnectController::class, 'onboarding'])->name('onboarding');
            Route::get('connect', [App\Http\Controllers\Agency\StripeConnectController::class, 'connect'])->name('connect');
            Route::get('callback', [App\Http\Controllers\Agency\StripeConnectController::class, 'callback'])->name('callback');
            Route::get('status', [App\Http\Controllers\Agency\StripeConnectController::class, 'status'])->name('status');
            Route::get('dashboard', [App\Http\Controllers\Agency\StripeConnectController::class, 'dashboard'])->name('dashboard');
            Route::post('refresh-status', [App\Http\Controllers\Agency\StripeConnectController::class, 'refreshStatus'])->name('refresh-status');
            Route::get('balance', [App\Http\Controllers\Agency\StripeConnectController::class, 'balance'])->name('balance');
        });
    });
});

// ============================================================================
// STRIPE CONNECT WEBHOOK ROUTES (AGY-003) - No CSRF verification
// ============================================================================
Route::post('/webhooks/stripe/connect', [App\Http\Controllers\Webhooks\StripeConnectWebhookController::class, 'handle'])
    ->name('webhooks.stripe.connect')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ============================================================================
// ADMIN ROUTES
// ============================================================================
Route::prefix('panel/admin')->middleware(['auth', 'admin'])->name('admin.')->group(function() {
    Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'admin'])->name('dashboard'); // Method is 'admin' not 'dashboard'
    Route::get('dashboard', [App\Http\Controllers\Admin\AdminController::class, 'admin']); // Alias for /panel/admin/dashboard

    // Admin Profile (redirects to settings for admins)
    Route::get('profile', function() {
        return redirect()->route('settings.index');
    })->name('profile');

    Route::get('shifts', [App\Http\Controllers\Admin\ShiftManagementController::class, 'index'])->name('shifts.index');
    Route::get('users', [App\Http\Controllers\Admin\AdminController::class, 'users'])->name('users');
    // Legacy disputes route (redirects to new dispute center)
    Route::get('disputes-legacy', [App\Http\Controllers\Admin\ShiftPaymentController::class, 'disputes'])->name('disputes.legacy');

    // ADM-002: Dispute Resolution Center with Automated Escalation
    Route::prefix('disputes')->name('disputes.')->group(function() {
        Route::get('/', [App\Http\Controllers\Admin\DisputeController::class, 'index'])->name('index');
        Route::get('/{id}', [App\Http\Controllers\Admin\DisputeController::class, 'show'])->name('show');
        Route::post('/{id}/assign', [App\Http\Controllers\Admin\DisputeController::class, 'assign'])->name('assign');
        Route::post('/{id}/status', [App\Http\Controllers\Admin\DisputeController::class, 'updateStatus'])->name('status');
        Route::post('/{id}/resolve', [App\Http\Controllers\Admin\DisputeController::class, 'resolve'])->name('resolve');
        Route::post('/{id}/close', [App\Http\Controllers\Admin\DisputeController::class, 'close'])->name('close');
        Route::post('/{id}/escalate', [App\Http\Controllers\Admin\DisputeController::class, 'escalate'])->name('escalate');
        Route::post('/{id}/message', [App\Http\Controllers\Admin\DisputeController::class, 'addMessage'])->name('message');
        Route::post('/{id}/evidence', [App\Http\Controllers\Admin\DisputeController::class, 'uploadEvidence'])->name('evidence');
        Route::get('/{id}/messages', [App\Http\Controllers\Admin\DisputeController::class, 'getMessages'])->name('messages');
        Route::get('/{id}/sla', [App\Http\Controllers\Admin\DisputeController::class, 'getSLAStatus'])->name('sla');

        // Bulk operations
        Route::post('/bulk-assign', [App\Http\Controllers\Admin\DisputeController::class, 'bulkAssign'])->name('bulk-assign');
        Route::post('/bulk-escalate', [App\Http\Controllers\Admin\DisputeController::class, 'bulkEscalate'])->name('bulk-escalate');
        Route::post('/bulk-close', [App\Http\Controllers\Admin\DisputeController::class, 'bulkClose'])->name('bulk-close');
    });

    Route::post('workers/{id}/verify', [App\Http\Controllers\Admin\AdminController::class, 'verifyWorker'])->name('workers.verify');
    Route::post('businesses/{id}/verify', [App\Http\Controllers\Admin\AdminController::class, 'verifyBusiness'])->name('businesses.verify');

    // Verification Queue Management (ADM-001)
    Route::prefix('verification-queue')->name('verification-queue.')->group(function() {
        Route::get('/', [App\Http\Controllers\Admin\VerificationQueueController::class, 'index'])->name('index');
        Route::get('/sla-stats', [App\Http\Controllers\Admin\VerificationQueueController::class, 'slaStats'])->name('sla-stats');
        Route::get('/queue-data', [App\Http\Controllers\Admin\VerificationQueueController::class, 'queueData'])->name('queue-data');
        Route::get('/{id}', [App\Http\Controllers\Admin\VerificationQueueController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [App\Http\Controllers\Admin\VerificationQueueController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [App\Http\Controllers\Admin\VerificationQueueController::class, 'reject'])->name('reject');
        Route::post('/bulk-approve', [App\Http\Controllers\Admin\VerificationQueueController::class, 'bulkApprove'])->name('bulk-approve');
        Route::post('/bulk-reject', [App\Http\Controllers\Admin\VerificationQueueController::class, 'bulkReject'])->name('bulk-reject');
    });

    // FIN-006: Appeal Review Management
    Route::prefix('appeals')->name('appeals.')->group(function() {
        Route::get('/', [App\Http\Controllers\Admin\AppealReviewController::class, 'index'])->name('index');
        Route::get('/{id}', [App\Http\Controllers\Admin\AppealReviewController::class, 'show'])->name('show');
        Route::post('/{id}/assign', [App\Http\Controllers\Admin\AppealReviewController::class, 'assignToMe'])->name('assign');
        Route::get('/{id}/approve', [App\Http\Controllers\Admin\AppealReviewController::class, 'approveForm'])->name('approve.form');
        Route::post('/{id}/approve', [App\Http\Controllers\Admin\AppealReviewController::class, 'approve'])->name('approve');
        Route::get('/{id}/reject', [App\Http\Controllers\Admin\AppealReviewController::class, 'rejectForm'])->name('reject.form');
        Route::post('/{id}/reject', [App\Http\Controllers\Admin\AppealReviewController::class, 'reject'])->name('reject');
        Route::post('/{id}/notes', [App\Http\Controllers\Admin\AppealReviewController::class, 'addNotes'])->name('notes');
    });

    // FIN-006: Penalty Management
    Route::prefix('penalties')->name('penalties.')->group(function() {
        Route::get('/', [App\Http\Controllers\Admin\AppealReviewController::class, 'penaltyIndex'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\AppealReviewController::class, 'penaltyCreate'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\AppealReviewController::class, 'penaltyStore'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Admin\AppealReviewController::class, 'penaltyShow'])->name('show');
        Route::post('/{id}/waive', [App\Http\Controllers\Admin\AppealReviewController::class, 'penaltyWaive'])->name('waive');
    });

    // Platform Configuration Management (ADM-003)
    Route::prefix('configuration')->name('configuration.')->group(function() {
        Route::get('/', [App\Http\Controllers\Admin\ConfigurationController::class, 'index'])->name('index');
        Route::put('/', [App\Http\Controllers\Admin\ConfigurationController::class, 'update'])->name('update');
        Route::get('/history', [App\Http\Controllers\Admin\ConfigurationController::class, 'history'])->name('history');
        Route::get('/history/{key}', [App\Http\Controllers\Admin\ConfigurationController::class, 'settingHistory'])->name('setting-history');
        Route::post('/reset/{key}', [App\Http\Controllers\Admin\ConfigurationController::class, 'reset'])->name('reset');
        Route::post('/reset-all', [App\Http\Controllers\Admin\ConfigurationController::class, 'resetAll'])->name('reset-all');
        Route::get('/export', [App\Http\Controllers\Admin\ConfigurationController::class, 'export'])->name('export');
        Route::post('/import', [App\Http\Controllers\Admin\ConfigurationController::class, 'import'])->name('import');
        Route::post('/clear-cache', [App\Http\Controllers\Admin\ConfigurationController::class, 'clearCache'])->name('clear-cache');
        Route::put('/{key}', [App\Http\Controllers\Admin\ConfigurationController::class, 'updateSingle'])->name('update-single');
    });

    // System Health Monitoring (ADM-004)
    Route::prefix('system-health')->name('system-health.')->group(function() {
        Route::get('/', [App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('index');
        Route::get('/realtime-metrics', [App\Http\Controllers\Admin\SystemHealthController::class, 'getRealtimeMetrics'])->name('metrics');
        Route::get('/metric-history/{metricType}', [App\Http\Controllers\Admin\SystemHealthController::class, 'getMetricHistory'])->name('metric-history');
        Route::get('/incidents', [App\Http\Controllers\Admin\SystemHealthController::class, 'incidents'])->name('incidents');
        Route::get('/incidents/{id}', [App\Http\Controllers\Admin\SystemHealthController::class, 'showIncident'])->name('incidents.show');
        Route::post('/incidents/{id}/acknowledge', [App\Http\Controllers\Admin\SystemHealthController::class, 'acknowledgeIncident'])->name('incidents.acknowledge');
        Route::post('/incidents/{id}/resolve', [App\Http\Controllers\Admin\SystemHealthController::class, 'resolveIncident'])->name('incidents.resolve');
        Route::post('/incidents/{id}/assign', [App\Http\Controllers\Admin\SystemHealthController::class, 'assignIncident'])->name('incidents.assign');
        Route::put('/incidents/{id}/severity', [App\Http\Controllers\Admin\SystemHealthController::class, 'updateIncidentSeverity'])->name('incidents.severity');
        Route::get('/incident-stats', [App\Http\Controllers\Admin\SystemHealthController::class, 'getIncidentStats'])->name('incidents.stats');
        Route::post('/test-alert', [App\Http\Controllers\Admin\SystemHealthController::class, 'testAlert'])->name('test-alert');
    });

    // External Alerting Configuration (ADM-004)
    Route::prefix('alerting')->name('alerting.')->group(function() {
        Route::get('/', [App\Http\Controllers\Admin\AlertingController::class, 'index'])->name('index');
        Route::put('/slack', [App\Http\Controllers\Admin\AlertingController::class, 'updateSlack'])->name('slack');
        Route::put('/pagerduty', [App\Http\Controllers\Admin\AlertingController::class, 'updatePagerDuty'])->name('pagerduty');
        Route::put('/email', [App\Http\Controllers\Admin\AlertingController::class, 'updateEmail'])->name('email');
        Route::post('/test-slack', [App\Http\Controllers\Admin\AlertingController::class, 'testSlack'])->name('test-slack');
        Route::post('/test-pagerduty', [App\Http\Controllers\Admin\AlertingController::class, 'testPagerDuty'])->name('test-pagerduty');
        Route::get('/history', [App\Http\Controllers\Admin\AlertingController::class, 'history'])->name('history');
        Route::get('/statistics', [App\Http\Controllers\Admin\AlertingController::class, 'statistics'])->name('statistics');
        Route::get('/integrations/status', [App\Http\Controllers\Admin\AlertingController::class, 'integrationsStatus'])->name('integrations.status');
        Route::post('/seed-defaults', [App\Http\Controllers\Admin\AlertingController::class, 'seedDefaults'])->name('seed-defaults');

        // Alert Configurations
        Route::post('/configurations', [App\Http\Controllers\Admin\AlertingController::class, 'storeConfiguration'])->name('configurations.store');
        Route::put('/configurations/{id}', [App\Http\Controllers\Admin\AlertingController::class, 'updateConfiguration'])->name('configurations.update');
        Route::delete('/configurations/{id}', [App\Http\Controllers\Admin\AlertingController::class, 'destroyConfiguration'])->name('configurations.destroy');
        Route::post('/configurations/{id}/toggle-mute', [App\Http\Controllers\Admin\AlertingController::class, 'toggleMute'])->name('configurations.toggle-mute');

        // Alert History Actions
        Route::post('/history/{id}/acknowledge', [App\Http\Controllers\Admin\AlertingController::class, 'acknowledgeAlert'])->name('history.acknowledge');
        Route::post('/history/{id}/retry', [App\Http\Controllers\Admin\AlertingController::class, 'retryAlert'])->name('history.retry');
    });

    // Compliance Reports (ADM-005)
    Route::prefix('reports')->name('reports.')->group(function() {
        Route::get('/', [App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('index');
        Route::get('/{id}', [App\Http\Controllers\Admin\ReportsController::class, 'show'])->name('show');
        Route::get('/{id}/download', [App\Http\Controllers\Admin\ReportsController::class, 'download'])->name('download');
        Route::get('/{id}/export-csv', [App\Http\Controllers\Admin\ReportsController::class, 'exportCSV'])->name('export-csv');
        Route::post('/generate-daily-reconciliation', [App\Http\Controllers\Admin\ReportsController::class, 'generateDailyReconciliation'])->name('generate-daily');
        Route::post('/generate-monthly-vat', [App\Http\Controllers\Admin\ReportsController::class, 'generateMonthlyVAT'])->name('generate-vat');
        Route::post('/generate-quarterly-worker-classification', [App\Http\Controllers\Admin\ReportsController::class, 'generateQuarterlyWorkerClassification'])->name('generate-worker');
        Route::post('/{id}/archive', [App\Http\Controllers\Admin\ReportsController::class, 'archive'])->name('archive');
        Route::delete('/{id}', [App\Http\Controllers\Admin\ReportsController::class, 'destroy'])->name('destroy');
        Route::get('/statistics', [App\Http\Controllers\Admin\ReportsController::class, 'getStatistics'])->name('statistics');
        Route::post('/bulk-generate', [App\Http\Controllers\Admin\ReportsController::class, 'bulkGenerate'])->name('bulk-generate');
        Route::post('/{id}/email', [App\Http\Controllers\Admin\ReportsController::class, 'email'])->name('email');
    });
});

// ============================================================================
// UTILITY ROUTES (Authenticated)
// ============================================================================
Route::middleware(['auth'])->group(function() {
    // Image Upload (used by admin and various forms)
    Route::post('upload/image', [App\Http\Controllers\UploadController::class, 'uploadImage'])->name('upload.image');
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
