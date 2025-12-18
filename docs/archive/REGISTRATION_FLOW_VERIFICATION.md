# Registration Flow Verification & Documentation

## Current Registration Flow Status

### ✅ Marketing Pages → Registration
All marketing pages correctly link to `route('register', ['type' => 'worker'|'business'])`:
- **Find Shifts** → `route('register', ['type' => 'worker'])`
- **Get Started** → `route('register', ['type' => 'worker'])`
- **Find Staff** → `route('register', ['type' => 'business'])`
- **Post Shifts** → `route('register', ['type' => 'business'])`
- **Business Pricing** → `route('register', ['type' => 'business'])`

### ✅ Unified Registration Page
**Route:** `route('register')` or `route('register', ['type' => 'worker'|'business'|'agency'])`

**Controller:** `App\Http\Controllers\Auth\RegisterController@showRegistrationForm`

**Current Implementation:**
- ✅ Accepts `?type=worker|business|agency` query parameter
- ✅ Pre-selects user type based on parameter
- ✅ Uses radio buttons for Worker/Business (currently missing Agency)
- ✅ Form fields: Name, Email, Password, Password Confirmation, Terms acceptance
- ✅ Validates: `user_type` must be `worker`, `business`, or `agency`
- ✅ Creates corresponding profile (WorkerProfile, BusinessProfile, or AgencyProfile)

**Issues Found:**
- ❌ Agency option missing from form (controller supports it, but form doesn't show it)
- ❌ Radio buttons instead of tabs (diagram shows tabs)
- ❌ No social registration buttons (diagram shows Google/Apple/Facebook)

### ✅ Email Verification Flow
**Routes:**
- `route('verification.notice')` - Shows "Verify your email" page
- `route('verification.verify', ['id' => $id, 'hash' => $hash])` - Email link verification
- `route('verification.resend')` - Resend verification email

**Flow:**
1. User registers → Account created
2. Email verification notification sent automatically
3. User auto-logged in
4. Redirected to `verification.notice`
5. User clicks link in email → `verification.verify`
6. Email verified → Proceed to onboarding

### ✅ Onboarding Flow
**Current Redirect:** `/onboarding` (after email verification)

**Role-Specific Onboarding:**
- **Worker:** Profile, Skills, Availability, ID Verify, Payment Setup
- **Business:** Company Info, Verification, Payment, First Shift (optional)
- **Agency:** Agency Info, Documents, Verification, Agreement, Go Live

### ✅ Dashboard Access
**Routes:**
- `route('dashboard.index')` - Main dashboard (role-based redirect)
- `route('dashboard.worker')` - Worker dashboard
- `route('dashboard.company')` - Business dashboard
- `route('dashboard.agency')` - Agency dashboard
- `route('dashboard.admin')` - Admin dashboard

## Flow Diagram Verification

### Marketing Pages ✅
```
Find Shifts → route('register', ['type' => 'worker'])
Get Started → route('register', ['type' => 'worker'])
Find Staff → route('register', ['type' => 'business'])
Post Shifts → route('register', ['type' => 'business'])
```

### Unified Registration Page ⚠️
**Current:** Radio buttons for Worker/Business (Agency missing)
**Expected (per diagram):** Tabs for Worker/Business/Agency

**Form Fields:**
- ✅ Name
- ✅ Email
- ✅ Phone (not in current form, but diagram doesn't show it)
- ✅ Password
- ✅ Terms acceptance
- ❌ Social registration (Google/Apple/Facebook) - Not implemented

### Email Verification ✅
```
route('verification.notice') → User clicks email link → route('verification.verify')
```

### Onboarding ✅
Role-specific onboarding flows exist and are required before dashboard access.

### Dashboard ✅
Role-specific dashboards exist and are accessible after onboarding completion.

## Recommendations

### 1. Add Agency Tab to Registration Form
The controller already supports `agency` type, but the form doesn't show it. Add Agency option.

### 2. Convert Radio Buttons to Tabs
Match the diagram by converting radio buttons to a tabbed interface.

### 3. Social Registration (Optional)
If social registration is desired, implement OAuth providers (Google, Apple, Facebook).

### 4. Agency Registration Flow
**Decision needed:** Should agency registration:
- **Option A:** Use unified `route('register', ['type' => 'agency'])` → Simple account creation → Then multi-step onboarding
- **Option B:** Redirect to `route('agency.register.index')` → Multi-step registration flow (current implementation)

**Current:** Agency has separate multi-step registration at `/register/agency`

## Current Registration Routes

| Route | Controller | Purpose |
|-------|------------|---------|
| `route('register')` | `RegisterController@showRegistrationForm` | Unified registration form |
| `route('register')` (POST) | `RegisterController@register` | Process registration |
| `route('business.register.index')` | `Business\RegistrationController@showRegistrationForm` | Legacy business registration |
| `route('worker.register.index')` | `Worker\RegistrationController@showRegistrationForm` | Legacy worker registration |
| `route('agency.register.index')` | `Agency\RegistrationController@index` | Agency multi-step registration |

## Next Steps

1. ✅ Fixed `tabbed-registration` component routes
2. ⚠️ Add Agency option to unified registration form
3. ⚠️ Convert radio buttons to tabs (optional - to match diagram)
4. ⚠️ Decide on agency registration flow (unified vs separate)
