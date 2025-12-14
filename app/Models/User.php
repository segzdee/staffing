<?php

namespace App\Models;

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
use Carbon\Carbon;

class User extends Authenticatable implements HasLocalePreference
{
    use Notifiable, Billable;

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
        // Dev account fields
        'is_dev_account',
        'dev_expires_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
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

      public function isAiAgent()
      {
          return $this->user_type === 'ai_agent';
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

          return match($this->user_type) {
              'worker' => route('worker.dashboard'),
              'business' => route('business.dashboard'),
              'agency' => route('agency.dashboard'),
              'ai_agent' => route('dashboard'),
              default => route('dashboard'),
          };
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

      public function aiAgentProfile()
      {
          return $this->hasOne(AiAgentProfile::class);
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
              case 'ai_agent':
                  return $this->aiAgentProfile;
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
}
