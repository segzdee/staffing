<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BusinessPaymentMethod Model
 *
 * BIZ-REG-007: Business Payment Setup
 *
 * Represents a payment method attached to a business for paying workers.
 * Supports multiple types: card, us_bank_account, sepa_debit, bacs_debit.
 */
class BusinessPaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'business_profile_id',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'stripe_setup_intent_id',
        'type',
        'display_brand',
        'display_last4',
        'display_exp_month',
        'display_exp_year',
        'bank_name',
        'bank_account_type',
        'bank_routing_display',
        'iban_last4',
        'sort_code_display',
        'verification_status',
        'verification_method',
        'verification_requested_at',
        'verified_at',
        'verification_failure_reason',
        'micro_deposit_attempts',
        'micro_deposit_sent_at',
        'three_d_secure_supported',
        'three_d_secure_status',
        'billing_name',
        'billing_email',
        'billing_phone',
        'billing_address_line1',
        'billing_address_line2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'is_default',
        'is_active',
        'auto_retry_enabled',
        'max_retry_attempts',
        'failed_payment_count',
        'last_failed_at',
        'last_failure_reason',
        'nickname',
        'metadata',
        'currency',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'verification_requested_at' => 'datetime',
        'verified_at' => 'datetime',
        'micro_deposit_sent_at' => 'datetime',
        'last_failed_at' => 'datetime',
        'three_d_secure_supported' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'auto_retry_enabled' => 'boolean',
        'micro_deposit_attempts' => 'integer',
        'max_retry_attempts' => 'integer',
        'failed_payment_count' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Payment method type constants.
     */
    const TYPE_CARD = 'card';
    const TYPE_US_BANK_ACCOUNT = 'us_bank_account';
    const TYPE_SEPA_DEBIT = 'sepa_debit';
    const TYPE_BACS_DEBIT = 'bacs_debit';

    /**
     * Verification status constants.
     */
    const VERIFICATION_PENDING = 'pending';
    const VERIFICATION_VERIFIED = 'verified';
    const VERIFICATION_FAILED = 'failed';
    const VERIFICATION_REQUIRES_ACTION = 'requires_action';

    /**
     * Get the business profile that owns this payment method.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    // =========================================
    // Status Check Methods
    // =========================================

    /**
     * Check if payment method is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    /**
     * Check if payment method requires verification action.
     */
    public function requiresAction(): bool
    {
        return $this->verification_status === self::VERIFICATION_REQUIRES_ACTION;
    }

    /**
     * Check if payment method is usable for payments.
     */
    public function isUsable(): bool
    {
        return $this->is_active && $this->isVerified();
    }

    /**
     * Check if this is a card payment method.
     */
    public function isCard(): bool
    {
        return $this->type === self::TYPE_CARD;
    }

    /**
     * Check if this is a bank account payment method.
     */
    public function isBankAccount(): bool
    {
        return in_array($this->type, [
            self::TYPE_US_BANK_ACCOUNT,
            self::TYPE_SEPA_DEBIT,
            self::TYPE_BACS_DEBIT,
        ]);
    }

    // =========================================
    // Display Methods
    // =========================================

    /**
     * Get a human-readable display name for the payment method.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->nickname) {
            return $this->nickname;
        }

        if ($this->isCard()) {
            $brand = ucfirst($this->display_brand ?? 'Card');
            return "{$brand} ending in {$this->display_last4}";
        }

        if ($this->type === self::TYPE_US_BANK_ACCOUNT) {
            $bank = $this->bank_name ?? 'Bank';
            return "{$bank} account ending in {$this->display_last4}";
        }

        if ($this->type === self::TYPE_SEPA_DEBIT) {
            return "SEPA account ending in {$this->iban_last4}";
        }

        if ($this->type === self::TYPE_BACS_DEBIT) {
            return "UK bank account ending in {$this->display_last4}";
        }

        return "Payment method ending in {$this->display_last4}";
    }

    /**
     * Get type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match($this->type) {
            self::TYPE_CARD => 'Credit/Debit Card',
            self::TYPE_US_BANK_ACCOUNT => 'Bank Account (ACH)',
            self::TYPE_SEPA_DEBIT => 'SEPA Direct Debit',
            self::TYPE_BACS_DEBIT => 'UK Bank Account (BACS)',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get verification status display.
     */
    public function getVerificationStatusDisplayAttribute(): string
    {
        return match($this->verification_status) {
            self::VERIFICATION_PENDING => 'Pending Verification',
            self::VERIFICATION_VERIFIED => 'Verified',
            self::VERIFICATION_FAILED => 'Verification Failed',
            self::VERIFICATION_REQUIRES_ACTION => 'Action Required',
            default => ucfirst($this->verification_status),
        };
    }

    /**
     * Get card expiry display (MM/YY).
     */
    public function getExpiryDisplayAttribute(): ?string
    {
        if (!$this->display_exp_month || !$this->display_exp_year) {
            return null;
        }

        $month = str_pad($this->display_exp_month, 2, '0', STR_PAD_LEFT);
        $year = substr($this->display_exp_year, -2);

        return "{$month}/{$year}";
    }

    /**
     * Get brand icon name.
     */
    public function getBrandIconAttribute(): string
    {
        if (!$this->isCard()) {
            return match($this->type) {
                self::TYPE_US_BANK_ACCOUNT => 'building-columns',
                self::TYPE_SEPA_DEBIT => 'euro-sign',
                self::TYPE_BACS_DEBIT => 'sterling-sign',
                default => 'credit-card',
            };
        }

        return match(strtolower($this->display_brand ?? '')) {
            'visa' => 'cc-visa',
            'mastercard' => 'cc-mastercard',
            'amex', 'american_express' => 'cc-amex',
            'discover' => 'cc-discover',
            'diners', 'diners_club' => 'cc-diners-club',
            'jcb' => 'cc-jcb',
            default => 'credit-card',
        };
    }

    // =========================================
    // Verification Methods
    // =========================================

    /**
     * Mark payment method as verified.
     */
    public function markVerified(): self
    {
        $this->update([
            'verification_status' => self::VERIFICATION_VERIFIED,
            'verified_at' => now(),
            'verification_failure_reason' => null,
        ]);

        return $this;
    }

    /**
     * Mark verification as failed.
     */
    public function markVerificationFailed(string $reason): self
    {
        $this->update([
            'verification_status' => self::VERIFICATION_FAILED,
            'verification_failure_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Record micro-deposit sent for ACH verification.
     */
    public function recordMicroDepositSent(): self
    {
        $this->update([
            'verification_method' => 'micro_deposits',
            'micro_deposit_sent_at' => now(),
            'verification_status' => self::VERIFICATION_REQUIRES_ACTION,
        ]);

        return $this;
    }

    /**
     * Increment micro-deposit verification attempt.
     */
    public function incrementMicroDepositAttempt(): self
    {
        $this->increment('micro_deposit_attempts');

        if ($this->micro_deposit_attempts >= 3) {
            $this->markVerificationFailed('Maximum micro-deposit verification attempts exceeded');
        }

        return $this;
    }

    // =========================================
    // Payment Failure Tracking
    // =========================================

    /**
     * Record a payment failure.
     */
    public function recordPaymentFailure(string $reason): self
    {
        $this->increment('failed_payment_count');
        $this->update([
            'last_failed_at' => now(),
            'last_failure_reason' => $reason,
        ]);

        // Disable if too many failures
        if ($this->failed_payment_count >= $this->max_retry_attempts) {
            $this->update(['is_active' => false]);
        }

        return $this;
    }

    /**
     * Reset failure count after successful payment.
     */
    public function resetFailureCount(): self
    {
        $this->update([
            'failed_payment_count' => 0,
            'last_failure_reason' => null,
        ]);

        return $this;
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get active payment methods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get verified payment methods.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    /**
     * Scope to get usable payment methods (active + verified).
     */
    public function scopeUsable($query)
    {
        return $query->active()->verified();
    }

    /**
     * Scope to get default payment method.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get cards only.
     */
    public function scopeCards($query)
    {
        return $query->where('type', self::TYPE_CARD);
    }

    /**
     * Scope to get bank accounts only.
     */
    public function scopeBankAccounts($query)
    {
        return $query->whereIn('type', [
            self::TYPE_US_BANK_ACCOUNT,
            self::TYPE_SEPA_DEBIT,
            self::TYPE_BACS_DEBIT,
        ]);
    }

    // =========================================
    // Billing Address Methods
    // =========================================

    /**
     * Get formatted billing address.
     */
    public function getFormattedBillingAddressAttribute(): ?string
    {
        if (!$this->billing_address_line1) {
            return null;
        }

        $parts = array_filter([
            $this->billing_address_line1,
            $this->billing_address_line2,
            $this->billing_city,
            $this->billing_state . ' ' . $this->billing_postal_code,
            $this->billing_country,
        ]);

        return implode("\n", $parts);
    }

    /**
     * Update billing address.
     */
    public function updateBillingAddress(array $address): self
    {
        $this->update([
            'billing_name' => $address['name'] ?? $this->billing_name,
            'billing_email' => $address['email'] ?? $this->billing_email,
            'billing_phone' => $address['phone'] ?? $this->billing_phone,
            'billing_address_line1' => $address['line1'] ?? $this->billing_address_line1,
            'billing_address_line2' => $address['line2'] ?? $this->billing_address_line2,
            'billing_city' => $address['city'] ?? $this->billing_city,
            'billing_state' => $address['state'] ?? $this->billing_state,
            'billing_postal_code' => $address['postal_code'] ?? $this->billing_postal_code,
            'billing_country' => $address['country'] ?? $this->billing_country,
        ]);

        return $this;
    }
}
