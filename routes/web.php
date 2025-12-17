<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| OvertimeStaff Web Routes - Clean Separated Structure
|--------------------------------------------------------------------------
|
| Marketing routes use layouts/marketing.blade.php
| Dashboard routes use layouts/dashboard.blade.php
|
*/

// ============================================================================
// MARKETING ROUTES - Public Pages (No Auth Required)
// ============================================================================
Route::middleware(['web'])->group(function () {
    // Homepage
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // Access Denied Page
    Route::get('/access-denied', [App\Http\Controllers\HomeController::class, 'accessDenied'])->name('errors.access-denied');

    // Public Marketing Pages
    Route::get('/terms', [App\Http\Controllers\HomeController::class, 'terms'])->name('terms');
    Route::get('/privacy', [App\Http\Controllers\HomeController::class, 'privacy'])->name('privacy');

    // For Businesses Pages
    Route::prefix('business')->name('business.')->group(function () {
        Route::get('/pricing', [App\Http\Controllers\HomeController::class, 'businessPricing'])->name('pricing');
    });

    // Public profile browsing (no auth required)
    Route::get('/profile/{username}', [App\Http\Controllers\PublicProfileController::class, 'show'])->name('profile.public');
    Route::get('/profile/{username}/portfolio/{itemId}', [App\Http\Controllers\PublicProfileController::class, 'portfolioItem'])->name('profile.portfolio');
    Route::get('/workers', [App\Http\Controllers\PublicProfileController::class, 'searchWorkers'])->name('workers.search');
    Route::get('/api/featured-workers', [App\Http\Controllers\PublicProfileController::class, 'featuredWorkers'])->name('api.featured-workers');

});

// ============================================================================
// AUTHENTICATION ROUTES - Login/Register/Password Reset
// ============================================================================
Route::middleware(['web'])->group(function () {

    // Login Routes
    Route::get('login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [App\Http\Controllers\Auth\LoginController::class, 'login'])->middleware('throttle:login');
    Route::post('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

    // Registration Routes
    Route::get('register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [App\Http\Controllers\Auth\RegisterController::class, 'register'])->middleware('throttle:registration');

    // Password Reset Routes
    Route::get('password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:password-reset');
    Route::get('password/reset/{token}', [App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('password.update')->middleware('throttle:password-reset');

    // Email Verification Routes
    Route::get('email/verify', [App\Http\Controllers\Auth\VerificationController::class, 'show'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [App\Http\Controllers\Auth\VerificationController::class, 'verify'])->name('verification.verify');
    Route::post('email/resend', [App\Http\Controllers\Auth\VerificationController::class, 'resend'])->name('verification.resend')->middleware('throttle:verification');

    // Password Confirmation
    Route::get('password/confirm', [App\Http\Controllers\Auth\ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
    Route::post('password/confirm', [App\Http\Controllers\Auth\ConfirmPasswordController::class, 'confirm'])->middleware('throttle:login');

    // Two-Factor Authentication Routes
    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        // Settings (authenticated only)
        Route::middleware('auth')->group(function () {
            Route::get('/', [App\Http\Controllers\Auth\TwoFactorAuthController::class, 'index'])->name('index');
            Route::get('/enable', [App\Http\Controllers\Auth\TwoFactorAuthController::class, 'enable'])->name('enable');
            Route::post('/confirm', [App\Http\Controllers\Auth\TwoFactorAuthController::class, 'confirm'])->name('confirm');
            Route::post('/disable', [App\Http\Controllers\Auth\TwoFactorAuthController::class, 'disable'])->name('disable');
            Route::get('/recovery-codes', [App\Http\Controllers\Auth\TwoFactorAuthController::class, 'showRecoveryCodes'])->name('recovery-codes');
            Route::post('/recovery-codes/regenerate', [App\Http\Controllers\Auth\TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->name('recovery-codes.regenerate');
        });

        // Verification during login (guest accessible)
        Route::get('/verify', [App\Http\Controllers\Auth\TwoFactorAuthController::class, 'verify'])->name('verify');
        Route::post('/verify-code', [App\Http\Controllers\Auth\TwoFactorAuthController::class, 'verifyCode'])->name('verify-code');
        Route::get('/recovery', [App\Http\Controllers\Auth\TwoFactorAuthController::class, 'showRecoveryForm'])->name('recovery');
        Route::post('/recovery/verify', [App\Http\Controllers\Auth\TwoFactorAuthController::class, 'verifyRecoveryCode'])->name('recovery.verify');
    });

    // Social Authentication Routes
    Route::prefix('auth/social')->name('social.')->group(function () {
        Route::get('/{provider}', [App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])
            ->name('redirect')
            ->where('provider', 'google|apple|facebook');
        Route::get('/{provider}/callback', [App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])
            ->name('callback')
            ->where('provider', 'google|apple|facebook');
    });

});

// ============================================================================
// DASHBOARD ROUTES - Authenticated User Dashboards
// ============================================================================
Route::prefix('dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard.')
    ->group(function () {

        // Main Dashboard Redirect (Role-based)
        Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('index');

        // Role-specific Dashboard Routes
        Route::get('/worker', [App\Http\Controllers\DashboardController::class, 'workerDashboard'])
            ->middleware(['role:worker'])
            ->name('worker');

        Route::get('/company', [App\Http\Controllers\DashboardController::class, 'businessDashboard'])
            ->middleware(['auth', 'verified', 'role:business'])
            ->name('company');

        Route::get('/agency', [App\Http\Controllers\DashboardController::class, 'agencyDashboard'])
            ->middleware(['auth', 'verified', 'role:agency'])
            ->name('agency');

        // Shared Authenticated Routes
        Route::view('/notifications', 'notifications.index')->name('notifications');
        Route::view('/transactions', 'transactions.index')->name('transactions');
    });

// Fix: Routes that need specific names matching legacy calls (without dashboard. prefix)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/business/available-workers', [App\Http\Controllers\DashboardController::class, 'availableWorkers'])
        ->middleware('role:business')
        ->name('business.available-workers');

    Route::get('/dashboard/agency/assignments', [App\Http\Controllers\DashboardController::class, 'agencyAssignments'])
        ->middleware('role:agency')
        ->name('agency.assignments');

    Route::get('/dashboard/agency/shifts', [App\Http\Controllers\DashboardController::class, 'agencyShiftsBrowse'])
        ->middleware('role:agency')
        ->name('agency.shifts.browse');

    Route::get('/dashboard/agency/shifts/{id}', [App\Http\Controllers\DashboardController::class, 'agencyShiftsView'])
        ->middleware('role:agency')
        ->name('agency.shifts.view');

    Route::get('/dashboard/agency/workers', [App\Http\Controllers\DashboardController::class, 'agencyWorkersIndex'])
        ->middleware('role:agency')
        ->name('agency.workers.index');

    Route::get('/dashboard/agency/commissions', [App\Http\Controllers\DashboardController::class, 'agencyCommissions'])
        ->middleware('role:agency')
        ->name('agency.commissions');

    // Business routes
    Route::get('/shifts/create', [App\Http\Controllers\Shift\ShiftController::class, 'create'])->name('shifts.create');
    Route::post('/shifts', [App\Http\Controllers\Shift\ShiftController::class, 'store'])->name('shifts.store');
});

// ============================================================================
// SETTINGS ROUTE - Authenticated User Settings (outside dashboard prefix)
// ============================================================================
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/settings', [App\Http\Controllers\User\SettingsController::class, 'index'])->name('settings.index');
});

// ============================================================================
// MESSAGES ROUTES - Authenticated User Messaging
// ============================================================================
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/messages', [App\Http\Controllers\MessagesController::class, 'index'])->name('messages.index');
    Route::get('/messages/{conversation}', [App\Http\Controllers\MessagesController::class, 'show'])->name('messages.show');
    Route::post('/messages/send', [App\Http\Controllers\MessagesController::class, 'send'])->name('messages.send');
    Route::post('/messages/{conversation}/archive', [App\Http\Controllers\MessagesController::class, 'archive'])->name('messages.archive');
    Route::post('/messages/{conversation}/restore', [App\Http\Controllers\MessagesController::class, 'restore'])->name('messages.restore');

    // Helper routes for creating conversations
    Route::get('/messages/business/{businessId}', [App\Http\Controllers\MessagesController::class, 'createWithBusiness'])->name('messages.business');
    Route::get('/messages/worker/{workerId}', [App\Http\Controllers\MessagesController::class, 'createWithWorker'])->name('messages.worker');

    // AJAX endpoint
    Route::get('/messages/unread/count', [App\Http\Controllers\MessagesController::class, 'unreadCount'])->name('messages.unread.count');
});

// ============================================================================
// LEGACY ROUTES - Keep existing functionality working
// ============================================================================

// Keep existing live market and shift application routes
Route::middleware(['auth'])->group(function () {
    Route::get('/shifts', [App\Http\Controllers\Shift\ShiftController::class, 'index'])->name('shifts.index');
    // Route::get('/shifts/create', [App\Http\Controllers\DashboardController::class, 'createShift'])->name('shifts.create'); // This route is now handled by ShiftController

    // Business Shifts Management
    Route::prefix('business')->name('business.')->middleware('role:business')->group(function () {
        Route::get('/shifts', [App\Http\Controllers\Business\ShiftManagementController::class, 'myShifts'])->name('shifts.index');
    });

    // Worker Routes (accessible without activation for onboarding)
    Route::prefix('worker')->name('worker.')->middleware(['auth', 'role:worker'])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'workerDashboard'])->name('dashboard');
        Route::get('/assignments', [App\Http\Controllers\DashboardController::class, 'workerAssignments'])->name('assignments');
        Route::get('/profile', [App\Http\Controllers\DashboardController::class, 'profile'])->name('profile'); // Align with dashboard logic or point to generic profile
        Route::get('/payment-setup', [App\Http\Controllers\Worker\PaymentSetupController::class, 'index'])->name('payment-setup');
        Route::get('/skills', [App\Http\Controllers\Worker\SkillsController::class, 'index'])->name('skills');
        Route::get('/certifications', [App\Http\Controllers\Worker\CertificationController::class, 'index'])->name('certifications');
        Route::get('/availability', [App\Http\Controllers\Worker\AvailabilityController::class, 'index'])->name('availability');
        Route::get('/applications', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'myApplications'])->name('applications');

        // Activation routes
        Route::prefix('activation')->name('activation.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\ActivationController::class, 'index'])->name('index');
        });
    });

    Route::post('/shifts/{shift}/apply', [App\Http\Controllers\LiveMarketController::class, 'apply'])->name('market.apply')->middleware(['worker', 'worker.activated']);
    Route::post('/shifts/{shift}/claim', [App\Http\Controllers\LiveMarketController::class, 'instantClaim'])->name('market.claim')->middleware(['worker', 'worker.activated']);
    Route::post('/shifts/{shift}/assign', [App\Http\Controllers\LiveMarketController::class, 'agencyAssign'])->name('market.assign')->middleware('agency');

    Route::prefix('api/market')->middleware('throttle:60,1')->group(function () {
        Route::get('/', [App\Http\Controllers\LiveMarketController::class, 'index'])->name('api.market.index');
        Route::get('/simulate', [App\Http\Controllers\LiveMarketController::class, 'simulateActivity'])->name('api.market.simulate');
    });
});

// Keep existing registration workflows
Route::prefix('register/business')->name('business.register.')->group(function () {
    Route::get('/', [App\Http\Controllers\Business\RegistrationController::class, 'showRegistrationForm']);
    Route::get('/verify-email', [App\Http\Controllers\Business\RegistrationController::class, 'verifyEmailLink']);
});

Route::prefix('register/worker')->name('worker.register.')->group(function () {
    Route::get('/', [App\Http\Controllers\Worker\RegistrationController::class, 'showRegistrationForm'])->name('index');
    Route::get('/invite/{token}', [App\Http\Controllers\Worker\RegistrationController::class, 'showRegistrationForm'])->name('agency-invite');
});

Route::prefix('worker')->name('worker.')->group(function () {
    Route::get('/verify/email', [App\Http\Controllers\Worker\RegistrationController::class, 'showVerifyEmailForm']);
    Route::get('/verify/phone', [App\Http\Controllers\Worker\RegistrationController::class, 'showVerifyPhoneForm']);
});

Route::prefix('register/agency')->name('agency.register.')->group(function () {
    Route::get('/', [App\Http\Controllers\Agency\RegistrationController::class, 'index'])->name('index');
    Route::get('/start', [App\Http\Controllers\Agency\RegistrationController::class, 'start'])->name('start');
    Route::get('/step/{step}', [App\Http\Controllers\Agency\RegistrationController::class, 'showStep'])->name('step.show');
    Route::post('/step/{step}', [App\Http\Controllers\Agency\RegistrationController::class, 'saveStep'])->name('step.save');
    Route::post('/step/{step}/previous', [App\Http\Controllers\Agency\RegistrationController::class, 'previousStep'])->name('step.previous');
    Route::post('/upload-document', [App\Http\Controllers\Agency\RegistrationController::class, 'uploadDocument'])->name('upload-document');
    Route::delete('/remove-document', [App\Http\Controllers\Agency\RegistrationController::class, 'removeDocument'])->name('remove-document');
    Route::get('/review', [App\Http\Controllers\Agency\RegistrationController::class, 'review'])->name('review');
    Route::post('/submit', [App\Http\Controllers\Agency\RegistrationController::class, 'submitApplication'])->name('submit');
    Route::get('/confirmation/{id}', [App\Http\Controllers\Agency\RegistrationController::class, 'confirmation'])->name('confirmation');
});

// ============================================================================
// DEV ROUTES - Local/Development Only
// ============================================================================
if (app()->environment('local', 'development')) {
    Route::get('/dev/login/{type}', [App\Http\Controllers\Dev\DevLoginController::class, 'login'])
        ->name('dev.login')
        ->where('type', 'worker|business|agency|admin');

    Route::match(['get', 'post'], '/dev/credentials', [App\Http\Controllers\Dev\DevLoginController::class, 'showCredentials'])
        ->name('dev.credentials');

    Route::get('/home', function () {
        return redirect('/');
    });

    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        return 'Cache cleared!';
    });
}