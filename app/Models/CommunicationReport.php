<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * CommunicationReport Model
 *
 * COM-005: Communication Compliance
 * Tracks user reports of inappropriate communications.
 *
 * @property int $id
 * @property string $reportable_type
 * @property int $reportable_id
 * @property int $reporter_id
 * @property int $reported_user_id
 * @property string $reason
 * @property string|null $description
 * @property string $status
 * @property int|null $resolved_by
 * @property string|null $resolution_notes
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $reporter
 * @property-read \App\Models\User $reportedUser
 * @property-read \App\Models\User|null $resolver
 * @property-read Model $reportable
 */
class CommunicationReport extends Model
{
    use HasFactory;

    /**
     * Reason constants.
     */
    public const REASON_HARASSMENT = 'harassment';

    public const REASON_SPAM = 'spam';

    public const REASON_INAPPROPRIATE = 'inappropriate';

    public const REASON_THREATENING = 'threatening';

    public const REASON_PII_SHARING = 'pii_sharing';

    public const REASON_OTHER = 'other';

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reportable_type',
        'reportable_id',
        'reporter_id',
        'reported_user_id',
        'reason',
        'description',
        'status',
        'resolved_by',
        'resolution_notes',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the reported content (polymorphic).
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the report.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the user being reported.
     */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    /**
     * Get the admin who resolved this report.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Start investigating the report.
     */
    public function startInvestigation(): bool
    {
        return $this->update(['status' => self::STATUS_INVESTIGATING]);
    }

    /**
     * Resolve the report.
     */
    public function resolve(User $resolver, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_by' => $resolver->id,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Dismiss the report.
     */
    public function dismiss(User $resolver, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_DISMISSED,
            'resolved_by' => $resolver->id,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Check if the report is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the report is under investigation.
     */
    public function isInvestigating(): bool
    {
        return $this->status === self::STATUS_INVESTIGATING;
    }

    /**
     * Check if the report has been resolved.
     */
    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_DISMISSED]);
    }

    /**
     * Get human-readable reason.
     */
    public function getReasonLabel(): string
    {
        return match ($this->reason) {
            self::REASON_HARASSMENT => 'Harassment',
            self::REASON_SPAM => 'Spam',
            self::REASON_INAPPROPRIATE => 'Inappropriate Content',
            self::REASON_THREATENING => 'Threatening Behavior',
            self::REASON_PII_SHARING => 'Sharing Personal Information',
            self::REASON_OTHER => 'Other',
            default => ucfirst($this->reason),
        };
    }

    /**
     * Get human-readable status.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_INVESTIGATING => 'Under Investigation',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_DISMISSED => 'Dismissed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope for pending reports.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for reports under investigation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInvestigating($query)
    {
        return $query->where('status', self::STATUS_INVESTIGATING);
    }

    /**
     * Scope for open reports (pending or investigating).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_INVESTIGATING]);
    }

    /**
     * Scope for resolved reports.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope for dismissed reports.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDismissed($query)
    {
        return $query->where('status', self::STATUS_DISMISSED);
    }

    /**
     * Scope for reports by reason.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Scope for reports against a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAgainstUser($query, int $userId)
    {
        return $query->where('reported_user_id', $userId);
    }

    /**
     * Scope for reports made by a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByReporter($query, int $userId)
    {
        return $query->where('reporter_id', $userId);
    }

    /**
     * Get all available reasons.
     */
    public static function getReasons(): array
    {
        return [
            self::REASON_HARASSMENT,
            self::REASON_SPAM,
            self::REASON_INAPPROPRIATE,
            self::REASON_THREATENING,
            self::REASON_PII_SHARING,
            self::REASON_OTHER,
        ];
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_INVESTIGATING,
            self::STATUS_RESOLVED,
            self::STATUS_DISMISSED,
        ];
    }
}
