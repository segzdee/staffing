<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BusinessOnboarding Model
 * BIZ-REG-002: Tracks business onboarding progress
 *
 * @property int $id
 * @property int $business_profile_id
 * @property int $user_id
 * @property int $current_step
 * @property int $total_steps
 * @property float $completion_percentage
 * @property string $status
 * @property array|null $steps_completed
 * @property float $profile_completion_score
 * @property array|null $missing_fields
 * @property array|null $optional_fields_completed
 * @property string $signup_source
 * @property string|null $referral_code
 * @property int|null $referred_by_business_id
 * @property string|null $sales_rep_id
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $email_domain
 * @property bool $email_verified
 * @property bool $profile_minimum_met
 * @property bool $terms_accepted
 * @property \Carbon\Carbon|null $terms_accepted_at
 * @property string|null $terms_version
 * @property bool $payment_method_added
 * @property bool $is_activated
 * @property \Carbon\Carbon|null $activated_at
 * @property \Carbon\Carbon|null $last_reminder_sent_at
 * @property int $reminders_sent_count
 * @property \Carbon\Carbon|null $next_reminder_at
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int|null $time_to_complete_minutes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read BusinessProfile $businessProfile
 * @property-read User $user
 * @property-read BusinessProfile|null $referredByBusiness
 */
class BusinessOnboarding extends Model
{
    use HasFactory;

    protected $table = 'business_onboarding';

    protected $fillable = [
        'business_profile_id',
        'user_id',
        'current_step',
        'total_steps',
        'completion_percentage',
        'status',
        'steps_completed',
        'profile_completion_score',
        'missing_fields',
        'optional_fields_completed',
        'signup_source',
        'referral_code',
        'referred_by_business_id',
        'sales_rep_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'email_domain',
        'email_verified',
        'profile_minimum_met',
        'terms_accepted',
        'terms_accepted_at',
        'terms_version',
        'payment_method_added',
        'is_activated',
        'activated_at',
        'last_reminder_sent_at',
        'reminders_sent_count',
        'next_reminder_at',
        'started_at',
        'completed_at',
        'time_to_complete_minutes',
    ];

    protected $casts = [
        'steps_completed' => 'array',
        'missing_fields' => 'array',
        'optional_fields_completed' => 'array',
        'completion_percentage' => 'decimal:2',
        'profile_completion_score' => 'decimal:2',
        'email_verified' => 'boolean',
        'profile_minimum_met' => 'boolean',
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'payment_method_added' => 'boolean',
        'is_activated' => 'boolean',
        'activated_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'next_reminder_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Signup source constants
     */
    const SOURCE_ORGANIC = 'organic';
    const SOURCE_REFERRAL = 'referral';
    const SOURCE_SALES_ASSISTED = 'sales_assisted';
    const SOURCE_PARTNERSHIP = 'partnership';
    const SOURCE_ADVERTISING = 'advertising';

    /**
     * Onboarding steps
     */
    const STEP_ACCOUNT_CREATED = 'account_created';
    const STEP_EMAIL_VERIFIED = 'email_verified';
    const STEP_COMPANY_INFO = 'company_info';
    const STEP_CONTACT_INFO = 'contact_info';
    const STEP_ADDRESS_INFO = 'address_info';
    const STEP_PAYMENT_SETUP = 'payment_setup';

    /**
     * Default steps structure
     */
    public static function getDefaultStepsStructure(): array
    {
        return [
            self::STEP_ACCOUNT_CREATED => ['completed' => false, 'completed_at' => null],
            self::STEP_EMAIL_VERIFIED => ['completed' => false, 'completed_at' => null],
            self::STEP_COMPANY_INFO => ['completed' => false, 'completed_at' => null],
            self::STEP_CONTACT_INFO => ['completed' => false, 'completed_at' => null],
            self::STEP_ADDRESS_INFO => ['completed' => false, 'completed_at' => null],
            self::STEP_PAYMENT_SETUP => ['completed' => false, 'completed_at' => null],
        ];
    }

    /**
     * Get the business profile.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the referring business.
     */
    public function referredByBusiness()
    {
        return $this->belongsTo(BusinessProfile::class, 'referred_by_business_id');
    }

    /**
     * Initialize onboarding for a new business.
     */
    public static function initializeForBusiness(BusinessProfile $businessProfile, User $user, array $signupData = []): self
    {
        return self::create([
            'business_profile_id' => $businessProfile->id,
            'user_id' => $user->id,
            'current_step' => 1,
            'total_steps' => 6,
            'completion_percentage' => 0,
            'status' => self::STATUS_IN_PROGRESS,
            'steps_completed' => self::getDefaultStepsStructure(),
            'signup_source' => $signupData['source'] ?? self::SOURCE_ORGANIC,
            'referral_code' => $signupData['referral_code'] ?? null,
            'sales_rep_id' => $signupData['sales_rep_id'] ?? null,
            'utm_source' => $signupData['utm_source'] ?? null,
            'utm_medium' => $signupData['utm_medium'] ?? null,
            'utm_campaign' => $signupData['utm_campaign'] ?? null,
            'email_domain' => $signupData['email_domain'] ?? null,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark a step as completed.
     */
    public function completeStep(string $stepName): void
    {
        $steps = $this->steps_completed ?? self::getDefaultStepsStructure();

        if (isset($steps[$stepName])) {
            $steps[$stepName] = [
                'completed' => true,
                'completed_at' => now()->toISOString(),
            ];

            $this->steps_completed = $steps;
            $this->recalculateProgress();
            $this->save();
        }
    }

    /**
     * Check if a step is completed.
     */
    public function isStepCompleted(string $stepName): bool
    {
        $steps = $this->steps_completed ?? [];
        return isset($steps[$stepName]) && $steps[$stepName]['completed'] === true;
    }

    /**
     * Get the next uncompleted step.
     */
    public function getNextStep(): ?string
    {
        $steps = $this->steps_completed ?? self::getDefaultStepsStructure();

        foreach ($steps as $stepName => $stepData) {
            if (!$stepData['completed']) {
                return $stepName;
            }
        }

        return null;
    }

    /**
     * Recalculate progress percentage.
     */
    public function recalculateProgress(): void
    {
        $steps = $this->steps_completed ?? [];
        $completedCount = 0;

        foreach ($steps as $stepData) {
            if ($stepData['completed']) {
                $completedCount++;
            }
        }

        $totalSteps = count($steps);
        $this->completion_percentage = $totalSteps > 0
            ? round(($completedCount / $totalSteps) * 100, 2)
            : 0;

        $this->current_step = min($completedCount + 1, $totalSteps);
    }

    /**
     * Check if onboarding is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if all required steps are complete.
     */
    public function areAllRequiredStepsComplete(): bool
    {
        $requiredSteps = [
            self::STEP_ACCOUNT_CREATED,
            self::STEP_EMAIL_VERIFIED,
            self::STEP_COMPANY_INFO,
        ];

        foreach ($requiredSteps as $step) {
            if (!$this->isStepCompleted($step)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark onboarding as complete.
     */
    public function markComplete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'time_to_complete_minutes' => $this->started_at
                ? now()->diffInMinutes($this->started_at)
                : null,
        ]);
    }

    /**
     * Activate the business account.
     */
    public function activate(): void
    {
        $this->update([
            'is_activated' => true,
            'activated_at' => now(),
        ]);
    }

    /**
     * Check if can be activated.
     */
    public function canBeActivated(): bool
    {
        return $this->email_verified
            && $this->profile_minimum_met
            && $this->terms_accepted
            && !$this->is_activated;
    }

    /**
     * Accept terms of service.
     */
    public function acceptTerms(string $version = '1.0'): void
    {
        $this->update([
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'terms_version' => $version,
        ]);
    }

    /**
     * Record a reminder sent.
     */
    public function recordReminderSent(): void
    {
        $this->update([
            'last_reminder_sent_at' => now(),
            'reminders_sent_count' => $this->reminders_sent_count + 1,
            'next_reminder_at' => now()->addDays(3),
        ]);
    }

    /**
     * Update profile completion score.
     */
    public function updateProfileCompletionScore(float $score, array $missingFields = []): void
    {
        $this->update([
            'profile_completion_score' => $score,
            'missing_fields' => $missingFields,
            'profile_minimum_met' => $score >= 80,
        ]);
    }

    /**
     * Scope to get incomplete onboarding.
     */
    public function scopeIncomplete($query)
    {
        return $query->where('status', '!=', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get activated businesses.
     */
    public function scopeActivated($query)
    {
        return $query->where('is_activated', true);
    }

    /**
     * Scope to get businesses needing reminders.
     */
    public function scopeNeedsReminder($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS)
            ->where('is_activated', false)
            ->where(function ($q) {
                $q->whereNull('next_reminder_at')
                    ->orWhere('next_reminder_at', '<=', now());
            })
            ->where('reminders_sent_count', '<', 5);
    }

    /**
     * Scope to filter by signup source.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('signup_source', $source);
    }

    /**
     * Scope to filter by email domain.
     */
    public function scopeByEmailDomain($query, string $domain)
    {
        return $query->where('email_domain', $domain);
    }
}
