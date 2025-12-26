<?php

use Illuminate\Support\Facades\Route;

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
    // Note: /privacy URL is used by PrivacyController (privacy.settings), so use /privacy-policy for static page
    Route::get('/privacy-policy', [App\Http\Controllers\HomeController::class, 'privacy'])->name('privacy-policy');
    Route::get('/contact', [App\Http\Controllers\HomeController::class, 'contact'])->name('contact');
    Route::get('/about', [App\Http\Controllers\HomeController::class, 'about'])->name('about');

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
// LOCALE ROUTES - GLO-006 Localization Engine
// ============================================================================
Route::middleware(['web'])->prefix('locale')->name('locale.')->group(function () {
    // Change locale (POST - for CSRF protection)
    Route::post('/change', [App\Http\Controllers\LocaleController::class, 'change'])->name('change');

    // Change locale via AJAX
    Route::post('/change-ajax', [App\Http\Controllers\LocaleController::class, 'changeAjax'])->name('change.ajax');

    // Get all available locales (API)
    Route::get('/list', [App\Http\Controllers\LocaleController::class, 'index'])->name('list');

    // Get current locale info (API)
    Route::get('/current', [App\Http\Controllers\LocaleController::class, 'current'])->name('current');

    // Get translations for JavaScript (API)
    Route::get('/translations', [App\Http\Controllers\LocaleController::class, 'translations'])->name('translations');

    // Format date (API)
    Route::post('/format/date', [App\Http\Controllers\LocaleController::class, 'formatDate'])->name('format.date');

    // Format currency (API)
    Route::post('/format/currency', [App\Http\Controllers\LocaleController::class, 'formatCurrency'])->name('format.currency');
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

        // Main Dashboard Redirect
        Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('index');

        // STAFF / WORKER ROUTES
        Route::prefix('staff')->name('staff.')->middleware('role:worker')->group(function () {
            Route::get('/overview', [App\Http\Controllers\DashboardController::class, 'workerDashboard'])->name('overview');
            Route::get('/marketplace', [App\Http\Controllers\Shift\ShiftController::class, 'index'])->name('marketplace'); // Find Shifts
            Route::get('/my-shifts', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'myApplications'])->name('my-shifts');
            Route::get('/schedule', [App\Http\Controllers\Worker\AvailabilityController::class, 'index'])->name('schedule');
            Route::view('/earnings', 'dashboard.staff.earnings')->name('earnings');
            Route::view('/reputation', 'dashboard.staff.reputation')->name('reputation');
            Route::view('/agencies', 'dashboard.staff.agencies')->name('agencies');
            Route::view('/loyalty', 'dashboard.staff.loyalty')->name('loyalty');
            Route::view('/verification', 'dashboard.staff.verification')->name('verification');
            Route::view('/documents', 'dashboard.staff.documents')->name('documents');
            Route::view('/availability', 'dashboard.staff.availability')->name('availability');
        });

        // COMPANY ROUTES
        Route::prefix('company')->name('company.')->middleware('role:business')->group(function () {
            Route::get('/overview', [App\Http\Controllers\DashboardController::class, 'businessDashboard'])->name('overview');
            Route::get('/post-shift', [App\Http\Controllers\Shift\ShiftController::class, 'create'])->name('post-shift');
            Route::get('/shifts', [App\Http\Controllers\Business\ShiftManagementController::class, 'myShifts'])->name('shifts'); // Manage Shifts
            Route::view('/applications', 'dashboard.company.applications')->name('applications');
            Route::view('/calendar', 'dashboard.company.calendar')->name('calendar');
            Route::view('/attendance', 'dashboard.company.attendance')->name('attendance');
            Route::view('/workers', 'dashboard.company.workers')->name('workers');
            Route::view('/staff-lists', 'dashboard.company.staff-lists')->name('staff-lists');
            Route::view('/templates', 'dashboard.company.templates')->name('templates');
            Route::view('/payments', 'dashboard.company.payments')->name('payments');
            Route::view('/analytics', 'dashboard.company.analytics')->name('analytics');
            Route::view('/reports', 'dashboard.company.reports')->name('reports');
            Route::view('/compliance', 'dashboard.company.compliance')->name('compliance');
        });

        // AGENCY ROUTES - Use Agency\DashboardController for all agency-specific methods
        Route::prefix('agency')->name('agency.')->middleware('role:agency')->group(function () {
            Route::get('/overview', [App\Http\Controllers\Agency\DashboardController::class, 'index'])->name('overview');
            Route::view('/clients', 'dashboard.agency.clients')->name('clients');
            Route::get('/staff-pool', [App\Http\Controllers\Agency\DashboardController::class, 'workersIndex'])->name('staff-pool');
            Route::get('/placements', [App\Http\Controllers\Agency\DashboardController::class, 'assignments'])->name('placements');
            Route::get('/shifts', [App\Http\Controllers\Agency\DashboardController::class, 'shiftsBrowse'])->name('shifts');
            Route::get('/commissions', [App\Http\Controllers\Agency\DashboardController::class, 'financeCommissions'])->name('commissions');
            Route::view('/invoices', 'dashboard.agency.invoices')->name('invoices');
            Route::view('/analytics', 'dashboard.agency.analytics')->name('analytics');
            Route::view('/compliance', 'dashboard.agency.compliance')->name('compliance');
        });

        // ADMIN ROUTES
        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
            // Main
            Route::view('/overview', 'dashboard.admin.overview')->name('overview');
            Route::view('/system', 'dashboard.admin.system')->name('system');

            // Users
            Route::view('/users', 'dashboard.admin.users')->name('users');
            Route::view('/companies', 'dashboard.admin.companies')->name('companies');

            // Agency Applications
            Route::resource('agency-applications', App\Http\Controllers\Admin\AgencyApplicationController::class);
            Route::post('agency-applications/{id}/review-documents', [App\Http\Controllers\Admin\AgencyApplicationController::class, 'reviewDocuments'])->name('agency-applications.review-documents');
            Route::post('agency-applications/{id}/compliance', [App\Http\Controllers\Admin\AgencyApplicationController::class, 'reviewCompliance'])->name('agency-applications.compliance');
            Route::post('agency-applications/{id}/approve', [App\Http\Controllers\Admin\AgencyApplicationController::class, 'approveApplication'])->name('agency-applications.approve');
            Route::post('agency-applications/{id}/reject', [App\Http\Controllers\Admin\AgencyApplicationController::class, 'rejectApplication'])->name('agency-applications.reject');
            Route::post('agency-applications/{id}/assign', [App\Http\Controllers\Admin\AgencyApplicationController::class, 'assignReviewer'])->name('agency-applications.assign');
            Route::post('agency-applications/{id}/note', [App\Http\Controllers\Admin\AgencyApplicationController::class, 'addNote'])->name('agency-applications.note');
            Route::post('agency-applications/{id}/start-compliance', [App\Http\Controllers\Admin\AgencyApplicationController::class, 'startComplianceChecks'])->name('agency-applications.start-compliance');

            // AGY-001: Agency Tier Management
            Route::prefix('agency-tiers')->name('agency-tiers.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\AgencyTierController::class, 'index'])->name('index');
                Route::get('/create', [App\Http\Controllers\Admin\AgencyTierController::class, 'create'])->name('create');
                Route::post('/', [App\Http\Controllers\Admin\AgencyTierController::class, 'store'])->name('store');
                Route::get('/agencies', [App\Http\Controllers\Admin\AgencyTierController::class, 'agencies'])->name('agencies');
                Route::get('/history', [App\Http\Controllers\Admin\AgencyTierController::class, 'history'])->name('history');
                Route::post('/review', [App\Http\Controllers\Admin\AgencyTierController::class, 'runReview'])->name('review');
                Route::post('/assign-initial', [App\Http\Controllers\Admin\AgencyTierController::class, 'assignInitialTiers'])->name('assign-initial');
                Route::get('/{agencyTier}', [App\Http\Controllers\Admin\AgencyTierController::class, 'show'])->name('show');
                Route::get('/{agencyTier}/edit', [App\Http\Controllers\Admin\AgencyTierController::class, 'edit'])->name('edit');
                Route::put('/{agencyTier}', [App\Http\Controllers\Admin\AgencyTierController::class, 'update'])->name('update');
                Route::delete('/{agencyTier}', [App\Http\Controllers\Admin\AgencyTierController::class, 'destroy'])->name('destroy');
                Route::get('/agency/{agencyProfile}/adjust', [App\Http\Controllers\Admin\AgencyTierController::class, 'adjustForm'])->name('adjust-form');
                Route::post('/agency/{agencyProfile}/adjust', [App\Http\Controllers\Admin\AgencyTierController::class, 'adjust'])->name('adjust');
            });

            // Operations
            Route::view('/all-shifts', 'dashboard.admin.all-shifts')->name('all-shifts');
            Route::view('/disputes', 'dashboard.admin.disputes')->name('disputes');
            Route::view('/financial', 'dashboard.admin.financial')->name('financial');

            // Platform
            Route::view('/analytics', 'dashboard.admin.analytics')->name('analytics');
            Route::view('/agents', 'dashboard.admin.agents')->name('agents');
            Route::view('/features', 'dashboard.admin.features')->name('features');
            Route::view('/audit-logs', 'dashboard.admin.audit-logs')->name('audit-logs');
            Route::view('/announcements', 'dashboard.admin.announcements')->name('announcements');

            // Compliance
            Route::view('/compliance', 'dashboard.admin.compliance')->name('compliance');
            Route::view('/security', 'dashboard.admin.security')->name('security');

            // ========================================
            // WKR-001: KYC REVIEW ROUTES
            // ========================================
            Route::prefix('kyc')->name('kyc.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\KycReviewController::class, 'index'])->name('index');
                Route::get('/stats', [App\Http\Controllers\Admin\KycReviewController::class, 'stats'])->name('stats');
                Route::get('/expiring', [App\Http\Controllers\Admin\KycReviewController::class, 'expiring'])->name('expiring');
                Route::get('/{id}', [App\Http\Controllers\Admin\KycReviewController::class, 'show'])->name('show');
                Route::post('/{id}/approve', [App\Http\Controllers\Admin\KycReviewController::class, 'approve'])->name('approve');
                Route::post('/{id}/reject', [App\Http\Controllers\Admin\KycReviewController::class, 'reject'])->name('reject');
                Route::get('/{id}/document/{type}', [App\Http\Controllers\Admin\KycReviewController::class, 'viewDocument'])->name('document');
                Route::post('/bulk-approve', [App\Http\Controllers\Admin\KycReviewController::class, 'bulkApprove'])->name('bulk-approve');
                Route::post('/bulk-reject', [App\Http\Controllers\Admin\KycReviewController::class, 'bulkReject'])->name('bulk-reject');
            });
        });

        // Shared Authenticated Routes
        Route::view('/notifications', 'notifications.index')->name('notifications');
        Route::view('/transactions', 'transactions.index')->name('transactions');
    });

// Fix: Routes that need specific names matching legacy calls (without dashboard. prefix)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/business/available-workers', [App\Http\Controllers\DashboardController::class, 'availableWorkers'])
        ->middleware('role:business')
        ->name('business.available-workers');

    // Agency Dashboard Routes
    Route::prefix('agency')->name('agency.')->middleware('role:agency')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Agency\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [App\Http\Controllers\Agency\DashboardController::class, 'profile'])->name('profile');
        Route::get('/branding', [App\Http\Controllers\Agency\DashboardController::class, 'branding'])->name('branding');
        Route::get('/compliance', [App\Http\Controllers\Agency\DashboardController::class, 'compliance'])->name('compliance');
        Route::get('/team', [App\Http\Controllers\Agency\DashboardController::class, 'team'])->name('team');

        // Analytics
        Route::get('/analytics/dashboard', [App\Http\Controllers\Agency\DashboardController::class, 'analyticsDashboard'])->name('analytics.dashboard');
        Route::get('/analytics/reports', [App\Http\Controllers\Agency\DashboardController::class, 'analyticsReports'])->name('analytics.reports');
        Route::get('/analytics/revenue', [App\Http\Controllers\Agency\DashboardController::class, 'analyticsRevenue'])->name('analytics.revenue');
        Route::get('/analytics/utilization', [App\Http\Controllers\Agency\DashboardController::class, 'analyticsUtilization'])->name('analytics.utilization');

        // Finance
        Route::get('/finance/overview', [App\Http\Controllers\Agency\DashboardController::class, 'financeOverview'])->name('finance.overview');
        Route::get('/finance/commissions', [App\Http\Controllers\Agency\DashboardController::class, 'financeCommissions'])->name('finance.commissions');
        Route::get('/finance/payroll', [App\Http\Controllers\Agency\DashboardController::class, 'financePayroll'])->name('finance.payroll');
        Route::get('/finance/invoices', [App\Http\Controllers\Agency\DashboardController::class, 'financeInvoices'])->name('finance.invoices');
        Route::get('/finance/settlements', [App\Http\Controllers\Agency\DashboardController::class, 'financeSettlements'])->name('finance.settlements');
        Route::get('/finance/reports', [App\Http\Controllers\Agency\DashboardController::class, 'financeReports'])->name('finance.reports');

        // Placements
        Route::get('/placements/active', [App\Http\Controllers\Agency\DashboardController::class, 'placementsActive'])->name('placements.active');
        Route::get('/placements/history', [App\Http\Controllers\Agency\DashboardController::class, 'placementsHistory'])->name('placements.history');

        // Assignments
        Route::get('/assignments', [App\Http\Controllers\Agency\DashboardController::class, 'assignments'])->name('assignments');

        // Commissions (shortcut route for sidebar)
        Route::get('/commissions', [App\Http\Controllers\Agency\DashboardController::class, 'financeCommissions'])->name('commissions');

        // Shifts
        Route::get('/shifts/browse', [App\Http\Controllers\Agency\DashboardController::class, 'shiftsBrowse'])->name('shifts.browse');
        Route::get('/shifts/assign', [App\Http\Controllers\Agency\DashboardController::class, 'shiftsAssign'])->name('shifts.assign');
        Route::get('/shifts/calendar', [App\Http\Controllers\Agency\DashboardController::class, 'shiftsCalendar'])->name('shifts.calendar');
        Route::get('/shifts/{id}', [App\Http\Controllers\Agency\DashboardController::class, 'shiftsView'])->name('shifts.view');

        // Venues
        Route::get('/venues', [App\Http\Controllers\Agency\DashboardController::class, 'venuesIndex'])->name('venues.index');
        Route::get('/venues/contracts', [App\Http\Controllers\Agency\DashboardController::class, 'venuesContracts'])->name('venues.contracts');
        Route::get('/venues/performance', [App\Http\Controllers\Agency\DashboardController::class, 'venuesPerformance'])->name('venues.performance');
        Route::get('/venues/requests', [App\Http\Controllers\Agency\DashboardController::class, 'venuesRequests'])->name('venues.requests');

        // Workers
        Route::get('/workers', [App\Http\Controllers\Agency\DashboardController::class, 'workersIndex'])->name('workers.index');
        Route::get('/workers/create', [App\Http\Controllers\Agency\DashboardController::class, 'workersCreate'])->name('workers.create');
        Route::get('/workers/pending', [App\Http\Controllers\Agency\DashboardController::class, 'workersPending'])->name('workers.pending');
        Route::get('/workers/compliance', [App\Http\Controllers\Agency\DashboardController::class, 'workersCompliance'])->name('workers.compliance');
        Route::get('/workers/documents', [App\Http\Controllers\Agency\DashboardController::class, 'workersDocuments'])->name('workers.documents');
        Route::get('/workers/groups', [App\Http\Controllers\Agency\DashboardController::class, 'workersGroups'])->name('workers.groups');

        // AGY-006: White-Label Portal Management
        Route::prefix('white-label')->name('white-label.')->group(function () {
            Route::get('/', [App\Http\Controllers\Agency\WhiteLabelController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Agency\WhiteLabelController::class, 'store'])->name('store');
            Route::put('/', [App\Http\Controllers\Agency\WhiteLabelController::class, 'update'])->name('update');
            Route::delete('/', [App\Http\Controllers\Agency\WhiteLabelController::class, 'destroy'])->name('destroy');
            Route::patch('/toggle-status', [App\Http\Controllers\Agency\WhiteLabelController::class, 'toggleStatus'])->name('toggle-status');
            Route::patch('/subdomain', [App\Http\Controllers\Agency\WhiteLabelController::class, 'updateSubdomain'])->name('subdomain.update');
            Route::get('/preview', [App\Http\Controllers\Agency\WhiteLabelController::class, 'preview'])->name('preview');
            Route::get('/css', [App\Http\Controllers\Agency\WhiteLabelController::class, 'getCss'])->name('css');
            Route::put('/email-templates', [App\Http\Controllers\Agency\WhiteLabelController::class, 'updateEmailTemplates'])->name('email-templates.update');

            // Domain Management
            Route::post('/domain', [App\Http\Controllers\Agency\WhiteLabelController::class, 'setupDomain'])->name('domain.setup');
            Route::get('/domain/{domain}/verify', [App\Http\Controllers\Agency\WhiteLabelController::class, 'showDomainVerification'])->name('domain.verify');
            Route::post('/domain/{domain}/verify', [App\Http\Controllers\Agency\WhiteLabelController::class, 'verifyDomain'])->name('domain.verify.check');
            Route::delete('/domain', [App\Http\Controllers\Agency\WhiteLabelController::class, 'removeDomain'])->name('domain.remove');
        });

        // Stripe Connect
        Route::prefix('stripe')->name('stripe.')->group(function () {
            Route::get('/onboarding', [App\Http\Controllers\Agency\StripeConnectController::class, 'onboarding'])->name('onboarding');
            Route::get('/connect', [App\Http\Controllers\Agency\StripeConnectController::class, 'connect'])->name('connect');
            Route::get('/callback', [App\Http\Controllers\Agency\StripeConnectController::class, 'callback'])->name('callback');
            Route::get('/status', [App\Http\Controllers\Agency\StripeConnectController::class, 'status'])->name('status');
            Route::get('/dashboard', [App\Http\Controllers\Agency\StripeConnectController::class, 'dashboard'])->name('dashboard');
            Route::post('/refresh-status', [App\Http\Controllers\Agency\StripeConnectController::class, 'refreshStatus'])->name('refresh-status');
            Route::get('/balance', [App\Http\Controllers\Agency\StripeConnectController::class, 'balance'])->name('balance');
        });
    });

    // Admin Dashboard Routes
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/activity', [App\Http\Controllers\Admin\DashboardController::class, 'activity'])->name('activity');
        Route::get('/alerts', [App\Http\Controllers\Admin\DashboardController::class, 'alerts'])->name('alerts');
        Route::get('/revenue', [App\Http\Controllers\Admin\DashboardController::class, 'revenue'])->name('revenue');
        Route::get('/statistics', [App\Http\Controllers\Admin\DashboardController::class, 'statistics'])->name('statistics');
        Route::get('/system-health', [App\Http\Controllers\Admin\DashboardController::class, 'systemHealth'])->name('system-health');

        // Access Control
        Route::get('/access/admins', [App\Http\Controllers\Admin\DashboardController::class, 'accessAdmins'])->name('access.admins');
        Route::get('/access/roles', [App\Http\Controllers\Admin\DashboardController::class, 'accessRoles'])->name('access.roles');
        Route::get('/access/audit', [App\Http\Controllers\Admin\DashboardController::class, 'accessAudit'])->name('access.audit');

        // Analytics
        Route::get('/analytics/platform', [App\Http\Controllers\Admin\DashboardController::class, 'analyticsPlatform'])->name('analytics.platform');
        Route::get('/analytics/revenue', [App\Http\Controllers\Admin\DashboardController::class, 'analyticsRevenue'])->name('analytics.revenue');
        Route::get('/analytics/growth', [App\Http\Controllers\Admin\DashboardController::class, 'analyticsGrowth'])->name('analytics.growth');
        Route::get('/analytics/geographic', [App\Http\Controllers\Admin\DashboardController::class, 'analyticsGeographic'])->name('analytics.geographic');
        Route::get('/analytics/export', [App\Http\Controllers\Admin\DashboardController::class, 'analyticsExport'])->name('analytics.export');

        // Finance
        Route::get('/finance/transactions', [App\Http\Controllers\Admin\DashboardController::class, 'financeTransactions'])->name('finance.transactions');
        Route::get('/finance/escrow', [App\Http\Controllers\Admin\DashboardController::class, 'financeEscrow'])->name('finance.escrow');
        Route::post('/finance/escrow/release-all-due', [App\Http\Controllers\Admin\DashboardController::class, 'financeEscrowReleaseAllDue'])->name('finance.escrow.release-all-due');
        Route::get('/finance/payouts', [App\Http\Controllers\Admin\DashboardController::class, 'financePayouts'])->name('finance.payouts');
        Route::get('/finance/refunds', [App\Http\Controllers\Admin\DashboardController::class, 'financeRefunds'])->name('finance.refunds');
        Route::get('/finance/disputed', [App\Http\Controllers\Admin\DashboardController::class, 'financeDisputed'])->name('finance.disputed');
        Route::get('/finance/commissions', [App\Http\Controllers\Admin\DashboardController::class, 'financeCommissions'])->name('finance.commissions');
        Route::get('/finance/reports', [App\Http\Controllers\Admin\DashboardController::class, 'financeReports'])->name('finance.reports');
        Route::get('/finance/reports/generate', [App\Http\Controllers\Admin\DashboardController::class, 'financeReportsGenerate'])->name('finance.reports.generate');

        // Moderation
        Route::get('/moderation/reports', [App\Http\Controllers\Admin\DashboardController::class, 'moderationReports'])->name('moderation.reports');
        Route::get('/moderation/disputes', [App\Http\Controllers\Admin\DashboardController::class, 'moderationDisputes'])->name('moderation.disputes');
        Route::get('/moderation/bans', [App\Http\Controllers\Admin\DashboardController::class, 'moderationBans'])->name('moderation.bans');
        Route::get('/moderation/reviews', [App\Http\Controllers\Admin\DashboardController::class, 'moderationReviews'])->name('moderation.reviews');

        // Settings
        Route::get('/settings/general', [App\Http\Controllers\Admin\DashboardController::class, 'settingsGeneral'])->name('settings.general');
        Route::get('/settings/categories', [App\Http\Controllers\Admin\DashboardController::class, 'settingsCategories'])->name('settings.categories');
        Route::get('/settings/skills', [App\Http\Controllers\Admin\DashboardController::class, 'settingsSkills'])->name('settings.skills');
        Route::get('/settings/areas', [App\Http\Controllers\Admin\DashboardController::class, 'settingsAreas'])->name('settings.areas');
        Route::get('/settings/commissions', [App\Http\Controllers\Admin\DashboardController::class, 'settingsCommissions'])->name('settings.commissions');
        Route::get('/settings/features', [App\Http\Controllers\Admin\DashboardController::class, 'settingsFeatures'])->name('settings.features');
        Route::get('/settings/notifications', [App\Http\Controllers\Admin\DashboardController::class, 'settingsNotifications'])->name('settings.notifications');
        Route::get('/settings/emails', [App\Http\Controllers\Admin\DashboardController::class, 'settingsEmails'])->name('settings.emails');
        Route::get('/settings/market', [App\Http\Controllers\Admin\DashboardController::class, 'settingsMarket'])->name('settings.market');

        // Shifts
        Route::get('/shifts', [App\Http\Controllers\Admin\DashboardController::class, 'shiftsIndex'])->name('shifts.index');
        Route::get('/shifts/active', [App\Http\Controllers\Admin\DashboardController::class, 'shiftsActive'])->name('shifts.active');
        Route::get('/shifts/cancelled', [App\Http\Controllers\Admin\DashboardController::class, 'shiftsCancelled'])->name('shifts.cancelled');
        Route::get('/shifts/disputed', [App\Http\Controllers\Admin\DashboardController::class, 'shiftsDisputed'])->name('shifts.disputed');
        Route::get('/shifts/audit', [App\Http\Controllers\Admin\DashboardController::class, 'shiftsAudit'])->name('shifts.audit');

        // Support
        Route::get('/support/tickets', [App\Http\Controllers\Admin\DashboardController::class, 'supportTickets'])->name('support.tickets');

        // System
        Route::get('/system/logs', [App\Http\Controllers\Admin\DashboardController::class, 'systemLogs'])->name('system.logs');
        Route::get('/system/jobs', [App\Http\Controllers\Admin\DashboardController::class, 'systemJobs'])->name('system.jobs');
        Route::get('/system/api-keys', [App\Http\Controllers\Admin\DashboardController::class, 'systemApiKeys'])->name('system.api-keys');
        Route::get('/system/webhooks', [App\Http\Controllers\Admin\DashboardController::class, 'systemWebhooks'])->name('system.webhooks');
        Route::get('/system/integrations', [App\Http\Controllers\Admin\DashboardController::class, 'systemIntegrations'])->name('system.integrations');

        // Users
        Route::get('/users', [App\Http\Controllers\Admin\DashboardController::class, 'users'])->name('users');
        Route::get('/users/workers', [App\Http\Controllers\Admin\DashboardController::class, 'usersWorkers'])->name('users.workers');
        Route::get('/users/venues', [App\Http\Controllers\Admin\DashboardController::class, 'usersVenues'])->name('users.venues');
        Route::get('/users/agencies', [App\Http\Controllers\Admin\DashboardController::class, 'usersAgencies'])->name('users.agencies');
        Route::get('/users/suspended', [App\Http\Controllers\Admin\DashboardController::class, 'usersSuspended'])->name('users.suspended');
        Route::get('/users/reports', [App\Http\Controllers\Admin\DashboardController::class, 'usersReports'])->name('users.reports');

        // Verification
        Route::get('/verifications/pending', [App\Http\Controllers\Admin\DashboardController::class, 'verificationsPending'])->name('verifications.pending');
        Route::get('/verification/id', [App\Http\Controllers\Admin\DashboardController::class, 'verificationId'])->name('verification.id');
        Route::get('/verification/documents', [App\Http\Controllers\Admin\DashboardController::class, 'verificationDocuments'])->name('verification.documents');
        Route::get('/verification/business', [App\Http\Controllers\Admin\DashboardController::class, 'verificationBusiness'])->name('verification.business');
        Route::get('/verification/compliance', [App\Http\Controllers\Admin\DashboardController::class, 'verificationCompliance'])->name('verification.compliance');

        // ========================================
        // USER MANAGEMENT ACTION ROUTES
        // ========================================
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('edit');
            Route::put('/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('update');
            Route::delete('/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/suspend', [App\Http\Controllers\Admin\UserManagementController::class, 'suspend'])->name('suspend');
            Route::post('/{user}/activate', [App\Http\Controllers\Admin\UserManagementController::class, 'activate'])->name('activate');
            Route::post('/{user}/verify-worker', [App\Http\Controllers\Admin\UserManagementController::class, 'verifyWorker'])->name('verify-worker');
            Route::post('/{user}/verify-business', [App\Http\Controllers\Admin\UserManagementController::class, 'verifyBusiness'])->name('verify-business');
            Route::post('/{user}/resend-email', [App\Http\Controllers\Admin\UserManagementController::class, 'resendConfirmationEmail'])->name('resend-email');
            Route::post('/{user}/login-as', [App\Http\Controllers\Admin\UserManagementController::class, 'loginAsUser'])->name('login-as');
        });

        // ========================================
        // VERIFICATION QUEUE ACTION ROUTES
        // ========================================
        Route::prefix('verifications')->name('verifications.')->group(function () {
            Route::get('/{verification}', [App\Http\Controllers\Admin\VerificationQueueController::class, 'show'])->name('show');
            Route::post('/{verification}/approve', [App\Http\Controllers\Admin\VerificationQueueController::class, 'approve'])->name('approve');
            Route::post('/{verification}/reject', [App\Http\Controllers\Admin\VerificationQueueController::class, 'reject'])->name('reject');
            Route::post('/bulk-approve', [App\Http\Controllers\Admin\VerificationQueueController::class, 'bulkApprove'])->name('bulk-approve');
            Route::post('/bulk-reject', [App\Http\Controllers\Admin\VerificationQueueController::class, 'bulkReject'])->name('bulk-reject');
            Route::get('/sla-stats', [App\Http\Controllers\Admin\VerificationQueueController::class, 'slaStats'])->name('sla-stats');
        });

        // ========================================
        // DISPUTE MANAGEMENT ROUTES
        // ========================================
        Route::prefix('disputes')->name('disputes.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\DisputeController::class, 'index'])->name('index');
            Route::get('/{dispute}', [App\Http\Controllers\Admin\DisputeController::class, 'show'])->name('show');
            Route::post('/{dispute}/assign', [App\Http\Controllers\Admin\DisputeController::class, 'assign'])->name('assign');
            Route::post('/{dispute}/status', [App\Http\Controllers\Admin\DisputeController::class, 'updateStatus'])->name('status');
            Route::post('/{dispute}/resolve', [App\Http\Controllers\Admin\DisputeController::class, 'resolve'])->name('resolve');
            Route::post('/{dispute}/close', [App\Http\Controllers\Admin\DisputeController::class, 'close'])->name('close');
            Route::post('/{dispute}/escalate', [App\Http\Controllers\Admin\DisputeController::class, 'escalate'])->name('escalate');
            Route::post('/{dispute}/message', [App\Http\Controllers\Admin\DisputeController::class, 'addMessage'])->name('message');
            Route::post('/{dispute}/evidence', [App\Http\Controllers\Admin\DisputeController::class, 'uploadEvidence'])->name('evidence');
        });

        // ========================================
        // SAF-002: INCIDENT MANAGEMENT ROUTES
        // ========================================
        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\IncidentController::class, 'index'])->name('index');
            Route::get('/analytics', [App\Http\Controllers\Admin\IncidentController::class, 'analytics'])->name('analytics');
            Route::get('/export', [App\Http\Controllers\Admin\IncidentController::class, 'export'])->name('export');
            Route::get('/my-incidents', [App\Http\Controllers\Admin\IncidentController::class, 'myIncidents'])->name('my-incidents');
            Route::post('/bulk-assign', [App\Http\Controllers\Admin\IncidentController::class, 'bulkAssign'])->name('bulk-assign');
            Route::get('/{incident}', [App\Http\Controllers\Admin\IncidentController::class, 'show'])->name('show');
            Route::post('/{incident}/assign', [App\Http\Controllers\Admin\IncidentController::class, 'assign'])->name('assign');
            Route::post('/{incident}/status', [App\Http\Controllers\Admin\IncidentController::class, 'updateStatus'])->name('status');
            Route::post('/{incident}/escalate', [App\Http\Controllers\Admin\IncidentController::class, 'escalate'])->name('escalate');
            Route::post('/{incident}/resolve', [App\Http\Controllers\Admin\IncidentController::class, 'resolve'])->name('resolve');
            Route::post('/{incident}/close', [App\Http\Controllers\Admin\IncidentController::class, 'close'])->name('close');
            Route::post('/{incident}/reopen', [App\Http\Controllers\Admin\IncidentController::class, 'reopen'])->name('reopen');
            Route::post('/{incident}/internal-note', [App\Http\Controllers\Admin\IncidentController::class, 'addInternalNote'])->name('internal-note');
            Route::post('/{incident}/public-update', [App\Http\Controllers\Admin\IncidentController::class, 'addPublicUpdate'])->name('public-update');
            Route::post('/{incident}/flag-insurance', [App\Http\Controllers\Admin\IncidentController::class, 'flagInsurance'])->name('flag-insurance');
            Route::post('/{incident}/claim-number', [App\Http\Controllers\Admin\IncidentController::class, 'recordClaimNumber'])->name('claim-number');
            Route::post('/{incident}/notify-authorities', [App\Http\Controllers\Admin\IncidentController::class, 'notifyAuthorities'])->name('notify-authorities');
        });

        // ========================================
        // SAF-001: EMERGENCY ALERT MANAGEMENT ROUTES
        // ========================================
        Route::prefix('emergency-alerts')->name('emergency-alerts.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'index'])->name('index');
        });

        // ========================================
        // GLO-003: LABOR LAW COMPLIANCE MANAGEMENT ROUTES
        // ========================================
        Route::prefix('labor-law')->name('labor-law.')->group(function () {
            // Dashboard
            Route::get('/dashboard', [App\Http\Controllers\Admin\LaborLawController::class, 'dashboard'])->name('dashboard');

            // Rules Management
            Route::get('/', [App\Http\Controllers\Admin\LaborLawController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\LaborLawController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\LaborLawController::class, 'store'])->name('store');
            Route::get('/{laborLaw}', [App\Http\Controllers\Admin\LaborLawController::class, 'show'])->name('show');
            Route::get('/{laborLaw}/edit', [App\Http\Controllers\Admin\LaborLawController::class, 'edit'])->name('edit');
            Route::put('/{laborLaw}', [App\Http\Controllers\Admin\LaborLawController::class, 'update'])->name('update');
            Route::patch('/{laborLaw}/toggle-active', [App\Http\Controllers\Admin\LaborLawController::class, 'toggleActive'])->name('toggle-active');
            Route::delete('/{laborLaw}', [App\Http\Controllers\Admin\LaborLawController::class, 'destroy'])->name('destroy');

            // Violations
            Route::get('/violations/list', [App\Http\Controllers\Admin\LaborLawController::class, 'violations'])->name('violations');
            Route::get('/violations/{violation}', [App\Http\Controllers\Admin\LaborLawController::class, 'showViolation'])->name('violation');
            Route::post('/violations/{violation}/resolve', [App\Http\Controllers\Admin\LaborLawController::class, 'resolveViolation'])->name('violation.resolve');

            // Exemptions
            Route::get('/exemptions/list', [App\Http\Controllers\Admin\LaborLawController::class, 'exemptions'])->name('exemptions');
            Route::get('/exemptions/{exemption}', [App\Http\Controllers\Admin\LaborLawController::class, 'showExemption'])->name('exemption');
            Route::post('/exemptions/{exemption}/approve', [App\Http\Controllers\Admin\LaborLawController::class, 'approveExemption'])->name('exemption.approve');
            Route::post('/exemptions/{exemption}/reject', [App\Http\Controllers\Admin\LaborLawController::class, 'rejectExemption'])->name('exemption.reject');
            Route::post('/exemptions/{exemption}/revoke', [App\Http\Controllers\Admin\LaborLawController::class, 'revokeExemption'])->name('exemption.revoke');
        });

        // ========================================
        // SHIFT MANAGEMENT ROUTES
        // ========================================
        Route::prefix('shift-management')->name('shift-management.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ShiftManagementController::class, 'index'])->name('index');
            Route::get('/flagged', [App\Http\Controllers\Admin\ShiftManagementController::class, 'flaggedShifts'])->name('flagged');
            Route::get('/statistics', [App\Http\Controllers\Admin\ShiftManagementController::class, 'statistics'])->name('statistics');
            Route::get('/{shift}', [App\Http\Controllers\Admin\ShiftManagementController::class, 'show'])->name('show');
            Route::post('/{shift}/flag', [App\Http\Controllers\Admin\ShiftManagementController::class, 'flagShift'])->name('flag');
            Route::post('/{shift}/unflag', [App\Http\Controllers\Admin\ShiftManagementController::class, 'unflagShift'])->name('unflag');
            Route::post('/{shift}/remove', [App\Http\Controllers\Admin\ShiftManagementController::class, 'removeShift'])->name('remove');
            Route::post('/bulk-approve', [App\Http\Controllers\Admin\ShiftManagementController::class, 'bulkApprove'])->name('bulk-approve');
        });

        // ========================================
        // WKR-009: SUSPENSION MANAGEMENT ROUTES
        // ========================================
        Route::prefix('suspensions')->name('suspensions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'store'])->name('store');
            Route::get('/export', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'export'])->name('export');
            Route::get('/analytics', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'analyticsJson'])->name('analytics');
            Route::get('/search-workers', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'searchWorkers'])->name('search-workers');
            Route::get('/appeals', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'appeals'])->name('appeals');
            Route::get('/appeals/{appeal}', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'reviewAppeal'])->name('appeals.review');
            Route::post('/appeals/{appeal}/start', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'startReview'])->name('appeals.start-review');
            Route::post('/appeals/{appeal}/decide', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'decideAppeal'])->name('appeals.decide');
            Route::get('/{suspension}', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'show'])->name('show');
            Route::post('/{suspension}/lift', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'lift'])->name('lift');
            Route::post('/workers/{worker}/reset-strikes', [App\Http\Controllers\Admin\SuspensionManagementController::class, 'resetStrikes'])->name('reset-strikes');
        });

        // ========================================
        // REFUND MANAGEMENT ROUTES
        // ========================================
        Route::prefix('refunds')->name('refunds.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\RefundController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\RefundController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\RefundController::class, 'store'])->name('store');
            Route::get('/{refund}', [App\Http\Controllers\Admin\RefundController::class, 'show'])->name('show');
            Route::post('/{refund}/process', [App\Http\Controllers\Admin\RefundController::class, 'process'])->name('process');
            Route::post('/{refund}/retry', [App\Http\Controllers\Admin\RefundController::class, 'retry'])->name('retry');
            Route::post('/{refund}/cancel', [App\Http\Controllers\Admin\RefundController::class, 'cancel'])->name('cancel');
            Route::get('/{refund}/credit-note', [App\Http\Controllers\Admin\RefundController::class, 'downloadCreditNote'])->name('credit-note');
        });

        // ========================================
        // REPORTS ROUTES
        // ========================================
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('index');
            Route::get('/{report}', [App\Http\Controllers\Admin\ReportsController::class, 'show'])->name('show');
            Route::post('/generate/daily-reconciliation', [App\Http\Controllers\Admin\ReportsController::class, 'generateDailyReconciliation'])->name('generate.daily');
            Route::post('/generate/monthly-vat', [App\Http\Controllers\Admin\ReportsController::class, 'generateMonthlyVAT'])->name('generate.vat');
            Route::get('/{report}/download', [App\Http\Controllers\Admin\ReportsController::class, 'download'])->name('download');
            Route::get('/{report}/csv', [App\Http\Controllers\Admin\ReportsController::class, 'exportCSV'])->name('csv');
        });

        // ========================================
        // FIN-007: TAX REPORTS MANAGEMENT ROUTES
        // ========================================
        Route::prefix('tax')->name('tax.')->group(function () {
            // Tax Reports
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\TaxReportController::class, 'index'])->name('index');
                Route::get('/bulk', [App\Http\Controllers\Admin\TaxReportController::class, 'bulkForm'])->name('bulk');
                Route::post('/bulk-generate', [App\Http\Controllers\Admin\TaxReportController::class, 'bulkGenerate'])->name('bulk-generate');
                Route::post('/bulk-send', [App\Http\Controllers\Admin\TaxReportController::class, 'bulkSend'])->name('bulk-send');
                Route::get('/compliance', [App\Http\Controllers\Admin\TaxReportController::class, 'complianceReport'])->name('compliance');
                Route::get('/export', [App\Http\Controllers\Admin\TaxReportController::class, 'exportCsv'])->name('export');
                Route::get('/{taxReport}', [App\Http\Controllers\Admin\TaxReportController::class, 'show'])->name('show');
                Route::post('/{taxReport}/regenerate', [App\Http\Controllers\Admin\TaxReportController::class, 'regenerate'])->name('regenerate');
                Route::get('/{taxReport}/download', [App\Http\Controllers\Admin\TaxReportController::class, 'download'])->name('download');
            });
        });

        // ========================================
        // SETTINGS ACTION ROUTES
        // ========================================
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::post('/general', [App\Http\Controllers\Admin\SettingsController::class, 'save'])->name('general.save');
            Route::post('/limits', [App\Http\Controllers\Admin\SettingsController::class, 'saveLimits'])->name('limits.save');
            Route::post('/maintenance', [App\Http\Controllers\Admin\SettingsController::class, 'maintenanceMode'])->name('maintenance');
            Route::post('/market', [App\Http\Controllers\Admin\SettingsController::class, 'saveMarket'])->name('market.save');
        });

        // ========================================
        // FEATURE FLAGS ROUTES (ADM-007)
        // ========================================
        Route::prefix('feature-flags')->name('feature-flags.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\FeatureFlagController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\FeatureFlagController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\FeatureFlagController::class, 'store'])->name('store');
            Route::get('/{featureFlag}', [App\Http\Controllers\Admin\FeatureFlagController::class, 'show'])->name('show');
            Route::get('/{featureFlag}/edit', [App\Http\Controllers\Admin\FeatureFlagController::class, 'edit'])->name('edit');
            Route::put('/{featureFlag}', [App\Http\Controllers\Admin\FeatureFlagController::class, 'update'])->name('update');
            Route::delete('/{featureFlag}', [App\Http\Controllers\Admin\FeatureFlagController::class, 'destroy'])->name('destroy');
            Route::post('/{featureFlag}/toggle', [App\Http\Controllers\Admin\FeatureFlagController::class, 'toggle'])->name('toggle');
            Route::post('/{featureFlag}/rollout', [App\Http\Controllers\Admin\FeatureFlagController::class, 'updateRollout'])->name('rollout');
            Route::get('/{featureFlag}/history', [App\Http\Controllers\Admin\FeatureFlagController::class, 'history'])->name('history');
            Route::post('/batch-toggle', [App\Http\Controllers\Admin\FeatureFlagController::class, 'batchToggle'])->name('batch-toggle');
            Route::post('/clear-cache', [App\Http\Controllers\Admin\FeatureFlagController::class, 'clearCache'])->name('clear-cache');
        });

        // ========================================
        // SAF-004: VENUE SAFETY MANAGEMENT ROUTES
        // ========================================
        Route::prefix('safety')->name('safety.')->group(function () {
            // Dashboard
            Route::get('/', [App\Http\Controllers\Admin\VenueSafetyController::class, 'index'])->name('index');

            // Flags management
            Route::get('/flags', [App\Http\Controllers\Admin\VenueSafetyController::class, 'flags'])->name('flags');
            Route::get('/flags/{flag}', [App\Http\Controllers\Admin\VenueSafetyController::class, 'showFlag'])->name('flags.show');
            Route::post('/flags/{flag}/assign', [App\Http\Controllers\Admin\VenueSafetyController::class, 'assignFlag'])->name('flags.assign');
            Route::post('/flags/{flag}/status', [App\Http\Controllers\Admin\VenueSafetyController::class, 'updateFlagStatus'])->name('flags.status');
            Route::post('/flags/{flag}/resolve', [App\Http\Controllers\Admin\VenueSafetyController::class, 'resolveFlag'])->name('flags.resolve');
            Route::post('/flags/{flag}/dismiss', [App\Http\Controllers\Admin\VenueSafetyController::class, 'dismissFlag'])->name('flags.dismiss');
            Route::post('/flags/bulk-assign', [App\Http\Controllers\Admin\VenueSafetyController::class, 'bulkAssign'])->name('flags.bulk-assign');

            // Venues safety management
            Route::get('/venues', [App\Http\Controllers\Admin\VenueSafetyController::class, 'venues'])->name('venues');
            Route::get('/venues/{venue}', [App\Http\Controllers\Admin\VenueSafetyController::class, 'showVenue'])->name('venues.show');
            Route::post('/venues/{venue}/status', [App\Http\Controllers\Admin\VenueSafetyController::class, 'updateVenueStatus'])->name('venues.status');
            Route::post('/venues/{venue}/audit', [App\Http\Controllers\Admin\VenueSafetyController::class, 'recordAudit'])->name('venues.audit');
            Route::post('/venues/{venue}/investigate', [App\Http\Controllers\Admin\VenueSafetyController::class, 'triggerInvestigation'])->name('venues.investigate');

            // Export
            Route::get('/export', [App\Http\Controllers\Admin\VenueSafetyController::class, 'export'])->name('export');

            // API endpoints
            Route::get('/api/stats', [App\Http\Controllers\Admin\VenueSafetyController::class, 'getStats'])->name('api.stats');
            Route::get('/api/flags-attention', [App\Http\Controllers\Admin\VenueSafetyController::class, 'getFlagsRequiringAttention'])->name('api.flags-attention');
            Route::get('/api/unsafe-venues', [App\Http\Controllers\Admin\VenueSafetyController::class, 'getUnsafeVenues'])->name('api.unsafe-venues');
        });

        // ========================================
        // COM-004: WHATSAPP TEMPLATE MANAGEMENT ROUTES
        // ========================================
        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'index'])->name('index');
            Route::get('/dashboard', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'dashboard'])->name('dashboard');
            Route::get('/create', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'store'])->name('store');
            Route::post('/sync', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'sync'])->name('sync');
            Route::get('/{template}', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'show'])->name('show');
            Route::get('/{template}/edit', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'edit'])->name('edit');
            Route::put('/{template}', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'update'])->name('update');
            Route::delete('/{template}', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'destroy'])->name('destroy');
            Route::post('/{template}/toggle-active', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'toggleActive'])->name('toggle-active');
            Route::post('/{template}/approve', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'approve'])->name('approve');
            Route::post('/{template}/reject', [App\Http\Controllers\Admin\WhatsAppTemplateController::class, 'reject'])->name('reject');
        });

        // ========================================
        // SL-008: SURGE PRICING MANAGEMENT ROUTES
        // ========================================
        Route::prefix('surge')->name('surge.')->group(function () {
            // Dashboard
            Route::get('/', [App\Http\Controllers\Admin\SurgeController::class, 'index'])->name('index');

            // Events management
            Route::get('/events', [App\Http\Controllers\Admin\SurgeController::class, 'events'])->name('events');
            Route::get('/events/create', [App\Http\Controllers\Admin\SurgeController::class, 'create'])->name('events.create');
            Route::post('/events', [App\Http\Controllers\Admin\SurgeController::class, 'store'])->name('events.store');
            Route::get('/events/{event}/edit', [App\Http\Controllers\Admin\SurgeController::class, 'edit'])->name('events.edit');
            Route::put('/events/{event}', [App\Http\Controllers\Admin\SurgeController::class, 'update'])->name('events.update');
            Route::delete('/events/{event}', [App\Http\Controllers\Admin\SurgeController::class, 'destroy'])->name('events.destroy');
            Route::post('/events/{event}/toggle', [App\Http\Controllers\Admin\SurgeController::class, 'toggleActive'])->name('events.toggle');

            // Demand metrics
            Route::get('/demand', [App\Http\Controllers\Admin\SurgeController::class, 'demand'])->name('demand');
            Route::post('/demand/recalculate', [App\Http\Controllers\Admin\SurgeController::class, 'recalculateMetrics'])->name('demand.recalculate');

            // Import events from APIs
            Route::post('/import-events', [App\Http\Controllers\Admin\SurgeController::class, 'importEvents'])->name('import-events');

            // API endpoints
            Route::get('/api/preview', [App\Http\Controllers\Admin\SurgeController::class, 'getSurgePreview'])->name('api.preview');
            Route::get('/api/demand-data', [App\Http\Controllers\Admin\SurgeController::class, 'getDemandData'])->name('api.demand-data');
            Route::get('/api/events-calendar', [App\Http\Controllers\Admin\SurgeController::class, 'getEventsCalendar'])->name('api.events-calendar');
        });

        // ========================================
        // GLO-009: REGIONAL PRICING MANAGEMENT ROUTES
        // ========================================
        Route::prefix('regional-pricing')->name('regional-pricing.')->group(function () {
            // Main CRUD routes
            Route::get('/', [App\Http\Controllers\Admin\RegionalPricingController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\RegionalPricingController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\RegionalPricingController::class, 'store'])->name('store');
            Route::get('/analytics', [App\Http\Controllers\Admin\RegionalPricingController::class, 'analytics'])->name('analytics');
            Route::get('/export', [App\Http\Controllers\Admin\RegionalPricingController::class, 'export'])->name('export');
            Route::post('/import', [App\Http\Controllers\Admin\RegionalPricingController::class, 'import'])->name('import');
            Route::post('/sync-ppp', [App\Http\Controllers\Admin\RegionalPricingController::class, 'syncPPP'])->name('sync-ppp');
            Route::post('/preview-pricing', [App\Http\Controllers\Admin\RegionalPricingController::class, 'previewPricing'])->name('preview-pricing');

            // Individual region routes
            Route::get('/{regionalPricing}', [App\Http\Controllers\Admin\RegionalPricingController::class, 'show'])->name('show');
            Route::get('/{regionalPricing}/edit', [App\Http\Controllers\Admin\RegionalPricingController::class, 'edit'])->name('edit');
            Route::put('/{regionalPricing}', [App\Http\Controllers\Admin\RegionalPricingController::class, 'update'])->name('update');
            Route::delete('/{regionalPricing}', [App\Http\Controllers\Admin\RegionalPricingController::class, 'destroy'])->name('destroy');
            Route::patch('/{regionalPricing}/toggle-status', [App\Http\Controllers\Admin\RegionalPricingController::class, 'toggleStatus'])->name('toggle-status');
            Route::patch('/{regionalPricing}/update-ppp', [App\Http\Controllers\Admin\RegionalPricingController::class, 'updatePPP'])->name('update-ppp');

            // Price adjustments
            Route::get('/{regionalPricing}/adjustments', [App\Http\Controllers\Admin\RegionalPricingController::class, 'adjustments'])->name('adjustments');
            Route::post('/{regionalPricing}/adjustments', [App\Http\Controllers\Admin\RegionalPricingController::class, 'storeAdjustment'])->name('adjustments.store');
        });

        // Price adjustment routes (outside regional pricing prefix for cleaner URLs)
        Route::prefix('price-adjustments')->name('price-adjustments.')->group(function () {
            Route::put('/{priceAdjustment}', [App\Http\Controllers\Admin\RegionalPricingController::class, 'updateAdjustment'])->name('update');
            Route::delete('/{priceAdjustment}', [App\Http\Controllers\Admin\RegionalPricingController::class, 'destroyAdjustment'])->name('destroy');
        });

        // ========================================
        // QUA-005: CONTINUOUS IMPROVEMENT SYSTEM ROUTES
        // ========================================
        Route::prefix('improvements')->name('improvements.')->group(function () {
            // Dashboard
            Route::get('/', [App\Http\Controllers\Admin\ImprovementController::class, 'index'])->name('index');

            // Suggestions Management
            Route::get('/suggestions', [App\Http\Controllers\Admin\ImprovementController::class, 'suggestions'])->name('suggestions');
            Route::get('/suggestions/{suggestion}', [App\Http\Controllers\Admin\ImprovementController::class, 'showSuggestion'])->name('suggestion');
            Route::put('/suggestions/{suggestion}', [App\Http\Controllers\Admin\ImprovementController::class, 'updateSuggestion'])->name('suggestion.update');
            Route::post('/suggestions/bulk', [App\Http\Controllers\Admin\ImprovementController::class, 'bulkUpdate'])->name('suggestions.bulk');

            // Metrics Management
            Route::get('/metrics', [App\Http\Controllers\Admin\ImprovementController::class, 'metrics'])->name('metrics');
            Route::put('/metrics/{metric}', [App\Http\Controllers\Admin\ImprovementController::class, 'updateMetric'])->name('metrics.update');
            Route::post('/metrics/{metric}/record', [App\Http\Controllers\Admin\ImprovementController::class, 'recordMetricValue'])->name('metrics.record');
            Route::post('/metrics/refresh', [App\Http\Controllers\Admin\ImprovementController::class, 'refreshMetrics'])->name('metrics.refresh');

            // Reports
            Route::get('/report', [App\Http\Controllers\Admin\ImprovementController::class, 'report'])->name('report');
            Route::get('/report/export', [App\Http\Controllers\Admin\ImprovementController::class, 'exportReport'])->name('report.export');
        });
    });

    // Business routes
    Route::get('/shifts/create', [App\Http\Controllers\Shift\ShiftController::class, 'create'])->name('shifts.create');
    Route::post('/shifts', [App\Http\Controllers\Shift\ShiftController::class, 'store'])->name('shifts.store');

    Route::get('/business/profile/complete', [App\Http\Controllers\Business\ProfileController::class, 'showSetup'])
        ->middleware('role:business')
        ->name('business.profile.complete');
});

// ============================================================================
// SHARED ROUTES - Available to all authenticated users
// ============================================================================
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/settings', [App\Http\Controllers\User\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [App\Http\Controllers\User\SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [App\Http\Controllers\User\SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::put('/settings/notifications', [App\Http\Controllers\User\SettingsController::class, 'updateNotificationPreferences'])->name('settings.notifications.update');
    Route::delete('/settings/account', [App\Http\Controllers\User\SettingsController::class, 'deleteAccount'])->name('settings.account.delete');
    Route::get('/help', fn () => view('help.index'))->name('help.index');
    Route::get('/notifications', fn () => view('notifications.index'))->name('notifications.index');

    // WKR-004: Rating response route (shared by workers and businesses)
    Route::post('/ratings/{rating}/respond', [App\Http\Controllers\RatingController::class, 'respond'])->name('ratings.respond');

    // WKR-004: Rating API endpoints
    Route::prefix('api/ratings')->name('api.ratings.')->group(function () {
        Route::get('/user/{userId}/summary', [App\Http\Controllers\RatingController::class, 'getSummary'])->name('summary');
        Route::get('/user/{userId}/trend', [App\Http\Controllers\RatingController::class, 'getTrend'])->name('trend');
        Route::get('/user/{userId}/distribution', [App\Http\Controllers\RatingController::class, 'getDistribution'])->name('distribution');
        Route::get('/user/{userId}/recent', [App\Http\Controllers\RatingController::class, 'getRecent'])->name('recent');
    });
});

// ============================================================================
// QUA-003: FEEDBACK LOOP SYSTEM ROUTES
// ============================================================================
Route::middleware(['web', 'auth', 'verified'])->prefix('feedback')->name('feedback.')->group(function () {
    // Survey Routes
    Route::prefix('surveys')->name('surveys.')->group(function () {
        Route::get('/', [App\Http\Controllers\Feedback\SurveyController::class, 'index'])->name('index');
        Route::get('/thanks', [App\Http\Controllers\Feedback\SurveyController::class, 'thanks'])->name('thanks');
        Route::get('/{slug}', [App\Http\Controllers\Feedback\SurveyController::class, 'show'])->name('show');
        Route::get('/{slug}/shift/{shiftId}', [App\Http\Controllers\Feedback\SurveyController::class, 'showPostShift'])->name('post-shift');
        Route::post('/{slug}/submit', [App\Http\Controllers\Feedback\SurveyController::class, 'submit'])->name('submit');
    });

    // Feature Request Routes
    Route::prefix('feature-requests')->name('feature-requests.')->group(function () {
        Route::get('/', [App\Http\Controllers\Feedback\FeatureRequestController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Feedback\FeatureRequestController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Feedback\FeatureRequestController::class, 'store'])->name('store');
        Route::get('/my-requests', [App\Http\Controllers\Feedback\FeatureRequestController::class, 'myRequests'])->name('my-requests');
        Route::get('/{id}', [App\Http\Controllers\Feedback\FeatureRequestController::class, 'show'])->name('show');
        Route::post('/{id}/vote', [App\Http\Controllers\Feedback\FeatureRequestController::class, 'vote'])->name('vote');
    });

    // Bug Report Routes
    Route::prefix('bug-reports')->name('bug-reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\Feedback\BugReportController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Feedback\BugReportController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Feedback\BugReportController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Feedback\BugReportController::class, 'show'])->name('show');
        Route::post('/{id}/details', [App\Http\Controllers\Feedback\BugReportController::class, 'addDetails'])->name('add-details');
    });
});

// Admin Feedback Management Routes (within admin section)
Route::middleware(['web', 'auth', 'verified', 'role:admin'])->prefix('admin/feedback')->name('admin.feedback.')->group(function () {
    // Survey Management
    Route::prefix('surveys')->name('surveys.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SurveyManagementController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\SurveyManagementController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\SurveyManagementController::class, 'store'])->name('store');
        Route::post('/create-defaults', [App\Http\Controllers\Admin\SurveyManagementController::class, 'createDefaults'])->name('create-defaults');
        Route::get('/{id}', [App\Http\Controllers\Admin\SurveyManagementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\Admin\SurveyManagementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Admin\SurveyManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Admin\SurveyManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-active', [App\Http\Controllers\Admin\SurveyManagementController::class, 'toggleActive'])->name('toggle-active');
        Route::get('/{id}/responses', [App\Http\Controllers\Admin\SurveyManagementController::class, 'responses'])->name('responses');
        Route::get('/{id}/export', [App\Http\Controllers\Admin\SurveyManagementController::class, 'exportResponses'])->name('export');
    });

    // Feedback Analytics
    Route::get('/analytics', [App\Http\Controllers\Admin\FeedbackAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/nps', [App\Http\Controllers\Admin\FeedbackAnalyticsController::class, 'npsDetails'])->name('analytics.nps');
    Route::get('/analytics/report', [App\Http\Controllers\Admin\FeedbackAnalyticsController::class, 'generateReport'])->name('analytics.report');

    // Feature Requests Management
    Route::get('/feature-requests', [App\Http\Controllers\Admin\FeedbackAnalyticsController::class, 'featureRequests'])->name('feature-requests');
    Route::put('/feature-requests/{id}', [App\Http\Controllers\Admin\FeedbackAnalyticsController::class, 'updateFeatureRequestStatus'])->name('feature-requests.update');

    // Bug Reports Management
    Route::get('/bug-reports', [App\Http\Controllers\Admin\FeedbackAnalyticsController::class, 'bugReports'])->name('bug-reports');
    Route::get('/bug-reports/{id}', [App\Http\Controllers\Admin\FeedbackAnalyticsController::class, 'showBugReport'])->name('bug-reports.show');
    Route::put('/bug-reports/{id}', [App\Http\Controllers\Admin\FeedbackAnalyticsController::class, 'updateBugReportStatus'])->name('bug-reports.update');
});

// ============================================================================
// QUA-005: IMPROVEMENT SUGGESTIONS ROUTES (User-Facing)
// ============================================================================
Route::middleware(['web'])->prefix('suggestions')->name('suggestions.')->group(function () {
    // Public routes (no auth required)
    Route::get('/', [App\Http\Controllers\SuggestionController::class, 'index'])->name('index');
    Route::get('/{suggestion}', [App\Http\Controllers\SuggestionController::class, 'show'])->name('show');
});

Route::middleware(['web', 'auth', 'verified'])->prefix('suggestions')->name('suggestions.')->group(function () {
    // Authenticated routes
    Route::get('/my/list', [App\Http\Controllers\SuggestionController::class, 'mySuggestions'])->name('my');
    Route::get('/create/new', [App\Http\Controllers\SuggestionController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\SuggestionController::class, 'store'])->name('store');
    Route::get('/{suggestion}/edit', [App\Http\Controllers\SuggestionController::class, 'edit'])->name('edit');
    Route::put('/{suggestion}', [App\Http\Controllers\SuggestionController::class, 'update'])->name('update');
    Route::delete('/{suggestion}', [App\Http\Controllers\SuggestionController::class, 'destroy'])->name('destroy');
    Route::post('/{suggestion}/vote', [App\Http\Controllers\SuggestionController::class, 'vote'])->name('vote');
    Route::delete('/{suggestion}/vote', [App\Http\Controllers\SuggestionController::class, 'removeVote'])->name('vote.remove');
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
    Route::get('/shifts/{shift}', [App\Http\Controllers\Shift\ShiftController::class, 'show'])->name('shifts.show');
    // Route::get('/shifts/create', [App\Http\Controllers\DashboardController::class, 'createShift'])->name('shifts.create'); // This route is now handled by ShiftController

    // Business Dashboard Routes
    Route::prefix('business')->name('business.')->middleware('role:business')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Business\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [App\Http\Controllers\Business\DashboardController::class, 'profile'])->name('profile');
        Route::get('/applications', [App\Http\Controllers\Business\DashboardController::class, 'applications'])->name('applications');
        Route::get('/documents', [App\Http\Controllers\Business\DashboardController::class, 'documents'])->name('documents');
        Route::get('/locations', [App\Http\Controllers\Business\DashboardController::class, 'locations'])->name('locations');
        Route::get('/team', [App\Http\Controllers\Business\DashboardController::class, 'team'])->name('team');

        // Shifts
        Route::get('/shifts', [App\Http\Controllers\Business\ShiftManagementController::class, 'myShifts'])->name('shifts.index');
        Route::get('/shifts/upcoming', [App\Http\Controllers\Business\DashboardController::class, 'shiftsUpcoming'])->name('shifts.upcoming');
        Route::get('/shifts/pending', [App\Http\Controllers\Business\DashboardController::class, 'shiftsPending'])->name('shifts.pending');
        Route::get('/shifts/history', [App\Http\Controllers\Business\DashboardController::class, 'shiftsHistory'])->name('shifts.history');
        Route::get('/shifts/templates', [App\Http\Controllers\Business\DashboardController::class, 'shiftsTemplates'])->name('shifts.templates');

        // Payments
        Route::get('/payments/pending', [App\Http\Controllers\Business\DashboardController::class, 'paymentsPending'])->name('payments.pending');
        Route::get('/payments/escrow', [App\Http\Controllers\Business\DashboardController::class, 'paymentsEscrow'])->name('payments.escrow');
        Route::get('/payments/history', [App\Http\Controllers\Business\DashboardController::class, 'paymentsHistory'])->name('payments.history');
        Route::get('/payments/invoices', [App\Http\Controllers\Business\DashboardController::class, 'paymentsInvoices'])->name('payments.invoices');
        Route::get('/payments/add-funds', [App\Http\Controllers\Business\DashboardController::class, 'paymentsAddFunds'])->name('payments.add-funds');
        Route::post('/payments/create-intent', [App\Http\Controllers\Business\DashboardController::class, 'paymentsCreateIntent'])->name('payments.create-intent');

        // Reports
        Route::get('/reports/spending', [App\Http\Controllers\Business\DashboardController::class, 'reportsSpending'])->name('reports.spending');
        Route::get('/reports/performance', [App\Http\Controllers\Business\DashboardController::class, 'reportsPerformance'])->name('reports.performance');
        Route::get('/reports/analytics', [App\Http\Controllers\Business\DashboardController::class, 'reportsAnalytics'])->name('reports.analytics');
        Route::get('/reports/export', [App\Http\Controllers\Business\DashboardController::class, 'reportsExport'])->name('reports.export');

        // Analytics (shortcut - views reference business.analytics directly)
        Route::get('/analytics-overview', [App\Http\Controllers\Business\AnalyticsController::class, 'index'])->name('analytics');

        // Workers
        Route::get('/workers/favourites', [App\Http\Controllers\Business\DashboardController::class, 'workersFavourites'])->name('workers.favourites');
        Route::get('/workers/blocked', [App\Http\Controllers\Business\DashboardController::class, 'workersBlocked'])->name('workers.blocked');
        Route::get('/workers/reviews', [App\Http\Controllers\Business\DashboardController::class, 'workersReviews'])->name('workers.reviews');
        Route::get('/workers/{id}', [App\Http\Controllers\Business\DashboardController::class, 'showWorker'])->name('workers.show');

        // ========================================
        // VENUE MANAGEMENT ROUTES
        // ========================================
        Route::prefix('venues')->name('venues.')->group(function () {
            Route::get('/', [App\Http\Controllers\Business\VenueController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Business\VenueController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Business\VenueController::class, 'store'])->name('store');
            Route::get('/{venue}', [App\Http\Controllers\Business\VenueController::class, 'show'])->name('show');
            Route::get('/{venue}/edit', [App\Http\Controllers\Business\VenueController::class, 'edit'])->name('edit');
            Route::put('/{venue}', [App\Http\Controllers\Business\VenueController::class, 'update'])->name('update');
            Route::delete('/{venue}', [App\Http\Controllers\Business\VenueController::class, 'destroy'])->name('destroy');
            Route::put('/{venue}/instructions', [App\Http\Controllers\Business\VenueController::class, 'updateInstructions'])->name('instructions');
            Route::put('/{venue}/settings', [App\Http\Controllers\Business\VenueController::class, 'updateSettings'])->name('settings');
            Route::post('/{venue}/deactivate', [App\Http\Controllers\Business\VenueController::class, 'deactivate'])->name('deactivate');
            Route::post('/{venue}/reactivate', [App\Http\Controllers\Business\VenueController::class, 'reactivate'])->name('reactivate');
            Route::post('/{venue}/managers', [App\Http\Controllers\Business\VenueController::class, 'assignManagers'])->name('managers');
            Route::post('/geocode', [App\Http\Controllers\Business\VenueController::class, 'geocode'])->name('geocode');
        });

        // ========================================
        // TEAM MANAGEMENT ROUTES
        // ========================================
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/', [App\Http\Controllers\Business\TeamController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Business\TeamController::class, 'create'])->name('create');
            Route::post('/invite', [App\Http\Controllers\Business\TeamController::class, 'invite'])->name('invite');
            Route::get('/{member}', [App\Http\Controllers\Business\TeamController::class, 'show'])->name('show');
            Route::get('/{member}/edit', [App\Http\Controllers\Business\TeamController::class, 'edit'])->name('edit');
            Route::put('/{member}', [App\Http\Controllers\Business\TeamController::class, 'update'])->name('update');
            Route::delete('/{member}', [App\Http\Controllers\Business\TeamController::class, 'destroy'])->name('destroy');
            Route::post('/{member}/resend', [App\Http\Controllers\Business\TeamController::class, 'resendInvitation'])->name('resend');
            Route::post('/{member}/revoke', [App\Http\Controllers\Business\TeamController::class, 'revokeInvitation'])->name('revoke');
            Route::post('/{member}/suspend', [App\Http\Controllers\Business\TeamController::class, 'suspend'])->name('suspend');
            Route::post('/{member}/reactivate', [App\Http\Controllers\Business\TeamController::class, 'reactivate'])->name('reactivate');
            Route::get('/permissions', [App\Http\Controllers\Business\TeamController::class, 'getPermissions'])->name('permissions');
        });

        // ========================================
        // SHIFT MANAGEMENT ACTION ROUTES
        // ========================================
        Route::prefix('shifts')->name('shifts.')->group(function () {
            Route::get('/create', [App\Http\Controllers\Shift\ShiftController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Shift\ShiftController::class, 'store'])->name('store');
            Route::get('/{shift}', [App\Http\Controllers\Business\ShiftManagementController::class, 'show'])->name('show');
            Route::get('/{shift}/edit', [App\Http\Controllers\Business\ShiftManagementController::class, 'edit'])->name('edit');
            Route::put('/{shift}', [App\Http\Controllers\Business\ShiftManagementController::class, 'update'])->name('update');
            Route::delete('/{shift}', [App\Http\Controllers\Business\ShiftManagementController::class, 'destroy'])->name('destroy');
            Route::get('/{shift}/applications', [App\Http\Controllers\Business\ShiftManagementController::class, 'viewApplications'])->name('applications');
            Route::post('/{shift}/start', [App\Http\Controllers\Business\ShiftManagementController::class, 'markShiftStarted'])->name('start');
            Route::post('/{shift}/complete', [App\Http\Controllers\Business\ShiftManagementController::class, 'markShiftCompleted'])->name('complete');
            Route::post('/{shift}/cancel', [App\Http\Controllers\Business\ShiftManagementController::class, 'cancelShift'])->name('cancel');
            Route::post('/{shift}/invite', [App\Http\Controllers\Business\ShiftManagementController::class, 'inviteWorker'])->name('invite');
            Route::get('/{shift}/analytics', [App\Http\Controllers\Business\ShiftManagementController::class, 'analytics'])->name('analytics');
        });

        // ========================================
        // ANALYTICS ROUTES
        // ========================================
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [App\Http\Controllers\Business\AnalyticsController::class, 'index'])->name('index');
            Route::get('/trend-data', [App\Http\Controllers\Business\AnalyticsController::class, 'getTrendData'])->name('trend-data');
            Route::get('/spend-by-role', [App\Http\Controllers\Business\AnalyticsController::class, 'getSpendByRole'])->name('spend-by-role');
            Route::get('/venue-comparison', [App\Http\Controllers\Business\AnalyticsController::class, 'getVenueComparison'])->name('venue-comparison');
            Route::get('/budget-alerts', [App\Http\Controllers\Business\AnalyticsController::class, 'getBudgetAlerts'])->name('budget-alerts');
            Route::get('/cancellation-history', [App\Http\Controllers\Business\AnalyticsController::class, 'getCancellationHistory'])->name('cancellation-history');
            Route::get('/export-pdf', [App\Http\Controllers\Business\AnalyticsController::class, 'exportPDF'])->name('export-pdf');
            Route::get('/export-csv', [App\Http\Controllers\Business\AnalyticsController::class, 'exportCSV'])->name('export-csv');
            Route::get('/export-xlsx', [App\Http\Controllers\Business\AnalyticsController::class, 'exportExcel'])->name('export-xlsx');
        });

        // Application Management
        Route::prefix('applications')->name('applications.')->group(function () {
            Route::post('/{application}/approve', [App\Http\Controllers\Business\ShiftManagementController::class, 'assignWorker'])->name('approve');
            Route::post('/{application}/reject', [App\Http\Controllers\Business\ShiftManagementController::class, 'rejectApplication'])->name('reject');
        });

        // Assignment Management
        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::post('/{assignment}/unassign', [App\Http\Controllers\Business\ShiftManagementController::class, 'unassignWorker'])->name('unassign');
            Route::post('/{assignment}/no-show', [App\Http\Controllers\Business\ShiftManagementController::class, 'markNoShow'])->name('no-show');
        });

        // ========================================
        // CREDIT & INVOICING ROUTES
        // ========================================
        Route::prefix('credit')->name('credit.')->group(function () {
            Route::get('/', [App\Http\Controllers\Business\CreditController::class, 'index'])->name('index');
            Route::get('/transactions', [App\Http\Controllers\Business\CreditController::class, 'transactions'])->name('transactions');
            Route::get('/invoices', [App\Http\Controllers\Business\CreditController::class, 'invoices'])->name('invoices');
            Route::get('/invoices/{invoice}', [App\Http\Controllers\Business\CreditController::class, 'invoiceShow'])->name('invoices.show');
            Route::get('/invoices/{invoice}/download', [App\Http\Controllers\Business\CreditController::class, 'invoiceDownload'])->name('invoices.download');
            Route::post('/invoices/{invoice}/pay', [App\Http\Controllers\Business\CreditController::class, 'invoicePayment'])->name('invoices.pay');
            Route::get('/apply', [App\Http\Controllers\Business\CreditController::class, 'apply'])->name('apply');
            Route::post('/apply', [App\Http\Controllers\Business\CreditController::class, 'submitApplication'])->name('apply.submit');
        });

        // ========================================
        // WKR-004: BUSINESS RATINGS ROUTES
        // ========================================
        Route::prefix('ratings')->name('ratings.')->group(function () {
            Route::get('/breakdown', [App\Http\Controllers\RatingController::class, 'businessBreakdown'])->name('breakdown');
        });

        Route::prefix('shifts/{shift}/assignments/{assignment}')->name('shifts.assignments.')->group(function () {
            Route::get('/rate', [App\Http\Controllers\RatingController::class, 'createBusinessRating'])->name('rate');
            Route::post('/rate', [App\Http\Controllers\RatingController::class, 'storeBusinessRating'])->name('rate.store');
        });

        // ========================================
        // BULK SHIFT UPLOAD ROUTES
        // ========================================
        Route::prefix('bulk-shifts')->name('bulk-shifts.')->group(function () {
            Route::get('/', [App\Http\Controllers\Business\BulkShiftController::class, 'index'])->name('index');
            Route::get('/template', [App\Http\Controllers\Business\BulkShiftController::class, 'downloadTemplate'])->name('template');
            Route::post('/validate', [App\Http\Controllers\Business\BulkShiftController::class, 'validateUpload'])->name('validate');
            Route::post('/upload', [App\Http\Controllers\Business\BulkShiftController::class, 'upload'])->name('upload');
            Route::get('/status/{batchId}', [App\Http\Controllers\Business\BulkShiftController::class, 'status'])->name('status');
        });

        // ========================================
        // PROFILE UPDATE ROUTES
        // ========================================
        Route::post('/profile', [App\Http\Controllers\Business\ProfileController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/logo', [App\Http\Controllers\Business\ProfileController::class, 'uploadLogo'])->name('profile.logo');

        // ========================================
        // BIZ-005: ROSTER MANAGEMENT ROUTES
        // ========================================
        Route::prefix('rosters')->name('rosters.')->group(function () {
            Route::get('/', [App\Http\Controllers\Business\RosterController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Business\RosterController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Business\RosterController::class, 'store'])->name('store');
            Route::get('/{roster}', [App\Http\Controllers\Business\RosterController::class, 'show'])->name('show');
            Route::get('/{roster}/edit', [App\Http\Controllers\Business\RosterController::class, 'edit'])->name('edit');
            Route::put('/{roster}', [App\Http\Controllers\Business\RosterController::class, 'update'])->name('update');
            Route::delete('/{roster}', [App\Http\Controllers\Business\RosterController::class, 'destroy'])->name('destroy');

            // Member management
            Route::get('/{roster}/add-member', [App\Http\Controllers\Business\RosterController::class, 'addMemberForm'])->name('add-member');
            Route::get('/{roster}/search-workers', [App\Http\Controllers\Business\RosterController::class, 'searchWorkers'])->name('search-workers');
            Route::post('/{roster}/members', [App\Http\Controllers\Business\RosterController::class, 'addMember'])->name('members.add');
            Route::put('/{roster}/members/{member}', [App\Http\Controllers\Business\RosterController::class, 'updateMember'])->name('members.update');
            Route::delete('/{roster}/members/{member}', [App\Http\Controllers\Business\RosterController::class, 'removeMember'])->name('members.remove');
            Route::post('/{roster}/members/{member}/move', [App\Http\Controllers\Business\RosterController::class, 'moveMember'])->name('members.move');

            // Invitations
            Route::post('/{roster}/invite', [App\Http\Controllers\Business\RosterController::class, 'inviteWorker'])->name('invite');
            Route::post('/{roster}/bulk-invite', [App\Http\Controllers\Business\RosterController::class, 'bulkInvite'])->name('bulk-invite');
            Route::post('/{roster}/bulk-add', [App\Http\Controllers\Business\RosterController::class, 'bulkAdd'])->name('bulk-add');

            // Shift integration
            Route::get('/for-shift', [App\Http\Controllers\Business\RosterController::class, 'forShift'])->name('for-shift');
        });

        // Blacklist management (quick actions)
        Route::post('/workers/blacklist', [App\Http\Controllers\Business\RosterController::class, 'blacklistWorker'])->name('workers.blacklist');
        Route::post('/workers/unblacklist', [App\Http\Controllers\Business\RosterController::class, 'unblacklistWorker'])->name('workers.unblacklist');

        // ========================================
        // BIZ-010: COMMUNICATION TEMPLATES ROUTES
        // ========================================
        Route::prefix('communication-templates')->name('communication-templates.')->group(function () {
            Route::get('/', [App\Http\Controllers\Business\TemplateController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Business\TemplateController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Business\TemplateController::class, 'store'])->name('store');
            Route::get('/history', [App\Http\Controllers\Business\TemplateController::class, 'history'])->name('history');
            Route::get('/variables', [App\Http\Controllers\Business\TemplateController::class, 'getVariables'])->name('variables');
            Route::post('/render-preview', [App\Http\Controllers\Business\TemplateController::class, 'renderPreview'])->name('render-preview');
            Route::get('/{template}', [App\Http\Controllers\Business\TemplateController::class, 'show'])->name('show');
            Route::get('/{template}/edit', [App\Http\Controllers\Business\TemplateController::class, 'edit'])->name('edit');
            Route::put('/{template}', [App\Http\Controllers\Business\TemplateController::class, 'update'])->name('update');
            Route::delete('/{template}', [App\Http\Controllers\Business\TemplateController::class, 'destroy'])->name('destroy');
            Route::get('/{template}/preview', [App\Http\Controllers\Business\TemplateController::class, 'preview'])->name('preview');
            Route::post('/{template}/duplicate', [App\Http\Controllers\Business\TemplateController::class, 'duplicate'])->name('duplicate');
            Route::post('/{template}/set-default', [App\Http\Controllers\Business\TemplateController::class, 'setDefault'])->name('set-default');
            Route::post('/{template}/toggle-active', [App\Http\Controllers\Business\TemplateController::class, 'toggleActive'])->name('toggle-active');
            Route::get('/{template}/send', [App\Http\Controllers\Business\TemplateController::class, 'showSendForm'])->name('send.form');
            Route::post('/{template}/send', [App\Http\Controllers\Business\TemplateController::class, 'send'])->name('send');
        });
    });

    // Worker Routes (accessible without activation for onboarding)
    Route::prefix('worker')->name('worker.')->middleware(['auth', 'role:worker'])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'workerDashboard'])->name('dashboard');
        // Note: worker.assignments.index is defined in the assignments prefix group below
        // Route::get('/assignments', ...)->name('assignments'); - removed, conflicts with assignments prefix group
        Route::get('/profile', [App\Http\Controllers\DashboardController::class, 'profile'])->name('profile');
        Route::get('/payment-setup', [App\Http\Controllers\Worker\PaymentSetupController::class, 'index'])->name('payment-setup');
        Route::get('/skills', [App\Http\Controllers\Worker\SkillsController::class, 'index'])->name('skills');
        Route::get('/certifications', [App\Http\Controllers\Worker\CertificationController::class, 'index'])->name('certifications');
        Route::get('/availability', [App\Http\Controllers\Worker\AvailabilityController::class, 'index'])->name('availability');
        Route::post('/availability', [App\Http\Controllers\Worker\AvailabilityController::class, 'store'])->name('availability.store');
        Route::post('/blackouts', [App\Http\Controllers\Worker\AvailabilityController::class, 'storeBlackout'])->name('blackouts.store');
        Route::get('/applications', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'myApplications'])->name('applications');

        // New navigation routes
        Route::get('/calendar', [App\Http\Controllers\Worker\DashboardController::class, 'calendar'])->name('calendar');
        Route::get('/documents', [App\Http\Controllers\Worker\DashboardController::class, 'documents'])->name('documents');
        Route::get('/preferences', [App\Http\Controllers\Worker\DashboardController::class, 'preferences'])->name('preferences');

        // ========================================
        // WKR-006: EARNINGS DASHBOARD ROUTES
        // ========================================
        Route::prefix('earnings')->name('earnings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\EarningsController::class, 'dashboard'])->name('index');
            Route::get('/history', [App\Http\Controllers\Worker\EarningsController::class, 'history'])->name('history');
            Route::get('/tax-summary', [App\Http\Controllers\Worker\EarningsController::class, 'taxSummary'])->name('tax-summary');
            Route::get('/export', [App\Http\Controllers\Worker\EarningsController::class, 'export'])->name('export');

            // API endpoints for async data loading
            Route::get('/api/compare', [App\Http\Controllers\Worker\EarningsController::class, 'comparePeriodsApi'])->name('api.compare');
            Route::get('/api/chart', [App\Http\Controllers\Worker\EarningsController::class, 'chartDataApi'])->name('api.chart');
            Route::post('/api/refresh', [App\Http\Controllers\Worker\EarningsController::class, 'refreshSummary'])->name('api.refresh');
        });
        Route::post('/preferences', [App\Http\Controllers\Worker\DashboardController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/shift-history', [App\Http\Controllers\Worker\DashboardController::class, 'shiftHistory'])->name('shift-history');
        Route::get('/tax-documents', [App\Http\Controllers\Worker\DashboardController::class, 'taxDocuments'])->name('tax-documents');
        Route::get('/withdraw', [App\Http\Controllers\Worker\DashboardController::class, 'withdraw'])->name('withdraw');

        // ========================================
        // FIN-004: INSTAPAY (SAME-DAY PAYOUT) ROUTES
        // ========================================
        Route::prefix('instapay')->name('instapay.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\InstaPayController::class, 'index'])->name('index');
        });

        // Activation routes
        Route::prefix('activation')->name('activation.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\ActivationController::class, 'index'])->name('index');
        });

        // ========================================
        // WORKER PROFILE UPDATE ROUTES
        // ========================================
        Route::post('/profile', [App\Http\Controllers\Worker\DashboardController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/photo', [App\Http\Controllers\Worker\ProfileController::class, 'uploadPhoto'])->name('profile.photo');

        // ========================================
        // WORKER PORTFOLIO ROUTES
        // ========================================
        Route::prefix('portfolio')->name('portfolio.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\PortfolioController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Worker\PortfolioController::class, 'store'])->name('store');
            Route::get('/{item}', [App\Http\Controllers\Worker\PortfolioController::class, 'show'])->name('show');
            Route::put('/{item}', [App\Http\Controllers\Worker\PortfolioController::class, 'update'])->name('update');
            Route::delete('/{item}', [App\Http\Controllers\Worker\PortfolioController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [App\Http\Controllers\Worker\PortfolioController::class, 'reorder'])->name('reorder');
        });

        // ========================================
        // WORKER ONBOARDING ROUTES
        // ========================================
        Route::prefix('onboarding')->name('onboarding.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\OnboardingController::class, 'dashboard'])->name('index');
            Route::get('/complete-profile', [App\Http\Controllers\Worker\OnboardingController::class, 'completeProfile'])->name('complete-profile');
            Route::post('/complete-step', [App\Http\Controllers\Worker\OnboardingController::class, 'completeStep'])->name('complete-step');
            Route::post('/skip-step', [App\Http\Controllers\Worker\OnboardingController::class, 'skipOptionalStep'])->name('skip-step');
        });

        // ========================================
        // WORKER SHIFT APPLICATION ACTIONS
        // ========================================
        Route::prefix('applications')->name('applications.')->group(function () {
            Route::post('/{application}/withdraw', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'withdraw'])->name('withdraw');
        });

        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'myAssignments'])->name('index');
            Route::get('/{assignment}', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'showAssignment'])->name('show');
            Route::post('/{assignment}/check-in', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'checkIn'])->name('check-in');
            Route::post('/{assignment}/check-out', [App\Http\Controllers\Worker\ShiftApplicationController::class, 'checkOut'])->name('check-out');
        });

        // ========================================
        // WORKER VERIFICATION ROUTES
        // ========================================
        Route::prefix('identity')->name('identity.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\IdentityVerificationController::class, 'index'])->name('index');
            Route::post('/initiate', [App\Http\Controllers\Worker\IdentityVerificationController::class, 'initiateVerification'])->name('initiate');
        });

        Route::prefix('right-to-work')->name('rtw.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\RightToWorkController::class, 'index'])->name('index');
            Route::post('/initiate', [App\Http\Controllers\Worker\RightToWorkController::class, 'initiateVerification'])->name('initiate');
            Route::post('/documents', [App\Http\Controllers\Worker\RightToWorkController::class, 'submitDocuments'])->name('documents');
        });

        Route::prefix('background-check')->name('background-check.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\BackgroundCheckController::class, 'index'])->name('index');
            Route::post('/initiate', [App\Http\Controllers\Worker\BackgroundCheckController::class, 'initiateBackgroundCheck'])->name('initiate');
            Route::post('/consent', [App\Http\Controllers\Worker\BackgroundCheckController::class, 'submitConsent'])->name('consent');
        });

        // ========================================
        // WKR-001: KYC VERIFICATION ROUTES
        // ========================================
        Route::prefix('kyc')->name('kyc.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\KycController::class, 'index'])->name('index');
            Route::get('/status', [App\Http\Controllers\Worker\KycController::class, 'status'])->name('status');
            Route::get('/create', [App\Http\Controllers\Worker\KycController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Worker\KycController::class, 'store'])->name('store');
            Route::get('/requirements', [App\Http\Controllers\Worker\KycController::class, 'requirements'])->name('requirements');
            Route::get('/history', [App\Http\Controllers\Worker\KycController::class, 'history'])->name('history');
            Route::get('/{id}', [App\Http\Controllers\Worker\KycController::class, 'show'])->name('show');
            Route::get('/resubmit/{id}', [App\Http\Controllers\Worker\KycController::class, 'resubmit'])->name('resubmit');
        });

        // ========================================
        // SL-005: FACE ENROLLMENT ROUTES
        // ========================================
        Route::prefix('face-enrollment')->name('face-enrollment.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\FaceEnrollmentController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Worker\FaceEnrollmentController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Worker\FaceEnrollmentController::class, 'store'])->name('store');
            Route::put('/', [App\Http\Controllers\Worker\FaceEnrollmentController::class, 'update'])->name('update');
            Route::delete('/', [App\Http\Controllers\Worker\FaceEnrollmentController::class, 'destroy'])->name('destroy');
            Route::post('/test', [App\Http\Controllers\Worker\FaceEnrollmentController::class, 'testVerification'])->name('test');
            Route::get('/status', [App\Http\Controllers\Worker\FaceEnrollmentController::class, 'status'])->name('status');
            Route::get('/history', [App\Http\Controllers\Worker\FaceEnrollmentController::class, 'history'])->name('history');
            Route::post('/add-photo', [App\Http\Controllers\Worker\FaceEnrollmentController::class, 'addPhoto'])->name('add-photo');
        });

        // ========================================
        // WORKER PAYMENT & WITHDRAWAL ROUTES
        // ========================================
        Route::post('/withdraw', [App\Http\Controllers\Worker\DashboardController::class, 'processWithdrawal'])->name('withdraw.process');
        Route::post('/payment-setup/initiate', [App\Http\Controllers\Worker\PaymentSetupController::class, 'initiateOnboarding'])->name('payment-setup.initiate');

        // ========================================
        // SAF-005: HEALTH PROTOCOL ROUTES
        // ========================================
        Route::prefix('health')->name('health.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\HealthDeclarationController::class, 'status'])->name('status');
            Route::get('/declare', [App\Http\Controllers\Worker\HealthDeclarationController::class, 'create'])->name('declare');
            Route::get('/declare/{shift}', [App\Http\Controllers\Worker\HealthDeclarationController::class, 'create'])->name('declare.shift');
            Route::post('/declare', [App\Http\Controllers\Worker\HealthDeclarationController::class, 'store'])->name('declare.store');
            Route::get('/clearance/{shift}', [App\Http\Controllers\Worker\HealthDeclarationController::class, 'checkClearance'])->name('clearance');
            Route::get('/vaccinations', [App\Http\Controllers\Worker\HealthDeclarationController::class, 'vaccinations'])->name('vaccinations');
            Route::post('/vaccinations', [App\Http\Controllers\Worker\HealthDeclarationController::class, 'storeVaccination'])->name('vaccinations.store');
            Route::delete('/vaccinations/{id}', [App\Http\Controllers\Worker\HealthDeclarationController::class, 'destroyVaccination'])->name('vaccinations.destroy');
            Route::post('/report-exposure/{shift}', [App\Http\Controllers\Worker\HealthDeclarationController::class, 'reportExposure'])->name('report-exposure');
            Route::get('/ppe-types', [App\Http\Controllers\Worker\HealthDeclarationController::class, 'ppeTypes'])->name('ppe-types');
        });

        // ========================================
        // WKR-004: WORKER RATINGS ROUTES
        // ========================================
        Route::prefix('ratings')->name('ratings.')->group(function () {
            Route::get('/breakdown', [App\Http\Controllers\RatingController::class, 'workerBreakdown'])->name('breakdown');
        });

        Route::prefix('shifts')->name('shifts.')->group(function () {
            Route::get('/{assignment}/rate', [App\Http\Controllers\RatingController::class, 'createWorkerRating'])->name('rate');
            Route::post('/{assignment}/rate', [App\Http\Controllers\RatingController::class, 'storeWorkerRating'])->name('rate.store');
        });

        // ========================================
        // BIZ-005: ROSTER INVITATIONS & MEMBERSHIPS
        // ========================================
        Route::prefix('roster-invitations')->name('roster-invitations.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\RosterInvitationController::class, 'index'])->name('index');
            Route::get('/{invitation}', [App\Http\Controllers\Worker\RosterInvitationController::class, 'show'])->name('show');
            Route::post('/{invitation}/accept', [App\Http\Controllers\Worker\RosterInvitationController::class, 'accept'])->name('accept');
            Route::post('/{invitation}/decline', [App\Http\Controllers\Worker\RosterInvitationController::class, 'decline'])->name('decline');
            Route::get('/pending/count', [App\Http\Controllers\Worker\RosterInvitationController::class, 'pendingCount'])->name('pending-count');
        });

        Route::prefix('roster-memberships')->name('roster-memberships.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\RosterInvitationController::class, 'memberships'])->name('index');
            Route::get('/{member}', [App\Http\Controllers\Worker\RosterInvitationController::class, 'showMembership'])->name('show');
            Route::delete('/{member}/leave', [App\Http\Controllers\Worker\RosterInvitationController::class, 'leave'])->name('leave');
        });

        // ========================================
        // SAF-002: INCIDENT REPORTING ROUTES
        // ========================================
        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\IncidentController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Worker\IncidentController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Worker\IncidentController::class, 'store'])->name('store');
            Route::get('/{incident}', [App\Http\Controllers\Worker\IncidentController::class, 'show'])->name('show');
            Route::post('/{incident}/update', [App\Http\Controllers\Worker\IncidentController::class, 'addUpdate'])->name('add-update');
            Route::post('/{incident}/evidence', [App\Http\Controllers\Worker\IncidentController::class, 'addEvidence'])->name('add-evidence');
            Route::post('/{incident}/witness', [App\Http\Controllers\Worker\IncidentController::class, 'addWitness'])->name('add-witness');
            // AJAX endpoints
            Route::get('/ajax/venues', [App\Http\Controllers\Worker\IncidentController::class, 'getVenuesForShift'])->name('ajax.venues');
            Route::get('/ajax/users', [App\Http\Controllers\Worker\IncidentController::class, 'searchUsers'])->name('ajax.users');
        });

        // ========================================
        // SAF-001: EMERGENCY CONTACTS ROUTES
        // ========================================
        Route::prefix('emergency-contacts')->name('emergency-contacts.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\EmergencyContactController::class, 'index'])->name('index');
        });

        // ========================================
        // GLO-003: LABOR LAW COMPLIANCE ROUTES
        // ========================================
        Route::prefix('compliance')->name('compliance.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\ComplianceController::class, 'index'])->name('index');
            Route::get('/weekly-hours', [App\Http\Controllers\Worker\ComplianceController::class, 'weeklyHours'])->name('weekly-hours');
            Route::get('/violations', [App\Http\Controllers\Worker\ComplianceController::class, 'violations'])->name('violations');
            Route::post('/violations/{violation}/acknowledge', [App\Http\Controllers\Worker\ComplianceController::class, 'acknowledgeViolation'])->name('acknowledge-violation');
            Route::get('/opt-out/{rule}', [App\Http\Controllers\Worker\ComplianceController::class, 'showOptOutForm'])->name('opt-out-form');
            Route::post('/opt-out/{rule}', [App\Http\Controllers\Worker\ComplianceController::class, 'submitOptOut'])->name('submit-opt-out');
            Route::delete('/opt-out/{exemption}/withdraw', [App\Http\Controllers\Worker\ComplianceController::class, 'withdrawOptOut'])->name('withdraw-opt-out');
        });

        // ========================================
        // WKR-009: WORKER SUSPENSION ROUTES
        // ========================================
        Route::prefix('suspensions')->name('suspensions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\SuspensionController::class, 'index'])->name('index');
            Route::get('/status', [App\Http\Controllers\Worker\SuspensionController::class, 'status'])->name('status');
            Route::get('/{suspension}', [App\Http\Controllers\Worker\SuspensionController::class, 'show'])->name('show');
            Route::get('/{suspension}/appeal', [App\Http\Controllers\Worker\SuspensionController::class, 'appealForm'])->name('appeal');
            Route::post('/{suspension}/appeal', [App\Http\Controllers\Worker\SuspensionController::class, 'submitAppeal'])->name('appeal.submit');
            Route::get('/{suspension}/appeal-status', [App\Http\Controllers\Worker\SuspensionController::class, 'appealStatus'])->name('appeal-status');
        });

        // ========================================
        // SAF-004: VENUE SAFETY ROUTES
        // ========================================
        Route::prefix('safety')->name('safety.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\VenueSafetyController::class, 'index'])->name('index');
            Route::get('/venue/{venue}', [App\Http\Controllers\Worker\VenueSafetyController::class, 'showVenueSafety'])->name('venue');
            Route::get('/rate/{shift}', [App\Http\Controllers\Worker\VenueSafetyController::class, 'createRating'])->name('rate');
            Route::post('/rate/{shift}', [App\Http\Controllers\Worker\VenueSafetyController::class, 'storeRating'])->name('rate.store');
            Route::get('/flag/{venue}', [App\Http\Controllers\Worker\VenueSafetyController::class, 'createFlag'])->name('flag.create');
            Route::post('/flag/{venue}', [App\Http\Controllers\Worker\VenueSafetyController::class, 'storeFlag'])->name('flag.store');
            Route::get('/flag/{flag}/show', [App\Http\Controllers\Worker\VenueSafetyController::class, 'showFlag'])->name('flag.show');
            // AJAX endpoints
            Route::get('/api/venue/{venue}/summary', [App\Http\Controllers\Worker\VenueSafetyController::class, 'getVenueSafetySummary'])->name('api.summary');
            Route::get('/api/venue/{venue}/warning', [App\Http\Controllers\Worker\VenueSafetyController::class, 'getSafetyWarning'])->name('api.warning');
            Route::get('/api/shift/{shift}/can-rate', [App\Http\Controllers\Worker\VenueSafetyController::class, 'canRateVenue'])->name('api.can-rate');
            Route::post('/api/rate/{shift}', [App\Http\Controllers\Worker\VenueSafetyController::class, 'submitRatingAjax'])->name('api.rate');
            Route::post('/api/flag/{venue}', [App\Http\Controllers\Worker\VenueSafetyController::class, 'submitFlagAjax'])->name('api.flag');
        });

        // ========================================
        // GLO-002: TAX JURISDICTION ENGINE ROUTES
        // ========================================
        Route::prefix('tax')->name('tax.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\TaxFormController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Worker\TaxFormController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Worker\TaxFormController::class, 'store'])->name('store');
            Route::get('/summary', [App\Http\Controllers\Worker\TaxFormController::class, 'summary'])->name('summary');
            Route::get('/estimate', [App\Http\Controllers\Worker\TaxFormController::class, 'estimate'])->name('estimate');
            Route::get('/{taxForm}', [App\Http\Controllers\Worker\TaxFormController::class, 'show'])->name('show');
            Route::get('/{taxForm}/edit', [App\Http\Controllers\Worker\TaxFormController::class, 'edit'])->name('edit');
            Route::put('/{taxForm}', [App\Http\Controllers\Worker\TaxFormController::class, 'update'])->name('update');
            Route::delete('/{taxForm}', [App\Http\Controllers\Worker\TaxFormController::class, 'destroy'])->name('destroy');
            Route::get('/{taxForm}/download', [App\Http\Controllers\Worker\TaxFormController::class, 'download'])->name('download');
        });

        // ========================================
        // FIN-007: TAX REPORTS ROUTES (1099-NEC, P60, etc.)
        // ========================================
        Route::prefix('tax-reports')->name('tax-reports.')->group(function () {
            Route::get('/', [App\Http\Controllers\Worker\TaxReportController::class, 'index'])->name('index');
            Route::post('/request', [App\Http\Controllers\Worker\TaxReportController::class, 'request'])->name('request');
            Route::get('/earnings', [App\Http\Controllers\Worker\TaxReportController::class, 'earningsSummary'])->name('earnings');
            Route::get('/{taxReport}', [App\Http\Controllers\Worker\TaxReportController::class, 'show'])->name('show');
            Route::get('/{taxReport}/download', [App\Http\Controllers\Worker\TaxReportController::class, 'download'])->name('download');
            Route::get('/{taxReport}/preview', [App\Http\Controllers\Worker\TaxReportController::class, 'preview'])->name('preview');
            Route::post('/{taxReport}/acknowledge', [App\Http\Controllers\Worker\TaxReportController::class, 'acknowledge'])->name('acknowledge');
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
    Route::get('/', [App\Http\Controllers\Business\RegistrationController::class, 'showRegistrationForm'])->name('index');
    Route::get('/verify-email', [App\Http\Controllers\Business\RegistrationController::class, 'verifyEmailLink'])->name('verify-email');
});

Route::prefix('register/worker')->name('worker.register.')->group(function () {
    Route::get('/', [App\Http\Controllers\Worker\RegistrationController::class, 'showRegistrationForm'])->name('index');
    Route::get('/invite/{token}', [App\Http\Controllers\Worker\RegistrationController::class, 'showRegistrationForm'])->name('agency-invite');
});

Route::prefix('worker')->name('worker.')->group(function () {
    Route::get('/verify/email', [App\Http\Controllers\Worker\RegistrationController::class, 'showVerifyEmailForm'])->name('verify.email');
    Route::get('/verify/phone', [App\Http\Controllers\Worker\RegistrationController::class, 'showVerifyPhoneForm'])->name('verify.phone');
});

Route::prefix('register/agency')->name('agency.register.')->group(function () {
    Route::get('/', [App\Http\Controllers\Agency\RegistrationController::class, 'index'])->name('index');
    Route::get('/start', [App\Http\Controllers\Agency\RegistrationController::class, 'start'])->name('start');
    Route::get('/step/{step}', [App\Http\Controllers\Agency\RegistrationController::class, 'showStep'])->name('step');
    Route::post('/step/{step}', [App\Http\Controllers\Agency\RegistrationController::class, 'saveStep'])->name('saveStep');
    Route::get('/step/{step}/previous', [App\Http\Controllers\Agency\RegistrationController::class, 'previousStep'])->name('previous');
    Route::post('/upload-document', [App\Http\Controllers\Agency\RegistrationController::class, 'uploadDocument'])->name('upload');
    Route::delete('/remove-document', [App\Http\Controllers\Agency\RegistrationController::class, 'removeDocument'])->name('remove-document');
    Route::get('/review', [App\Http\Controllers\Agency\RegistrationController::class, 'review'])->name('review');
    Route::post('/submit', [App\Http\Controllers\Agency\RegistrationController::class, 'submitApplication'])->name('submit');
    Route::get('/confirmation/{id}', [App\Http\Controllers\Agency\RegistrationController::class, 'confirmation'])->name('confirmation');
});

// ============================================================================
// GLO-005: PRIVACY COMPLIANCE ROUTES (GDPR/CCPA)
// ============================================================================
Route::prefix('privacy')->name('privacy.')->group(function () {
    // Public DSR submission routes
    Route::get('/request', [App\Http\Controllers\PrivacyController::class, 'showRequestForm'])->name('request-form');
    Route::post('/request', [App\Http\Controllers\PrivacyController::class, 'submitRequest'])->name('submit-request');
    Route::get('/request-submitted', [App\Http\Controllers\PrivacyController::class, 'showRequestSubmitted'])->name('request-submitted');
    Route::get('/verify/{requestNumber}/{token}', [App\Http\Controllers\PrivacyController::class, 'verifyRequest'])->name('verify-request');
    Route::get('/download/{requestNumber}/{token}', [App\Http\Controllers\PrivacyController::class, 'downloadExport'])->name('download-export');

    // Cookie consent API endpoints
    Route::post('/cookie-consent', [App\Http\Controllers\PrivacyController::class, 'recordCookieConsent'])->name('cookie-consent');
    Route::get('/cookie-consent', [App\Http\Controllers\PrivacyController::class, 'getCookieConsent'])->name('cookie-consent.get');

    // Authenticated user privacy settings
    Route::middleware(['auth'])->group(function () {
        Route::get('/', [App\Http\Controllers\PrivacyController::class, 'index'])->name('settings');
        Route::post('/consents', [App\Http\Controllers\PrivacyController::class, 'updateConsents'])->name('update-consents');
        Route::post('/request-export', [App\Http\Controllers\PrivacyController::class, 'requestExport'])->name('request-export');
        Route::post('/request-deletion', [App\Http\Controllers\PrivacyController::class, 'requestDeletion'])->name('request-deletion');
        Route::post('/request-portability', [App\Http\Controllers\PrivacyController::class, 'requestPortability'])->name('request-portability');
        Route::get('/requests', [App\Http\Controllers\PrivacyController::class, 'viewRequests'])->name('view-requests');
        Route::delete('/requests/{dsRequest}', [App\Http\Controllers\PrivacyController::class, 'cancelRequest'])->name('cancel-request');
    });
});

// Admin Privacy/DSR Management Routes
Route::prefix('admin/privacy')->name('admin.privacy.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DataSubjectRequestController::class, 'dashboard'])->name('dashboard');
    Route::get('/dsr', [App\Http\Controllers\Admin\DataSubjectRequestController::class, 'index'])->name('dsr.index');
    Route::get('/dsr/{dataSubjectRequest}', [App\Http\Controllers\Admin\DataSubjectRequestController::class, 'show'])->name('dsr.show');
    Route::post('/dsr/{dataSubjectRequest}/assign', [App\Http\Controllers\Admin\DataSubjectRequestController::class, 'assign'])->name('dsr.assign');
    Route::post('/dsr/{dataSubjectRequest}/process', [App\Http\Controllers\Admin\DataSubjectRequestController::class, 'process'])->name('dsr.process');
    Route::post('/dsr/{dataSubjectRequest}/execute', [App\Http\Controllers\Admin\DataSubjectRequestController::class, 'execute'])->name('dsr.execute');
    Route::post('/dsr/{dataSubjectRequest}/reject', [App\Http\Controllers\Admin\DataSubjectRequestController::class, 'reject'])->name('dsr.reject');
    Route::post('/dsr/{dataSubjectRequest}/note', [App\Http\Controllers\Admin\DataSubjectRequestController::class, 'addNote'])->name('dsr.note');
    Route::get('/dsr/{dataSubjectRequest}/preview', [App\Http\Controllers\Admin\DataSubjectRequestController::class, 'previewData'])->name('dsr.preview');
    Route::get('/report', [App\Http\Controllers\Admin\DataSubjectRequestController::class, 'exportReport'])->name('report');
});

// Dev routes removed per request

// ============================================================================
// GLO-007: Holiday Calendar Routes
// ============================================================================

// Public holiday calendar view
Route::get('/holidays', [App\Http\Controllers\HolidayController::class, 'index'])->name('holidays.index');

// Admin holiday management routes
Route::prefix('admin/holidays')->name('admin.holidays.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\HolidayController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Admin\HolidayController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Admin\HolidayController::class, 'store'])->name('store');
    Route::get('/{holiday}/edit', [App\Http\Controllers\Admin\HolidayController::class, 'edit'])->name('edit');
    Route::put('/{holiday}', [App\Http\Controllers\Admin\HolidayController::class, 'update'])->name('update');
    Route::delete('/{holiday}', [App\Http\Controllers\Admin\HolidayController::class, 'destroy'])->name('destroy');

    // Sync management
    Route::get('/sync', [App\Http\Controllers\Admin\HolidayController::class, 'showSync'])->name('sync.show');
    Route::post('/sync', [App\Http\Controllers\Admin\HolidayController::class, 'sync'])->name('sync');
    Route::post('/sync-api', [App\Http\Controllers\Admin\HolidayController::class, 'syncApi'])->name('sync.api');

    // Bulk operations
    Route::post('/bulk-update-surge', [App\Http\Controllers\Admin\HolidayController::class, 'bulkUpdateSurge'])->name('bulk-update-surge');
    Route::get('/export', [App\Http\Controllers\Admin\HolidayController::class, 'export'])->name('export');
    Route::post('/import', [App\Http\Controllers\Admin\HolidayController::class, 'import'])->name('import');

    // Cache management
    Route::post('/clear-cache', [App\Http\Controllers\Admin\HolidayController::class, 'clearCache'])->name('clear-cache');

    // Statistics API
    Route::get('/statistics', [App\Http\Controllers\Admin\HolidayController::class, 'statistics'])->name('statistics');
});

// ============================================================================
// COM-003: EMAIL COMMUNICATION SYSTEM ROUTES
// ============================================================================

// Public email routes (no auth required)
Route::prefix('email')->name('email.')->group(function () {
    // Email unsubscribe (public - accessible via token in email)
    Route::get('/unsubscribe/{token}', [App\Http\Controllers\EmailPreferencesController::class, 'unsubscribe'])->name('unsubscribe');
    Route::post('/unsubscribe/{token}', [App\Http\Controllers\EmailPreferencesController::class, 'processUnsubscribe'])->name('unsubscribe.process');
    Route::post('/resubscribe/{token}', [App\Http\Controllers\EmailPreferencesController::class, 'resubscribe'])->name('resubscribe');

    // Email tracking (open/click)
    Route::get('/track/open/{id}', [App\Http\Controllers\EmailWebhookController::class, 'trackOpen'])->name('track.open');
    Route::get('/track/click/{id}', [App\Http\Controllers\EmailWebhookController::class, 'trackClick'])->name('track.click');
});

// Email webhooks (no auth - verified by signature)
Route::prefix('webhooks/email')->name('webhooks.email.')->group(function () {
    Route::post('/sendgrid', [App\Http\Controllers\EmailWebhookController::class, 'sendgrid'])->name('sendgrid');
    Route::post('/mailgun', [App\Http\Controllers\EmailWebhookController::class, 'mailgun'])->name('mailgun');
});

// User email preferences (authenticated)
Route::prefix('settings')->middleware(['auth'])->name('settings.')->group(function () {
    Route::get('/email-preferences', [App\Http\Controllers\EmailPreferencesController::class, 'index'])->name('email-preferences');
    Route::post('/email-preferences', [App\Http\Controllers\EmailPreferencesController::class, 'update'])->name('email-preferences.update');
});

// Admin email management routes
Route::prefix('admin/email')->name('admin.email.')->middleware(['auth', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('/', [App\Http\Controllers\Admin\EmailController::class, 'index'])->name('index');

    // Templates
    Route::get('/templates', [App\Http\Controllers\Admin\EmailController::class, 'templates'])->name('templates');
    Route::get('/templates/create', [App\Http\Controllers\Admin\EmailController::class, 'createTemplate'])->name('templates.create');
    Route::post('/templates', [App\Http\Controllers\Admin\EmailController::class, 'storeTemplate'])->name('templates.store');
    Route::get('/templates/{template}/edit', [App\Http\Controllers\Admin\EmailController::class, 'editTemplate'])->name('templates.edit');
    Route::put('/templates/{template}', [App\Http\Controllers\Admin\EmailController::class, 'updateTemplate'])->name('templates.update');
    Route::delete('/templates/{template}', [App\Http\Controllers\Admin\EmailController::class, 'destroyTemplate'])->name('templates.destroy');
    Route::get('/templates/{template}/preview', [App\Http\Controllers\Admin\EmailController::class, 'previewTemplate'])->name('templates.preview');
    Route::post('/templates/{template}/test', [App\Http\Controllers\Admin\EmailController::class, 'sendTestEmail'])->name('templates.test');

    // Logs
    Route::get('/logs', [App\Http\Controllers\Admin\EmailController::class, 'logs'])->name('logs');
    Route::get('/logs/export', [App\Http\Controllers\Admin\EmailController::class, 'exportLogs'])->name('logs.export');
    Route::get('/logs/{log}', [App\Http\Controllers\Admin\EmailController::class, 'showLog'])->name('logs.show');

    // Stats
    Route::get('/stats', [App\Http\Controllers\Admin\EmailController::class, 'stats'])->name('stats');

    // Bounce management
    Route::get('/bounces', [App\Http\Controllers\Admin\EmailController::class, 'bounces'])->name('bounces');
    Route::post('/retry/{log}', [App\Http\Controllers\Admin\EmailController::class, 'retryEmail'])->name('retry');

    // Bulk send
    Route::post('/bulk-send', [App\Http\Controllers\Admin\EmailController::class, 'bulkSend'])->name('bulk-send');
});

// ============================================================================
// FIN-011: SUBSCRIPTION ROUTES
// ============================================================================

// User subscription routes (authenticated)
Route::prefix('subscription')->middleware(['auth', 'verified'])->name('subscription.')->group(function () {
    // View plans
    Route::get('/plans', [App\Http\Controllers\SubscriptionController::class, 'plans'])->name('plans');

    // Checkout
    Route::get('/checkout/{plan}', [App\Http\Controllers\SubscriptionController::class, 'checkout'])->name('checkout');
    Route::post('/subscribe/{plan}', [App\Http\Controllers\SubscriptionController::class, 'subscribe'])->name('subscribe');

    // Manage subscription
    Route::get('/manage', [App\Http\Controllers\SubscriptionController::class, 'manage'])->name('manage');
    Route::post('/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('/resume', [App\Http\Controllers\SubscriptionController::class, 'resume'])->name('resume');
    Route::post('/change-plan/{plan}', [App\Http\Controllers\SubscriptionController::class, 'changePlan'])->name('change-plan');

    // Invoices
    Route::get('/invoices', [App\Http\Controllers\SubscriptionController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/{invoice}/download', [App\Http\Controllers\SubscriptionController::class, 'downloadInvoice'])->name('download-invoice');

    // Payment method
    Route::get('/payment-method', [App\Http\Controllers\SubscriptionController::class, 'paymentMethod'])->name('payment-method');
    Route::post('/payment-method', [App\Http\Controllers\SubscriptionController::class, 'updatePaymentMethod'])->name('update-payment-method');

    // Feature check (API)
    Route::get('/feature/{feature}', [App\Http\Controllers\SubscriptionController::class, 'checkFeature'])->name('check-feature');
});

// Admin subscription management routes
Route::prefix('admin/subscriptions')->name('admin.subscriptions.')->middleware(['auth', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('/', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'index'])->name('index');

    // Plans management
    Route::get('/plans', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'plans'])->name('plans');
    Route::get('/plans/create', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'createPlan'])->name('plans.create');
    Route::post('/plans', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'storePlan'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'editPlan'])->name('plans.edit');
    Route::put('/plans/{plan}', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{plan}', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'deletePlan'])->name('plans.delete');

    // Subscriptions list
    Route::get('/list', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'subscriptions'])->name('list');
    Route::get('/subscriptions/{subscription}', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'viewSubscription'])->name('view');
    Route::post('/subscriptions/{subscription}/cancel', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'cancelSubscription'])->name('cancel');

    // Grant complimentary subscription
    Route::get('/grant', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'grantForm'])->name('grant');
    Route::post('/grant', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'grant'])->name('grant.store');

    // Revenue reporting
    Route::get('/revenue', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'revenue'])->name('revenue');

    // Export
    Route::get('/export', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'export'])->name('export');
});

// Admin configuration management routes
Route::prefix('admin/configuration')->name('admin.configuration.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\ConfigurationController::class, 'index'])->name('index');
    Route::put('/', [App\Http\Controllers\Admin\ConfigurationController::class, 'update'])->name('update');
    Route::get('/history', [App\Http\Controllers\Admin\ConfigurationController::class, 'history'])->name('history');
    Route::get('/setting-history/{key}', [App\Http\Controllers\Admin\ConfigurationController::class, 'settingHistory'])->name('setting-history');
    Route::get('/export', [App\Http\Controllers\Admin\ConfigurationController::class, 'export'])->name('export');
    Route::post('/import', [App\Http\Controllers\Admin\ConfigurationController::class, 'import'])->name('import');
    Route::post('/reset/{key}', [App\Http\Controllers\Admin\ConfigurationController::class, 'reset'])->name('reset');
    Route::post('/reset-all', [App\Http\Controllers\Admin\ConfigurationController::class, 'resetAll'])->name('reset-all');
    Route::get('/all-grouped', [App\Http\Controllers\Admin\ConfigurationController::class, 'allGrouped'])->name('all-grouped');
    Route::post('/batch-update', [App\Http\Controllers\Admin\ConfigurationController::class, 'batchUpdate'])->name('batch-update');
    Route::post('/clear-cache', [App\Http\Controllers\Admin\ConfigurationController::class, 'clearCache'])->name('clear-cache');
});

// Stripe subscription webhook (no auth, csrf exempt, signature verification required)
Route::post('/webhook/stripe/subscription', [App\Http\Controllers\Webhook\StripeSubscriptionWebhookController::class, 'handle'])
    ->name('webhook.stripe.subscription')
    ->middleware(['web', 'webhook.verify:stripe'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// PayPal webhook (no auth, csrf exempt, signature verification required)
Route::post('/webhooks/paypal', [App\Http\Controllers\Webhooks\PayPalWebhookController::class, 'handle'])
    ->name('webhooks.paypal')
    ->middleware(['web', 'webhook.verify:paypal'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Paystack webhook (no auth, csrf exempt, signature verification required)
Route::post('/webhooks/paystack', [App\Http\Controllers\Webhooks\PaystackWebhookController::class, 'handle'])
    ->name('webhooks.paystack')
    ->middleware(['web', 'webhook.verify:paystack'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Stripe Connect webhook (no auth, csrf exempt, signature verification required) - for agency payouts
Route::post('/webhooks/stripe/connect', [App\Http\Controllers\Webhooks\StripeConnectWebhookController::class, 'handle'])
    ->name('webhooks.stripe.connect')
    ->middleware(['web', 'webhook.verify:stripe'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Checkr webhook (no auth, csrf exempt) - for background check status updates
Route::post('/webhooks/checkr', [App\Http\Controllers\Webhooks\CheckrWebhookController::class, 'handleWebhook'])
    ->name('webhooks.checkr')
    ->middleware('web')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Onfido webhook (no auth, csrf exempt) - for identity verification updates
Route::post('/webhooks/onfido', [App\Http\Controllers\Webhooks\OnfidoWebhookController::class, 'handle'])
    ->name('webhooks.onfido')
    ->middleware('web')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ============================================================================
// GLO-010: DATA RESIDENCY ROUTES
// ============================================================================

// User Data Residency Settings (authenticated)
Route::prefix('settings')->middleware(['auth'])->name('settings.')->group(function () {
    Route::get('/data-residency', [App\Http\Controllers\User\DataResidencyController::class, 'index'])->name('data-residency');
    Route::post('/data-residency', [App\Http\Controllers\User\DataResidencyController::class, 'update'])->name('data-residency.update');
    Route::post('/data-residency/consent', [App\Http\Controllers\User\DataResidencyController::class, 'consent'])->name('data-residency.consent');
    Route::get('/data-residency/report', [App\Http\Controllers\User\DataResidencyController::class, 'report'])->name('data-residency.report');
});

// Admin Data Residency Management
Route::prefix('admin/data-residency')->name('admin.data-residency.')->middleware(['auth', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('/', [App\Http\Controllers\Admin\DataResidencyController::class, 'index'])->name('index');

    // Regions
    Route::get('/regions', [App\Http\Controllers\Admin\DataResidencyController::class, 'regions'])->name('regions');
    Route::get('/regions/create', [App\Http\Controllers\Admin\DataResidencyController::class, 'createRegion'])->name('create-region');
    Route::post('/regions', [App\Http\Controllers\Admin\DataResidencyController::class, 'storeRegion'])->name('store-region');
    Route::get('/regions/{region}', [App\Http\Controllers\Admin\DataResidencyController::class, 'showRegion'])->name('show-region');
    Route::get('/regions/{region}/edit', [App\Http\Controllers\Admin\DataResidencyController::class, 'editRegion'])->name('edit-region');
    Route::put('/regions/{region}', [App\Http\Controllers\Admin\DataResidencyController::class, 'updateRegion'])->name('update-region');
    Route::post('/regions/{region}/toggle', [App\Http\Controllers\Admin\DataResidencyController::class, 'toggleRegion'])->name('toggle-region');

    // User Distribution
    Route::get('/user-distribution', [App\Http\Controllers\Admin\DataResidencyController::class, 'userDistribution'])->name('user-distribution');
    Route::get('/user/{targetUser}/report', [App\Http\Controllers\Admin\DataResidencyController::class, 'userReport'])->name('user-report');

    // Transfer Logs
    Route::get('/transfer-logs', [App\Http\Controllers\Admin\DataResidencyController::class, 'transferLogs'])->name('transfer-logs');
    Route::get('/transfer-logs/export', [App\Http\Controllers\Admin\DataResidencyController::class, 'exportTransferLogs'])->name('export-transfer-logs');
    Route::get('/transfer-logs/{transfer}', [App\Http\Controllers\Admin\DataResidencyController::class, 'showTransfer'])->name('show-transfer');

    // Actions
    Route::post('/migrate', [App\Http\Controllers\Admin\DataResidencyController::class, 'initiateMigration'])->name('migrate');
    Route::post('/batch-assign', [App\Http\Controllers\Admin\DataResidencyController::class, 'batchAssign'])->name('batch-assign');
});

// ============================================================================
// FIN-005: PAYROLL PROCESSING SYSTEM ROUTES
// ============================================================================

// Admin Payroll Management Routes
Route::prefix('admin/payroll')->name('admin.payroll.')->middleware(['auth', 'role:admin'])->group(function () {
    // Payroll Run CRUD
    Route::get('/', [App\Http\Controllers\Admin\PayrollController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Admin\PayrollController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Admin\PayrollController::class, 'store'])->name('store');
    Route::get('/{payrollRun}', [App\Http\Controllers\Admin\PayrollController::class, 'show'])->name('show');
    Route::delete('/{payrollRun}', [App\Http\Controllers\Admin\PayrollController::class, 'destroy'])->name('destroy');

    // Payroll Item Management
    Route::post('/{payrollRun}/regenerate-items', [App\Http\Controllers\Admin\PayrollController::class, 'regenerateItems'])->name('regenerate-items');
    Route::post('/{payrollRun}/items', [App\Http\Controllers\Admin\PayrollController::class, 'addItem'])->name('add-item');
    Route::delete('/{payrollRun}/items/{payrollItem}', [App\Http\Controllers\Admin\PayrollController::class, 'removeItem'])->name('remove-item');

    // Workflow Actions
    Route::post('/{payrollRun}/submit-for-approval', [App\Http\Controllers\Admin\PayrollController::class, 'submitForApproval'])->name('submit-for-approval');
    Route::post('/{payrollRun}/approve', [App\Http\Controllers\Admin\PayrollController::class, 'approve'])->name('approve');
    Route::post('/{payrollRun}/reject', [App\Http\Controllers\Admin\PayrollController::class, 'reject'])->name('reject');

    // Processing
    Route::get('/{payrollRun}/process', [App\Http\Controllers\Admin\PayrollController::class, 'process'])->name('process');
    Route::post('/{payrollRun}/execute-process', [App\Http\Controllers\Admin\PayrollController::class, 'executeProcess'])->name('execute-process');
    Route::get('/{payrollRun}/progress', [App\Http\Controllers\Admin\PayrollController::class, 'getProgress'])->name('get-progress');
    Route::post('/{payrollRun}/retry-failed', [App\Http\Controllers\Admin\PayrollController::class, 'retryFailed'])->name('retry-failed');

    // Export & Reports
    Route::get('/{payrollRun}/export', [App\Http\Controllers\Admin\PayrollController::class, 'export'])->name('export');
    Route::get('/{payrollRun}/paystub/{user}', [App\Http\Controllers\Admin\PayrollController::class, 'paystub'])->name('paystub');
});

// Worker Paystub Routes
Route::prefix('worker/paystubs')->name('worker.paystubs.')->middleware(['auth'])->group(function () {
    Route::get('/', [App\Http\Controllers\Worker\PaystubController::class, 'index'])->name('index');
    Route::get('/summary', [App\Http\Controllers\Worker\PaystubController::class, 'summary'])->name('summary');
    Route::get('/{payrollRun}', [App\Http\Controllers\Worker\PaystubController::class, 'show'])->name('show');
    Route::get('/{payrollRun}/download', [App\Http\Controllers\Worker\PaystubController::class, 'download'])->name('download');
    Route::get('/{payrollRun}/preview', [App\Http\Controllers\Worker\PaystubController::class, 'preview'])->name('preview');
});

// ============================================================================
// Route Aliases for Backward Compatibility
// These aliases support legacy code that uses the old naming convention
// ============================================================================
Route::get('/dashboard-worker', fn () => redirect()->route('worker.dashboard'))->name('dashboard.worker');
Route::get('/dashboard-company', fn () => redirect()->route('business.dashboard'))->name('dashboard.company');
Route::get('/dashboard-agency', fn () => redirect()->route('agency.dashboard'))->name('dashboard.agency');
Route::get('/dashboard-admin', fn () => redirect()->route('admin.dashboard'))->name('dashboard.admin');
