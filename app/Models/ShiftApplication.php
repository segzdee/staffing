<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $shift_id
 * @property int $worker_id
 * @property string $status
 * @property numeric $match_score
 * @property numeric $skill_score
 * @property numeric $proximity_score
 * @property numeric $reliability_score
 * @property numeric $rating_score
 * @property numeric $recency_score
 * @property int|null $rank_position
 * @property numeric|null $distance_km
 * @property string $priority_tier
 * @property string|null $application_note
 * @property \Illuminate\Support\Carbon $applied_at
 * @property string|null $notification_sent_at
 * @property string|null $notification_opened_at
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property string|null $acknowledged_at
 * @property string|null $acknowledgment_required_by
 * @property string|null $reminder_sent_at
 * @property string|null $auto_cancelled_at
 * @property int $acknowledgment_late
 * @property int $is_favorited
 * @property int $is_blocked
 * @property string $application_source
 * @property string|null $device_type
 * @property string|null $app_version
 * @property string|null $viewed_by_business_at
 * @property int|null $responded_by
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Shift $shift
 * @property-read \App\Models\User $worker
 * @property-read \App\Models\WorkerProfile|null $workerProfile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication accepted()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication byWorker($workerId)
 * @method static \Database\Factories\ShiftApplicationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereAcknowledgedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereAcknowledgmentLate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereAcknowledgmentRequiredBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereAppVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereApplicationNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereApplicationSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereAppliedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereAutoCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereDeviceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereDistanceKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereIsBlocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereIsFavorited($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereMatchScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereNotificationOpenedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereNotificationSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication wherePriorityTier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereProximityScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereRankPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereRatingScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereRecencyScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereReliabilityScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereReminderSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereRespondedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereSkillScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereViewedByBusinessAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftApplication whereWorkerId($value)
 *
 * @mixin \Eloquent
 */
class ShiftApplication extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shift_id',
        'worker_id',
        'status',
        'application_note',
        'applied_at',
        'responded_at',
        // AI-powered matching scores
        'match_score',
        'skill_score',
        'proximity_score',
        'reliability_score',
        'rating_score',
        'recency_score',
        'rank_position',
        'distance_km',
        'priority_tier',
        // Notification tracking
        'notification_sent_at',
        'notification_opened_at',
        'acknowledged_at',
        'acknowledgment_required_by',
        // Business interaction tracking
        'is_favorited',
        'is_blocked',
        'viewed_by_business_at',
        'responded_by',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'applied_at' => 'datetime',
        'responded_at' => 'datetime',
        // AI-powered matching score casts
        'match_score' => 'decimal:4',
        'skill_score' => 'decimal:4',
        'proximity_score' => 'decimal:4',
        'reliability_score' => 'decimal:4',
        'rating_score' => 'decimal:4',
        'recency_score' => 'decimal:4',
        'distance_km' => 'decimal:2',
        'rank_position' => 'integer',
        // Boolean casts
        'is_favorited' => 'boolean',
        'is_blocked' => 'boolean',
        // Datetime casts for tracking
        'notification_sent_at' => 'datetime',
        'notification_opened_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'acknowledgment_required_by' => 'datetime',
        'viewed_by_business_at' => 'datetime',
    ];

    /**
     * Get the shift this application is for.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the worker who applied.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the worker's profile directly.
     * Convenience accessor for views.
     */
    public function workerProfile()
    {
        return $this->hasOneThrough(
            WorkerProfile::class,
            User::class,
            'id',         // Foreign key on users (primary key)
            'user_id',    // Foreign key on worker_profiles
            'worker_id',  // Local key on shift_applications
            'id'          // Local key on users
        );
    }

    /**
     * Check if application is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if application is accepted.
     */
    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if application is rejected.
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Accept the application.
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
    }

    /**
     * Reject the application.
     */
    public function reject()
    {
        $this->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);
    }

    /**
     * Withdraw the application.
     */
    public function withdraw()
    {
        $this->update([
            'status' => 'withdrawn',
            'responded_at' => now(),
        ]);
    }

    /**
     * Scope for pending applications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for accepted applications.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for applications by worker.
     */
    public function scopeByWorker($query, $workerId)
    {
        return $query->where('worker_id', $workerId);
    }
}
