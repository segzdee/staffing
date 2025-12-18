<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * WKR-009: Worker Suspension Model
 *
 * Represents a suspension record for a worker, including type, reason,
 * duration, and appeal status.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $reason_category
 * @property string $reason_details
 * @property int|null $related_shift_id
 * @property int $issued_by
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property string $status
 * @property bool $affects_booking
 * @property bool $affects_visibility
 * @property int $strike_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WorkerSuspension extends Model
{
    use HasFactory;

    // Suspension Types
    public const TYPE_WARNING = 'warning';

    public const TYPE_TEMPORARY = 'temporary';

    public const TYPE_INDEFINITE = 'indefinite';

    public const TYPE_PERMANENT = 'permanent';

    // Reason Categories
    public const REASON_NO_SHOW = 'no_show';

    public const REASON_LATE_CANCELLATION = 'late_cancellation';

    public const REASON_MISCONDUCT = 'misconduct';

    public const REASON_POLICY_VIOLATION = 'policy_violation';

    public const REASON_FRAUD = 'fraud';

    public const REASON_SAFETY = 'safety';

    public const REASON_OTHER = 'other';

    // Status Values
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_APPEALED = 'appealed';

    public const STATUS_OVERTURNED = 'overturned';

    public const STATUS_ESCALATED = 'escalated';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'reason_category',
        'reason_details',
        'related_shift_id',
        'issued_by',
        'starts_at',
        'ends_at',
        'status',
        'affects_booking',
        'affects_visibility',
        'strike_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'affects_booking' => 'boolean',
        'affects_visibility' => 'boolean',
        'strike_count' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the worker (user) this suspension belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() - get the suspended worker.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin who issued this suspension.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the related shift (if applicable).
     */
    public function relatedShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'related_shift_id');
    }

    /**
     * Get all appeals for this suspension.
     */
    public function appeals(): HasMany
    {
        return $this->hasMany(SuspensionAppeal::class, 'suspension_id');
    }

    /**
     * Get the latest appeal for this suspension.
     */
    public function latestAppeal(): HasOne
    {
        return $this->hasOne(SuspensionAppeal::class, 'suspension_id')->latestOfMany();
    }

    /**
     * Get pending appeal for this suspension.
     */
    public function pendingAppeal(): HasOne
    {
        return $this->hasOne(SuspensionAppeal::class, 'suspension_id')
            ->whereIn('status', [SuspensionAppeal::STATUS_PENDING, SuspensionAppeal::STATUS_UNDER_REVIEW]);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to active suspensions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to currently effective suspensions (started and not yet ended).
     */
    public function scopeCurrentlyEffective($query)
    {
        return $query->active()
            ->where('starts_at', '<=', Carbon::now())
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', Carbon::now());
            });
    }

    /**
     * Scope to expired suspensions (ends_at has passed but status still active).
     */
    public function scopeExpired($query)
    {
        return $query->active()
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', Carbon::now());
    }

    /**
     * Scope to suspensions by reason category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('reason_category', $category);
    }

    /**
     * Scope to suspensions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to suspensions that affect booking.
     */
    public function scopeAffectsBooking($query)
    {
        return $query->where('affects_booking', true);
    }

    /**
     * Scope to suspensions for a specific worker.
     */
    public function scopeForWorker($query, User|int $worker)
    {
        $workerId = $worker instanceof User ? $worker->id : $worker;

        return $query->where('user_id', $workerId);
    }

    /**
     * Scope to suspensions with pending appeals.
     */
    public function scopeWithPendingAppeal($query)
    {
        return $query->whereHas('appeals', function ($q) {
            $q->whereIn('status', [SuspensionAppeal::STATUS_PENDING, SuspensionAppeal::STATUS_UNDER_REVIEW]);
        });
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if the suspension is currently active and effective.
     */
    public function isCurrentlyActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at > $now) {
            return false;
        }

        if ($this->ends_at && $this->ends_at <= $now) {
            return false;
        }

        return true;
    }

    /**
     * Check if the suspension has expired (end time passed).
     */
    public function hasExpired(): bool
    {
        if (! $this->ends_at) {
            return false; // Indefinite/permanent suspensions don't expire
        }

        return $this->ends_at <= Carbon::now();
    }

    /**
     * Check if this suspension can be appealed.
     */
    public function canBeAppealed(): bool
    {
        // Cannot appeal if already overturned or completed
        if (in_array($this->status, [self::STATUS_OVERTURNED, self::STATUS_COMPLETED])) {
            return false;
        }

        // Cannot appeal permanent suspensions (per business logic)
        if ($this->type === self::TYPE_PERMANENT) {
            return false;
        }

        // Check if within appeal window
        $appealWindowDays = config('suspensions.appeal_window_days', 7);
        $appealDeadline = $this->created_at->addDays($appealWindowDays);

        if (Carbon::now() > $appealDeadline) {
            return false;
        }

        // Check if there's already a pending appeal
        if ($this->pendingAppeal()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Get the days remaining until the appeal window closes.
     */
    public function appealDaysRemaining(): ?int
    {
        if (! $this->canBeAppealed()) {
            return null;
        }

        $appealWindowDays = config('suspensions.appeal_window_days', 7);
        $appealDeadline = $this->created_at->addDays($appealWindowDays);

        return max(0, Carbon::now()->diffInDays($appealDeadline, false));
    }

    /**
     * Get days remaining in suspension.
     */
    public function daysRemaining(): ?int
    {
        if (! $this->ends_at || ! $this->isCurrentlyActive()) {
            return null;
        }

        return max(0, Carbon::now()->diffInDays($this->ends_at, false));
    }

    /**
     * Get hours remaining in suspension.
     */
    public function hoursRemaining(): ?int
    {
        if (! $this->ends_at || ! $this->isCurrentlyActive()) {
            return null;
        }

        return max(0, Carbon::now()->diffInHours($this->ends_at, false));
    }

    /**
     * Get total duration in hours.
     */
    public function getDurationHours(): ?int
    {
        if (! $this->ends_at) {
            return null;
        }

        return $this->starts_at->diffInHours($this->ends_at);
    }

    /**
     * Get human-readable duration.
     */
    public function getDurationForHumans(): string
    {
        if (! $this->ends_at) {
            return $this->type === self::TYPE_PERMANENT ? 'Permanent' : 'Indefinite';
        }

        return $this->starts_at->diffForHumans($this->ends_at, ['parts' => 2, 'syntax' => true]);
    }

    /**
     * Get the human-readable type label.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_WARNING => 'Warning',
            self::TYPE_TEMPORARY => 'Temporary Suspension',
            self::TYPE_INDEFINITE => 'Indefinite Suspension',
            self::TYPE_PERMANENT => 'Permanent Ban',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get the human-readable reason category label.
     */
    public function getReasonCategoryLabel(): string
    {
        return match ($this->reason_category) {
            self::REASON_NO_SHOW => 'No Show',
            self::REASON_LATE_CANCELLATION => 'Late Cancellation',
            self::REASON_MISCONDUCT => 'Misconduct',
            self::REASON_POLICY_VIOLATION => 'Policy Violation',
            self::REASON_FRAUD => 'Fraud',
            self::REASON_SAFETY => 'Safety Concern',
            self::REASON_OTHER => 'Other',
            default => ucfirst(str_replace('_', ' ', $this->reason_category)),
        };
    }

    /**
     * Get the human-readable status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_APPEALED => 'Under Appeal',
            self::STATUS_OVERTURNED => 'Overturned',
            self::STATUS_ESCALATED => 'Escalated',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the status badge color for UI.
     */
    public function getStatusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'red',
            self::STATUS_COMPLETED => 'gray',
            self::STATUS_APPEALED => 'yellow',
            self::STATUS_OVERTURNED => 'green',
            self::STATUS_ESCALATED => 'orange',
            default => 'gray',
        };
    }

    /**
     * Get available suspension types.
     *
     * @return array<string, string>
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_WARNING => 'Warning',
            self::TYPE_TEMPORARY => 'Temporary Suspension',
            self::TYPE_INDEFINITE => 'Indefinite Suspension',
            self::TYPE_PERMANENT => 'Permanent Ban',
        ];
    }

    /**
     * Get available reason categories.
     *
     * @return array<string, string>
     */
    public static function getReasonCategories(): array
    {
        return [
            self::REASON_NO_SHOW => 'No Show',
            self::REASON_LATE_CANCELLATION => 'Late Cancellation',
            self::REASON_MISCONDUCT => 'Misconduct',
            self::REASON_POLICY_VIOLATION => 'Policy Violation',
            self::REASON_FRAUD => 'Fraud',
            self::REASON_SAFETY => 'Safety Concern',
            self::REASON_OTHER => 'Other',
        ];
    }

    /**
     * Get available statuses.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_APPEALED => 'Under Appeal',
            self::STATUS_OVERTURNED => 'Overturned',
            self::STATUS_ESCALATED => 'Escalated',
        ];
    }
}
