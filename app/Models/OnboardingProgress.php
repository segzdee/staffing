<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * OnboardingProgress Model
 *
 * Tracks each user's progress through individual onboarding steps.
 *
 * @property int $id
 * @property int $user_id
 * @property int $onboarding_step_id
 * @property string $status
 * @property int $progress_percentage
 * @property array|null $progress_data
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int $time_spent_seconds
 * @property int $attempt_count
 * @property string|null $completed_by
 * @property string|null $completion_notes
 * @property \Illuminate\Support\Carbon|null $skipped_at
 * @property string|null $skip_reason
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon|null $last_reminder_at
 * @property int $reminder_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OnboardingProgress extends Model
{
    use HasFactory;

    protected $table = 'onboarding_progress';

    protected $fillable = [
        'user_id',
        'onboarding_step_id',
        'status',
        'progress_percentage',
        'progress_data',
        'started_at',
        'completed_at',
        'time_spent_seconds',
        'attempt_count',
        'completed_by',
        'completion_notes',
        'skipped_at',
        'skip_reason',
        'failed_at',
        'failure_reason',
        'last_reminder_at',
        'reminder_count',
    ];

    protected $casts = [
        'progress_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'skipped_at' => 'datetime',
        'failed_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'progress_percentage' => 'integer',
        'time_spent_seconds' => 'integer',
        'attempt_count' => 'integer',
        'reminder_count' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user this progress belongs to
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the step configuration
     */
    public function step()
    {
        return $this->belongsTo(OnboardingStep::class, 'onboarding_step_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to pending progress
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to in progress
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to completed
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to failed
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to skipped
     */
    public function scopeSkipped(Builder $query): Builder
    {
        return $query->where('status', 'skipped');
    }

    /**
     * Scope to incomplete (pending or in_progress)
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    /**
     * Scope to finished (completed, failed, or skipped)
     */
    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereIn('status', ['completed', 'failed', 'skipped']);
    }

    /**
     * Scope for a specific user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope that needs reminders
     */
    public function scopeNeedsReminder(Builder $query, int $daysSinceLastReminder = 2): Builder
    {
        return $query->incomplete()
            ->where(function ($q) use ($daysSinceLastReminder) {
                $q->whereNull('last_reminder_at')
                  ->orWhere('last_reminder_at', '<', now()->subDays($daysSinceLastReminder));
            });
    }

    // ==================== STATUS METHODS ====================

    /**
     * Check if step is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if step is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if step is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if step is skipped
     */
    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    /**
     * Check if step is failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if step is incomplete
     */
    public function isIncomplete(): bool
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    /**
     * Check if step is finished (any terminal state)
     */
    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'skipped']);
    }

    // ==================== ACTION METHODS ====================

    /**
     * Start this step
     */
    public function start(): self
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => $this->started_at ?? now(),
            'attempt_count' => $this->attempt_count + 1,
        ]);

        return $this;
    }

    /**
     * Complete this step
     */
    public function complete(string $completedBy = 'user', ?string $notes = null): self
    {
        $timeSpent = $this->started_at
            ? now()->diffInSeconds($this->started_at)
            : 0;

        $this->update([
            'status' => 'completed',
            'progress_percentage' => 100,
            'completed_at' => now(),
            'completed_by' => $completedBy,
            'completion_notes' => $notes,
            'time_spent_seconds' => $this->time_spent_seconds + $timeSpent,
        ]);

        return $this;
    }

    /**
     * Skip this step
     */
    public function skip(?string $reason = null): self
    {
        $this->update([
            'status' => 'skipped',
            'skipped_at' => now(),
            'skip_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Mark step as failed
     */
    public function fail(?string $reason = null): self
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Reset step to pending
     */
    public function reset(): self
    {
        $this->update([
            'status' => 'pending',
            'progress_percentage' => 0,
            'progress_data' => null,
            'completed_at' => null,
            'completed_by' => null,
            'completion_notes' => null,
            'skipped_at' => null,
            'skip_reason' => null,
            'failed_at' => null,
            'failure_reason' => null,
        ]);

        return $this;
    }

    /**
     * Update progress percentage
     */
    public function updateProgress(int $percentage, ?array $data = null): self
    {
        $this->update([
            'progress_percentage' => min(100, max(0, $percentage)),
            'progress_data' => $data ?? $this->progress_data,
            'status' => $percentage >= 100 ? 'completed' : ($this->status === 'pending' ? 'in_progress' : $this->status),
            'started_at' => $this->started_at ?? now(),
        ]);

        if ($percentage >= 100 && !$this->completed_at) {
            $this->complete('auto');
        }

        return $this;
    }

    /**
     * Record a reminder was sent
     */
    public function recordReminderSent(): self
    {
        $this->update([
            'last_reminder_at' => now(),
            'reminder_count' => $this->reminder_count + 1,
        ]);

        return $this;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get time spent as a formatted string
     */
    public function getTimeSpentString(): string
    {
        $seconds = $this->time_spent_seconds;

        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0
                ? "{$minutes}m {$remainingSeconds}s"
                : "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0
            ? "{$hours}h {$remainingMinutes}m"
            : "{$hours} hours";
    }

    /**
     * Get step ID from related step
     */
    public function getStepId(): ?string
    {
        return $this->step?->step_id;
    }

    /**
     * Get days since started
     */
    public function getDaysSinceStarted(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        return $this->started_at->diffInDays(now());
    }

    /**
     * Get days since last activity
     */
    public function getDaysSinceLastActivity(): int
    {
        $lastActivity = $this->updated_at ?? $this->created_at;
        return $lastActivity->diffInDays(now());
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Get or create progress for a user and step
     */
    public static function getOrCreate(int $userId, int $stepId): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'onboarding_step_id' => $stepId,
            ],
            [
                'status' => 'pending',
                'progress_percentage' => 0,
            ]
        );
    }

    /**
     * Get all progress for a user
     */
    public static function getForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::forUser($userId)
            ->with('step')
            ->get();
    }

    /**
     * Get completion stats for a user
     */
    public static function getStatsForUser(int $userId): array
    {
        $progress = static::forUser($userId)->with('step')->get();

        $total = $progress->count();
        $completed = $progress->where('status', 'completed')->count();
        $inProgress = $progress->where('status', 'in_progress')->count();
        $skipped = $progress->where('status', 'skipped')->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'pending' => $total - $completed - $inProgress - $skipped,
            'skipped' => $skipped,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }
}
