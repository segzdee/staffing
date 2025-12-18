<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GLO-002: Tax Jurisdiction Engine - Tax Forms Model
 *
 * @property int $id
 * @property int $user_id
 * @property string $form_type
 * @property string|null $tax_id
 * @property string $legal_name
 * @property string|null $business_name
 * @property string $address
 * @property string $country_code
 * @property string $entity_type
 * @property string|null $document_url
 * @property string $status
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon $submitted_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int|null $verified_by
 * @property array|null $form_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $verifier
 */
class TaxForm extends Model
{
    use HasFactory;

    // Form types
    public const TYPE_W9 = 'w9';

    public const TYPE_W8BEN = 'w8ben';

    public const TYPE_W8BENE = 'w8bene';

    public const TYPE_P45 = 'p45';

    public const TYPE_P60 = 'p60';

    public const TYPE_SELF_ASSESSMENT = 'self_assessment';

    public const TYPE_TAX_DECLARATION = 'tax_declaration';

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    // Entity types
    public const ENTITY_INDIVIDUAL = 'individual';

    public const ENTITY_SOLE_PROPRIETOR = 'sole_proprietor';

    public const ENTITY_LLC = 'llc';

    public const ENTITY_CORPORATION = 'corporation';

    public const ENTITY_PARTNERSHIP = 'partnership';

    public const ENTITY_TRUST = 'trust';

    public const ENTITY_ESTATE = 'estate';

    protected $fillable = [
        'user_id',
        'form_type',
        'tax_id',
        'legal_name',
        'business_name',
        'address',
        'country_code',
        'entity_type',
        'document_url',
        'status',
        'rejection_reason',
        'submitted_at',
        'verified_at',
        'expires_at',
        'verified_by',
        'form_data',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'form_data' => 'array',
    ];

    protected $hidden = [
        'tax_id', // Sensitive data
    ];

    /**
     * The user who submitted this form.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin/staff who verified this form.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope for pending forms.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for verified forms.
     */
    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    /**
     * Scope for valid (verified and not expired) forms.
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_VERIFIED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for expired forms.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere(function ($q2) {
                    $q2->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                });
        });
    }

    /**
     * Scope for a specific form type.
     */
    public function scopeOfType($query, string $formType)
    {
        return $query->where('form_type', $formType);
    }

    /**
     * Check if the form is currently valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_VERIFIED) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the form is expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the form is pending verification.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verify the form.
     */
    public function verify(int $verifiedBy, ?string $expiresAt = null): self
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
            'expires_at' => $expiresAt,
            'rejection_reason' => null,
        ]);

        return $this;
    }

    /**
     * Reject the form.
     */
    public function reject(int $verifiedBy, string $reason): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'verified_at' => now(),
            'verified_by' => $verifiedBy,
            'rejection_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Mark the form as expired.
     */
    public function markExpired(): self
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);

        return $this;
    }

    /**
     * Get the human-readable form type name.
     */
    public function getFormTypeNameAttribute(): string
    {
        return match ($this->form_type) {
            self::TYPE_W9 => 'Form W-9',
            self::TYPE_W8BEN => 'Form W-8BEN',
            self::TYPE_W8BENE => 'Form W-8BEN-E',
            self::TYPE_P45 => 'Form P45',
            self::TYPE_P60 => 'Form P60',
            self::TYPE_SELF_ASSESSMENT => 'Self Assessment Declaration',
            self::TYPE_TAX_DECLARATION => 'Tax Declaration',
            default => ucfirst(str_replace('_', ' ', $this->form_type)),
        };
    }

    /**
     * Get the masked tax ID for display.
     */
    public function getMaskedTaxIdAttribute(): ?string
    {
        if (empty($this->tax_id)) {
            return null;
        }

        $length = strlen($this->tax_id);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).substr($this->tax_id, -4);
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_VERIFIED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * Check if the form expires soon (within 30 days).
     */
    public function expiresSoon(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->diffInDays(now()) <= 30 && $this->expires_at->isFuture();
    }

    /**
     * Get all available form types.
     */
    public static function getFormTypes(): array
    {
        return [
            self::TYPE_W9 => 'Form W-9 (US Persons)',
            self::TYPE_W8BEN => 'Form W-8BEN (Non-US Individuals)',
            self::TYPE_W8BENE => 'Form W-8BEN-E (Non-US Entities)',
            self::TYPE_P45 => 'Form P45 (UK)',
            self::TYPE_P60 => 'Form P60 (UK)',
            self::TYPE_SELF_ASSESSMENT => 'Self Assessment Declaration',
            self::TYPE_TAX_DECLARATION => 'Tax Declaration',
        ];
    }

    /**
     * Get all entity types.
     */
    public static function getEntityTypes(): array
    {
        return [
            self::ENTITY_INDIVIDUAL => 'Individual',
            self::ENTITY_SOLE_PROPRIETOR => 'Sole Proprietor',
            self::ENTITY_LLC => 'Limited Liability Company',
            self::ENTITY_CORPORATION => 'Corporation',
            self::ENTITY_PARTNERSHIP => 'Partnership',
            self::ENTITY_TRUST => 'Trust',
            self::ENTITY_ESTATE => 'Estate',
        ];
    }
}
