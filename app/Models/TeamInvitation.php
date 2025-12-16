<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * BIZ-REG-008: Team Invitation Model
 *
 * Manages team member invitations with token-based acceptance.
 * Supports invitations to non-existent users via email.
 *
 * @property int $id
 * @property int $business_id
 * @property int $invited_by
 * @property string $email
 * @property int|null $user_id
 * @property string $role
 * @property array|null $venue_access
 * @property array|null $custom_permissions
 * @property string $token
 * @property string $token_hash
 * @property string $status
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $accepted_at
 * @property \Carbon\Carbon|null $declined_at
 * @property \Carbon\Carbon|null $revoked_at
 * @property string|null $message
 * @property string|null $revocation_reason
 * @property int $resend_count
 * @property \Carbon\Carbon|null $last_resent_at
 * @property string|null $accepted_ip
 * @property string|null $accepted_user_agent
 */
class TeamInvitation extends Model
{
    use HasFactory;

    /**
     * Invitation statuses.
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'expired' => 'Expired',
        'revoked' => 'Revoked',
    ];

    /**
     * Available roles for invitations.
     */
    public const ROLES = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'scheduler' => 'Scheduler',
        'viewer' => 'Viewer',
    ];

    /**
     * Default invitation expiry in days.
     */
    public const EXPIRY_DAYS = 7;

    /**
     * Maximum resend attempts.
     */
    public const MAX_RESENDS = 3;

    protected $fillable = [
        'business_id',
        'invited_by',
        'email',
        'user_id',
        'role',
        'venue_access',
        'custom_permissions',
        'token',
        'token_hash',
        'status',
        'expires_at',
        'accepted_at',
        'declined_at',
        'revoked_at',
        'message',
        'revocation_reason',
        'resend_count',
        'last_resent_at',
        'accepted_ip',
        'accepted_user_agent',
    ];

    protected $casts = [
        'venue_access' => 'array',
        'custom_permissions' => 'array',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_resent_at' => 'datetime',
        'resend_count' => 'integer',
    ];

    protected $hidden = [
        'token',
        'token_hash',
    ];

    protected $appends = [
        'is_expired',
        'is_pending',
        'role_label',
        'status_label',
        'can_resend',
        'days_until_expiry',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the business this invitation is for.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the business profile.
     */
    public function businessProfile()
    {
        return $this->hasOneThrough(
            BusinessProfile::class,
            User::class,
            'id',
            'user_id',
            'business_id',
            'id'
        );
    }

    /**
     * Get the user who sent the invitation.
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the invited user (if they exist).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the team member created from this invitation.
     */
    public function teamMember()
    {
        return $this->hasOne(TeamMember::class, 'invitation_id');
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Check if invitation is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if invitation is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending' && !$this->is_expired;
    }

    /**
     * Get role label.
     */
    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'pending' && $this->is_expired) {
            return 'Expired';
        }

        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Check if invitation can be resent.
     */
    public function getCanResendAttribute(): bool
    {
        return $this->status === 'pending'
            && $this->resend_count < self::MAX_RESENDS;
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        if ($this->is_expired) {
            return 0;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    // =========================================
    // Token Management
    // =========================================

    /**
     * Generate a new invitation token.
     */
    public static function generateToken(): array
    {
        $token = Str::random(64);
        $hash = hash('sha256', $token);

        return [
            'token' => $token,
            'hash' => $hash,
        ];
    }

    /**
     * Find invitation by token.
     */
    public static function findByToken(string $token): ?self
    {
        $hash = hash('sha256', $token);

        return static::where('token_hash', $hash)->first();
    }

    /**
     * Verify a token matches this invitation.
     */
    public function verifyToken(string $token): bool
    {
        return hash_equals($this->token_hash, hash('sha256', $token));
    }

    /**
     * Regenerate token for resending.
     */
    public function regenerateToken(): string
    {
        $tokenData = self::generateToken();

        $this->update([
            'token' => $tokenData['token'],
            'token_hash' => $tokenData['hash'],
            'expires_at' => now()->addDays(self::EXPIRY_DAYS),
            'resend_count' => $this->resend_count + 1,
            'last_resent_at' => now(),
        ]);

        return $tokenData['token'];
    }

    // =========================================
    // Status Management
    // =========================================

    /**
     * Check if invitation can be accepted.
     */
    public function canBeAccepted(): bool
    {
        return $this->status === 'pending' && !$this->is_expired;
    }

    /**
     * Accept the invitation.
     */
    public function accept(User $user, ?string $ip = null, ?string $userAgent = null): TeamMember
    {
        if (!$this->canBeAccepted()) {
            throw new \Exception('This invitation cannot be accepted.');
        }

        // Update invitation status
        $this->update([
            'status' => 'accepted',
            'user_id' => $user->id,
            'accepted_at' => now(),
            'accepted_ip' => $ip,
            'accepted_user_agent' => $userAgent,
        ]);

        // Create team member
        $teamMember = TeamMember::create([
            'business_id' => $this->business_id,
            'user_id' => $user->id,
            'invited_by' => $this->invited_by,
            'role' => $this->role,
            'venue_access' => $this->venue_access,
            'status' => 'active',
            'invitation_id' => $this->id,
            'joined_at' => now(),
        ]);

        // Apply role permissions
        $teamMember->applyRolePermissions();

        // Apply custom permissions if set
        if ($this->custom_permissions) {
            $teamMember->update($this->custom_permissions);
        }

        return $teamMember;
    }

    /**
     * Decline the invitation.
     */
    public function decline(): void
    {
        $this->update([
            'status' => 'declined',
            'declined_at' => now(),
        ]);
    }

    /**
     * Revoke the invitation.
     */
    public function revoke(string $reason = null): void
    {
        $this->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);
    }

    /**
     * Mark expired invitations.
     */
    public static function markExpired(): int
    {
        return static::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope for pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for specific business.
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for specific email.
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', strtolower($email));
    }

    /**
     * Scope for expired invitations.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
              ->orWhere(function ($q2) {
                  $q2->where('status', 'pending')
                     ->where('expires_at', '<', now());
              });
        });
    }

    // =========================================
    // Factory Methods
    // =========================================

    /**
     * Create a new invitation.
     */
    public static function createInvitation(
        int $businessId,
        int $invitedBy,
        string $email,
        string $role,
        ?array $venueAccess = null,
        ?string $message = null,
        ?array $customPermissions = null
    ): self {
        // Check for existing user
        $user = User::where('email', strtolower($email))->first();

        // Generate token
        $tokenData = self::generateToken();

        return static::create([
            'business_id' => $businessId,
            'invited_by' => $invitedBy,
            'email' => strtolower($email),
            'user_id' => $user?->id,
            'role' => $role,
            'venue_access' => $venueAccess,
            'custom_permissions' => $customPermissions,
            'token' => $tokenData['token'],
            'token_hash' => $tokenData['hash'],
            'status' => 'pending',
            'expires_at' => now()->addDays(self::EXPIRY_DAYS),
            'message' => $message,
        ]);
    }

    /**
     * Get invitation URL.
     */
    public function getInvitationUrl(): string
    {
        return route('team.invitation.accept', ['token' => $this->token]);
    }

    // =========================================
    // Boot Methods
    // =========================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            // Normalize email
            $invitation->email = strtolower($invitation->email);

            // Set default expiry
            if (!$invitation->expires_at) {
                $invitation->expires_at = now()->addDays(self::EXPIRY_DAYS);
            }
        });
    }
}
