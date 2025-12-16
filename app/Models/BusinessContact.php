<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BusinessContact Model
 * BIZ-REG-003: Stores contact information for businesses
 *
 * @property int $id
 * @property int $business_profile_id
 * @property string $contact_type
 * @property string $first_name
 * @property string $last_name
 * @property string|null $job_title
 * @property string $email
 * @property string|null $phone
 * @property string|null $phone_extension
 * @property string|null $mobile
 * @property bool $receives_shift_notifications
 * @property bool $receives_billing_notifications
 * @property bool $receives_marketing_emails
 * @property string $preferred_contact_method
 * @property bool $email_verified
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string|null $verification_token
 * @property bool $is_active
 * @property bool $is_primary
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read BusinessProfile $businessProfile
 */
class BusinessContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_profile_id',
        'contact_type',
        'first_name',
        'last_name',
        'job_title',
        'email',
        'phone',
        'phone_extension',
        'mobile',
        'receives_shift_notifications',
        'receives_billing_notifications',
        'receives_marketing_emails',
        'preferred_contact_method',
        'email_verified',
        'email_verified_at',
        'verification_token',
        'is_active',
        'is_primary',
    ];

    protected $casts = [
        'receives_shift_notifications' => 'boolean',
        'receives_billing_notifications' => 'boolean',
        'receives_marketing_emails' => 'boolean',
        'email_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
    ];

    /**
     * Contact type constants
     */
    const TYPE_PRIMARY = 'primary';
    const TYPE_BILLING = 'billing';
    const TYPE_OPERATIONS = 'operations';
    const TYPE_EMERGENCY = 'emergency';
    const TYPE_HR = 'hr';

    /**
     * Get the business profile that owns this contact.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the full name of the contact.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the formatted phone number.
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        $phone = $this->phone;
        if ($this->phone_extension) {
            $phone .= " ext. {$this->phone_extension}";
        }

        return $phone;
    }

    /**
     * Check if the contact's email is verified.
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified;
    }

    /**
     * Generate a new verification token.
     */
    public function generateVerificationToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['verification_token' => $token]);
        return $token;
    }

    /**
     * Verify the email with token.
     */
    public function verifyEmail(string $token): bool
    {
        if ($this->verification_token === $token) {
            $this->update([
                'email_verified' => true,
                'email_verified_at' => now(),
                'verification_token' => null,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Mark this contact as primary.
     */
    public function markAsPrimary(): void
    {
        // Remove primary flag from other contacts of same type
        static::where('business_profile_id', $this->business_profile_id)
            ->where('contact_type', $this->contact_type)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }

    /**
     * Scope to get active contacts only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get primary contacts only.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to filter by contact type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('contact_type', $type);
    }

    /**
     * Scope to get contacts that receive shift notifications.
     */
    public function scopeReceivesShiftNotifications($query)
    {
        return $query->where('receives_shift_notifications', true);
    }

    /**
     * Scope to get contacts that receive billing notifications.
     */
    public function scopeReceivesBillingNotifications($query)
    {
        return $query->where('receives_billing_notifications', true);
    }
}
