<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * OnboardingEvent Model
 *
 * Audit trail for all onboarding-related events.
 * Used for analytics, debugging, and funnel analysis.
 *
 * @property int $id
 * @property int $user_id
 * @property string $event_type
 * @property string|null $step_id
 * @property array|null $metadata
 * @property string|null $source
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $duration_seconds
 * @property string|null $session_id
 * @property string|null $cohort_id
 * @property string|null $cohort_variant
 * @property int|null $related_event_id
 * @property \Illuminate\Support\Carbon $created_at
 */
class OnboardingEvent extends Model
{
    use HasFactory;

    // Event type constants
    const EVENT_ONBOARDING_STARTED = 'onboarding_started';
    const EVENT_ONBOARDING_COMPLETED = 'onboarding_completed';
    const EVENT_ONBOARDING_ABANDONED = 'onboarding_abandoned';
    const EVENT_STEP_VIEWED = 'step_viewed';
    const EVENT_STEP_STARTED = 'step_started';
    const EVENT_STEP_COMPLETED = 'step_completed';
    const EVENT_STEP_FAILED = 'step_failed';
    const EVENT_STEP_SKIPPED = 'step_skipped';
    const EVENT_STEP_RETRIED = 'step_retried';
    const EVENT_HELP_VIEWED = 'help_viewed';
    const EVENT_SUPPORT_CONTACTED = 'support_contacted';
    const EVENT_REMINDER_SENT = 'reminder_sent';
    const EVENT_REMINDER_CLICKED = 'reminder_clicked';
    const EVENT_DASHBOARD_VIEWED = 'dashboard_viewed';
    const EVENT_PROGRESS_UPDATED = 'progress_updated';
    const EVENT_COHORT_ASSIGNED = 'cohort_assigned';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event_type',
        'step_id',
        'metadata',
        'source',
        'ip_address',
        'user_agent',
        'duration_seconds',
        'session_id',
        'cohort_id',
        'cohort_variant',
        'related_event_id',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'duration_seconds' => 'integer',
        'created_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user this event belongs to
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related event
     */
    public function relatedEvent()
    {
        return $this->belongsTo(OnboardingEvent::class, 'related_event_id');
    }

    /**
     * Get cohort information
     */
    public function cohort()
    {
        return $this->belongsTo(OnboardingCohort::class, 'cohort_id', 'cohort_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope by event type
     */
    public function scopeOfType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope by multiple event types
     */
    public function scopeOfTypes(Builder $query, array $eventTypes): Builder
    {
        return $query->whereIn('event_type', $eventTypes);
    }

    /**
     * Scope for a specific user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific step
     */
    public function scopeForStep(Builder $query, string $stepId): Builder
    {
        return $query->where('step_id', $stepId);
    }

    /**
     * Scope for a specific session
     */
    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for a specific cohort
     */
    public function scopeForCohort(Builder $query, string $cohortId): Builder
    {
        return $query->where('cohort_id', $cohortId);
    }

    /**
     * Scope by date range
     */
    public function scopeInDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for today
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for last N days
     */
    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope by source
     */
    public function scopeFromSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Log an onboarding event
     */
    public static function log(
        int $userId,
        string $eventType,
        ?string $stepId = null,
        ?array $metadata = null,
        ?int $durationSeconds = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'step_id' => $stepId,
            'metadata' => $metadata,
            'source' => static::detectSource(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'duration_seconds' => $durationSeconds,
            'session_id' => session()->getId(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log event with cohort information
     */
    public static function logWithCohort(
        int $userId,
        string $eventType,
        ?string $stepId = null,
        ?array $metadata = null,
        ?string $cohortId = null,
        ?string $cohortVariant = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'step_id' => $stepId,
            'metadata' => $metadata,
            'source' => static::detectSource(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'cohort_id' => $cohortId,
            'cohort_variant' => $cohortVariant,
            'created_at' => now(),
        ]);
    }

    /**
     * Detect the source of the request
     */
    protected static function detectSource(): string
    {
        $userAgent = strtolower(request()->userAgent() ?? '');

        if (request()->is('api/*')) {
            return 'api';
        }

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') || str_contains($userAgent, 'iphone')) {
            return 'mobile';
        }

        return 'web';
    }

    /**
     * Get event counts by type for a period
     */
    public static function getEventCounts($startDate, $endDate): array
    {
        return static::inDateRange($startDate, $endDate)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();
    }

    /**
     * Get step completion events grouped by step
     */
    public static function getStepCompletions($startDate, $endDate): array
    {
        return static::inDateRange($startDate, $endDate)
            ->ofType(self::EVENT_STEP_COMPLETED)
            ->selectRaw('step_id, COUNT(*) as completions')
            ->groupBy('step_id')
            ->pluck('completions', 'step_id')
            ->toArray();
    }

    /**
     * Get user funnel events
     */
    public static function getUserFunnel(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::forUser($userId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get session events
     */
    public static function getSessionEvents(string $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return static::forSession($sessionId)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Calculate average time between events
     */
    public static function getAverageTimeBetweenEvents(string $fromEvent, string $toEvent, $startDate, $endDate): ?float
    {
        $fromEvents = static::ofType($fromEvent)
            ->inDateRange($startDate, $endDate)
            ->pluck('created_at', 'user_id');

        $toEvents = static::ofType($toEvent)
            ->inDateRange($startDate, $endDate)
            ->pluck('created_at', 'user_id');

        $times = [];
        foreach ($fromEvents as $userId => $fromTime) {
            if (isset($toEvents[$userId]) && $toEvents[$userId] > $fromTime) {
                $times[] = $toEvents[$userId]->diffInSeconds($fromTime);
            }
        }

        return count($times) > 0 ? array_sum($times) / count($times) : null;
    }
}
