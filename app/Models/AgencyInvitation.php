<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Model for tracking agency invitations to workers.
 */
class AgencyInvitation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'agency_id',
        'token',
        'email',
        'phone',
        'name',
        'type',
        'status',
        'expires_at',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'accepted_by_user_id',
        'preset_commission_rate',
        'preset_skills',
        'preset_certifications',
        'personal_message',
        'batch_id',
        'invitation_ip',
        'accepted_ip',
        'accepted_user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'preset_commission_rate' => 'decimal:2',
        'preset_skills' => 'array',
        'preset_certifications' => 'array',
    ];

    /**
     * Default expiration period in days.
     */
    public const DEFAULT_EXPIRY_DAYS = 7;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->token)) {
                $model->token = self::generateUniqueToken();
            }
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addDays(self::DEFAULT_EXPIRY_DAYS);
            }
        });
    }

    // ===================== Relationships =====================

    /**
     * Get the agency that created this invitation.
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
     * Get the user who accepted this invitation.
     */
    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    // ===================== Scopes =====================

    /**
     * Scope to pending invitations.
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'sent']);
    }

    /**
     * Scope to valid (not expired) invitations.
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->whereIn('status', ['pending', 'sent', 'viewed']);
    }

    /**
     * Scope to find by token.
     */
    public function scopeByToken($query, string $token)
    {
        return $query->where('token', $token);
    }

    /**
     * Scope to find by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to find by phone.
     */
    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    /**
     * Scope to find by batch.
     */
    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    // ===================== Methods =====================

    /**
     * Generate a unique invitation token.
     */
    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Check if the invitation is still valid.
     */
    public function isValid(): bool
    {
        if ($this->expires_at->isPast()) {
            return false;
        }

        if (in_array($this->status, ['accepted', 'expired', 'cancelled'])) {
            return false;
        }

        return true;
    }

    /**
     * Get the reason why invitation is invalid (if it is).
     */
    public function getInvalidReason(): ?string
    {
        if ($this->expires_at->isPast()) {
            return 'This invitation has expired.';
        }

        if ($this->status === 'accepted') {
            return 'This invitation has already been accepted.';
        }

        if ($this->status === 'cancelled') {
            return 'This invitation has been cancelled.';
        }

        if ($this->status === 'expired') {
            return 'This invitation has expired.';
        }

        return null;
    }

    /**
     * Mark the invitation as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark the invitation as viewed.
     */
    public function markAsViewed(): void
    {
        if ($this->status !== 'viewed') {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }

    /**
     * Accept the invitation.
     */
    public function accept(User $user, ?string $ip = null, ?string $userAgent = null): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_by_user_id' => $user->id,
            'accepted_ip' => $ip,
            'accepted_user_agent' => $userAgent,
        ]);
    }

    /**
     * Cancel the invitation.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Expire the invitation.
     */
    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Resend the invitation.
     */
    public function resend(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'expires_at' => now()->addDays(self::DEFAULT_EXPIRY_DAYS),
        ]);
    }

    /**
     * Get the invitation URL (directs to view/preview page).
     */
    public function getInvitationUrl(): string
    {
        return route('worker.agency-invitation.show', ['token' => $this->token]);
    }

    /**
     * Get the registration URL (directs to registration with invite token).
     */
    public function getRegistrationUrl(): string
    {
        return route('worker.register.agency-invite', ['token' => $this->token]);
    }

    /**
     * Check if an invitation exists for this email/phone and agency.
     */
    public static function existsForContact(int $agencyId, ?string $email = null, ?string $phone = null): bool
    {
        $query = self::where('agency_id', $agencyId)
            ->whereIn('status', ['pending', 'sent', 'viewed']);

        if ($email) {
            $query->where('email', $email);
        } elseif ($phone) {
            $query->where('phone', $phone);
        } else {
            return false;
        }

        return $query->exists();
    }
}
