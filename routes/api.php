<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Demo route for development only - displays PHP configuration
if (app()->environment('local', 'development')) {
    Route::get('/demo', function () {
        return response()->json([
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'environment' => app()->environment(),
        ]);
    });
}

// Dashboard API (for live updates)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'stats']);
    Route::get('dashboard/notifications/count', [App\Http\Controllers\Api\DashboardController::class, 'notificationsCount']);

    // Legacy notification endpoint (kept for compatibility)
    Route::get('notifications/unread-count', [App\Http\Controllers\Api\DashboardController::class, 'notificationsCount']);

    // Shifts API
    Route::get('shifts', [App\Http\Controllers\Api\ShiftController::class, 'index']);
    Route::get('shifts/{id}', [App\Http\Controllers\Api\ShiftController::class, 'show']);
});

// Live Market API (for real-time updates)
Route::middleware('auth:sanctum')->prefix('market')->group(function () {
    Route::get('/live', [App\Http\Controllers\LiveMarketController::class, 'apiIndex'])->name('api.market.live');
});

// Public Market API
Route::prefix('market')->group(function () {
    Route::get('/public', [App\Http\Controllers\LiveMarketController::class, 'apiIndex'])->name('api.market.public');
    Route::get('/simulate', [App\Http\Controllers\LiveMarketController::class, 'simulate'])->name('api.market.simulate');
});

// ============================================================================
// BIZ-REG-002 & 003: Business Registration & Profile API Routes
// ============================================================================

// Public business registration routes (no auth required)
// SECURITY: Enhanced rate limiting for authentication routes
Route::prefix('business')->name('api.business.')->group(function () {
    // Registration - 5 attempts per hour per IP
    Route::post('/register', [App\Http\Controllers\Business\RegistrationController::class, 'register'])
        ->name('register')
        ->middleware('throttle:registration');

    // Email verification - uses verification-code limiter
    Route::post('/verify-email', [App\Http\Controllers\Business\RegistrationController::class, 'verifyEmail'])
        ->name('verify-email')
        ->middleware('throttle:verification-code');

    // Resend verification - 3 attempts per hour per user
    Route::post('/resend-verification', [App\Http\Controllers\Business\RegistrationController::class, 'resendVerification'])
        ->name('resend-verification')
        ->middleware('throttle:verification');

    // Pre-registration validation
    Route::post('/validate-email', [App\Http\Controllers\Business\RegistrationController::class, 'validateEmail'])
        ->name('validate-email')
        ->middleware('throttle:30,1');

    Route::post('/validate-referral', [App\Http\Controllers\Business\RegistrationController::class, 'validateReferralCode'])
        ->name('validate-referral')
        ->middleware('throttle:30,1');

    // Reference data (public)
    Route::get('/business-types', [App\Http\Controllers\Business\ProfileController::class, 'getBusinessTypes'])
        ->name('business-types')
        ->withoutMiddleware(['auth', 'auth:sanctum']);

    Route::get('/industries', [App\Http\Controllers\Business\ProfileController::class, 'getIndustries'])
        ->name('industries')
        ->withoutMiddleware(['auth', 'auth:sanctum']);

    Route::get('/timezones', [App\Http\Controllers\Business\ProfileController::class, 'getTimezones'])
        ->name('timezones')
        ->withoutMiddleware(['auth', 'auth:sanctum']);

    Route::get('/currencies', [App\Http\Controllers\Business\ProfileController::class, 'getCurrencies'])
        ->name('currencies')
        ->withoutMiddleware(['auth', 'auth:sanctum']);
});

// Authenticated business profile routes
Route::prefix('business')->name('api.business.')->middleware(['auth:sanctum', 'business'])->group(function () {
    // Profile management
    Route::get('/profile', [App\Http\Controllers\Business\ProfileController::class, 'getProfile'])
        ->name('profile');

    Route::put('/profile', [App\Http\Controllers\Business\ProfileController::class, 'updateProfile'])
        ->name('profile.update');

    Route::post('/profile/logo', [App\Http\Controllers\Business\ProfileController::class, 'uploadLogo'])
        ->name('profile.logo');

    Route::get('/profile/completion', [App\Http\Controllers\Business\ProfileController::class, 'getProfileCompletion'])
        ->name('profile.completion');

    // Onboarding Progress (BIZ-REG-010)
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/progress', [App\Http\Controllers\Business\OnboardingController::class, 'getProgress'])
            ->name('progress');
        Route::get('/next-step', [App\Http\Controllers\Business\OnboardingController::class, 'getNextStep'])
            ->name('next-step');
        Route::post('/complete-step', [App\Http\Controllers\Business\OnboardingController::class, 'completeStep'])
            ->name('complete-step');
        Route::post('/skip-step', [App\Http\Controllers\Business\OnboardingController::class, 'skipOptionalStep'])
            ->name('skip-step');
        Route::post('/initialize', [App\Http\Controllers\Business\OnboardingController::class, 'initialize'])
            ->name('initialize');
    });

    // Activation (legacy)
    Route::post('/activate', [App\Http\Controllers\Business\ProfileController::class, 'activate'])
        ->name('activate');

    Route::post('/accept-terms', [App\Http\Controllers\Business\ProfileController::class, 'acceptTerms'])
        ->name('accept-terms');

    // BIZ-REG-011: Business Account Activation
    Route::prefix('activation')->name('activation.')->group(function () {
        Route::get('/status', [App\Http\Controllers\Business\ActivationController::class, 'getActivationStatus'])
            ->name('status');
        Route::post('/activate', [App\Http\Controllers\Business\ActivationController::class, 'activateAccount'])
            ->name('activate');
        Route::get('/can-post-shifts', [App\Http\Controllers\Business\ActivationController::class, 'canPostShifts'])
            ->name('can-post-shifts');
    });
});

// ============================================================================
// STAFF-REG-002: Worker Registration & Verification API Routes
// ============================================================================

// Public registration routes (no auth required)
// SECURITY: Enhanced rate limiting for authentication routes
Route::prefix('worker')->name('api.worker.')->group(function () {
    // Registration - 5 attempts per hour per IP
    Route::post('/register', [App\Http\Controllers\Worker\RegistrationController::class, 'register'])
        ->name('register')
        ->middleware('throttle:registration');

    // Pre-registration validation
    Route::post('/check-email', [App\Http\Controllers\Worker\RegistrationController::class, 'checkEmailAvailability'])
        ->name('check-email')
        ->middleware('throttle:30,1');
    Route::post('/check-phone', [App\Http\Controllers\Worker\RegistrationController::class, 'checkPhoneAvailability'])
        ->name('check-phone')
        ->middleware('throttle:30,1');
    Route::post('/validate-referral', [App\Http\Controllers\Worker\RegistrationController::class, 'validateReferralCode'])
        ->name('validate-referral')
        ->middleware('throttle:30,1');
});

// Authenticated verification routes
// SECURITY: Enhanced rate limiting for verification endpoints
Route::prefix('worker')->name('api.worker.')->middleware('auth:sanctum')->group(function () {
    // Email verification - 5 attempts per 10 minutes per user
    Route::post('/verify-email', [App\Http\Controllers\Worker\RegistrationController::class, 'verifyEmail'])
        ->name('verify-email')
        ->middleware('throttle:verification-code');

    // Phone verification - 5 attempts per 10 minutes per user
    Route::post('/verify-phone', [App\Http\Controllers\Worker\RegistrationController::class, 'verifyPhone'])
        ->name('verify-phone')
        ->middleware('throttle:verification-code');

    // Resend verification - 3 attempts per hour per user
    Route::post('/resend-verification', [App\Http\Controllers\Worker\RegistrationController::class, 'resendVerification'])
        ->name('resend-verification')
        ->middleware('throttle:verification');

    // Verification status
    Route::get('/verification-status', [App\Http\Controllers\Worker\RegistrationController::class, 'getVerificationStatus'])
        ->name('verification-status');
});

// Social Authentication API Routes
Route::prefix('auth/social')->name('api.auth.social.')->group(function () {
    Route::get('/{provider}', [App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])
        ->name('redirect')
        ->where('provider', 'google|apple|facebook');
    Route::get('/{provider}/callback', [App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])
        ->name('callback')
        ->where('provider', 'google|apple|facebook');
});

// Authenticated social account management
Route::prefix('auth/social')->name('api.auth.social.')->middleware('auth:sanctum')->group(function () {
    Route::delete('/{provider}/disconnect', [App\Http\Controllers\Auth\SocialAuthController::class, 'disconnect'])
        ->name('disconnect')
        ->where('provider', 'google|apple|facebook');
    Route::get('/accounts', [App\Http\Controllers\Auth\SocialAuthController::class, 'getConnectedAccounts'])
        ->name('accounts');
});

// ============================================================================
// STAFF-REG-009 & 010: Worker Availability & Onboarding API Routes
// ============================================================================

Route::prefix('worker')->name('api.worker.')->middleware(['auth:sanctum', 'worker'])->group(function () {

    // Availability Management (STAFF-REG-009)
    Route::prefix('availability')->name('availability.')->group(function () {
        Route::get('/', [App\Http\Controllers\Worker\AvailabilityController::class, 'getAvailability'])
            ->name('index');
        Route::put('/schedule', [App\Http\Controllers\Worker\AvailabilityController::class, 'setWeeklySchedule'])
            ->name('schedule');
        Route::post('/override', [App\Http\Controllers\Worker\AvailabilityController::class, 'addDateOverride'])
            ->name('override');
        Route::delete('/override/{id}', [App\Http\Controllers\Worker\AvailabilityController::class, 'deleteOverride'])
            ->name('override.delete');
        Route::put('/preferences', [App\Http\Controllers\Worker\AvailabilityController::class, 'setPreferences'])
            ->name('preferences');
        Route::post('/blackout', [App\Http\Controllers\Worker\AvailabilityController::class, 'addBlackoutDate'])
            ->name('blackout');
        Route::delete('/blackout/{id}', [App\Http\Controllers\Worker\AvailabilityController::class, 'deleteBlackoutDate'])
            ->name('blackout.delete');
        Route::get('/slots', [App\Http\Controllers\Worker\AvailabilityController::class, 'getAvailableSlots'])
            ->name('slots');
        Route::post('/check', [App\Http\Controllers\Worker\AvailabilityController::class, 'checkAvailability'])
            ->name('check');
    });

    // Onboarding Progress (STAFF-REG-010)
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/progress', [App\Http\Controllers\Worker\OnboardingController::class, 'getProgress'])
            ->name('progress');
        Route::get('/next-step', [App\Http\Controllers\Worker\OnboardingController::class, 'getNextStep'])
            ->name('next-step');
        Route::post('/complete-step', [App\Http\Controllers\Worker\OnboardingController::class, 'completeStep'])
            ->name('complete-step');
        Route::post('/skip-step', [App\Http\Controllers\Worker\OnboardingController::class, 'skipOptionalStep'])
            ->name('skip-step');
        Route::post('/initialize', [App\Http\Controllers\Worker\OnboardingController::class, 'initialize'])
            ->name('initialize');
    });

    // Activation (STAFF-REG-010)
    Route::prefix('activation')->name('activation.')->group(function () {
        Route::get('/eligibility', [App\Http\Controllers\Worker\ActivationController::class, 'checkEligibility'])
            ->name('eligibility');
        Route::post('/activate', [App\Http\Controllers\Worker\ActivationController::class, 'activate'])
            ->name('activate');
        Route::get('/status', [App\Http\Controllers\Worker\ActivationController::class, 'getActivationStatus'])
            ->name('status');
        Route::post('/referral-code', [App\Http\Controllers\Worker\ActivationController::class, 'applyReferralCode'])
            ->name('referral-code');
    });

    // Profile Management (STAFF-REG-003)
    Route::prefix('profile')->name('profile.')->group(function () {
        // Get profile
        Route::get('/', [App\Http\Controllers\Worker\ProfileController::class, 'show'])
            ->name('show');

        // Update profile
        Route::put('/', [App\Http\Controllers\Worker\ProfileController::class, 'update'])
            ->name('update');

        // Upload profile photo
        Route::post('/photo', [App\Http\Controllers\Worker\ProfileController::class, 'uploadPhoto'])
            ->name('photo');

        // Profile completion
        Route::get('/completion', [App\Http\Controllers\Worker\ProfileController::class, 'getCompletion'])
            ->name('completion');

        // Profile suggestions
        Route::get('/suggestions', [App\Http\Controllers\Worker\ProfileController::class, 'getSuggestions'])
            ->name('suggestions');

        // Field metadata
        Route::get('/fields', [App\Http\Controllers\Worker\ProfileController::class, 'getFields'])
            ->name('fields');

        // Age verification
        Route::post('/verify-age', [App\Http\Controllers\Worker\ProfileController::class, 'verifyAge'])
            ->name('verify-age');

        // Geocode location
        Route::post('/geocode', [App\Http\Controllers\Worker\ProfileController::class, 'geocodeLocation'])
            ->name('geocode');
    });

    // Skills Management (STAFF-REG-007)
    Route::prefix('skills')->name('skills.')->group(function () {
        // Available skills
        Route::get('/available', [App\Http\Controllers\Worker\SkillsController::class, 'getAvailableSkills'])
            ->name('available');

        // Skills by category
        Route::get('/category', [App\Http\Controllers\Worker\SkillsController::class, 'getSkillsByCategory'])
            ->name('category');

        // Get categories for industry
        Route::get('/categories', [App\Http\Controllers\Worker\SkillsController::class, 'getCategories'])
            ->name('categories');

        // Search skills
        Route::get('/search', [App\Http\Controllers\Worker\SkillsController::class, 'search'])
            ->name('search');

        // Worker's skills
        Route::get('/', [App\Http\Controllers\Worker\SkillsController::class, 'getSkills'])
            ->name('index');

        // Add skill
        Route::post('/', [App\Http\Controllers\Worker\SkillsController::class, 'store'])
            ->name('store');

        // Update skill
        Route::put('/{id}', [App\Http\Controllers\Worker\SkillsController::class, 'update'])
            ->name('update');

        // Remove skill
        Route::delete('/{id}', [App\Http\Controllers\Worker\SkillsController::class, 'destroy'])
            ->name('destroy');

        // Get required certifications for skill
        Route::get('/{id}/certifications', [App\Http\Controllers\Worker\SkillsController::class, 'getRequiredCertifications'])
            ->name('certifications');

        // Check certification requirements for skill
        Route::get('/{id}/check-requirements', [App\Http\Controllers\Worker\SkillsController::class, 'checkCertificationRequirements'])
            ->name('check-requirements');
    });

    // Certification Management (STAFF-REG-007)
    Route::prefix('certifications')->name('certifications.')->group(function () {
        // Available certification types
        Route::get('/types', [App\Http\Controllers\Worker\CertificationController::class, 'getAvailableTypes'])
            ->name('types');

        // Worker's certifications
        Route::get('/', [App\Http\Controllers\Worker\CertificationController::class, 'getCertifications'])
            ->name('index');

        // Get single certification
        Route::get('/{id}', [App\Http\Controllers\Worker\CertificationController::class, 'show'])
            ->name('show');

        // Submit new certification
        Route::post('/', [App\Http\Controllers\Worker\CertificationController::class, 'store'])
            ->name('store');

        // Update certification
        Route::put('/{id}', [App\Http\Controllers\Worker\CertificationController::class, 'update'])
            ->name('update');

        // Delete certification
        Route::delete('/{id}', [App\Http\Controllers\Worker\CertificationController::class, 'destroy'])
            ->name('destroy');

        // Upload additional document
        Route::post('/{id}/documents', [App\Http\Controllers\Worker\CertificationController::class, 'uploadDocument'])
            ->name('documents');

        // Start renewal process
        Route::post('/{id}/renew', [App\Http\Controllers\Worker\CertificationController::class, 'startRenewal'])
            ->name('renew');

        // Check expiry status
        Route::get('/{id}/expiry', [App\Http\Controllers\Worker\CertificationController::class, 'checkExpiry'])
            ->name('expiry');
    });

    // Emergency Contacts Management (SAF-001)
    Route::prefix('emergency-contacts')->name('emergency-contacts.')->group(function () {
        Route::get('/', [App\Http\Controllers\Worker\EmergencyContactController::class, 'list'])
            ->name('index');
        Route::post('/', [App\Http\Controllers\Worker\EmergencyContactController::class, 'store'])
            ->name('store');
        Route::put('/{id}', [App\Http\Controllers\Worker\EmergencyContactController::class, 'update'])
            ->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Worker\EmergencyContactController::class, 'destroy'])
            ->name('destroy');
        Route::post('/{id}/verify', [App\Http\Controllers\Worker\EmergencyContactController::class, 'verify'])
            ->name('verify');
        Route::post('/{id}/resend-verification', [App\Http\Controllers\Worker\EmergencyContactController::class, 'resendVerification'])
            ->name('resend-verification');
        Route::post('/{id}/set-primary', [App\Http\Controllers\Worker\EmergencyContactController::class, 'setPrimary'])
            ->name('set-primary');
        Route::put('/priorities', [App\Http\Controllers\Worker\EmergencyContactController::class, 'updatePriorities'])
            ->name('priorities');
        Route::get('/verification-status', [App\Http\Controllers\Worker\EmergencyContactController::class, 'verificationStatus'])
            ->name('verification-status');
    });

    // ========================================
    // FIN-004: InstaPay (Same-Day Payout) API Routes
    // ========================================
    Route::prefix('instapay')->name('instapay.')->group(function () {
        // Get InstaPay status and eligibility
        Route::get('/status', [App\Http\Controllers\Worker\InstaPayController::class, 'getStatus'])
            ->name('status');

        // Calculate fee preview
        Route::post('/calculate', [App\Http\Controllers\Worker\InstaPayController::class, 'calculateFee'])
            ->name('calculate');

        // Request instant payout
        Route::post('/request', [App\Http\Controllers\Worker\InstaPayController::class, 'requestPayout'])
            ->name('request');

        // Get request history
        Route::get('/history', [App\Http\Controllers\Worker\InstaPayController::class, 'getHistory'])
            ->name('history');

        // Get specific request
        Route::get('/{instapayRequest}', [App\Http\Controllers\Worker\InstaPayController::class, 'show'])
            ->name('show');

        // Cancel pending request
        Route::post('/{instapayRequest}/cancel', [App\Http\Controllers\Worker\InstaPayController::class, 'cancelRequest'])
            ->name('cancel');

        // Get earnings awaiting payout
        Route::get('/earnings', [App\Http\Controllers\Worker\InstaPayController::class, 'getEarnings'])
            ->name('earnings');

        // Get statistics
        Route::get('/statistics', [App\Http\Controllers\Worker\InstaPayController::class, 'getStatistics'])
            ->name('statistics');

        // Settings
        Route::get('/settings', [App\Http\Controllers\Worker\InstaPayController::class, 'getSettings'])
            ->name('settings.show');
        Route::put('/settings', [App\Http\Controllers\Worker\InstaPayController::class, 'updateSettings'])
            ->name('settings.update');
    });

    // ========================================
    // STAFF-REG-008: Payment Setup API Routes
    // ========================================
    Route::prefix('payment')->name('payment.')->group(function () {
        // Get payment status
        Route::get('/status', [App\Http\Controllers\Worker\PaymentSetupController::class, 'getStatus'])
            ->name('status');

        // Initiate Stripe Connect onboarding
        Route::post('/initiate', [App\Http\Controllers\Worker\PaymentSetupController::class, 'initiateOnboarding'])
            ->name('initiate');

        // Refresh payment status from Stripe
        Route::post('/refresh', [App\Http\Controllers\Worker\PaymentSetupController::class, 'refreshStatus'])
            ->name('refresh');

        // Get missing requirements
        Route::get('/requirements', [App\Http\Controllers\Worker\PaymentSetupController::class, 'getRequirements'])
            ->name('requirements');

        // Update payout schedule
        Route::put('/schedule', [App\Http\Controllers\Worker\PaymentSetupController::class, 'updateSchedule'])
            ->name('schedule');

        // Get Stripe dashboard link
        Route::get('/dashboard-link', [App\Http\Controllers\Worker\PaymentSetupController::class, 'getDashboardLink'])
            ->name('dashboard-link');

        // Handle callback from Stripe
        Route::get('/callback', [App\Http\Controllers\Worker\PaymentSetupController::class, 'handleCallback'])
            ->name('callback');
    });
});

// ============================================================================
// SAF-001: Emergency SOS API Routes
// ============================================================================

// SOS Trigger endpoint - requires authentication
Route::prefix('sos')->name('api.sos.')->middleware('auth:sanctum')->group(function () {
    // ONE-TAP SOS TRIGGER - Designed for emergency situations with minimal data
    Route::post('/trigger', [App\Http\Controllers\Api\EmergencyAlertController::class, 'trigger'])
        ->name('trigger');

    // Update location during active alert
    Route::post('/location', [App\Http\Controllers\Api\EmergencyAlertController::class, 'updateLocation'])
        ->name('location');

    // Get current alert status
    Route::get('/status', [App\Http\Controllers\Api\EmergencyAlertController::class, 'status'])
        ->name('status');

    // Cancel alert (user-initiated)
    Route::post('/cancel', [App\Http\Controllers\Api\EmergencyAlertController::class, 'cancel'])
        ->name('cancel');

    // Request notification to emergency contacts
    Route::post('/notify-contacts', [App\Http\Controllers\Api\EmergencyAlertController::class, 'notifyContacts'])
        ->name('notify-contacts');

    // Get user's alert history
    Route::get('/history', [App\Http\Controllers\Api\EmergencyAlertController::class, 'history'])
        ->name('history');
});

// ============================================================================
// SAF-001: Admin Emergency Alert Management API Routes
// ============================================================================

Route::prefix('admin/emergency-alerts')->name('api.admin.emergency-alerts.')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Get active alerts (for live dashboard)
    Route::get('/active', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'getActiveAlerts'])
        ->name('active');

    // Get alert statistics
    Route::get('/statistics', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'statistics'])
        ->name('statistics');

    // List all alerts with filtering
    Route::get('/', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'list'])
        ->name('index');

    // Get single alert details
    Route::get('/{id}', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'show'])
        ->name('show');

    // Acknowledge an alert
    Route::post('/{id}/acknowledge', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'acknowledge'])
        ->name('acknowledge');

    // Resolve an alert
    Route::post('/{id}/resolve', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'resolve'])
        ->name('resolve');

    // Mark as false alarm
    Route::post('/{id}/false-alarm', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'markFalseAlarm'])
        ->name('false-alarm');

    // Notify emergency contacts
    Route::post('/{id}/notify-contacts', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'notifyContacts'])
        ->name('notify-contacts');

    // Mark emergency services called
    Route::post('/{id}/emergency-services-called', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'markEmergencyServicesCalled'])
        ->name('emergency-services-called');

    // Get location history
    Route::get('/{id}/location-history', [App\Http\Controllers\Admin\EmergencyAlertController::class, 'locationHistory'])
        ->name('location-history');
});

// ============================================================================
// COM-002: Push Notification API Routes
// ============================================================================

Route::prefix('push')->name('api.push.')->middleware('auth:sanctum')->group(function () {
    // Register device token
    Route::post('/register', [App\Http\Controllers\Api\PushNotificationController::class, 'register'])
        ->name('register');

    // Unregister device token
    Route::delete('/unregister', [App\Http\Controllers\Api\PushNotificationController::class, 'unregister'])
        ->name('unregister');

    // Send test notification
    Route::get('/test', [App\Http\Controllers\Api\PushNotificationController::class, 'test'])
        ->name('test');

    // List registered tokens
    Route::get('/tokens', [App\Http\Controllers\Api\PushNotificationController::class, 'tokens'])
        ->name('tokens');

    // Get push notification stats
    Route::get('/stats', [App\Http\Controllers\Api\PushNotificationController::class, 'stats'])
        ->name('stats');
});

// FCM Delivery Receipt Webhook (public endpoint with signature verification)
Route::post('/push/receipt', [App\Http\Controllers\Api\PushNotificationController::class, 'receipt'])
    ->name('api.push.receipt');

// ============================================================================
// GLO-001: Multi-Currency Support API Routes
// ============================================================================

// Public currency endpoints (no auth required)
Route::prefix('currency')->name('api.currency.')->group(function () {
    // Get list of supported currencies
    Route::get('/supported', [App\Http\Controllers\Api\CurrencyWalletController::class, 'supportedCurrencies'])
        ->name('supported');

    // Get current exchange rates (public)
    Route::get('/rates', [App\Http\Controllers\Api\CurrencyWalletController::class, 'exchangeRates'])
        ->name('rates');
});

// Authenticated currency wallet endpoints
Route::prefix('currency')->name('api.currency.')->middleware('auth:sanctum')->group(function () {
    // Wallet Management
    Route::get('/wallets', [App\Http\Controllers\Api\CurrencyWalletController::class, 'index'])
        ->name('wallets.index');

    Route::get('/wallets/{currency}', [App\Http\Controllers\Api\CurrencyWalletController::class, 'show'])
        ->name('wallets.show');

    Route::post('/wallets', [App\Http\Controllers\Api\CurrencyWalletController::class, 'store'])
        ->name('wallets.store');

    Route::post('/wallets/{currency}/primary', [App\Http\Controllers\Api\CurrencyWalletController::class, 'setPrimary'])
        ->name('wallets.set-primary');

    // Total balance across all wallets
    Route::get('/total-balance', [App\Http\Controllers\Api\CurrencyWalletController::class, 'totalBalance'])
        ->name('total-balance');

    // Currency Conversion
    Route::post('/convert/preview', [App\Http\Controllers\Api\CurrencyWalletController::class, 'previewConversion'])
        ->name('convert.preview');

    Route::post('/convert', [App\Http\Controllers\Api\CurrencyWalletController::class, 'convert'])
        ->name('convert');

    // Conversion History
    Route::get('/conversions', [App\Http\Controllers\Api\CurrencyWalletController::class, 'conversionHistory'])
        ->name('conversions');
});

// ============================================================================
// WKR-013: Availability Forecasting API Routes
// ============================================================================

Route::prefix('forecasting')->name('api.forecasting.')->middleware('auth:sanctum')->group(function () {
    // Worker Patterns & Predictions
    Route::get('/workers/{workerId}/patterns', [App\Http\Controllers\AvailabilityForecastController::class, 'getWorkerPatterns'])
        ->name('worker.patterns');

    Route::get('/workers/{workerId}/predictions', [App\Http\Controllers\AvailabilityForecastController::class, 'getWorkerPredictions'])
        ->name('worker.predictions');

    Route::get('/workers/{workerId}/predictions/{date}', [App\Http\Controllers\AvailabilityForecastController::class, 'getWorkerPredictionForDate'])
        ->name('worker.prediction.date');

    Route::post('/workers/{workerId}/refresh-patterns', [App\Http\Controllers\AvailabilityForecastController::class, 'refreshWorkerPatterns'])
        ->name('worker.refresh-patterns');

    // Demand Forecasts
    Route::get('/demand', [App\Http\Controllers\AvailabilityForecastController::class, 'getDemandForecasts'])
        ->name('demand');

    Route::get('/demand/gap', [App\Http\Controllers\AvailabilityForecastController::class, 'getSupplyDemandGap'])
        ->name('demand.gap');

    Route::get('/demand/critical', [App\Http\Controllers\AvailabilityForecastController::class, 'getCriticalForecasts'])
        ->name('demand.critical');

    Route::get('/demand/trends', [App\Http\Controllers\AvailabilityForecastController::class, 'getDemandTrends'])
        ->name('demand.trends');

    // Available Workers
    Route::get('/available-workers', [App\Http\Controllers\AvailabilityForecastController::class, 'getAvailableWorkers'])
        ->name('available-workers');

    Route::get('/shifts/{shiftId}/recommended-workers', [App\Http\Controllers\AvailabilityForecastController::class, 'getRecommendedWorkersForShift'])
        ->name('shift.recommended-workers');

    // Analytics
    Route::get('/accuracy', [App\Http\Controllers\AvailabilityForecastController::class, 'getPredictionAccuracy'])
        ->name('accuracy');

    // Reference Data
    Route::get('/regions', [App\Http\Controllers\AvailabilityForecastController::class, 'getRegions'])
        ->name('regions');

    Route::get('/skill-categories', [App\Http\Controllers\AvailabilityForecastController::class, 'getSkillCategories'])
        ->name('skill-categories');
});

// ============================================================================
// GLO-007: Holiday Calendar API Routes
// ============================================================================

Route::prefix('holidays')->name('api.holidays.')->group(function () {
    // Public holiday data (no auth required)
    Route::get('/countries', [App\Http\Controllers\HolidayController::class, 'countries'])
        ->name('countries');

    Route::get('/check', [App\Http\Controllers\HolidayController::class, 'checkDate'])
        ->name('check');

    Route::get('/list', [App\Http\Controllers\HolidayController::class, 'getHolidays'])
        ->name('list');

    Route::get('/upcoming', [App\Http\Controllers\HolidayController::class, 'upcoming'])
        ->name('upcoming');

    Route::get('/next', [App\Http\Controllers\HolidayController::class, 'next'])
        ->name('next');

    Route::get('/range', [App\Http\Controllers\HolidayController::class, 'forDateRange'])
        ->name('range');

    Route::get('/search', [App\Http\Controllers\HolidayController::class, 'search'])
        ->name('search');
});

// ============================================================================
// COM-004: SMS & WhatsApp Messaging Webhook Routes
// ============================================================================

// WhatsApp Webhook (Meta/Facebook) - public endpoints for webhook verification and events
Route::prefix('webhooks/whatsapp')->name('api.webhooks.whatsapp.')->group(function () {
    // Webhook verification (GET) - Meta sends this during webhook setup
    Route::get('/', [App\Http\Controllers\Api\MessagingWebhookController::class, 'verifyWhatsApp'])
        ->name('verify');

    // Webhook events (POST) - Message status updates, incoming messages
    Route::post('/', [App\Http\Controllers\Api\MessagingWebhookController::class, 'handleWhatsApp'])
        ->name('handle');
});

// SMS Provider Webhooks - status callbacks
Route::prefix('webhooks/sms')->name('api.webhooks.sms.')->group(function () {
    // Twilio Status Callback
    Route::post('/twilio/status', [App\Http\Controllers\Api\MessagingWebhookController::class, 'handleTwilioStatus'])
        ->name('twilio.status');

    // Twilio WhatsApp Status Callback
    Route::post('/twilio/whatsapp', [App\Http\Controllers\Api\MessagingWebhookController::class, 'handleTwilioWhatsApp'])
        ->name('twilio.whatsapp');

    // Vonage (Nexmo) Delivery Receipt
    Route::post('/vonage/status', [App\Http\Controllers\Api\MessagingWebhookController::class, 'handleVonageStatus'])
        ->name('vonage.status');

    // MessageBird Status Webhook
    Route::post('/messagebird/status', [App\Http\Controllers\Api\MessagingWebhookController::class, 'handleMessageBirdStatus'])
        ->name('messagebird.status');

    // Generic incoming SMS handler
    Route::post('/{provider}/incoming', [App\Http\Controllers\Api\MessagingWebhookController::class, 'handleIncomingSms'])
        ->name('incoming')
        ->where('provider', 'twilio|vonage|messagebird');
});

// ============================================================================
// COM-004: Authenticated Messaging API Routes
// ============================================================================

Route::prefix('messaging')->name('api.messaging.')->middleware('auth:sanctum')->group(function () {
    // User messaging preferences
    Route::get('/preferences', [App\Http\Controllers\Api\MessagingController::class, 'getPreferences'])
        ->name('preferences');

    Route::put('/preferences', [App\Http\Controllers\Api\MessagingController::class, 'updatePreferences'])
        ->name('preferences.update');

    Route::post('/preferences/quiet-hours', [App\Http\Controllers\Api\MessagingController::class, 'setQuietHours'])
        ->name('preferences.quiet-hours');

    Route::delete('/preferences/quiet-hours', [App\Http\Controllers\Api\MessagingController::class, 'clearQuietHours'])
        ->name('preferences.quiet-hours.clear');

    Route::post('/preferences/whatsapp/enable', [App\Http\Controllers\Api\MessagingController::class, 'enableWhatsApp'])
        ->name('preferences.whatsapp.enable');

    Route::post('/preferences/whatsapp/disable', [App\Http\Controllers\Api\MessagingController::class, 'disableWhatsApp'])
        ->name('preferences.whatsapp.disable');

    // Message history
    Route::get('/history', [App\Http\Controllers\Api\MessagingController::class, 'getHistory'])
        ->name('history');

    // Message statistics
    Route::get('/stats', [App\Http\Controllers\Api\MessagingController::class, 'getStats'])
        ->name('stats');

    // Send test message (development only)
    Route::post('/test', [App\Http\Controllers\Api\MessagingController::class, 'sendTest'])
        ->name('test');
});

// ============================================================================
// BIZ-012: Integration APIs - Calendar Feed (Public)
// ============================================================================

// Calendar iCal feed - public but token-secured
Route::get('/calendar/{token}', [App\Http\Controllers\Api\CalendarFeedController::class, 'show'])
    ->name('api.calendar.feed')
    ->where('token', '[a-zA-Z0-9]+\.?ics?');

// ============================================================================
// BIZ-012: Integration APIs - Business Integrations
// ============================================================================

Route::prefix('business/integrations')->name('api.business.integrations.')->middleware(['auth:sanctum', 'business'])->group(function () {
    // List integrations and providers
    Route::get('/', [App\Http\Controllers\Business\IntegrationController::class, 'getIntegrations'])
        ->name('index');
    Route::get('/providers', [App\Http\Controllers\Business\IntegrationController::class, 'getProviders'])
        ->name('providers');

    // Connect/disconnect integrations
    Route::post('/connect', [App\Http\Controllers\Business\IntegrationController::class, 'connect'])
        ->name('connect');
    Route::delete('/{integration}/disconnect', [App\Http\Controllers\Business\IntegrationController::class, 'disconnect'])
        ->name('disconnect');

    // Test connection
    Route::get('/{integration}/test', [App\Http\Controllers\Business\IntegrationController::class, 'testConnection'])
        ->name('test');

    // Sync operations
    Route::post('/{integration}/sync/shifts', [App\Http\Controllers\Business\IntegrationController::class, 'syncShifts'])
        ->name('sync.shifts');
    Route::post('/{integration}/sync/timesheets', [App\Http\Controllers\Business\IntegrationController::class, 'syncTimesheets'])
        ->name('sync.timesheets');
    Route::post('/{integration}/import/workers', [App\Http\Controllers\Business\IntegrationController::class, 'importWorkers'])
        ->name('import.workers');
    Route::post('/{integration}/export/payroll', [App\Http\Controllers\Business\IntegrationController::class, 'exportPayroll'])
        ->name('export.payroll');

    // Sync history
    Route::get('/{integration}/history', [App\Http\Controllers\Business\IntegrationController::class, 'syncHistory'])
        ->name('history');

    // Settings
    Route::put('/{integration}/settings', [App\Http\Controllers\Business\IntegrationController::class, 'updateSettings'])
        ->name('settings');

    // Calendar URL
    Route::get('/calendar-url', [App\Http\Controllers\Business\IntegrationController::class, 'getCalendarUrl'])
        ->name('calendar-url');
    Route::post('/calendar-url/regenerate', [App\Http\Controllers\Business\IntegrationController::class, 'regenerateCalendarUrl'])
        ->name('calendar-url.regenerate');
});

// ============================================================================
// BIZ-012: Integration APIs - Business Webhooks
// ============================================================================

Route::prefix('business/webhooks')->name('api.business.webhooks.')->middleware(['auth:sanctum', 'business'])->group(function () {
    // List and manage webhooks
    Route::get('/', [App\Http\Controllers\Business\WebhookController::class, 'getWebhooks'])
        ->name('index');
    Route::get('/events', [App\Http\Controllers\Business\WebhookController::class, 'getEvents'])
        ->name('events');
    Route::post('/', [App\Http\Controllers\Business\WebhookController::class, 'store'])
        ->name('store');
    Route::get('/{webhook}', [App\Http\Controllers\Business\WebhookController::class, 'show'])
        ->name('show');
    Route::put('/{webhook}', [App\Http\Controllers\Business\WebhookController::class, 'update'])
        ->name('update');
    Route::delete('/{webhook}', [App\Http\Controllers\Business\WebhookController::class, 'destroy'])
        ->name('destroy');

    // Webhook operations
    Route::post('/{webhook}/test', [App\Http\Controllers\Business\WebhookController::class, 'test'])
        ->name('test');
    Route::post('/{webhook}/regenerate-secret', [App\Http\Controllers\Business\WebhookController::class, 'regenerateSecret'])
        ->name('regenerate-secret');
    Route::post('/{webhook}/reactivate', [App\Http\Controllers\Business\WebhookController::class, 'reactivate'])
        ->name('reactivate');
    Route::post('/{webhook}/toggle', [App\Http\Controllers\Business\WebhookController::class, 'toggle'])
        ->name('toggle');
});
