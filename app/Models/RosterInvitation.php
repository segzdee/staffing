<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BIZ-005: Roster Invitation
 *
 * Represents an invitation for a worker to join a business roster.
 *
 * @property int $id
 * @property int $roster_id
 * @property int $worker_id
 * @property int $invited_by
 * @property string $status
 * @property string|null $message
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BusinessRoster $roster
 * @property-read \App\Models\User $worker
 * @property-read \App\Models\User $invitedByUser
 */
class RosterInvitation extends Model
{
    use HasFactory;

    /**
     * Invitation statuses
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    /**
     * Default invitation expiry in days
     */
    public const DEFAULT_EXPIRY_DAYS = 7;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'roster_id',
        'worker_id',
        'invited_by',
        'status',
        'message',
        'expires_at',
        'responded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the roster this invitation is for.
     */
    public function roster(): BelongsTo
    {
        return $this->belongsTo(BusinessRoster::class, 'roster_id');
    }

    /**
     * Get the worker being invited.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scope for pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for accepted invitations.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope for declined invitations.
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', self::STATUS_DECLINED);
    }

    /**
     * Scope for expired invitations.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    /**
     * Scope for active (non-expired pending) invitations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for invitations that need to be marked as expired.
     */
    public function scopeNeedingExpiration($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '<=', now());
    }

    /**
     * Check if invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if invitation is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if invitation is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || $this->expires_at <= now();
    }

    /**
     * Check if invitation can be responded to.
     */
    public function canRespond(): bool
    {
        return $this->isPending() && ! $this->isExpired();
    }

    /**
     * Accept the invitation.
     */
    public function accept(): self
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        return $this;
    }

    /**
     * Decline the invitation.
     */
    public function decline(): self
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
            'responded_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark the invitation as expired.
     */
    public function markExpired(): self
    {
        $this->update(['status' => self::STATUS_EXPIRED]);

        return $this;
    }

    /**
     * Get the display status.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => $this->isExpired() ? 'Expired' : 'Pending',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_EXPIRED => 'Expired',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the status badge color.
     */
    public function getStatusBadgeColorAttribute(): string
    {
        if ($this->status === self::STATUS_PENDING && $this->isExpired()) {
            return 'secondary';
        }

        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_ACCEPTED => 'success',
            self::STATUS_DECLINED => 'danger',
            self::STATUS_EXPIRED => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get time remaining until expiration.
     */
    public function getTimeRemainingAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_EXPIRED => 'Expired',
        ];
    }
}
