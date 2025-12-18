# OvertimeStaff - Complete Routes & Components List
**Date**: 2025-12-15  
**Total Routes**: 130+  
**Total Views**: 374 Blade files

---

## Table of Contents

1. [Web Routes](#web-routes)
2. [API Routes](#api-routes)
3. [Layouts](#layouts)
4. [Global Components](#global-components)
5. [UI Components](#ui-components)
6. [Dashboard Components](#dashboard-components)
7. [Public Pages](#public-pages)
8. [Authenticated Pages](#authenticated-pages)
9. [Error Pages](#error-pages)

---

## Web Routes

### Marketing/Public Routes (No Auth Required)

| Method | URI | Name | Controller/View |
|--------|-----|------|-----------------|
| GET | `/` | `home` | `welcome.blade.php` |
| GET | `/features` | `features` | `public.features` |
| GET | `/pricing` | `pricing` | `public.pricing` |
| GET | `/about` | `about` | `public.about` |
| GET | `/contact` | `contact` | `public.contact` |
| POST | `/contact` | `contact.submit` | `HomeController@submitContact` |
| GET | `/terms` | `terms` | `public.terms` |
| GET | `/privacy` | `privacy` | `public.privacy` |
| GET | `/access-denied` | `errors.access-denied` | `errors.access-denied` |

#### Workers Marketing Routes

| Method | URI | Name | View |
|--------|-----|------|------|
| GET | `/workers/find-shifts` | `workers.find-shifts` | `public.workers.find-shifts` |
| GET | `/workers/features` | `workers.features` | `public.workers.features` |
| GET | `/workers/get-started` | `workers.get-started` | `public.workers.get-started` |

#### Businesses Marketing Routes

| Method | URI | Name | View |
|--------|-----|------|------|
| GET | `/business/find-staff` | `business.find-staff` | `public.business.find-staff` |
| GET | `/business/pricing` | `business.pricing` | `public.business.pricing` |
| GET | `/business/post-shifts` | `business.post-shifts` | `public.business.post-shifts` |

#### Public Profile Routes

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/profile/{username}` | `profile.public` | `PublicProfileController@show` |
| GET | `/profile/{username}/portfolio/{itemId}` | `profile.portfolio` | `PublicProfileController@portfolioItem` |
| GET | `/workers` | `workers.search` | `PublicProfileController@searchWorkers` |
| GET | `/api/featured-workers` | `api.featured-workers` | `PublicProfileController@featuredWorkers` |

---

### Authentication Routes

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/login` | `login` | `LoginController@showLoginForm` |
| POST | `/login` | - | `LoginController@login` |
| POST | `/logout` | `logout` | `LoginController@logout` |
| GET | `/register` | `register` | `RegisterController@showRegistrationForm` |
| POST | `/register` | - | `RegisterController@register` |
| GET | `/password/reset` | `password.request` | `ForgotPasswordController@showLinkRequestForm` |
| POST | `/password/email` | `password.email` | `ForgotPasswordController@sendResetLinkEmail` |
| GET | `/password/reset/{token}` | `password.reset` | `ResetPasswordController@showResetForm` |
| POST | `/password/reset` | `password.update` | `ResetPasswordController@reset` |
| GET | `/email/verify` | `verification.notice` | `VerificationController@show` |
| GET | `/email/verify/{id}/{hash}` | `verification.verify` | `VerificationController@verify` |
| POST | `/email/resend` | `verification.resend` | `VerificationController@resend` |
| GET | `/password/confirm` | `password.confirm` | `ConfirmPasswordController@showConfirmForm` |
| POST | `/password/confirm` | - | `ConfirmPasswordController@confirm` |

#### Two-Factor Authentication Routes

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/two-factor` | `two-factor.index` | `TwoFactorAuthController@index` | `auth` |
| GET | `/two-factor/enable` | `two-factor.enable` | `TwoFactorAuthController@enable` | `auth` |
| POST | `/two-factor/confirm` | `two-factor.confirm` | `TwoFactorAuthController@confirm` | `auth` |
| POST | `/two-factor/disable` | `two-factor.disable` | `TwoFactorAuthController@disable` | `auth` |
| GET | `/two-factor/recovery-codes` | `two-factor.recovery-codes` | `TwoFactorAuthController@showRecoveryCodes` | `auth` |
| POST | `/two-factor/recovery-codes/regenerate` | `two-factor.recovery-codes.regenerate` | `TwoFactorAuthController@regenerateRecoveryCodes` | `auth` |
| GET | `/two-factor/verify` | `two-factor.verify` | `TwoFactorAuthController@verify` | - |
| POST | `/two-factor/verify-code` | `two-factor.verify-code` | `TwoFactorAuthController@verifyCode` | - |
| GET | `/two-factor/recovery` | `two-factor.recovery` | `TwoFactorAuthController@showRecoveryForm` | - |
| POST | `/two-factor/recovery/verify` | `two-factor.recovery.verify` | `TwoFactorAuthController@verifyRecoveryCode` | - |

---

### Dashboard Routes (Authenticated)

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/dashboard` | `dashboard.index` | `DashboardController@index` | `auth`, `verified` |
| GET | `/dashboard/worker` | `dashboard.worker` | `DashboardController@workerDashboard` | `auth`, `verified`, `role:worker` |
| GET | `/dashboard/company` | `dashboard.company` | `DashboardController@businessDashboard` | `auth`, `verified`, `role:business` |
| GET | `/dashboard/agency` | `dashboard.agency` | `DashboardController@agencyDashboard` | `auth`, `verified`, `role:agency` |
| GET | `/dashboard/admin` | `dashboard.admin` | `DashboardController@adminDashboard` | `auth`, `verified`, `role:admin` |
| GET | `/dashboard/profile` | `dashboard.profile` | View: `profile.show` | `auth`, `verified` |
| GET | `/dashboard/notifications` | `dashboard.notifications` | View: `notifications.index` | `auth`, `verified` |
| GET | `/dashboard/transactions` | `dashboard.transactions` | View: `transactions.index` | `auth`, `verified` |

---

### Settings & Messages Routes

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/settings` | `settings.index` | `SettingsController@index` | `auth`, `verified` |
| GET | `/messages` | `messages.index` | `MessagesController@index` | `auth` |
| GET | `/messages/{conversation}` | `messages.show` | `MessagesController@show` | `auth` |
| POST | `/messages/send` | `messages.send` | `MessagesController@send` | `auth` |
| POST | `/messages/{conversation}/archive` | `messages.archive` | `MessagesController@archive` | `auth` |
| POST | `/messages/{conversation}/restore` | `messages.restore` | `MessagesController@restore` | `auth` |
| GET | `/messages/business/{businessId}` | `messages.business` | `MessagesController@createWithBusiness` | `auth` |
| GET | `/messages/worker/{workerId}` | `messages.worker` | `MessagesController@createWithWorker` | `auth` |
| GET | `/messages/unread/count` | `messages.unread.count` | `MessagesController@unreadCount` | `auth` |

---

### Legacy/Shift Routes

| Method | URI | Name | Controller/View | Middleware |
|--------|-----|------|-----------------|------------|
| GET | `/shifts` | `shifts.index` | View: `shifts.index` | `auth` |
| GET | `/shifts/create` | `shifts.create` | View: `shifts.create` | `auth` |
| POST | `/shifts/{shift}/apply` | `market.apply` | `LiveMarketController@apply` | `auth`, `worker`, `worker.activated` |
| POST | `/shifts/{shift}/claim` | `market.claim` | `LiveMarketController@instantClaim` | `auth`, `worker`, `worker.activated` |
| POST | `/shifts/{shift}/assign` | `market.assign` | `LiveMarketController@agencyAssign` | `auth`, `agency` |
| GET | `/api/market` | `api.market.index` | `LiveMarketController@index` | `auth`, `throttle:60,1` |
| GET | `/api/market/simulate` | `api.market.simulate` | `LiveMarketController@simulateActivity` | `auth`, `throttle:60,1` |

#### Business Shift Routes

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/business/shifts` | `business.shifts.index` | `ShiftManagementController@myShifts` | `auth`, `role:business` |

#### Worker Routes

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/worker/dashboard` | `worker.dashboard` | `DashboardController@workerDashboard` | `auth`, `role:worker` |
| GET | `/worker/payment-setup` | `worker.payment-setup` | `PaymentSetupController@index` | `auth`, `role:worker` |
| GET | `/worker/skills` | `worker.skills` | `SkillsController@index` | `auth`, `role:worker` |
| GET | `/worker/certifications` | `worker.certifications` | `CertificationController@index` | `auth`, `role:worker` |
| GET | `/worker/availability` | `worker.availability` | `AvailabilityController@index` | `auth`, `role:worker` |
| GET | `/worker/applications` | `worker.applications` | `ShiftApplicationController@myApplications` | `auth`, `role:worker` |
| GET | `/worker/activation` | `worker.activation.index` | `ActivationController@index` | `auth`, `role:worker` |

---

### Registration Routes

#### Business Registration

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/register/business` | `business.register.index` | `Business\RegistrationController@showRegistrationForm` |
| GET | `/register/business/verify-email` | `business.register.verify-email` | `Business\RegistrationController@verifyEmailLink` |

#### Worker Registration

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/register/worker` | `worker.register.index` | `Worker\RegistrationController@showRegistrationForm` |
| GET | `/register/worker/invite/{token}` | `worker.register.agency-invite` | `Worker\RegistrationController@showRegistrationForm` |
| GET | `/worker/verify/email` | `worker.verify.email` | `Worker\RegistrationController@showVerifyEmailForm` |
| GET | `/worker/verify/phone` | `worker.verify.phone` | `Worker\RegistrationController@showVerifyPhoneForm` |

#### Agency Registration

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/register/agency` | `agency.register.index` | `Agency\RegistrationController@index` |
| GET | `/register/agency/start` | `agency.register.start` | `Agency\RegistrationController@start` |
| GET | `/register/agency/step/{step}` | `agency.register.step.show` | `Agency\RegistrationController@showStep` |
| POST | `/register/agency/step/{step}` | `agency.register.step.save` | `Agency\RegistrationController@saveStep` |
| POST | `/register/agency/step/{step}/previous` | `agency.register.step.previous` | `Agency\RegistrationController@previousStep` |
| POST | `/register/agency/upload-document` | `agency.register.upload-document` | `Agency\RegistrationController@uploadDocument` |
| DELETE | `/register/agency/remove-document` | `agency.register.remove-document` | `Agency\RegistrationController@removeDocument` |
| GET | `/register/agency/review` | `agency.register.review` | `Agency\RegistrationController@review` |
| POST | `/register/agency/submit` | `agency.register.submit` | `Agency\RegistrationController@submitApplication` |
| GET | `/register/agency/confirmation/{id}` | `agency.register.confirmation` | `Agency\RegistrationController@confirmation` |

---

### Dev Routes (Local/Development Only)

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/dev/login/{type}` | `dev.login` | `Dev\DevLoginController@login` |
| GET\|POST | `/dev/credentials` | `dev.credentials` | `Dev\DevLoginController@showCredentials` |
| GET | `/home` | - | Redirects to `/` |
| GET | `/clear-cache` | - | Clears all caches |

**Note**: All dev routes are wrapped in `app()->environment('local', 'development')` check.

---

## API Routes

### Public API Routes

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/api/user` | - | Returns authenticated user (requires `auth:api`) |
| GET | `/api/demo` | - | PHP config info (dev only) |

### Dashboard API

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/dashboard/stats` | - | `Api\DashboardController@stats` | `auth:sanctum` |
| GET | `/api/dashboard/notifications/count` | - | `Api\DashboardController@notificationsCount` | `auth:sanctum` |
| GET | `/api/notifications/unread-count` | - | `Api\DashboardController@notificationsCount` | `auth:sanctum` |

### Shifts API

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/shifts` | - | `Api\ShiftController@index` | `auth:sanctum` |
| GET | `/api/shifts/{id}` | - | `Api\ShiftController@show` | `auth:sanctum` |

### Market API

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/market/live` | `api.market.live` | `LiveMarketController@apiIndex` | `auth:sanctum` |

---

### Business API Routes

#### Public Business Routes

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| POST | `/api/business/register` | `api.business.register` | `Business\RegistrationController@register` | `throttle:registration` |
| POST | `/api/business/verify-email` | `api.business.verify-email` | `Business\RegistrationController@verifyEmail` | `throttle:verification-code` |
| POST | `/api/business/resend-verification` | `api.business.resend-verification` | `Business\RegistrationController@resendVerification` | `throttle:verification` |
| POST | `/api/business/validate-email` | `api.business.validate-email` | `Business\RegistrationController@validateEmail` | `throttle:30,1` |
| POST | `/api/business/validate-referral` | `api.business.validate-referral` | `Business\RegistrationController@validateReferralCode` | `throttle:30,1` |
| GET | `/api/business/business-types` | `api.business.business-types` | `Business\ProfileController@getBusinessTypes` | Public |
| GET | `/api/business/industries` | `api.business.industries` | `Business\ProfileController@getIndustries` | Public |
| GET | `/api/business/timezones` | `api.business.timezones` | `Business\ProfileController@getTimezones` | Public |
| GET | `/api/business/currencies` | `api.business.currencies` | `Business\ProfileController@getCurrencies` | Public |

#### Authenticated Business Routes

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/business/profile` | `api.business.profile` | `Business\ProfileController@getProfile` | `auth:sanctum`, `business` |
| PUT | `/api/business/profile` | `api.business.profile.update` | `Business\ProfileController@updateProfile` | `auth:sanctum`, `business` |
| POST | `/api/business/profile/logo` | `api.business.profile.logo` | `Business\ProfileController@uploadLogo` | `auth:sanctum`, `business` |
| GET | `/api/business/profile/completion` | `api.business.profile.completion` | `Business\ProfileController@getProfileCompletion` | `auth:sanctum`, `business` |
| GET | `/api/business/onboarding/progress` | `api.business.onboarding.progress` | `Business\OnboardingController@getProgress` | `auth:sanctum`, `business` |
| GET | `/api/business/onboarding/next-step` | `api.business.onboarding.next-step` | `Business\OnboardingController@getNextStep` | `auth:sanctum`, `business` |
| POST | `/api/business/onboarding/complete-step` | `api.business.onboarding.complete-step` | `Business\OnboardingController@completeStep` | `auth:sanctum`, `business` |
| POST | `/api/business/onboarding/skip-step` | `api.business.onboarding.skip-step` | `Business\OnboardingController@skipOptionalStep` | `auth:sanctum`, `business` |
| POST | `/api/business/onboarding/initialize` | `api.business.onboarding.initialize` | `Business\OnboardingController@initialize` | `auth:sanctum`, `business` |
| POST | `/api/business/activate` | `api.business.activate` | `Business\ProfileController@activate` | `auth:sanctum`, `business` |
| POST | `/api/business/accept-terms` | `api.business.accept-terms` | `Business\ProfileController@acceptTerms` | `auth:sanctum`, `business` |
| GET | `/api/business/activation/status` | `api.business.activation.status` | `Business\ActivationController@getActivationStatus` | `auth:sanctum`, `business` |
| POST | `/api/business/activation/activate` | `api.business.activation.activate` | `Business\ActivationController@activateAccount` | `auth:sanctum`, `business` |
| GET | `/api/business/activation/can-post-shifts` | `api.business.activation.can-post-shifts` | `Business\ActivationController@canPostShifts` | `auth:sanctum`, `business` |

---

### Worker API Routes

#### Public Worker Routes

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| POST | `/api/worker/register` | `api.worker.register` | `Worker\RegistrationController@register` | `throttle:registration` |
| POST | `/api/worker/check-email` | `api.worker.check-email` | `Worker\RegistrationController@checkEmailAvailability` | `throttle:30,1` |
| POST | `/api/worker/check-phone` | `api.worker.check-phone` | `Worker\RegistrationController@checkPhoneAvailability` | `throttle:30,1` |
| POST | `/api/worker/validate-referral` | `api.worker.validate-referral` | `Worker\RegistrationController@validateReferralCode` | `throttle:30,1` |

#### Authenticated Worker Routes

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| POST | `/api/worker/verify-email` | `api.worker.verify-email` | `Worker\RegistrationController@verifyEmail` | `auth:sanctum`, `throttle:verification-code` |
| POST | `/api/worker/verify-phone` | `api.worker.verify-phone` | `Worker\RegistrationController@verifyPhone` | `auth:sanctum`, `throttle:verification-code` |
| POST | `/api/worker/resend-verification` | `api.worker.resend-verification` | `Worker\RegistrationController@resendVerification` | `auth:sanctum`, `throttle:verification` |
| GET | `/api/worker/verification-status` | `api.worker.verification-status` | `Worker\RegistrationController@getVerificationStatus` | `auth:sanctum` |

#### Worker Availability API

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/worker/availability` | `api.worker.availability.index` | `Worker\AvailabilityController@getAvailability` | `auth:sanctum`, `worker` |
| PUT | `/api/worker/availability/schedule` | `api.worker.availability.schedule` | `Worker\AvailabilityController@setWeeklySchedule` | `auth:sanctum`, `worker` |
| POST | `/api/worker/availability/override` | `api.worker.availability.override` | `Worker\AvailabilityController@addDateOverride` | `auth:sanctum`, `worker` |
| DELETE | `/api/worker/availability/override/{id}` | `api.worker.availability.override.delete` | `Worker\AvailabilityController@deleteOverride` | `auth:sanctum`, `worker` |
| PUT | `/api/worker/availability/preferences` | `api.worker.availability.preferences` | `Worker\AvailabilityController@setPreferences` | `auth:sanctum`, `worker` |
| POST | `/api/worker/availability/blackout` | `api.worker.availability.blackout` | `Worker\AvailabilityController@addBlackoutDate` | `auth:sanctum`, `worker` |
| DELETE | `/api/worker/availability/blackout/{id}` | `api.worker.availability.blackout.delete` | `Worker\AvailabilityController@deleteBlackoutDate` | `auth:sanctum`, `worker` |
| GET | `/api/worker/availability/slots` | `api.worker.availability.slots` | `Worker\AvailabilityController@getAvailableSlots` | `auth:sanctum`, `worker` |
| POST | `/api/worker/availability/check` | `api.worker.availability.check` | `Worker\AvailabilityController@checkAvailability` | `auth:sanctum`, `worker` |

#### Worker Onboarding API

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/worker/onboarding/progress` | `api.worker.onboarding.progress` | `Worker\OnboardingController@getProgress` | `auth:sanctum`, `worker` |
| GET | `/api/worker/onboarding/next-step` | `api.worker.onboarding.next-step` | `Worker\OnboardingController@getNextStep` | `auth:sanctum`, `worker` |
| POST | `/api/worker/onboarding/complete-step` | `api.worker.onboarding.complete-step` | `Worker\OnboardingController@completeStep` | `auth:sanctum`, `worker` |
| POST | `/api/worker/onboarding/skip-step` | `api.worker.onboarding.skip-step` | `Worker\OnboardingController@skipOptionalStep` | `auth:sanctum`, `worker` |
| POST | `/api/worker/onboarding/initialize` | `api.worker.onboarding.initialize` | `Worker\OnboardingController@initialize` | `auth:sanctum`, `worker` |

#### Worker Activation API

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/worker/activation/eligibility` | `api.worker.activation.eligibility` | `Worker\ActivationController@checkEligibility` | `auth:sanctum`, `worker` |
| POST | `/api/worker/activation/activate` | `api.worker.activation.activate` | `Worker\ActivationController@activate` | `auth:sanctum`, `worker` |
| GET | `/api/worker/activation/status` | `api.worker.activation.status` | `Worker\ActivationController@getActivationStatus` | `auth:sanctum`, `worker` |
| POST | `/api/worker/activation/referral-code` | `api.worker.activation.referral-code` | `Worker\ActivationController@applyReferralCode` | `auth:sanctum`, `worker` |

#### Worker Profile API

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/worker/profile` | `api.worker.profile.show` | `Worker\ProfileController@show` | `auth:sanctum`, `worker` |
| PUT | `/api/worker/profile` | `api.worker.profile.update` | `Worker\ProfileController@update` | `auth:sanctum`, `worker` |
| POST | `/api/worker/profile/photo` | `api.worker.profile.photo` | `Worker\ProfileController@uploadPhoto` | `auth:sanctum`, `worker` |
| GET | `/api/worker/profile/completion` | `api.worker.profile.completion` | `Worker\ProfileController@getCompletion` | `auth:sanctum`, `worker` |
| GET | `/api/worker/profile/suggestions` | `api.worker.profile.suggestions` | `Worker\ProfileController@getSuggestions` | `auth:sanctum`, `worker` |
| GET | `/api/worker/profile/fields` | `api.worker.profile.fields` | `Worker\ProfileController@getFields` | `auth:sanctum`, `worker` |
| POST | `/api/worker/profile/verify-age` | `api.worker.profile.verify-age` | `Worker\ProfileController@verifyAge` | `auth:sanctum`, `worker` |
| POST | `/api/worker/profile/geocode` | `api.worker.profile.geocode` | `Worker\ProfileController@geocodeLocation` | `auth:sanctum`, `worker` |

#### Worker Skills API

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/worker/skills/available` | `api.worker.skills.available` | `Worker\SkillsController@getAvailableSkills` | `auth:sanctum`, `worker` |
| GET | `/api/worker/skills/category` | `api.worker.skills.category` | `Worker\SkillsController@getSkillsByCategory` | `auth:sanctum`, `worker` |
| GET | `/api/worker/skills/categories` | `api.worker.skills.categories` | `Worker\SkillsController@getCategories` | `auth:sanctum`, `worker` |
| GET | `/api/worker/skills/search` | `api.worker.skills.search` | `Worker\SkillsController@search` | `auth:sanctum`, `worker` |
| GET | `/api/worker/skills` | `api.worker.skills.index` | `Worker\SkillsController@getSkills` | `auth:sanctum`, `worker` |
| POST | `/api/worker/skills` | `api.worker.skills.store` | `Worker\SkillsController@store` | `auth:sanctum`, `worker` |
| PUT | `/api/worker/skills/{id}` | `api.worker.skills.update` | `Worker\SkillsController@update` | `auth:sanctum`, `worker` |
| DELETE | `/api/worker/skills/{id}` | `api.worker.skills.destroy` | `Worker\SkillsController@destroy` | `auth:sanctum`, `worker` |
| GET | `/api/worker/skills/{id}/certifications` | `api.worker.skills.certifications` | `Worker\SkillsController@getRequiredCertifications` | `auth:sanctum`, `worker` |
| GET | `/api/worker/skills/{id}/check-requirements` | `api.worker.skills.check-requirements` | `Worker\SkillsController@checkCertificationRequirements` | `auth:sanctum`, `worker` |

#### Worker Certifications API

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/worker/certifications/types` | `api.worker.certifications.types` | `Worker\CertificationController@getAvailableTypes` | `auth:sanctum`, `worker` |
| GET | `/api/worker/certifications` | `api.worker.certifications.index` | `Worker\CertificationController@getCertifications` | `auth:sanctum`, `worker` |
| GET | `/api/worker/certifications/{id}` | `api.worker.certifications.show` | `Worker\CertificationController@show` | `auth:sanctum`, `worker` |
| POST | `/api/worker/certifications` | `api.worker.certifications.store` | `Worker\CertificationController@store` | `auth:sanctum`, `worker` |
| PUT | `/api/worker/certifications/{id}` | `api.worker.certifications.update` | `Worker\CertificationController@update` | `auth:sanctum`, `worker` |
| DELETE | `/api/worker/certifications/{id}` | `api.worker.certifications.destroy` | `Worker\CertificationController@destroy` | `auth:sanctum`, `worker` |
| POST | `/api/worker/certifications/{id}/documents` | `api.worker.certifications.documents` | `Worker\CertificationController@uploadDocument` | `auth:sanctum`, `worker` |
| POST | `/api/worker/certifications/{id}/renew` | `api.worker.certifications.renew` | `Worker\CertificationController@startRenewal` | `auth:sanctum`, `worker` |
| GET | `/api/worker/certifications/{id}/expiry` | `api.worker.certifications.expiry` | `Worker\CertificationController@checkExpiry` | `auth:sanctum`, `worker` |

---

### Social Authentication API

| Method | URI | Name | Controller | Middleware |
|--------|-----|------|------------|------------|
| GET | `/api/auth/social/{provider}` | `api.auth.social.redirect` | `SocialAuthController@redirect` | - |
| GET | `/api/auth/social/{provider}/callback` | `api.auth.social.callback` | `SocialAuthController@callback` | - |
| DELETE | `/api/auth/social/{provider}/disconnect` | `api.auth.social.disconnect` | `SocialAuthController@disconnect` | `auth:sanctum` |
| GET | `/api/auth/social/accounts` | `api.auth.social.accounts` | `SocialAuthController@getConnectedAccounts` | `auth:sanctum` |

**Providers**: `google`, `apple`, `facebook`

---

## Layouts

| File | Purpose | Used By |
|------|---------|---------|
| `layouts/marketing.blade.php` | Marketing/public pages | All public marketing pages |
| `layouts/dashboard.blade.php` | Unified dashboard layout | Worker, Business, Agency, Admin dashboards |
| `layouts/authenticated.blade.php` | Authenticated pages | Feature-specific authenticated pages |
| `layouts/guest.blade.php` | Guest/auth pages | Login, register, password reset |
| `layouts/admin.blade.php` | Admin-specific layout | Admin panel pages |
| `layouts/public.blade.php` | Public pages layout | Public help pages |

---

## Global Components

| Component | File | Purpose |
|-----------|------|---------|
| `<x-global-header>` | `components/global-header.blade.php` | Site-wide header with navigation |
| `<x-global-footer>` | `components/global-footer.blade.php` | Site-wide footer with links |
| `<x-trust-section>` | `components/trust-section.blade.php` | Trust indicators section |
| `<x-logo>` | `components/logo.blade.php` | Logo component |
| `<x-icon>` | `components/icon.blade.php` | Icon component |
| `<x-auth-card>` | `components/auth-card.blade.php` | Authentication card |
| `<x-clean-navbar>` | `components/clean-navbar.blade.php` | Clean navigation bar |
| `<x-how-it-works>` | `components/how-it-works.blade.php` | How it works section |
| `<x-live-shift-market>` | `components/live-shift-market.blade.php` | Live shift market component |
| `<x-stat-card>` | `components/stat-card.blade.php` | Statistics card |
| `<x-worker-badges>` | `components/worker-badges.blade.php` | Worker badges display |

---

## UI Components

| Component | File | Purpose |
|-----------|------|---------|
| `<x-ui.badge-pill>` | `components/ui/badge-pill.blade.php` | Pill-shaped badge |
| `<x-ui.button-primary>` | `components/ui/button-primary.blade.php` | Primary button component |
| `<x-ui.card-white>` | `components/ui/card-white.blade.php` | White card container |
| `<x-ui.tabbed-registration>` | `components/ui/tabbed-registration.blade.php` | Tabbed registration form |

---

## Dashboard Components

| Component | File | Purpose |
|-----------|------|---------|
| `<x-dashboard.widget-card>` | `components/dashboard/widget-card.blade.php` | Dashboard widget card |
| `<x-dashboard.empty-state>` | `components/dashboard/empty-state.blade.php` | Empty state display |
| `<x-dashboard.progress-bar>` | `components/dashboard/progress-bar.blade.php` | Progress bar |
| `<x-dashboard.quick-action>` | `components/dashboard/quick-action.blade.php` | Quick action button |
| `<x-dashboard.quick-actions>` | `components/dashboard/quick-actions.blade.php` | Quick actions container |
| `<x-dashboard.shift-list-item>` | `components/dashboard/shift-list-item.blade.php` | Shift list item |
| `<x-dashboard.sidebar-nav>` | `components/dashboard/sidebar-nav.blade.php` | Sidebar navigation |
| `<x-dashboard.sidebar-section>` | `components/dashboard/sidebar-section.blade.php` | Sidebar section |
| `<x-dashboard.stat-list>` | `components/dashboard/stat-list.blade.php` | Statistics list |
| `<x-dashboard.stat-metric>` | `components/dashboard/stat-metric.blade.php` | Single stat metric |

---

## Public Pages

### Core Marketing Pages

| Page | File | Route | Layout |
|------|------|-------|--------|
| Homepage | `welcome.blade.php` | `/` | `layouts.marketing` |
| Features | `public/features.blade.php` | `/features` | `layouts.marketing` |
| Pricing | `public/pricing.blade.php` | `/pricing` | `layouts.marketing` |
| About | `public/about.blade.php` | `/about` | Standalone HTML |
| Contact | `public/contact.blade.php` | `/contact` | Standalone HTML |
| Terms | `public/terms.blade.php` | `/terms` | Standalone HTML |
| Privacy | `public/privacy.blade.php` | `/privacy` | Standalone HTML |

### Worker Marketing Pages

| Page | File | Route | Layout |
|------|------|-------|--------|
| Find Shifts | `public/workers/find-shifts.blade.php` | `/workers/find-shifts` | `layouts.marketing` |
| Features | `public/workers/features.blade.php` | `/workers/features` | `layouts.marketing` |
| Get Started | `public/workers/get-started.blade.php` | `/workers/get-started` | `layouts.marketing` |

### Business Marketing Pages

| Page | File | Route | Layout |
|------|------|-------|--------|
| Find Staff | `public/business/find-staff.blade.php` | `/business/find-staff` | `layouts.marketing` |
| Pricing | `public/business/pricing.blade.php` | `/business/pricing` | `layouts.marketing` |
| Post Shifts | `public/business/post-shifts.blade.php` | `/business/post-shifts` | `layouts.marketing` |

---

## Authenticated Pages

### Worker Pages

| Page | File | Route | Layout |
|------|------|-------|--------|
| Dashboard | `worker/dashboard.blade.php` | `/dashboard/worker` | `layouts.dashboard` |
| Applications | `worker/applications.blade.php` | `/worker/applications` | `layouts.authenticated` |
| Applications (Index) | `worker/applications/index.blade.php` | `/worker/applications` | `layouts.authenticated` |
| Assignments | `worker/assignments.blade.php` | - | `layouts.authenticated` |
| Assignments (Index) | `worker/assignments/index.blade.php` | - | `layouts.authenticated` |
| Assignments (Show) | `worker/assignments/show.blade.php` | - | `layouts.authenticated` |
| Availability | `worker/availability.blade.php` | `/worker/availability` | - |
| Availability (Index) | `worker/availability/index.blade.php` | `/worker/availability` | `layouts.authenticated` |
| Calendar | `worker/calendar.blade.php` | - | `layouts.authenticated` |
| Calendar (Index) | `worker/calendar/index.blade.php` | - | `layouts.authenticated` |
| Certifications | `worker/certifications.blade.php` | `/worker/certifications` | - |
| Profile | `worker/profile.blade.php` | - | `layouts.authenticated` |
| Profile (Public) | `worker/profile/public.blade.php` | - | - |
| Profile (Public Preview) | `worker/profile/public-preview.blade.php` | - | `layouts.dashboard` |
| Profile (Featured) | `worker/profile/featured.blade.php` | - | `layouts.dashboard` |
| Profile (Badges) | `worker/profile/badges.blade.php` | - | `layouts.dashboard` |
| Portfolio (Index) | `worker/portfolio/index.blade.php` | - | `layouts.dashboard` |
| Portfolio (Edit) | `worker/portfolio/edit.blade.php` | - | `layouts.dashboard` |
| Portfolio (Upload) | `worker/portfolio/upload.blade.php` | - | `layouts.dashboard` |
| Skills | `worker/skills.blade.php` | `/worker/skills` | - |
| Market | `worker/market/index.blade.php` | - | `layouts.dashboard` |
| Shifts (Applications) | `worker/shifts/applications.blade.php` | - | `layouts.authenticated` |
| Shifts (Assignments) | `worker/shifts/assignments.blade.php` | - | - |
| Shifts (Rate) | `worker/shifts/rate.blade.php` | - | - |
| Swaps (Index) | `worker/swaps/index.blade.php` | - | - |
| Activation | `worker/activation/index.blade.php` | `/worker/activation` | - |
| Agency Invitation (Show) | `worker/agency-invitation/show.blade.php` | - | - |
| Agency Invitation (Invalid) | `worker/agency-invitation/invalid.blade.php` | - | - |
| Payment Setup | `worker/payment-setup.blade.php` | `/worker/payment-setup` | - |
| Onboarding (Complete Profile) | `worker/onboarding/complete-profile.blade.php` | - | `layouts.dashboard` |
| Onboarding (Dashboard) | `worker/onboarding/dashboard.blade.php` | - | - |
| Auth (Register) | `worker/auth/register.blade.php` | `/register/worker` | - |

### Business Pages

| Page | File | Route | Layout |
|------|------|-------|--------|
| Dashboard | `business/dashboard.blade.php` | `/dashboard/company` | `layouts.dashboard` |
| Profile | `business/profile.blade.php` | - | `layouts.authenticated` |
| Shifts | `business/shifts.blade.php` | - | `layouts.authenticated` |
| Shifts (Index) | `business/shifts/index.blade.php` | - | `layouts.authenticated` |
| Shifts (Show) | `business/shifts/show.blade.php` | - | `layouts.authenticated` |
| Shifts (Applications) | `business/shifts/applications.blade.php` | - | `layouts.authenticated` |
| Shifts (Rate) | `business/shifts/rate.blade.php` | - | `layouts.authenticated` |
| Shifts (Analytics) | `business/shifts/analytics.blade.php` | - | `layouts.authenticated` |
| Applications | `business/applications.blade.php` | - | `layouts.authenticated` |
| Swaps (Index) | `business/swaps/index.blade.php` | - | `layouts.authenticated` |
| Available Workers (Index) | `business/available_workers/index.blade.php` | - | `layouts.authenticated` |
| Available Workers (Match) | `business/available_workers/match.blade.php` | - | `layouts.authenticated` |
| Analytics | `business/analytics/index.blade.php` | - | - |
| Team (Index) | `business/team/index.blade.php` | - | `layouts.dashboard` |
| Team (Invite) | `business/team/invite.blade.php` | - | `layouts.dashboard` |
| Templates (Index) | `business/templates/index.blade.php` | - | `layouts.authenticated` |
| Onboarding (Complete Profile) | `business/onboarding/complete-profile.blade.php` | - | `layouts.dashboard` |
| Onboarding (Setup Payment) | `business/onboarding/setup-payment.blade.php` | - | `layouts.dashboard` |

### Agency Pages

| Page | File | Route | Layout |
|------|------|-------|--------|
| Dashboard | `agency/dashboard.blade.php` | `/dashboard/agency` | `layouts.dashboard` |
| Profile (Show) | `agency/profile/show.blade.php` | - | - |
| Profile (Edit) | `agency/profile/edit.blade.php` | - | - |
| Shifts (Index) | `agency/shifts/index.blade.php` | - | - |
| Shifts (Browse) | `agency/shifts/browse.blade.php` | - | - |
| Shifts (View) | `agency/shifts/view.blade.php` | - | - |
| Workers (Index) | `agency/workers/index.blade.php` | - | - |
| Workers (Add) | `agency/workers/add.blade.php` | - | - |
| Workers (Import) | `agency/workers/import.blade.php` | - | - |
| Workers (Invitations) | `agency/workers/invitations.blade.php` | - | - |
| Clients (Index) | `agency/clients/index.blade.php` | - | - |
| Clients (Show) | `agency/clients/show.blade.php` | - | - |
| Clients (Create) | `agency/clients/create.blade.php` | - | - |
| Clients (Edit) | `agency/clients/edit.blade.php` | - | - |
| Clients (Post Shift) | `agency/clients/post-shift.blade.php` | - | - |
| Placements (Create) | `agency/placements/create.blade.php` | - | - |
| Assignments (Index) | `agency/assignments/index.blade.php` | - | - |
| Commissions (Index) | `agency/commissions/index.blade.php` | - | - |
| Analytics | `agency/analytics.blade.php` | - | - |
| Go Live (Checklist) | `agency/go-live/checklist.blade.php` | - | - |
| Go Live (Agreement) | `agency/go-live/agreement.blade.php` | - | - |
| Onboarding (Verification Pending) | `agency/onboarding/verification-pending.blade.php` | - | - |
| Onboarding (Complete Profile) | `agency/onboarding/complete-profile.blade.php` | - | - |
| Stripe (Onboarding) | `agency/stripe/onboarding.blade.php` | - | - |
| Stripe (Status) | `agency/stripe/status.blade.php` | - | - |
| Sidebar | `agency/partials/sidebar.blade.php` | - | Partial |

### Admin Pages

| Page | File | Route | Layout |
|------|------|-------|--------|
| Dashboard | `admin/dashboard.blade.php` | `/dashboard/admin` | `layouts.dashboard` |
| Agencies (Performance) | `admin/agencies/performance.blade.php` | - | - |
| Agency Applications (Index) | `admin/agency-applications/index.blade.php` | - | - |
| Agency Applications (Show) | `admin/agency-applications/show.blade.php` | - | - |
| Businesses (Index) | `admin/businesses/index.blade.php` | - | - |
| Businesses (Show) | `admin/businesses/show.blade.php` | - | - |
| Workers (Show) | `admin/workers/show.blade.php` | - | - |
| Workers (Certifications) | `admin/workers/certifications.blade.php` | - | - |
| Shifts (Index) | `admin/shifts/index.blade.php` | - | - |
| Shifts (Show) | `admin/shifts/show.blade.php` | - | - |
| Shifts (Flagged) | `admin/shifts/flagged.blade.php` | - | - |
| Shifts (Statistics) | `admin/shifts/statistics.blade.php` | - | - |
| Payments (Show) | `admin/payments/show.blade.php` | - | - |
| Disputes (Index) | `admin/disputes/index.blade.php` | - | - |
| Disputes (Show) | `admin/disputes/show.blade.php` | - | - |
| Reports (Index) | `admin/reports/index.blade.php` | - | - |
| Alerting (Index) | `admin/alerting/index.blade.php` | - | - |
| Alerting (History) | `admin/alerting/history.blade.php` | - | - |
| System Health | `admin/system-health/index.blade.php` | - | - |
| Settings (Market) | `admin/settings/market.blade.php` | - | `layouts.admin` |
| Account Lockouts | `admin/account-lockouts/index.blade.php` | - | - |

---

## Authentication Pages

| Page | File | Route | Layout |
|------|------|-------|--------|
| Login | `auth/login.blade.php` | `/login` | `layouts.guest` |
| Register | `auth/register.blade.php` | `/register` | `layouts.guest` |
| Verify | `auth/verify.blade.php` | `/email/verify` | `layouts.authenticated` |
| Password (Email) | `auth/passwords/email.blade.php` | `/password/reset` | `layouts.guest` |
| Password (Reset) | `auth/passwords/reset.blade.php` | `/password/reset/{token}` | `layouts.guest` |
| Password (Confirm) | `auth/passwords/confirm.blade.php` | `/password/confirm` | `layouts.guest` |
| Two-Factor (Index) | `auth/two-factor/index.blade.php` | `/two-factor` | - |
| Two-Factor (Enable) | `auth/two-factor/enable.blade.php` | `/two-factor/enable` | - |
| Two-Factor (Verify) | `auth/two-factor/verify.blade.php` | `/two-factor/verify` | - |
| Two-Factor (Recovery) | `auth/two-factor/recovery.blade.php` | `/two-factor/recovery` | - |
| Two-Factor (Recovery Codes) | `auth/two-factor/recovery-codes.blade.php` | `/two-factor/recovery-codes` | - |

---

## Shared Pages

| Page | File | Route | Layout |
|------|------|-------|--------|
| Messages (Index) | `messages/index.blade.php` | `/messages` | `layouts.authenticated` |
| Messages (Show) | `messages/show.blade.php` | `/messages/{conversation}` | `layouts.authenticated` |
| Messages (Create) | `messages/create.blade.php` | - | `layouts.authenticated` |
| Notifications | `notifications/index.blade.php` | `/dashboard/notifications` | `layouts.authenticated` |
| Settings | `settings/index.blade.php` | `/settings` | - |
| Shifts (Index) | `shifts/index.blade.php` | `/shifts` | - |
| Shifts (Create) | `shifts/create.blade.php` | `/shifts/create` | - |
| Shifts (Show) | `shifts/show.blade.php` | - | - |
| Shifts (Edit) | `shifts/edit.blade.php` | - | - |
| Shifts (Recommended) | `shifts/recommended.blade.php` | - | - |
| Swaps (Index) | `swaps/index.blade.php` | - | - |
| Swaps (Create) | `swaps/create.blade.php` | - | - |
| Swaps (Show) | `swaps/show.blade.php` | - | - |
| Templates (Index) | `templates/index.blade.php` | - | - |
| Templates (Create) | `templates/create.blade.php` | - | - |
| Templates (Edit) | `templates/edit.blade.php` | - | - |
| Templates (Show) | `templates/show.blade.php` | - | - |
| Calendar (Business) | `calendar/business.blade.php` | - | `layouts.authenticated` |
| Calendar (Worker) | `calendar/worker.blade.php` | - | `layouts.authenticated` |
| Onboarding (Start) | `onboarding/start.blade.php` | - | `layouts.authenticated` |
| Onboarding (Role Selection) | `onboarding/role-selection.blade.php` | - | - |
| Onboarding (Worker) | `onboarding/worker.blade.php` | - | - |
| Onboarding (Business) | `onboarding/business.blade.php` | - | - |
| Onboarding (Agency) | `onboarding/agency.blade.php` | - | `layouts.authenticated` |
| Onboarding (Complete) | `onboarding/complete.blade.php` | - | - |
| Onboarding (Verification Pending) | `onboarding/verification-pending.blade.php` | - | - |
| Dashboard (Welcome) | `dashboard/welcome.blade.php` | - | `layouts.authenticated` |
| Users (Profile) | `users/profile.blade.php` | - | - |
| Users (Referrals) | `users/referrals.blade.php` | - | - |
| Users (Transactions) | `users/transactions.blade.php` | - | - |
| Users (Messages New) | `users/messages-new.blade.php` | - | - |
| Users (Shift Messages) | `users/shift-messages.blade.php` | - | - |
| My (Transactions) | `my/transactions.blade.php` | - | `layouts.authenticated` |
| Pages (Show) | `pages/show.blade.php` | - | - |

---

## Error Pages

| Page | File | Route | Layout |
|------|------|-------|--------|
| 401 Unauthorized | `errors/401.blade.php` | - | - |
| 403 Forbidden | `errors/403.blade.php` | - | - |
| 404 Not Found | `errors/404.blade.php` | - | `layouts.guest` |
| 419 CSRF Token Mismatch | `errors/419.blade.php` | - | - |
| 429 Too Many Requests | `errors/429.blade.php` | - | - |
| 500 Server Error | `errors/500.blade.php` | - | `layouts.guest` |
| 503 Service Unavailable | `errors/503.blade.php` | - | - |
| Access Denied | `errors/access-denied.blade.php` | `/access-denied` | - |
| Errors Forms | `errors/errors-forms.blade.php` | - | Partial |

---

## Statistics

- **Total Web Routes**: 130+
- **Total API Routes**: 80+
- **Total Views**: 374 Blade files
- **Layouts**: 6
- **Global Components**: 11
- **UI Components**: 4
- **Dashboard Components**: 10
- **Public Pages**: 13
- **Worker Pages**: 30+
- **Business Pages**: 20+
- **Agency Pages**: 25+
- **Admin Pages**: 20+
- **Error Pages**: 8

---

## Route Categories Summary

### By Middleware

- **Public Routes** (No Auth): 15 routes
- **Guest Routes** (Login/Register): 11 routes
- **Authenticated Routes** (Auth Required): 50+ routes
- **Role-Specific Routes**: 30+ routes
- **API Routes**: 80+ routes

### By User Type

- **Worker Routes**: 15+ web routes, 30+ API routes
- **Business Routes**: 10+ web routes, 20+ API routes
- **Agency Routes**: 10+ web routes
- **Admin Routes**: 5+ web routes
- **Shared Routes**: 20+ routes

---

**Last Updated**: 2025-12-15  
**Status**: Complete inventory of all routes and components
