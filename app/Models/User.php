<?php

namespace App\Models;

use App\Notifications\ResetPassword as ResetPasswordNotification;
use App\Traits\CachesUserProfile;
use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Cashier\Billable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $dev_expires_at
 * @property bool $is_dev_account
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $username
 * @property string $role
 * @property string $status
 * @property int $mfa_enabled
 * @property string $user_type
 * @property int $is_verified_worker
 * @property int $is_verified_business
 * @property string|null $onboarding_step
 * @property int $onboarding_completed
 * @property string|null $notification_preferences
 * @property string|null $availability_schedule
 * @property int|null $max_commute_distance
 * @property numeric $rating_as_worker
 * @property numeric $rating_as_business
 * @property int $total_shifts_completed
 * @property int $total_shifts_posted
 * @property numeric $reliability_score
 * @property string|null $bio
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AgencyClient> $agencyClients
 * @property-read int|null $agency_clients_count
 * @property-read \App\Models\AgencyProfile|null $agencyProfile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $agencyWorkers
 * @property-read int|null $agency_workers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $appliedShifts
 * @property-read int|null $applied_shifts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $assignedShifts
 * @property-read int|null $assigned_shifts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AvailabilityBroadcast> $availabilityBroadcasts
 * @property-read int|null $availability_broadcasts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkerBadge> $badges
 * @property-read int|null $badges_count
 * @property-read \App\Models\BusinessProfile|null $businessProfile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Certification> $certifications
 * @property-read int|null $certifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversationsAsBusiness
 * @property-read int|null $conversations_as_business_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Conversation> $conversationsAsWorker
 * @property-read int|null $conversations_as_worker_count
 * @property-read mixed $balance
 * @property-read string $dashboard_route
 * @property-read mixed $first_name
 * @property-read mixed $last_name
 * @property-read string $profile_route
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Notifications> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Shift> $postedShifts
 * @property-read int|null $posted_shifts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rating> $ratingsGiven
 * @property-read int|null $ratings_given_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rating> $ratingsReceived
 * @property-read int|null $ratings_received_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $receivedMessages
 * @property-read int|null $received_messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $sentMessages
 * @property-read int|null $sent_messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftApplication> $shiftApplications
 * @property-read int|null $shift_applications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftAssignment> $shiftAssignments
 * @property-read int|null $shift_assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftInvitation> $shiftInvitations
 * @property-read int|null $shift_invitations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftPayment> $shiftPaymentsMade
 * @property-read int|null $shift_payments_made_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftPayment> $shiftPaymentsReceived
 * @property-read int|null $shift_payments_received_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Skill> $skills
 * @property-read int|null $skills_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Cashier\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \App\Models\VerificationQueue|null $verificationRequest
 * @property-read \App\Models\WorkerProfile|null $workerProfile
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User hasExpiredGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvailabilitySchedule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDevExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsDevAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsVerifiedBusiness($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsVerifiedWorker($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMaxCommuteDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMfaEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereNotificationPreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOnboardingCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOnboardingStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRatingAsBusiness($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRatingAsWorker($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereReliabilityScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTotalShiftsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTotalShiftsPosted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUserType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUsername($value)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements FilamentUser, HasLocalePreference, MustVerifyEmail
{
    use Billable, CachesUserProfile, HasFactory, \Laravel\Sanctum\HasApiTokens, Notifiable;

    // Use standard Laravel timestamps for OvertimeStaff
    // const CREATED_AT = 'date';
    // const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'countries_id',
        'name',
        'email',
        'password',
        'avatar',
        'cover',
        'status',
        'role',
        'permission',
        'confirmation_code',
        'oauth_uid',
        'oauth_provider',
        'token',
        'bio',
        'verified_id',
        'ip',
        'language',
        'stripe_connect_id',
        'completed_stripe_onboarding',
        'device_token',
        // OvertimeStaff shift marketplace fields
        'user_type',
        'is_verified_worker',
        'is_verified_business',
        'onboarding_step',
        'onboarding_completed',
        'notification_preferences',
        'availability_schedule',
        'max_commute_distance',
        'rating_as_worker',
        'rating_as_business',
        'total_shifts_completed',
        'total_shifts_posted',
        'reliability_score',
        // Suspension fields
        'suspended_until',
        'suspension_reason',
        'suspension_count',
        'last_suspended_at',
        // Dev account fields
        'is_dev_account',
        'dev_expires_at',
        // Account lockout fields
        'locked_until',
        'lock_reason',
        'failed_login_attempts',
        'last_failed_login_at',
        'locked_at',
        'locked_by_admin_id',
        // KYC fields
        'kyc_verified',
        'kyc_verified_at',
        'kyc_level',
        // Strike/suspension status fields (WKR-009)
        'is_suspended',
        'strike_count',
        'last_strike_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'dev_expires_at' => 'datetime',
        'is_dev_account' => 'boolean',
        'suspended_until' => 'datetime',
        'last_suspended_at' => 'datetime',
        // Account lockout casts
        'locked_until' => 'datetime',
        'last_failed_login_at' => 'datetime',
        'locked_at' => 'datetime',
        'failed_login_attempts' => 'integer',
        // Two-Factor Authentication casts
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
        // KYC casts
        'kyc_verified' => 'boolean',
        'kyc_verified_at' => 'datetime',
        // Strike/suspension casts (WKR-009)
        'is_suspended' => 'boolean',
        'strike_count' => 'integer',
        'last_strike_at' => 'datetime',
    ];

    /**
     * Get applicable tax rates for shift payments (OvertimeStaff)
     *
     * @return array
     */
    public function taxRates()
    {
        // For OvertimeStaff shift payments, use TaxRates model
        // Tax calculation is handled in ShiftPaymentService
        return $this->isTaxable()->pluck('percentage')->toArray();
    }

    public function isTaxable()
    {
        // Check if TaxRates model and table exist
        if (! class_exists('App\Models\TaxRates') || ! \Schema::hasTable('tax_rates')) {
            return collect(); // Return empty collection if table doesn't exist
        }

        try {
            return TaxRates::whereStatus('1')
                ->whereIsoState($this->getRegion())
                ->whereCountry($this->getCountry())
                ->orWhere('country', $this->getCountry())
                ->whereNull('iso_state')
                ->whereStatus('1')
                ->get();
        } catch (\Exception $e) {
            return collect(); // Return empty collection on error
        }
    }

    public function taxesPayable()
    {
        return $this->isTaxable()
            ->pluck('id')
            ->implode('_');
    }

    public function getCountry()
    {
        $ip = request()->ip();

        return cache('userCountry-'.$ip) ?? ($this->country()->country_code ?? null);
    }

    public function getRegion()
    {
        $ip = request()->ip();

        return cache('userRegion-'.$ip);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Countries, User>|null
     */
    public function country(): ?\Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        // Return null if Countries model/table doesn't exist
        try {
            if (class_exists('App\Models\Countries') && Schema::hasTable('countries')) {
                return $this->belongsTo(Countries::class, 'countries_id');
            }
        } catch (\Exception $e) {
            // Table doesn't exist, return null
        }

        return null;
    }

    public function notifications()
    {
        return $this->hasMany(Notifications::class, 'destination');
    }

    public function getFirstNameAttribute()
    {
        $name = explode(' ', $this->name);

        return $name[0] ?? null;
    }

    public function getLastNameAttribute()
    {
        $name = explode(' ', $this->name);

        return $name[1] ?? null;
    }

    /**
     * Get the user's preferred locale.
     */
    public function preferredLocale()
    {
        return $this->language;
    }

    /**
     * Get the user's is Super Admin.
     */
    public function isSuperAdmin()
    {
        if ($this->permissions == 'full_access') {
            return $this->id;
        }

        return false;
    }

    /**
     * Get the user's permissions.
     */
    public function hasPermission($section)
    {
        $permissions = explode(',', $this->permissions);

        return in_array($section, $permissions)
            || $this->permissions == 'full_access'
            || $this->permissions == 'limited_access'
            ? true
            : false;
    }

    /**
     * Get the user's blocked countries.
     */
    public function blockedCountries()
    {
        return explode(',', $this->blocked_countries);
    }

    // ==================== OVERTIMESTAFF SHIFT MARKETPLACE METHODS ====================

    /**
     * User Type Check Methods
     */
    public function isWorker()
    {
        return $this->user_type === 'worker';
    }

    public function isBusiness()
    {
        return $this->user_type === 'business';
    }

    public function isAgency()
    {
        return $this->user_type === 'agency';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Get the appropriate dashboard route for this user
     */
    public function getDashboardRoute()
    {
        if ($this->isAdmin()) {
            return route('admin.dashboard');
        }

        // All other user types use the generic dashboard route
        // which automatically routes to the correct dashboard
        return route('dashboard');
    }

    /**
     * Get the profile route for this user with safe fallback
     * Used as an accessor: $user->profile_route
     *
     * @return string
     */
    public function getProfileRouteAttribute()
    {
        // Admin users go to settings
        if ($this->isAdmin()) {
            return \Route::has('admin.profile') ? route('admin.profile') : route('settings.index');
        }

        // Build the route name based on user type
        $routeName = $this->user_type.'.profile';

        // Check if the route exists, otherwise fallback to dashboard or home
        if (\Route::has($routeName)) {
            return route($routeName);
        }

        // Fallback to dashboard
        $dashboardRoute = $this->user_type.'.dashboard';
        if (\Route::has($dashboardRoute)) {
            return route($dashboardRoute);
        }

        // Ultimate fallback
        return route('home');
    }

    /**
     * Get the dashboard route for this user with safe fallback
     * Used as an accessor: $user->dashboard_route
     *
     * @return string
     */
    public function getDashboardRouteAttribute()
    {
        return $this->getDashboardRoute();
    }

    /**
     * Profile Relationships
     */
    public function workerProfile()
    {
        return $this->hasOne(WorkerProfile::class);
    }

    public function businessProfile()
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function agencyProfile()
    {
        return $this->hasOne(AgencyProfile::class);
    }

    /**
     * SL-005: Face Profile for clock-in/out verification
     */
    public function faceProfile()
    {
        return $this->hasOne(FaceProfile::class);
    }

    /**
     * SL-005: Face Verification Logs
     */
    public function faceVerificationLogs()
    {
        return $this->hasMany(FaceVerificationLog::class);
    }

    /**
     * Agency Relationships
     */
    public function agencyWorkers()
    {
        return $this->belongsToMany(User::class, 'agency_workers', 'agency_id', 'worker_id')
            ->withPivot('status', 'commission_rate')
            ->withTimestamps();
    }

    public function agencyClients()
    {
        return $this->hasMany(AgencyClient::class, 'agency_id');
    }

    /**
     * AGY-006: White-Label Configuration for Agencies
     */
    public function whiteLabelConfig()
    {
        return $this->hasOne(WhiteLabelConfig::class, 'agency_id');
    }

    /**
     * GLO-010: Data Residency - User's data region assignment
     */
    public function dataResidency()
    {
        return $this->hasOne(UserDataResidency::class);
    }

    /**
     * GLO-010: Data Residency - User's data transfer logs
     */
    public function dataTransferLogs()
    {
        return $this->hasMany(DataTransferLog::class);
    }

    /**
     * Get the active profile based on user type
     */
    public function profile()
    {
        switch ($this->user_type) {
            case 'worker':
                return $this->workerProfile;
            case 'business':
                return $this->businessProfile;
            case 'agency':
                return $this->agencyProfile;
            default:
                return null;
        }
    }

    /**
     * Shift Relationships - For Businesses
     */
    public function postedShifts()
    {
        return $this->hasMany(Shift::class, 'business_id');
    }

    /**
     * Shift Relationships - For Workers
     */
    public function appliedShifts()
    {
        return $this->belongsToMany(Shift::class, 'shift_applications', 'worker_id', 'shift_id')
            ->withPivot('status', 'application_note', 'applied_at', 'responded_at')
            ->withTimestamps();
    }

    public function assignedShifts()
    {
        return $this->belongsToMany(Shift::class, 'shift_assignments', 'worker_id', 'shift_id')
            ->withPivot('status', 'check_in_time', 'check_out_time', 'hours_worked')
            ->withTimestamps();
    }

    public function completedShifts()
    {
        return $this->assignedShifts()->wherePivot('status', 'completed');
    }

    /**
     * Shift Applications
     */
    public function shiftApplications()
    {
        return $this->hasMany(ShiftApplication::class, 'worker_id');
    }

    /**
     * Shift Assignments
     */
    public function shiftAssignments()
    {
        return $this->hasMany(ShiftAssignment::class, 'worker_id');
    }

    /**
     * Shift Payments - As Worker
     */
    public function shiftPaymentsReceived()
    {
        return $this->hasMany(ShiftPayment::class, 'worker_id');
    }

    /**
     * Shift Payments - As Business
     */
    public function shiftPaymentsMade()
    {
        return $this->hasMany(ShiftPayment::class, 'business_id');
    }

    /**
     * Alias for shiftPaymentsMade() - used in views/controllers.
     * Returns all shift payments made by this user (as a business).
     */
    public function shiftPayments()
    {
        return $this->shiftPaymentsMade();
    }

    /**
     * Skills - For Workers
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'worker_skills', 'worker_id', 'skill_id')
            ->withPivot('proficiency_level', 'years_experience', 'verified')
            ->withTimestamps();
    }

    /**
     * Certifications - For Workers
     */
    public function certifications()
    {
        return $this->belongsToMany(Certification::class, 'worker_certifications', 'worker_id', 'certification_id')
            ->withPivot('certification_number', 'issue_date', 'expiry_date', 'document_url', 'verified', 'verified_at')
            ->withTimestamps();
    }

    /**
     * Ratings Given
     */
    public function ratingsGiven()
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    /**
     * Ratings Received
     */
    public function ratingsReceived()
    {
        return $this->hasMany(Rating::class, 'rated_id');
    }

    /**
     * Alias for ratingsReceived() - used by admin controllers.
     * Returns all ratings where this user was rated.
     */
    public function ratings()
    {
        return $this->ratingsReceived();
    }

    /**
     * Assignments - Alias for shiftAssignments() for use in analytics.
     */
    public function assignments()
    {
        return $this->shiftAssignments();
    }

    /**
     * Verification Request - For pending verifications.
     * Returns the user's pending verification request if any.
     */
    public function verificationRequest()
    {
        return $this->hasOne(VerificationQueue::class, 'user_id')->where('status', 'pending');
    }

    /**
     * Shift Invitations - For Workers
     */
    public function shiftInvitations()
    {
        return $this->hasMany(ShiftInvitation::class, 'worker_id');
    }

    /**
     * Availability Broadcasts - For Workers
     */
    public function availabilityBroadcasts()
    {
        return $this->hasMany(AvailabilityBroadcast::class, 'worker_id');
    }

    /**
     * Worker Badges - For Workers
     */
    public function badges()
    {
        return $this->hasMany(WorkerBadge::class, 'worker_id');
    }

    /**
     * Loyalty Points Account
     */
    public function loyaltyPoints()
    {
        return $this->hasOne(LoyaltyPoints::class);
    }

    /**
     * QUA-002: Mystery Shopper Profile
     */
    public function mysteryShopper()
    {
        return $this->hasOne(MysteryShopper::class);
    }

    /**
     * QUA-002: Shift Audits conducted by this user (as auditor)
     */
    public function conductedAudits()
    {
        return $this->hasMany(ShiftAudit::class, 'auditor_id');
    }

    /**
     * Loyalty Transactions
     */
    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /**
     * Loyalty Redemptions
     */
    public function loyaltyRedemptions()
    {
        return $this->hasMany(LoyaltyRedemption::class);
    }

    /**
     * WKR-006: Worker Earnings - Individual earnings records
     */
    public function workerEarnings()
    {
        return $this->hasMany(WorkerEarning::class);
    }

    /**
     * WKR-006: Earnings Summaries - Cached aggregate summaries
     */
    public function earningsSummaries()
    {
        return $this->hasMany(EarningsSummary::class);
    }

    /**
     * WKR-013: Availability Patterns - Historical availability patterns for ML predictions
     */
    public function availabilityPatterns()
    {
        return $this->hasMany(AvailabilityPattern::class);
    }

    /**
     * WKR-013: Availability Predictions - Predicted future availability
     */
    public function availabilityPredictions()
    {
        return $this->hasMany(AvailabilityPrediction::class);
    }

    /**
     * WKR-013: Get availability prediction for a specific date
     */
    public function getAvailabilityPredictionFor(\Carbon\Carbon $date): ?AvailabilityPrediction
    {
        return $this->availabilityPredictions()
            ->where('prediction_date', $date->toDateString())
            ->first();
    }

    /**
     * WKR-013: Get availability pattern for a specific day of week
     */
    public function getAvailabilityPatternFor(int $dayOfWeek): ?AvailabilityPattern
    {
        return $this->availabilityPatterns()
            ->where('day_of_week', $dayOfWeek)
            ->first();
    }

    /**
     * GLO-001: Currency Wallets - Multi-currency wallet support
     */
    public function currencyWallets()
    {
        return $this->hasMany(CurrencyWallet::class);
    }

    /**
     * GLO-001: Primary Currency Wallet
     */
    public function primaryCurrencyWallet()
    {
        return $this->hasOne(CurrencyWallet::class)->where('is_primary', true);
    }

    /**
     * GLO-001: Currency Conversions
     */
    public function currencyConversions()
    {
        return $this->hasMany(CurrencyConversion::class);
    }

    /**
     * WKR-010: Portfolio Items - For Workers
     */
    public function portfolioItems()
    {
        return $this->hasMany(WorkerPortfolioItem::class, 'worker_id');
    }

    /**
     * WKR-010: Featured Statuses - For Workers
     */
    public function featuredStatuses()
    {
        return $this->hasMany(WorkerFeaturedStatus::class, 'worker_id');
    }

    /**
     * WKR-010: Profile Views - For Workers
     */
    public function profileViews()
    {
        return $this->hasMany(WorkerProfileView::class, 'worker_id');
    }

    /**
     * WKR-010: Endorsements Received - For Workers
     */
    public function endorsementsReceived()
    {
        return $this->hasMany(WorkerEndorsement::class, 'worker_id');
    }

    /**
     * WKR-010: Active Featured Status - For Workers
     */
    public function activeFeaturedStatus()
    {
        return $this->hasOne(WorkerFeaturedStatus::class, 'worker_id')
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * SAF-005: Health Declarations - For Workers
     */
    public function healthDeclarations()
    {
        return $this->hasMany(HealthDeclaration::class);
    }

    /**
     * SAF-005: Vaccination Records - For Workers
     */
    public function vaccinationRecords()
    {
        return $this->hasMany(VaccinationRecord::class);
    }

    /**
     * SAF-005: Verified Vaccination Records - For Workers
     */
    public function verifiedVaccinations()
    {
        return $this->vaccinationRecords()->verified();
    }

    /**
     * SAF-005: Vaccination Records Verified By This Admin
     */
    public function verifiedVaccinationRecords()
    {
        return $this->hasMany(VaccinationRecord::class, 'verified_by');
    }

    /**
     * SAF-001: Emergency Contacts
     */
    public function emergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class)->orderBy('priority');
    }

    /**
     * SAF-001: Primary Emergency Contact
     */
    public function primaryEmergencyContact()
    {
        return $this->hasOne(EmergencyContact::class)->where('is_primary', true);
    }

    /**
     * SAF-001: Verified Emergency Contacts
     */
    public function verifiedEmergencyContacts()
    {
        return $this->hasMany(EmergencyContact::class)
            ->where('is_verified', true)
            ->orderBy('priority');
    }

    /**
     * SAF-001: Emergency Alerts (triggered by this user)
     */
    public function emergencyAlerts()
    {
        return $this->hasMany(EmergencyAlert::class);
    }

    /**
     * SAF-001: Active Emergency Alert
     */
    public function activeEmergencyAlert()
    {
        return $this->hasOne(EmergencyAlert::class)
            ->where('status', 'active');
    }

    /**
     * SAF-001: Emergency Alerts Acknowledged By This Admin
     */
    public function acknowledgedEmergencyAlerts()
    {
        return $this->hasMany(EmergencyAlert::class, 'acknowledged_by');
    }

    /**
     * SAF-001: Emergency Alerts Resolved By This Admin
     */
    public function resolvedEmergencyAlerts()
    {
        return $this->hasMany(EmergencyAlert::class, 'resolved_by');
    }

    /**
     * COM-002: Push Notification Tokens
     */
    public function pushNotificationTokens()
    {
        return $this->hasMany(PushNotificationToken::class);
    }

    /**
     * COM-002: Active Push Notification Tokens
     */
    public function activePushTokens()
    {
        return $this->pushNotificationTokens()->active();
    }

    /**
     * COM-002: Push Notification Logs
     */
    public function pushNotificationLogs()
    {
        return $this->hasMany(PushNotificationLog::class);
    }

    /**
     * Rating Calculation Methods
     */
    public function averageRatingAsWorker()
    {
        return $this->ratingsReceived()
            ->where('rater_type', 'business')
            ->avg('rating') ?? 0;
    }

    public function averageRatingAsBusiness()
    {
        return $this->ratingsReceived()
            ->where('rater_type', 'worker')
            ->avg('rating') ?? 0;
    }

    /**
     * GLO-008: Bank Accounts - For Cross-Border Payments
     */
    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    /**
     * GLO-008: Primary Bank Account
     */
    public function primaryBankAccount()
    {
        return $this->hasOne(BankAccount::class)->where('is_primary', true);
    }

    /**
     * GLO-008: Cross Border Transfers
     */
    public function crossBorderTransfers()
    {
        return $this->hasMany(CrossBorderTransfer::class);
    }

    /**
     * Payment Methods
     */
    public function canReceiveInstantPayouts()
    {
        // Check if worker has Stripe Connect set up and verified
        return $this->stripe_connect_id
            && $this->completed_stripe_onboarding
            && $this->isWorker();
    }

    public function hasValidPayoutMethod()
    {
        return ! empty($this->stripe_connect_id) && $this->completed_stripe_onboarding;
    }

    // ==================== FIN-004: INSTAPAY METHODS ====================

    /**
     * FIN-004: InstaPay Requests - All instant payout requests by this user
     */
    public function instapayRequests()
    {
        return $this->hasMany(InstapayRequest::class);
    }

    /**
     * FIN-004: InstaPay Settings - User's InstaPay preferences
     */
    public function instapaySettings()
    {
        return $this->hasOne(InstapaySettings::class);
    }

    /**
     * FIN-004: Get or create InstaPay settings for this user
     */
    public function getOrCreateInstapaySettings(): InstapaySettings
    {
        return InstapaySettings::getOrCreateForUser($this);
    }

    /**
     * FIN-004: Check if user is eligible for InstaPay
     */
    public function isEligibleForInstapay(): bool
    {
        // Must be a worker
        if (! $this->isWorker()) {
            return false;
        }

        // Must have completed minimum shifts
        $minShifts = config('instapay.eligibility.min_completed_shifts', 3);
        if ($this->total_shifts_completed < $minShifts) {
            return false;
        }

        // Must have minimum reliability score
        $minReliability = config('instapay.eligibility.min_reliability_score', 70);
        if ($this->reliability_score < $minReliability) {
            return false;
        }

        // Must be verified if required
        if (config('instapay.eligibility.require_verified', true) && ! $this->is_verified_worker) {
            return false;
        }

        // Must have a valid payout method
        if (config('instapay.eligibility.require_payment_method', true) && ! $this->hasValidPayoutMethod()) {
            return false;
        }

        return true;
    }

    /**
     * FIN-004: Get today's InstaPay total for this user
     */
    public function getTodayInstapayTotal(): float
    {
        return $this->instapayRequests()
            ->today()
            ->whereIn('status', [
                InstapayRequest::STATUS_PENDING,
                InstapayRequest::STATUS_PROCESSING,
                InstapayRequest::STATUS_COMPLETED,
            ])
            ->sum('gross_amount');
    }

    /**
     * FIN-004: Get remaining daily InstaPay limit
     */
    public function getRemainingInstapayLimit(): float
    {
        $settings = $this->instapaySettings;
        $dailyLimit = $settings ? $settings->getEffectiveDailyLimit() : config('instapay.daily_limit', 500.00);
        $usedToday = $this->getTodayInstapayTotal();

        return max(0, $dailyLimit - $usedToday);
    }

    /**
     * Onboarding Status
     */
    public function hasCompletedOnboarding()
    {
        return $this->onboarding_completed ?? false;
    }

    public function getOnboardingProgress()
    {
        return $this->onboarding_step ?? 'not_started';
    }

    /**
     * Active Shift - Current shift in progress
     */
    public function activeShift()
    {
        return $this->shiftAssignments()
            ->whereIn('status', ['assigned', 'checked_in'])
            ->with('shift')
            ->first();
    }

    /**
     * Upcoming Shifts
     */
    public function upcomingShifts()
    {
        return $this->assignedShifts()
            ->where('shift_date', '>=', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderBy('shift_date')
            ->orderBy('start_time');
    }

    /**
     * Messaging Relationships
     */
    public function conversations()
    {
        return Conversation::forUser($this->id);
    }

    public function conversationsAsWorker()
    {
        return $this->hasMany(Conversation::class, 'worker_id');
    }

    public function conversationsAsBusiness()
    {
        return $this->hasMany(Conversation::class, 'business_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'from_user_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'to_user_id');
    }

    /**
     * Get unread conversations count
     */
    public function unreadConversationsCount()
    {
        return Conversation::forUser($this->id)
            ->active()
            ->withUnreadFor($this->id)
            ->count();
    }

    /**
     * Get unread messages count
     */
    public function unreadMessagesCount()
    {
        return Message::forRecipient($this->id)
            ->unread()
            ->count();
    }

    /**
     * Get unread notifications count
     * Static method for use in views/blade templates
     * Note: Legacy notifications table uses 'read' (boolean), not 'read_at'
     */
    public static function notificationsCount()
    {
        if (! auth()->check()) {
            return 0;
        }

        $user = auth()->user();

        return $user->notifications()
            ->where('read', false)
            ->count();
    }

    /**
     * Get unread notifications count (instance method)
     * Note: Legacy notifications table uses 'read' (boolean), not 'read_at'
     */
    public function getUnreadNotificationsCount()
    {
        return $this->notifications()
            ->where('read', false)
            ->count();
    }

    /**
     * Legacy Balance Accessor (for backward compatibility with old views)
     * Returns 0.00 as OvertimeStaff doesn't use wallet/balance system
     */
    public function getBalanceAttribute()
    {
        return 0.00;
    }

    /**
     * Profile Completeness Accessor
     * WKR-010: Enhanced Profile Marketing
     *
     * @return array
     */
    public function getProfileCompletenessAttribute()
    {
        if ($this->user_type !== 'worker' || ! $this->workerProfile) {
            return [
                'score' => 0,
                'percentage' => 0,
                'sections' => [],
                'tips' => [],
            ];
        }

        $service = app(\App\Services\ProfileCompletionService::class);

        return $service->calculateCompletion($this);
    }

    // ==================== ACCOUNT LOCKOUT METHODS ====================

    /**
     * Maximum number of failed login attempts before lockout.
     */
    const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Duration of account lockout in minutes.
     */
    const LOCKOUT_DURATION_MINUTES = 30;

    /**
     * Check if user account is currently locked.
     */
    public function isLocked(): bool
    {
        if (! $this->locked_until) {
            return false;
        }

        // Check if lock has expired
        if (Carbon::now()->gte($this->locked_until)) {
            // Auto-unlock expired locks
            $this->unlock();

            return false;
        }

        return true;
    }

    /**
     * Get the number of minutes remaining until account unlocks.
     */
    public function lockoutMinutesRemaining(): ?int
    {
        if (! $this->isLocked()) {
            return null;
        }

        return Carbon::now()->diffInMinutes($this->locked_until, false);
    }

    /**
     * Lock the user account due to failed login attempts.
     */
    public function lockAccount(string $reason = 'Too many failed login attempts', ?int $durationMinutes = null): void
    {
        $duration = $durationMinutes ?? self::LOCKOUT_DURATION_MINUTES;

        $this->update([
            'locked_until' => Carbon::now()->addMinutes($duration),
            'lock_reason' => $reason,
            'locked_at' => Carbon::now(),
        ]);
    }

    /**
     * Lock the user account by an admin (manual lock).
     */
    public function lockByAdmin(int $adminId, string $reason, ?int $durationMinutes = null): void
    {
        $lockedUntil = $durationMinutes
            ? Carbon::now()->addMinutes($durationMinutes)
            : null; // Indefinite lock if no duration specified

        $this->update([
            'locked_until' => $lockedUntil,
            'lock_reason' => $reason,
            'locked_at' => Carbon::now(),
            'locked_by_admin_id' => $adminId,
        ]);
    }

    /**
     * Unlock the user account.
     */
    public function unlock(): void
    {
        $this->update([
            'locked_until' => null,
            'lock_reason' => null,
            'locked_at' => null,
            'locked_by_admin_id' => null,
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Increment failed login attempts and lock if threshold reached.
     *
     * @return bool True if account was locked, false otherwise
     */
    public function incrementFailedLoginAttempts(): bool
    {
        $this->update([
            'failed_login_attempts' => $this->failed_login_attempts + 1,
            'last_failed_login_at' => Carbon::now(),
        ]);

        // Refresh the model to get updated value
        $this->refresh();

        // Check if we should lock the account
        if ($this->failed_login_attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $this->lockAccount();

            return true;
        }

        return false;
    }

    /**
     * Reset failed login attempts (called on successful login).
     */
    public function resetFailedLoginAttempts(): void
    {
        if ($this->failed_login_attempts > 0) {
            $this->update([
                'failed_login_attempts' => 0,
                'last_failed_login_at' => null,
            ]);
        }
    }

    /**
     * Get the admin who locked this account (if applicable).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lockedByAdmin()
    {
        return $this->belongsTo(User::class, 'locked_by_admin_id');
    }

    /**
     * Check if account was locked by an admin (vs auto-locked).
     */
    public function wasLockedByAdmin(): bool
    {
        return $this->locked_by_admin_id !== null;
    }

    /**
     * Get remaining failed login attempts before lockout.
     */
    public function remainingLoginAttempts(): int
    {
        return max(0, self::MAX_LOGIN_ATTEMPTS - $this->failed_login_attempts);
    }

    /**
     * Scope for locked accounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLocked($query)
    {
        return $query->whereNotNull('locked_until')
            ->where('locked_until', '>', Carbon::now());
    }

    /**
     * Scope for accounts with failed login attempts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFailedAttempts($query, int $minAttempts = 1)
    {
        return $query->where('failed_login_attempts', '>=', $minAttempts);
    }

    // ==================== SUSPENSION METHODS ====================

    /**
     * Check if user is currently suspended
     *
     * @return bool
     */
    public function isSuspended()
    {
        if ($this->status !== 'suspended') {
            return false;
        }

        if (! $this->suspended_until) {
            return true; // Indefinite suspension
        }

        return Carbon::now()->lt($this->suspended_until);
    }

    /**
     * Suspend the user
     *
     * @return void
     */
    public function suspend(int $days, string $reason)
    {
        $this->update([
            'status' => 'suspended',
            'suspended_until' => Carbon::now()->addDays($days),
            'suspension_reason' => $reason,
            'suspension_count' => $this->suspension_count + 1,
            'last_suspended_at' => Carbon::now(),
        ]);
    }

    /**
     * Reinstate the user from suspension
     *
     * @return void
     */
    public function reinstate()
    {
        $this->update([
            'status' => 'active',
            'suspended_until' => null,
            'suspension_reason' => null,
        ]);
    }

    /**
     * Get days remaining in suspension
     *
     * @return int|null
     */
    public function suspensionDaysRemaining()
    {
        if (! $this->isSuspended() || ! $this->suspended_until) {
            return null;
        }

        return Carbon::now()->diffInDays($this->suspended_until, false);
    }

    // ==================== RELIABILITY SCORE METHODS ====================

    /**
     * Reliability score history relationship
     */
    public function reliabilityScoreHistory()
    {
        return $this->hasMany(ReliabilityScoreHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get current reliability score (latest from history or default)
     *
     * @return float
     */
    public function getReliabilityScoreAttribute()
    {
        // If we have a cached score in the user table, use it
        if (isset($this->attributes['reliability_score']) && $this->attributes['reliability_score'] > 0) {
            return (float) $this->attributes['reliability_score'];
        }

        // Otherwise get the latest from history
        $latestScore = $this->reliabilityScoreHistory()->first();

        if ($latestScore) {
            return (float) $latestScore->score;
        }

        // Default score for new workers
        return 70.0;
    }

    /**
     * Get reliability grade (A-F)
     *
     * @return string
     */
    public function getReliabilityGrade()
    {
        $score = $this->reliability_score;

        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F'
        };
    }

    /**
     * Update cached reliability score
     *
     * @return void
     */
    public function updateReliabilityScore(float $score)
    {
        $this->update(['reliability_score' => $score]);
    }

    /**
     * ADM-002: Disputes assigned to this admin.
     * Used for workload balancing in dispute assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignedDisputes()
    {
        return $this->hasMany(AdminDisputeQueue::class, 'assigned_to_admin');
    }

    /**
     * ADM-002: Disputes filed by this user (as worker).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function disputesAsWorker()
    {
        return $this->hasMany(AdminDisputeQueue::class, 'worker_id');
    }

    /**
     * ADM-002: Disputes filed by this user (as business).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function disputesAsBusiness()
    {
        return $this->hasMany(AdminDisputeQueue::class, 'business_id');
    }

    // ==================== TWO-FACTOR AUTHENTICATION METHODS ====================

    /**
     * Check if two-factor authentication is enabled for this user.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Check if two-factor authentication has been confirmed (setup complete).
     */
    public function hasConfirmedTwoFactor(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * Get the number of remaining recovery codes.
     *
     * @return int
     */
    public function recoveryCodes(): array
    {
        return $this->two_factor_recovery_codes ?? [];
    }

    /**
     * Get the count of remaining recovery codes.
     */
    public function recoveryCodesCount(): int
    {
        return count($this->recoveryCodes());
    }

    /**
     * Replace the current recovery codes with new ones.
     */
    public function replaceRecoveryCodes(array $codes): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => $codes,
        ])->save();
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enableTwoFactorAuth(string $secret): void
    {
        $this->forceFill([
            'two_factor_secret' => $secret,
        ])->save();
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactorAuth(): void
    {
        $this->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disableTwoFactorAuth(): void
    {
        $this->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Use a recovery code (removes it from the list).
     */
    public function useRecoveryCode(string $code): bool
    {
        $codes = $this->recoveryCodes();
        $index = array_search($code, $codes);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);
        $this->replaceRecoveryCodes(array_values($codes));

        return true;
    }

    /**
     * Check if the provided code is a valid recovery code.
     */
    public function isValidRecoveryCode(string $code): bool
    {
        return in_array($code, $this->recoveryCodes());
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin' || $this->user_type === 'admin';
    }

    // ==================== GLO-003: LABOR LAW COMPLIANCE ====================

    /**
     * GLO-003: Compliance Violations for this user.
     */
    public function complianceViolations()
    {
        return $this->hasMany(ComplianceViolation::class);
    }

    /**
     * GLO-003: Worker Exemptions (opt-outs) for this user.
     */
    public function workerExemptions()
    {
        return $this->hasMany(WorkerExemption::class);
    }

    /**
     * GLO-003: Active Worker Exemptions.
     */
    public function activeExemptions()
    {
        return $this->workerExemptions()->active();
    }

    // ==================== GLO-002: TAX JURISDICTION ENGINE ====================

    /**
     * GLO-002: Tax Forms submitted by this user.
     */
    public function taxForms()
    {
        return $this->hasMany(TaxForm::class);
    }

    /**
     * GLO-002: Valid/active tax forms for this user.
     */
    public function validTaxForms()
    {
        return $this->taxForms()
            ->where('status', TaxForm::STATUS_VERIFIED)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * GLO-002: Tax calculations for this user.
     */
    public function taxCalculations()
    {
        return $this->hasMany(TaxCalculation::class);
    }

    /**
     * GLO-002: Check if user has a valid W-9 form.
     */
    public function hasValidW9(): bool
    {
        return $this->validTaxForms()
            ->where('form_type', TaxForm::TYPE_W9)
            ->exists();
    }

    /**
     * GLO-002: Check if user has a valid W-8BEN form.
     */
    public function hasValidW8BEN(): bool
    {
        return $this->validTaxForms()
            ->where('form_type', TaxForm::TYPE_W8BEN)
            ->exists();
    }

    /**
     * GLO-002: Check if user has any required tax forms missing.
     */
    public function hasMissingTaxForms(): bool
    {
        $taxService = app(\App\Services\TaxJurisdictionService::class);
        $requiredForms = $taxService->getRequiredForms($this);

        foreach ($requiredForms as $form) {
            if ($form['required'] && ! $form['submitted']) {
                return true;
            }
        }

        return false;
    }

    /**
     * GLO-002: Get the user's effective tax rate based on their jurisdiction.
     */
    public function getEffectiveTaxRate(): float
    {
        $taxService = app(\App\Services\TaxJurisdictionService::class);
        $jurisdiction = $taxService->getJurisdiction(
            $this->getCountry() ?? 'US',
            $this->getRegion()
        );

        if (! $jurisdiction) {
            return 0;
        }

        return $taxService->getEffectiveTaxRate($this, $jurisdiction);
    }

    /**
     * GLO-002: Get the user's tax jurisdiction based on their profile.
     */
    public function getTaxJurisdiction(): ?TaxJurisdiction
    {
        $taxService = app(\App\Services\TaxJurisdictionService::class);

        return $taxService->getJurisdiction(
            $this->getCountry() ?? 'US',
            $this->getRegion()
        );
    }

    /**
     * GLO-002: Get annual tax summary for this user.
     */
    public function getTaxSummary(int $year): array
    {
        $taxService = app(\App\Services\TaxJurisdictionService::class);

        return $taxService->generateTaxSummary($this, $year);
    }

    // ==================== BIZ-005: ROSTER MANAGEMENT ====================

    /**
     * BIZ-005: Rosters owned by this business.
     */
    public function businessRosters()
    {
        return $this->hasMany(BusinessRoster::class, 'business_id');
    }

    /**
     * BIZ-005: Roster memberships for this worker.
     */
    public function rosterMemberships()
    {
        return $this->hasMany(RosterMember::class, 'worker_id');
    }

    /**
     * BIZ-005: Active roster memberships for this worker.
     */
    public function activeRosterMemberships()
    {
        return $this->rosterMemberships()->where('status', 'active');
    }

    /**
     * BIZ-005: Roster invitations received by this worker.
     */
    public function rosterInvitations()
    {
        return $this->hasMany(RosterInvitation::class, 'worker_id');
    }

    /**
     * BIZ-005: Pending roster invitations for this worker.
     */
    public function pendingRosterInvitations()
    {
        return $this->rosterInvitations()
            ->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * BIZ-005: Check if this worker is on a business's roster.
     */
    public function isOnRoster(User|int $business, ?string $rosterType = null): bool
    {
        $businessId = $business instanceof User ? $business->id : $business;

        $query = $this->rosterMemberships()
            ->whereHas('roster', function ($q) use ($businessId, $rosterType) {
                $q->where('business_id', $businessId);
                if ($rosterType) {
                    $q->where('type', $rosterType);
                }
            })
            ->where('status', 'active');

        return $query->exists();
    }

    /**
     * BIZ-005: Check if this worker is blacklisted by a business.
     */
    public function isBlacklistedBy(User|int $business): bool
    {
        return $this->isOnRoster($business, BusinessRoster::TYPE_BLACKLIST);
    }

    /**
     * BIZ-005: Check if this worker is a preferred worker for a business.
     */
    public function isPreferredWorkerFor(User|int $business): bool
    {
        return $this->isOnRoster($business, BusinessRoster::TYPE_PREFERRED);
    }

    /**
     * BIZ-005: Get the custom rate for this worker from a business roster.
     */
    public function getCustomRateForBusiness(User|int $business): ?float
    {
        $businessId = $business instanceof User ? $business->id : $business;

        $membership = $this->rosterMemberships()
            ->whereHas('roster', function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->where('status', 'active')
            ->whereNotNull('custom_rate')
            ->first();

        return $membership?->custom_rate;
    }

    // ==================== COM-003: EMAIL PREFERENCES ====================

    /**
     * COM-003: Email Preferences for this user.
     */
    public function emailPreferences()
    {
        return $this->hasOne(EmailPreference::class);
    }

    /**
     * COM-003: Get or create email preferences for this user.
     */
    public function getOrCreateEmailPreferences(): EmailPreference
    {
        return EmailPreference::getOrCreateForUser($this);
    }

    /**
     * COM-003: Email Logs for this user.
     */
    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * COM-003: Check if user allows a specific email category.
     */
    public function allowsEmailCategory(string $category): bool
    {
        $preferences = $this->emailPreferences;

        if (! $preferences) {
            return true; // Default to allowing if no preferences set
        }

        return $preferences->allowsCategory($category);
    }

    // ==================== WKR-007: WORKER CAREER TIERS ====================

    /**
     * WKR-007: Worker Tier History
     */
    public function tierHistory()
    {
        return $this->hasMany(WorkerTierHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * WKR-007: Get current worker tier through profile.
     */
    public function getWorkerTierAttribute(): ?WorkerTier
    {
        if (! $this->isWorker()) {
            return null;
        }

        return $this->workerProfile?->workerTier;
    }

    /**
     * WKR-007: Get tier progress for this worker.
     */
    public function getTierProgressAttribute(): array
    {
        if (! $this->isWorker()) {
            return [];
        }

        return app(\App\Services\WorkerTierService::class)->getTierProgress($this);
    }

    /**
     * WKR-007: Check if worker has access to premium shifts.
     */
    public function hasPremiumShiftsAccess(): bool
    {
        $tier = $this->workerTier;

        return $tier && $tier->premium_shifts_access;
    }

    /**
     * WKR-007: Check if worker has instant payout access.
     */
    public function hasTierInstantPayout(): bool
    {
        $tier = $this->workerTier;

        return $tier && $tier->instant_payout;
    }

    /**
     * WKR-007: Get the worker's fee discount percentage based on tier.
     */
    public function getTierFeeDiscount(): float
    {
        $tier = $this->workerTier;

        return $tier ? $tier->fee_discount_percent : 0;
    }

    /**
     * WKR-007: Get the worker's priority booking hours based on tier.
     */
    public function getTierPriorityHours(): int
    {
        $tier = $this->workerTier;

        return $tier ? $tier->priority_booking_hours : 0;
    }
}
