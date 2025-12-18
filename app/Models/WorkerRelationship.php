<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-014: Team Formation - Worker Relationship Model
 *
 * Represents a relationship between two workers (buddy, preferred, avoided, mentor, mentee).
 *
 * @property int $id
 * @property int $worker_id
 * @property int $related_worker_id
 * @property string $relationship_type
 * @property int $shifts_together
 * @property float|null $compatibility_score
 * @property string|null $notes
 * @property bool $is_mutual
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $last_calculated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $worker
 * @property-read \App\Models\User $relatedWorker
 */
class WorkerRelationship extends Model
{
    use HasFactory;

    /**
     * Relationship type constants.
     */
    public const TYPE_BUDDY = 'buddy';

    public const TYPE_PREFERRED = 'preferred';

    public const TYPE_AVOIDED = 'avoided';

    public const TYPE_MENTOR = 'mentor';

    public const TYPE_MENTEE = 'mentee';

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_REMOVED = 'removed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'worker_id',
        'related_worker_id',
        'relationship_type',
        'shifts_together',
        'compatibility_score',
        'notes',
        'is_mutual',
        'status',
        'confirmed_at',
        'last_calculated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shifts_together' => 'integer',
        'compatibility_score' => 'decimal:2',
        'is_mutual' => 'boolean',
        'confirmed_at' => 'datetime',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Get the worker who initiated the relationship.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the related worker.
     */
    public function relatedWorker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_worker_id');
    }

    /**
     * Scope to get relationships for a specific worker.
     */
    public function scopeForWorker($query, int $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    /**
     * Scope to get relationships involving a specific worker (either side).
     */
    public function scopeInvolvingWorker($query, int $workerId)
    {
        return $query->where(function ($q) use ($workerId) {
            $q->where('worker_id', $workerId)
                ->orWhere('related_worker_id', $workerId);
        });
    }

    /**
     * Scope to get active relationships.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get pending relationships.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get relationships of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Scope to get buddy relationships.
     */
    public function scopeBuddies($query)
    {
        return $query->where('relationship_type', self::TYPE_BUDDY);
    }

    /**
     * Scope to get preferred coworker relationships.
     */
    public function scopePreferred($query)
    {
        return $query->where('relationship_type', self::TYPE_PREFERRED);
    }

    /**
     * Scope to get avoided worker relationships.
     */
    public function scopeAvoided($query)
    {
        return $query->where('relationship_type', self::TYPE_AVOIDED);
    }

    /**
     * Scope to get mutual relationships.
     */
    public function scopeMutual($query)
    {
        return $query->where('is_mutual', true);
    }

    /**
     * Check if this is a buddy relationship.
     */
    public function isBuddy(): bool
    {
        return $this->relationship_type === self::TYPE_BUDDY;
    }

    /**
     * Check if this is an avoided relationship.
     */
    public function isAvoided(): bool
    {
        return $this->relationship_type === self::TYPE_AVOIDED;
    }

    /**
     * Check if relationship is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if relationship is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Confirm the relationship (for buddy requests).
     */
    public function confirm(): bool
    {
        $this->status = self::STATUS_ACTIVE;
        $this->is_mutual = true;
        $this->confirmed_at = now();

        return $this->save();
    }

    /**
     * Decline the relationship (for buddy requests).
     */
    public function decline(): bool
    {
        $this->status = self::STATUS_DECLINED;

        return $this->save();
    }

    /**
     * Remove the relationship.
     */
    public function remove(): bool
    {
        $this->status = self::STATUS_REMOVED;

        return $this->save();
    }

    /**
     * Increment shift count.
     */
    public function incrementShiftCount(): bool
    {
        $this->shifts_together++;

        return $this->save();
    }

    /**
     * Get the inverse relationship type (for creating reciprocal relationships).
     */
    public static function getInverseType(string $type): string
    {
        return match ($type) {
            self::TYPE_MENTOR => self::TYPE_MENTEE,
            self::TYPE_MENTEE => self::TYPE_MENTOR,
            default => $type, // buddy, preferred, avoided are symmetric
        };
    }

    /**
     * Get display label for relationship type.
     */
    public function getTypeLabel(): string
    {
        return match ($this->relationship_type) {
            self::TYPE_BUDDY => 'Buddy',
            self::TYPE_PREFERRED => 'Preferred Coworker',
            self::TYPE_AVOIDED => 'Avoided',
            self::TYPE_MENTOR => 'Mentor',
            self::TYPE_MENTEE => 'Mentee',
            default => ucfirst($this->relationship_type),
        };
    }

    /**
     * Get display label for status.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_REMOVED => 'Removed',
            default => ucfirst($this->status),
        };
    }
}
