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
    Route::get("/demo", function () {
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
});
