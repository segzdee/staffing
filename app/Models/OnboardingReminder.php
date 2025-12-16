<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * OnboardingReminder Model
 *
 * Tracks scheduled and sent reminders for onboarding completion.
 *
 * @property int $id
 * @property int $user_id
 * @property string $reminder_type
 * @property string|null $step_id
 * @property \Illuminate\Support\Carbon $scheduled_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $opened_at
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property string $status
 * @property string $channel
 * @property string|null $subject
 * @property string|null $message
 * @property array|null $template_data
 * @property string|null $tracking_id
 * @property \Illuminate\Support\Carbon|null $user_responded_at
 * @property string|null $response_action
 * @property bool $is_suppressed
 * @property string|null $suppression_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OnboardingReminder extends Model
{
    use HasFactory;

    // Reminder type constants
    const TYPE_WELCOME = 'welcome';
    const TYPE_FIRST_STEP = 'first_step';
    const TYPE_INCOMPLETE_STEP = 'incomplete_step';
    const TYPE_INACTIVITY = 'inactivity';
    const TYPE_MILESTONE = 'milestone';
    const TYPE_COMPLETION_NUDGE = 'completion_nudge';
    const TYPE_CELEBRATION = 'celebration';
    const TYPE_SPECIAL_OFFER = 'special_offer';
    const TYPE_SUPPORT_OFFER = 'support_offer';

    // Channel constants
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_PUSH = 'push';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_IN_APP = 'in_app';

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_OPENED = 'opened';
    const STATUS_CLICKED = 'clicked';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'reminder_type',
        'step_id',
        'scheduled_at',
        'sent_at',
        'opened_at',
        'clicked_at',
        'status',
        'channel',
        'subject',
        'message',
        'template_data',
        'tracking_id',
        'user_responded_at',
        'response_action',
        'is_suppressed',
        'suppression_reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'user_responded_at' => 'datetime',
        'template_data' => 'array',
        'is_suppressed' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user this reminder belongs to
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the step (if step-specific reminder)
     */
    public function step()
    {
        return $this->belongsTo(OnboardingStep::class, 'step_id', 'step_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to scheduled reminders
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope to sent reminders
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope to pending (ready to send)
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->scheduled()
            ->where('scheduled_at', '<=', now())
            ->where('is_suppressed', false);
    }

    /**
     * Scope for a specific user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by reminder type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('reminder_type', $type);
    }

    /**
     * Scope by channel
     */
    public function scopeViaChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for a specific step
     */
    public function scopeForStep(Builder $query, string $stepId): Builder
    {
        return $query->where('step_id', $stepId);
    }

    /**
     * Scope to not suppressed
     */
    public function scopeNotSuppressed(Builder $query): Builder
    {
        return $query->where('is_suppressed', false);
    }

    /**
     * Scope by date range
     */
    public function scopeScheduledBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('scheduled_at', [$startDate, $endDate]);
    }

    // ==================== STATUS METHODS ====================

    /**
     * Check if reminder is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if reminder was sent
     */
    public function isSent(): bool
    {
        return in_array($this->status, [
            self::STATUS_SENT,
            self::STATUS_DELIVERED,
            self::STATUS_OPENED,
            self::STATUS_CLICKED,
        ]);
    }

    /**
     * Check if reminder was opened
     */
    public function wasOpened(): bool
    {
        return in_array($this->status, [self::STATUS_OPENED, self::STATUS_CLICKED]);
    }

    /**
     * Check if reminder was clicked
     */
    public function wasClicked(): bool
    {
        return $this->status === self::STATUS_CLICKED;
    }

    /**
     * Check if reminder is suppressed
     */
    public function isSuppressed(): bool
    {
        return $this->is_suppressed;
    }

    // ==================== ACTION METHODS ====================

    /**
     * Mark as sent
     */
    public function markAsSent(): self
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(): self
    {
        $this->update(['status' => self::STATUS_DELIVERED]);
        return $this;
    }

    /**
     * Mark as opened
     */
    public function markAsOpened(): self
    {
        $this->update([
            'status' => self::STATUS_OPENED,
            'opened_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as clicked
     */
    public function markAsClicked(): self
    {
        $this->update([
            'status' => self::STATUS_CLICKED,
            'clicked_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'suppression_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Cancel the reminder
     */
    public function cancel(?string $reason = null): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'suppression_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Suppress the reminder
     */
    public function suppress(string $reason): self
    {
        $this->update([
            'is_suppressed' => true,
            'suppression_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Record user response
     */
    public function recordResponse(string $action): self
    {
        $this->update([
            'user_responded_at' => now(),
            'response_action' => $action,
        ]);

        return $this;
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Schedule a reminder
     */
    public static function schedule(
        int $userId,
        string $type,
        \DateTimeInterface $scheduledAt,
        string $channel = self::CHANNEL_EMAIL,
        ?string $stepId = null,
        ?array $templateData = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'reminder_type' => $type,
            'step_id' => $stepId,
            'scheduled_at' => $scheduledAt,
            'status' => self::STATUS_SCHEDULED,
            'channel' => $channel,
            'template_data' => $templateData,
            'tracking_id' => static::generateTrackingId(),
        ]);
    }

    /**
     * Generate unique tracking ID
     */
    public static function generateTrackingId(): string
    {
        return 'onb_' . bin2hex(random_bytes(16));
    }

    /**
     * Get pending reminders ready to send
     */
    public static function getPendingReminders(int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return static::pending()
            ->with('user', 'step')
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Cancel all scheduled reminders for a user
     */
    public static function cancelAllForUser(int $userId, ?string $reason = null): int
    {
        return static::forUser($userId)
            ->scheduled()
            ->update([
                'status' => self::STATUS_CANCELLED,
                'suppression_reason' => $reason ?? 'User completed onboarding',
            ]);
    }

    /**
     * Check if user has pending reminder of type
     */
    public static function hasPendingReminderOfType(int $userId, string $type): bool
    {
        return static::forUser($userId)
            ->ofType($type)
            ->scheduled()
            ->exists();
    }

    /**
     * Get reminder statistics for a period
     */
    public static function getStatistics($startDate, $endDate): array
    {
        $base = static::scheduledBetween($startDate, $endDate);

        return [
            'total_scheduled' => (clone $base)->count(),
            'sent' => (clone $base)->whereNotNull('sent_at')->count(),
            'opened' => (clone $base)->whereNotNull('opened_at')->count(),
            'clicked' => (clone $base)->whereNotNull('clicked_at')->count(),
            'cancelled' => (clone $base)->where('status', self::STATUS_CANCELLED)->count(),
            'failed' => (clone $base)->where('status', self::STATUS_FAILED)->count(),
            'by_type' => (clone $base)
                ->selectRaw('reminder_type, COUNT(*) as count')
                ->groupBy('reminder_type')
                ->pluck('count', 'reminder_type')
                ->toArray(),
            'by_channel' => (clone $base)
                ->selectRaw('channel, COUNT(*) as count')
                ->groupBy('channel')
                ->pluck('count', 'channel')
                ->toArray(),
        ];
    }

    /**
     * Get reminder templates for a type
     */
    public static function getReminderContent(string $type, User $user, ?OnboardingStep $step = null): array
    {
        $templates = [
            self::TYPE_WELCOME => [
                'subject' => "Welcome to OvertimeStaff, {$user->first_name}!",
                'message' => "You're just a few steps away from getting started. Complete your profile to unlock all features.",
            ],
            self::TYPE_FIRST_STEP => [
                'subject' => "Let's get started, {$user->first_name}!",
                'message' => "You haven't started your onboarding yet. It only takes a few minutes to get set up.",
            ],
            self::TYPE_INCOMPLETE_STEP => [
                'subject' => "Almost there! Complete your " . ($step?->name ?? 'profile'),
                'message' => "You're making great progress! Complete your {$step?->name} to continue.",
            ],
            self::TYPE_INACTIVITY => [
                'subject' => "We miss you, {$user->first_name}!",
                'message' => "You haven't been active recently. Come back and finish setting up your account.",
            ],
            self::TYPE_MILESTONE => [
                'subject' => "Great progress, {$user->first_name}!",
                'message' => "You're doing great! Keep going to unlock all the benefits of OvertimeStaff.",
            ],
            self::TYPE_COMPLETION_NUDGE => [
                'subject' => "You're almost done!",
                'message' => "Just one more step to complete your setup. Finish now and start finding shifts!",
            ],
            self::TYPE_CELEBRATION => [
                'subject' => "Congratulations! You're all set up!",
                'message' => "Welcome to OvertimeStaff! Your account is now fully activated. Start exploring available shifts.",
            ],
            self::TYPE_SPECIAL_OFFER => [
                'subject' => "Special offer: Complete your profile today!",
                'message' => "Finish setting up your account today and get priority access to new shifts.",
            ],
            self::TYPE_SUPPORT_OFFER => [
                'subject' => "Need help with your setup?",
                'message' => "Having trouble? Our support team is here to help you get started.",
            ],
        ];

        return $templates[$type] ?? [
            'subject' => 'Continue your setup',
            'message' => 'Complete your OvertimeStaff setup to get started.',
        ];
    }
}
