<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-014: Team Formation - Team Shift Request Model
 *
 * Represents a team's application to work a shift together.
 *
 * @property int $id
 * @property int $team_id
 * @property int $shift_id
 * @property int $requested_by
 * @property string $status
 * @property int $members_needed
 * @property int $members_confirmed
 * @property array|null $confirmed_members
 * @property array|null $assigned_members
 * @property string|null $application_message
 * @property string|null $response_message
 * @property int|null $responded_by
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon|null $confirmation_deadline
 * @property int $priority_score
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WorkerTeam $team
 * @property-read \App\Models\Shift $shift
 * @property-read \App\Models\User $requester
 * @property-read \App\Models\User|null $responder
 */
class TeamShiftRequest extends Model
{
    use HasFactory;

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_PARTIAL = 'partial';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'team_id',
        'shift_id',
        'requested_by',
        'status',
        'members_needed',
        'members_confirmed',
        'confirmed_members',
        'assigned_members',
        'application_message',
        'response_message',
        'responded_by',
        'responded_at',
        'confirmation_deadline',
        'priority_score',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'members_needed' => 'integer',
        'members_confirmed' => 'integer',
        'confirmed_members' => 'array',
        'assigned_members' => 'array',
        'responded_at' => 'datetime',
        'confirmation_deadline' => 'datetime',
        'priority_score' => 'integer',
    ];

    /**
     * Get the team.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(WorkerTeam::class, 'team_id');
    }

    /**
     * Get the shift.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the user who requested.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who responded.
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    /**
     * Scope to get requests for a specific team.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to get requests for a specific shift.
     */
    public function scopeForShift($query, int $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to get active requests (pending or partial).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIAL]);
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if all required members have confirmed.
     */
    public function isFullyConfirmed(): bool
    {
        return $this->members_confirmed >= $this->members_needed;
    }

    /**
     * Add a confirmed member.
     */
    public function confirmMember(int $userId): bool
    {
        $confirmedMembers = $this->confirmed_members ?? [];

        if (! in_array($userId, $confirmedMembers)) {
            $confirmedMembers[] = $userId;
            $this->confirmed_members = $confirmedMembers;
            $this->members_confirmed = count($confirmedMembers);

            // Update status to partial if not all confirmed yet
            if ($this->status === self::STATUS_PENDING && $this->members_confirmed > 0) {
                $this->status = self::STATUS_PARTIAL;
            }

            return $this->save();
        }

        return false;
    }

    /**
     * Remove a confirmed member.
     */
    public function unconfirmMember(int $userId): bool
    {
        $confirmedMembers = $this->confirmed_members ?? [];

        if (($key = array_search($userId, $confirmedMembers)) !== false) {
            unset($confirmedMembers[$key]);
            $this->confirmed_members = array_values($confirmedMembers);
            $this->members_confirmed = count($this->confirmed_members);

            return $this->save();
        }

        return false;
    }

    /**
     * Approve the request.
     */
    public function approve(User $responder, ?string $message = null): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->responded_by = $responder->id;
        $this->responded_at = now();
        $this->response_message = $message;

        // Set assigned members to confirmed members
        $this->assigned_members = $this->confirmed_members;

        return $this->save();
    }

    /**
     * Reject the request.
     */
    public function reject(User $responder, ?string $message = null): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->responded_by = $responder->id;
        $this->responded_at = now();
        $this->response_message = $message;

        return $this->save();
    }

    /**
     * Cancel the request.
     */
    public function cancel(): bool
    {
        $this->status = self::STATUS_CANCELLED;

        return $this->save();
    }

    /**
     * Expire the request.
     */
    public function expire(): bool
    {
        $this->status = self::STATUS_EXPIRED;

        return $this->save();
    }

    /**
     * Check if confirmation deadline has passed.
     */
    public function isDeadlinePassed(): bool
    {
        if (! $this->confirmation_deadline) {
            return false;
        }

        return now()->isAfter($this->confirmation_deadline);
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_PARTIAL => 'Awaiting Confirmations',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentage(): int
    {
        if ($this->members_needed === 0) {
            return 100;
        }

        return min(100, (int) (($this->members_confirmed / $this->members_needed) * 100));
    }
}
