<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * GLO-008: Cross-Border Payments - Bank Account
 *
 * Stores user bank account details for cross-border payouts.
 *
 * @property int $id
 * @property int $user_id
 * @property string $account_holder_name
 * @property string|null $bank_name
 * @property string $country_code
 * @property string $currency_code
 * @property string|null $iban
 * @property string|null $account_number
 * @property string|null $routing_number
 * @property string|null $sort_code
 * @property string|null $bsb_code
 * @property string|null $swift_bic
 * @property string $account_type
 * @property bool $is_verified
 * @property bool $is_primary
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CrossBorderTransfer> $transfers
 */
class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Account type constants.
     */
    public const TYPE_CHECKING = 'checking';

    public const TYPE_SAVINGS = 'savings';

    /**
     * Country code mappings for bank account requirements.
     */
    public const COUNTRY_REQUIREMENTS = [
        'US' => ['routing_number', 'account_number'],
        'GB' => ['sort_code', 'account_number'],
        'AU' => ['bsb_code', 'account_number'],
        'SEPA' => ['iban'],
        'DEFAULT' => ['iban', 'swift_bic'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'account_holder_name',
        'bank_name',
        'country_code',
        'currency_code',
        'iban',
        'account_number',
        'routing_number',
        'sort_code',
        'bsb_code',
        'swift_bic',
        'account_type',
        'is_verified',
        'is_primary',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_verified' => 'boolean',
        'is_primary' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'account_number',
        'routing_number',
        'sort_code',
        'bsb_code',
        'iban',
    ];

    /**
     * Get the user that owns the bank account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transfers associated with this bank account.
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(CrossBorderTransfer::class);
    }

    /**
     * Scope for verified accounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for primary accounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for accounts by country.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    /**
     * Get a masked version of the account number for display.
     */
    public function getMaskedAccountNumber(): string
    {
        if ($this->iban) {
            $length = strlen($this->iban);

            return substr($this->iban, 0, 4).'****'.substr($this->iban, -4);
        }

        if ($this->account_number) {
            $length = strlen($this->account_number);

            return str_repeat('*', max(0, $length - 4)).substr($this->account_number, -4);
        }

        return '****';
    }

    /**
     * Get the display name for this bank account.
     */
    public function getDisplayName(): string
    {
        $name = $this->bank_name ?? 'Bank Account';

        return "{$name} ({$this->getMaskedAccountNumber()})";
    }

    /**
     * Mark this account as primary and unset others.
     */
    public function markAsPrimary(): void
    {
        // Unset other primary accounts for this user
        static::query()
            ->where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);

        // Set this one as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Mark this account as verified.
     */
    public function markAsVerified(): void
    {
        $this->update(['is_verified' => true]);
    }

    /**
     * Check if this is a SEPA country account.
     */
    public function isSepaCountry(): bool
    {
        return in_array($this->country_code, PaymentCorridor::SEPA_COUNTRIES);
    }

    /**
     * Check if this is a US account.
     */
    public function isUsAccount(): bool
    {
        return $this->country_code === 'US';
    }

    /**
     * Check if this is a UK account.
     */
    public function isUkAccount(): bool
    {
        return $this->country_code === 'GB';
    }

    /**
     * Check if this is an Australian account.
     */
    public function isAustralianAccount(): bool
    {
        return $this->country_code === 'AU';
    }

    /**
     * Get required fields for this account's country.
     */
    public function getRequiredFields(): array
    {
        if ($this->isUsAccount()) {
            return self::COUNTRY_REQUIREMENTS['US'];
        }

        if ($this->isUkAccount()) {
            return self::COUNTRY_REQUIREMENTS['GB'];
        }

        if ($this->isAustralianAccount()) {
            return self::COUNTRY_REQUIREMENTS['AU'];
        }

        if ($this->isSepaCountry()) {
            return self::COUNTRY_REQUIREMENTS['SEPA'];
        }

        return self::COUNTRY_REQUIREMENTS['DEFAULT'];
    }

    /**
     * Validate that all required fields are present.
     */
    public function hasRequiredFields(): bool
    {
        foreach ($this->getRequiredFields() as $field) {
            if (empty($this->{$field})) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the best payment method for transfers to this account.
     */
    public function getSuggestedPaymentMethod(): string
    {
        if ($this->isUsAccount()) {
            return PaymentCorridor::METHOD_ACH;
        }

        if ($this->isUkAccount()) {
            return PaymentCorridor::METHOD_FASTER_PAYMENTS;
        }

        if ($this->isSepaCountry()) {
            return PaymentCorridor::METHOD_SEPA;
        }

        return PaymentCorridor::METHOD_SWIFT;
    }

    /**
     * Get the account type label.
     */
    public function getAccountTypeLabel(): string
    {
        return match ($this->account_type) {
            self::TYPE_CHECKING => 'Checking',
            self::TYPE_SAVINGS => 'Savings',
            default => ucfirst($this->account_type),
        };
    }

    /**
     * Store additional metadata.
     */
    public function setMeta(string $key, mixed $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->update(['metadata' => $metadata]);
    }

    /**
     * Get metadata value.
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
