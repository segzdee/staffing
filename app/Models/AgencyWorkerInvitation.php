<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Agency Worker Invitation Model
 * AGY-REG: Agency Registration & Onboarding System
 *
 * Tracks invitations sent by agencies to workers to join their roster.
 * Supports both invitations to existing platform workers and new users.
 *
 * @property int $id
 * @property int $agency_id
 * @property int|null $worker_id
 * @property string $token
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $name
 * @property string $type
 * @property string $status
 * @property numeric|null $offered_commission_rate
 * @property array|null $offered_benefits
 * @property string|null $personal_message
 * @property array|null $preset_skills
 * @property array|null $preset_certifications
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $viewed_at
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $declined_at
 * @property string|null $decline_reason
 * @property string|null $invitation_ip
 * @property string|null $response_ip
 * @property string|null $response_user_agent
 * @property string|null $batch_id
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $agency
 * @property-read \App\Models\AgencyProfile|null $agencyProfile
 * @property-read \App\Models\User|null $worker
 */
class AgencyWorkerInvitation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'agency_id',
        'worker_id',
        'token',
        'email',
        'phone',
        'name',
        'type',
        'status',
        'offered_commission_rate',
        'offered_benefits',
        'personal_message',
        'preset_skills',
        'preset_certifications',
        'expires_at',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'declined_at',
        'decline_reason',
        'invitation_ip',
        'response_ip',
        'response_user_agent',
        'batch_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'offered_commission_rate' => 'decimal:2',
        'offered_benefits' => 'array',
        'preset_skills' => 'array',
        'preset_certifications' => 'array',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
    ];

    // ==================== INVITATION TYPE CONSTANTS ====================

    /**
     * Invitation to existing platform worker
     */
    const TYPE_EXISTING_WORKER = 'existing_worker';

    /**
     * Invitation to new user (not yet registered)
     */
    const TYPE_NEW_USER = 'new_user';

    /**
     * Bulk import invitation
     */
    const TYPE_BULK_IMPORT = 'bulk_import';

    /**
     * Referral invitation
     */
    const TYPE_REFERRAL = 'referral';

    /**
     * All valid invitation types
     */
    const TYPES = [
        self::TYPE_EXISTING_WORKER,
        self::TYPE_NEW_USER,
        self::TYPE_BULK_IMPORT,
        self::TYPE_REFERRAL,
    ];

    // ==================== STATUS CONSTANTS ====================

    /**
     * Invitation created but not yet sent
     */
    const STATUS_PENDING = 'pending';

    /**
     * Invitation sent to worker
     */
    const STATUS_SENT = 'sent';

    /**
     * Invitation viewed by worker
     */
    const STATUS_VIEWED = 'viewed';

    /**
     * Invitation accepted by worker
     */
    const STATUS_ACCEPTED = 'accepted';

    /**
     * Invitation declined by worker
     */
    const STATUS_DECLINED = 'declined';

    /**
     * Invitation expired
     */
    const STATUS_EXPIRED = 'expired';

    /**
     * Invitation cancelled by agency
     */
    const STATUS_CANCELLED = 'cancelled';

    /**
     * All valid statuses
     */
    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SENT,
        self::STATUS_VIEWED,
        self::STATUS_ACCEPTED,
        self::STATUS_DECLINED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    /**
     * Default expiration period in days.
     */
    const DEFAULT_EXPIRY_DAYS = 14;

    // ==================== MODEL BOOT ====================

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = self::generateToken();
            }
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addDays(self::DEFAULT_EXPIRY_DAYS);
            }
            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the agency that sent this invitation.
     */
    public function agency()
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    /**
     * Get the agency's profile.
     */
    public function agencyProfile()
    {
        return $this->hasOneThrough(
            AgencyProfile::class,
            User::class,
            'id',
            'user_id',
            'agency_id',
            'id'
        );
    }

    /**
     * Get the worker (if invitation is to existing user).
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to pending invitations.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_SENT]);
    }

    /**
     * Scope to valid (not expired and not responded) invitations.
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_SENT, self::STATUS_VIEWED]);
    }

    /**
     * Scope to accepted invitations.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope to declined invitations.
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', self::STATUS_DECLINED);
    }

    /**
     * Scope to expired invitations.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
            ->orWhere(function ($q) {
                $q->where('expires_at', '<', now())
                  ->whereNotIn('status', [self::STATUS_ACCEPTED, self::STATUS_DECLINED, self::STATUS_CANCELLED]);
            });
    }

    /**
     * Scope by agency.
     */
    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    /**
     * Scope by worker.
     */
    public function scopeForWorker($query, int $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    /**
     * Scope by token.
     */
    public function scopeByToken($query, string $token)
    {
        return $query->where('token', $token);
    }

    /**
     * Scope by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope by batch.
     */
    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    // ==================== STATUS HELPER METHODS ====================

    /**
     * Check if invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if invitation has been sent.
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if invitation has been viewed.
     */
    public function isViewed(): bool
    {
        return $this->status === self::STATUS_VIEWED || $this->viewed_at !== null;
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
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if invitation is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if invitation is still valid (can be acted upon).
     */
    public function isValid(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_SENT,
            self::STATUS_VIEWED,
        ]);
    }

    /**
     * Check if invitation is for an existing worker.
     */
    public function isForExistingWorker(): bool
    {
        return $this->type === self::TYPE_EXISTING_WORKER || $this->worker_id !== null;
    }

    /**
     * Check if invitation is for a new user.
     */
    public function isForNewUser(): bool
    {
        return $this->type === self::TYPE_NEW_USER && $this->worker_id === null;
    }

    // ==================== TRANSITION METHODS ====================

    /**
     * Mark invitation as sent.
     */
    public function markAsSent(?string $ip = null): self
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'invitation_ip' => $ip ?? request()->ip(),
        ]);

        return $this;
    }

    /**
     * Mark invitation as viewed.
     */
    public function markAsViewed(): self
    {
        if (!$this->isViewed()) {
            $this->update([
                'status' => self::STATUS_VIEWED,
                'viewed_at' => now(),
            ]);
        }

        return $this;
    }

    /**
     * Accept the invitation.
     */
    public function accept(?User $worker = null, ?string $ip = null, ?string $userAgent = null): self
    {
        if (!$this->isValid()) {
            throw new \InvalidArgumentException('This invitation is no longer valid.');
        }

        $updateData = [
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'response_ip' => $ip ?? request()->ip(),
            'response_user_agent' => $userAgent ?? request()->userAgent(),
        ];

        if ($worker) {
            $updateData['worker_id'] = $worker->id;
        }

        $this->update($updateData);

        return $this;
    }

    /**
     * Decline the invitation.
     */
    public function decline(?string $reason = null, ?string $ip = null, ?string $userAgent = null): self
    {
        if (!$this->isValid()) {
            throw new \InvalidArgumentException('This invitation is no longer valid.');
        }

        $this->update([
            'status' => self::STATUS_DECLINED,
            'declined_at' => now(),
            'decline_reason' => $reason,
            'response_ip' => $ip ?? request()->ip(),
            'response_user_agent' => $userAgent ?? request()->userAgent(),
        ]);

        return $this;
    }

    /**
     * Cancel the invitation.
     */
    public function cancel(): self
    {
        if ($this->isAccepted()) {
            throw new \InvalidArgumentException('Cannot cancel an accepted invitation.');
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);

        return $this;
    }

    /**
     * Expire the invitation.
     */
    public function expire(): self
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        return $this;
    }

    /**
     * Resend the invitation.
     */
    public function resend(int $additionalDays = null): self
    {
        if ($this->isAccepted() || $this->isDeclined()) {
            throw new \InvalidArgumentException('Cannot resend a responded invitation.');
        }

        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'expires_at' => now()->addDays($additionalDays ?? self::DEFAULT_EXPIRY_DAYS),
            'token' => self::generateToken(), // New token for security
        ]);

        return $this;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Generate a unique invitation token.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Get the invitation URL.
     */
    public function getInvitationUrl(): string
    {
        return route('worker.agency-invitation', ['token' => $this->token]);
    }

    /**
     * Get the invalid reason (if invitation is invalid).
     */
    public function getInvalidReason(): ?string
    {
        if ($this->isExpired()) {
            return 'This invitation has expired.';
        }

        if ($this->isAccepted()) {
            return 'This invitation has already been accepted.';
        }

        if ($this->isDeclined()) {
            return 'This invitation has been declined.';
        }

        if ($this->isCancelled()) {
            return 'This invitation has been cancelled.';
        }

        return null;
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SENT => 'Sent',
            self::STATUS_VIEWED => 'Viewed',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        $colors = [
            self::STATUS_PENDING => 'gray',
            self::STATUS_SENT => 'blue',
            self::STATUS_VIEWED => 'purple',
            self::STATUS_ACCEPTED => 'green',
            self::STATUS_DECLINED => 'red',
            self::STATUS_EXPIRED => 'gray',
            self::STATUS_CANCELLED => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    /**
     * Get invitation type label for display.
     */
    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_EXISTING_WORKER => 'Existing Worker',
            self::TYPE_NEW_USER => 'New User',
            self::TYPE_BULK_IMPORT => 'Bulk Import',
            self::TYPE_REFERRAL => 'Referral',
        ];

        return $labels[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiry(): int
    {
        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * Get the recipient identifier (email or phone).
     */
    public function getRecipientIdentifier(): ?string
    {
        return $this->email ?? $this->phone;
    }

    /**
     * Get the recipient display name.
     */
    public function getRecipientName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->worker) {
            return $this->worker->name;
        }

        return $this->email ?? $this->phone ?? 'Unknown';
    }

    /**
     * Check if an active invitation exists for this contact and agency.
     */
    public static function existsActiveInvitation(int $agencyId, ?string $email = null, ?int $workerId = null): bool
    {
        $query = self::where('agency_id', $agencyId)
            ->valid();

        if ($workerId) {
            $query->where('worker_id', $workerId);
        } elseif ($email) {
            $query->where('email', $email);
        } else {
            return false;
        }

        return $query->exists();
    }

    /**
     * Get commission rate display (or default text).
     */
    public function getCommissionRateDisplay(): string
    {
        if ($this->offered_commission_rate) {
            return number_format($this->offered_commission_rate, 2) . '%';
        }

        return 'Standard rate';
    }
}
