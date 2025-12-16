<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Laravel\Cashier\Billable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Contracts\Translation\HasLocalePreference;
use App\Models\Notifications;
use App\Models\AgencyClient;
use App\Traits\CachesUserProfile;
use Carbon\Carbon;

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
 * @mixin \Eloquent
 */
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    use HasFactory, Notifiable, Billable, \Laravel\Sanctum\HasApiTokens, CachesUserProfile;

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
      if (!class_exists('App\Models\TaxRates') || !\Schema::hasTable('tax_rates')) {
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

  	public function country()
    {
          // Return null if Countries model/table doesn't exist
          try {
              if (class_exists('App\Models\Countries') && Schema::hasTable('countries')) {
                  return $this->belongsTo(Countries::class, 'countries_id')->first();
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

      function getFirstNameAttribute()
      {
        $name = explode(' ', $this->name);
        return $name[0] ?? null;
      }

      function getLastNameAttribute()
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
          $routeName = $this->user_type . '.profile';

          // Check if the route exists, otherwise fallback to dashboard or home
          if (\Route::has($routeName)) {
              return route($routeName);
          }

          // Fallback to dashboard
          $dashboardRoute = $this->user_type . '.dashboard';
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
          return !empty($this->stripe_connect_id) && $this->completed_stripe_onboarding;
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
          if (!auth()->check()) {
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
          if ($this->user_type !== 'worker' || !$this->workerProfile) {
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
       *
       * @return bool
       */
      public function isLocked(): bool
      {
          if (!$this->locked_until) {
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
       *
       * @return int|null
       */
      public function lockoutMinutesRemaining(): ?int
      {
          if (!$this->isLocked()) {
              return null;
          }

          return Carbon::now()->diffInMinutes($this->locked_until, false);
      }

      /**
       * Lock the user account due to failed login attempts.
       *
       * @param string $reason
       * @param int|null $durationMinutes
       * @return void
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
       *
       * @param int $adminId
       * @param string $reason
       * @param int|null $durationMinutes
       * @return void
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
       *
       * @return void
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
       *
       * @return void
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
       *
       * @return bool
       */
      public function wasLockedByAdmin(): bool
      {
          return $this->locked_by_admin_id !== null;
      }

      /**
       * Get remaining failed login attempts before lockout.
       *
       * @return int
       */
      public function remainingLoginAttempts(): int
      {
          return max(0, self::MAX_LOGIN_ATTEMPTS - $this->failed_login_attempts);
      }

      /**
       * Scope for locked accounts.
       *
       * @param \Illuminate\Database\Eloquent\Builder $query
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
       * @param \Illuminate\Database\Eloquent\Builder $query
       * @param int $minAttempts
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

          if (!$this->suspended_until) {
              return true; // Indefinite suspension
          }

          return Carbon::now()->lt($this->suspended_until);
      }

      /**
       * Suspend the user
       *
       * @param int $days
       * @param string $reason
       * @return void
       */
      public function suspend(int $days, string $reason)
      {
          $this->update([
              'status' => 'suspended',
              'suspended_until' => Carbon::now()->addDays($days),
              'suspension_reason' => $reason,
              'suspension_count' => $this->suspension_count + 1,
              'last_suspended_at' => Carbon::now()
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
              'suspension_reason' => null
          ]);
      }

      /**
       * Get days remaining in suspension
       *
       * @return int|null
       */
      public function suspensionDaysRemaining()
      {
          if (!$this->isSuspended() || !$this->suspended_until) {
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
       * @param float $score
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
       *
       * @return bool
       */
      public function hasTwoFactorEnabled(): bool
      {
          return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
      }

      /**
       * Check if two-factor authentication has been confirmed (setup complete).
       *
       * @return bool
       */
      public function hasConfirmedTwoFactor(): bool
      {
          return !is_null($this->two_factor_confirmed_at);
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
       *
       * @return int
       */
      public function recoveryCodesCount(): int
      {
          return count($this->recoveryCodes());
      }

      /**
       * Replace the current recovery codes with new ones.
       *
       * @param array $codes
       * @return void
       */
      public function replaceRecoveryCodes(array $codes): void
      {
          $this->forceFill([
              'two_factor_recovery_codes' => $codes,
          ])->save();
      }

      /**
       * Enable two-factor authentication for the user.
       *
       * @param string $secret
       * @return void
       */
      public function enableTwoFactorAuth(string $secret): void
      {
          $this->forceFill([
              'two_factor_secret' => $secret,
          ])->save();
      }

      /**
       * Confirm two-factor authentication for the user.
       *
       * @return void
       */
      public function confirmTwoFactorAuth(): void
      {
          $this->forceFill([
              'two_factor_confirmed_at' => now(),
          ])->save();
      }

      /**
       * Disable two-factor authentication for the user.
       *
       * @return void
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
       *
       * @param string $code
       * @return bool
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
       *
       * @param string $code
       * @return bool
       */
      public function isValidRecoveryCode(string $code): bool
      {
          return in_array($code, $this->recoveryCodes());
      }
}
