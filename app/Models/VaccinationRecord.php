<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SAF-005: COVID/Health Protocols - Vaccination Record Model (Optional/Encrypted)
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $vaccine_type
 * @property \Illuminate\Support\Carbon|null $vaccination_date
 * @property string|null $document_url
 * @property string $verification_status
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property int|null $verified_by
 * @property string|null $rejection_reason
 * @property string|null $lot_number
 * @property string|null $provider_name
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property bool $is_booster
 * @property int|null $dose_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\User|null $verifier
 */
class VaccinationRecord extends Model
{
    use HasFactory;

    /**
     * Common vaccine types.
     */
    public const VACCINE_TYPES = [
        'COVID-19' => 'COVID-19',
        'Flu' => 'Influenza (Flu)',
        'Hepatitis B' => 'Hepatitis B',
        'Hepatitis A' => 'Hepatitis A',
        'Tdap' => 'Tdap (Tetanus, Diphtheria, Pertussis)',
        'MMR' => 'MMR (Measles, Mumps, Rubella)',
        'Varicella' => 'Varicella (Chickenpox)',
        'Meningococcal' => 'Meningococcal',
        'Pneumococcal' => 'Pneumococcal',
        'Other' => 'Other',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'vaccine_type',
        'vaccination_date',
        'document_url',
        'verification_status',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'lot_number',
        'provider_name',
        'expiry_date',
        'is_booster',
        'dose_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vaccination_date' => 'date',
        'verified_at' => 'datetime',
        'expiry_date' => 'date',
        'is_booster' => 'boolean',
        'dose_number' => 'integer',
    ];

    /**
     * The attributes that should be encrypted (sensitive health data).
     * Note: Actual encryption handled at storage level or via casts if needed.
     *
     * @var array<int, string>
     */
    protected $encryptable = [
        'lot_number',
        'document_url',
    ];

    /**
     * Get the user this vaccination record belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who verified this record.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if the vaccination is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if the vaccination is pending verification.
     */
    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Check if the vaccination was rejected.
     */
    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    /**
     * Check if the vaccination is still valid (not expired).
     */
    public function isValid(): bool
    {
        if (! $this->isVerified()) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Verify the vaccination record.
     */
    public function verify(int $verifierId): void
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $verifierId,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the vaccination record.
     */
    public function reject(int $verifierId, string $reason): void
    {
        $this->update([
            'verification_status' => 'rejected',
            'verified_at' => now(),
            'verified_by' => $verifierId,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Get the display name for the vaccine type.
     */
    public function getVaccineDisplayName(): string
    {
        return self::VACCINE_TYPES[$this->vaccine_type] ?? $this->vaccine_type ?? 'Unknown';
    }

    /**
     * Scope to get records for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get verified records only.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope to get pending records only.
     */
    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    /**
     * Scope to get records by vaccine type.
     */
    public function scopeOfType($query, string $vaccineType)
    {
        return $query->where('vaccine_type', $vaccineType);
    }

    /**
     * Scope to get valid (verified and not expired) records.
     */
    public function scopeValid($query)
    {
        return $query->verified()
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scope to get expired records.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now());
    }

    /**
     * Get days until expiry (null if no expiry).
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check if the vaccination is expiring soon (within 30 days).
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        $daysUntilExpiry = $this->getDaysUntilExpiry();

        if ($daysUntilExpiry === null) {
            return false;
        }

        return $daysUntilExpiry > 0 && $daysUntilExpiry <= $days;
    }
}
