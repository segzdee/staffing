<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * COM-004: User Phone and Messaging Preferences Model
 *
 * Stores user preferences for SMS vs WhatsApp communication,
 * opt-in status, and quiet hours settings.
 *
 * @property int $id
 * @property int $user_id
 * @property string $phone_number
 * @property string $country_code
 * @property bool $whatsapp_enabled
 * @property bool $sms_enabled
 * @property string $preferred_channel
 * @property bool $verified
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property string|null $verification_method
 * @property bool $marketing_opt_in
 * @property bool $transactional_opt_in
 * @property bool $urgent_alerts_opt_in
 * @property array|null $quiet_hours
 * @property string|null $whatsapp_opt_in_message_id
 * @property \Illuminate\Support\Carbon|null $whatsapp_opted_in_at
 * @property \Illuminate\Support\Carbon|null $whatsapp_opted_out_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 */
class UserPhonePreference extends Model
{
    use HasFactory;

    protected $table = 'user_phone_preferences';

    protected $fillable = [
        'user_id',
        'phone_number',
        'country_code',
        'whatsapp_enabled',
        'sms_enabled',
        'preferred_channel',
        'verified',
        'verified_at',
        'verification_method',
        'marketing_opt_in',
        'transactional_opt_in',
        'urgent_alerts_opt_in',
        'quiet_hours',
        'whatsapp_opt_in_message_id',
        'whatsapp_opted_in_at',
        'whatsapp_opted_out_at',
    ];

    protected $casts = [
        'whatsapp_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
        'marketing_opt_in' => 'boolean',
        'transactional_opt_in' => 'boolean',
        'urgent_alerts_opt_in' => 'boolean',
        'quiet_hours' => 'array',
        'whatsapp_opted_in_at' => 'datetime',
        'whatsapp_opted_out_at' => 'datetime',
    ];

    /**
     * Channel constants
     */
    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    /**
     * Verification method constants
     */
    public const VERIFY_SMS_CODE = 'sms_code';

    public const VERIFY_WHATSAPP_CODE = 'whatsapp_code';

    public const VERIFY_MANUAL = 'manual';

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Verified phone numbers only
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope: WhatsApp enabled
     */
    public function scopeWhatsappEnabled($query)
    {
        return $query->where('whatsapp_enabled', true);
    }

    /**
     * Scope: SMS enabled
     */
    public function scopeSmsEnabled($query)
    {
        return $query->where('sms_enabled', true);
    }

    /**
     * Scope: Prefer WhatsApp
     */
    public function scopePreferWhatsapp($query)
    {
        return $query->where('preferred_channel', self::CHANNEL_WHATSAPP);
    }

    /**
     * Scope: Prefer SMS
     */
    public function scopePreferSms($query)
    {
        return $query->where('preferred_channel', self::CHANNEL_SMS);
    }

    /**
     * Scope: Marketing opt-in
     */
    public function scopeMarketingOptIn($query)
    {
        return $query->where('marketing_opt_in', true);
    }

    /**
     * Get the full phone number with country code
     */
    public function getFullPhoneNumberAttribute(): string
    {
        $countryCode = ltrim($this->country_code, '+');
        $phone = ltrim($this->phone_number, '+');

        // If phone already starts with country code, return as is
        if (str_starts_with($phone, $countryCode)) {
            return '+'.$phone;
        }

        return '+'.$countryCode.$phone;
    }

    /**
     * Get the E.164 formatted phone number
     */
    public function getE164PhoneAttribute(): string
    {
        return preg_replace('/[^\d+]/', '', $this->full_phone_number);
    }

    /**
     * Determine the best channel to use for sending messages
     *
     * @param  string  $messageType  Type of message (otp, shift_reminder, urgent_alert, etc.)
     */
    public function getBestChannel(string $messageType = 'transactional'): string
    {
        // For urgent alerts, always use user's preference if both enabled
        if ($messageType === 'urgent_alert' && ! $this->urgent_alerts_opt_in) {
            // Fall through - will use best available
        }

        // For marketing, require explicit opt-in
        if ($messageType === 'marketing' && ! $this->marketing_opt_in) {
            return self::CHANNEL_SMS; // Fallback, but marketing won't be sent
        }

        // If only one channel is enabled, use that
        if ($this->whatsapp_enabled && ! $this->sms_enabled) {
            return self::CHANNEL_WHATSAPP;
        }

        if ($this->sms_enabled && ! $this->whatsapp_enabled) {
            return self::CHANNEL_SMS;
        }

        // Both enabled - use preference
        return $this->preferred_channel;
    }

    /**
     * Check if user can receive messages on the given channel
     */
    public function canReceiveOn(string $channel): bool
    {
        if (! $this->verified) {
            return false;
        }

        return match ($channel) {
            self::CHANNEL_WHATSAPP => $this->whatsapp_enabled,
            self::CHANNEL_SMS => $this->sms_enabled,
            default => false,
        };
    }

    /**
     * Check if user can receive specific message type
     */
    public function canReceiveMessageType(string $type): bool
    {
        if (! $this->verified) {
            return false;
        }

        return match ($type) {
            'marketing' => $this->marketing_opt_in,
            'urgent_alert' => $this->urgent_alerts_opt_in,
            'transactional', 'otp', 'shift_reminder' => $this->transactional_opt_in,
            default => true,
        };
    }

    /**
     * Check if currently within quiet hours
     */
    public function isInQuietHours(): bool
    {
        if (empty($this->quiet_hours)) {
            return false;
        }

        $timezone = $this->quiet_hours['timezone'] ?? 'UTC';
        $now = now()->setTimezone($timezone);

        $start = \Carbon\Carbon::createFromFormat('H:i', $this->quiet_hours['start'] ?? '22:00', $timezone);
        $end = \Carbon\Carbon::createFromFormat('H:i', $this->quiet_hours['end'] ?? '07:00', $timezone);

        // Handle overnight quiet hours (e.g., 22:00 to 07:00)
        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }

    /**
     * Mark phone as verified
     */
    public function markVerified(string $method = self::VERIFY_SMS_CODE): bool
    {
        return $this->update([
            'verified' => true,
            'verified_at' => now(),
            'verification_method' => $method,
        ]);
    }

    /**
     * Enable WhatsApp
     */
    public function enableWhatsApp(?string $messageId = null): bool
    {
        $data = [
            'whatsapp_enabled' => true,
            'whatsapp_opted_in_at' => now(),
            'whatsapp_opted_out_at' => null,
        ];

        if ($messageId) {
            $data['whatsapp_opt_in_message_id'] = $messageId;
        }

        return $this->update($data);
    }

    /**
     * Disable WhatsApp
     */
    public function disableWhatsApp(): bool
    {
        $data = [
            'whatsapp_enabled' => false,
            'whatsapp_opted_out_at' => now(),
        ];

        // If WhatsApp was preferred, switch to SMS
        if ($this->preferred_channel === self::CHANNEL_WHATSAPP) {
            $data['preferred_channel'] = self::CHANNEL_SMS;
        }

        return $this->update($data);
    }

    /**
     * Set preferred channel
     */
    public function setPreferredChannel(string $channel): bool
    {
        if (! in_array($channel, [self::CHANNEL_SMS, self::CHANNEL_WHATSAPP])) {
            return false;
        }

        // Can't prefer a disabled channel
        if ($channel === self::CHANNEL_WHATSAPP && ! $this->whatsapp_enabled) {
            return false;
        }

        if ($channel === self::CHANNEL_SMS && ! $this->sms_enabled) {
            return false;
        }

        return $this->update(['preferred_channel' => $channel]);
    }

    /**
     * Set quiet hours
     *
     * @param  string  $start  Start time in H:i format
     * @param  string  $end  End time in H:i format
     * @param  string  $timezone  Timezone identifier
     */
    public function setQuietHours(string $start, string $end, string $timezone = 'UTC'): bool
    {
        return $this->update([
            'quiet_hours' => [
                'start' => $start,
                'end' => $end,
                'timezone' => $timezone,
            ],
        ]);
    }

    /**
     * Clear quiet hours
     */
    public function clearQuietHours(): bool
    {
        return $this->update(['quiet_hours' => null]);
    }

    /**
     * Update marketing opt-in status
     */
    public function setMarketingOptIn(bool $optIn): bool
    {
        return $this->update(['marketing_opt_in' => $optIn]);
    }

    /**
     * Get or create preferences for a user
     */
    public static function getOrCreateForUser(User $user, ?string $phone = null): self
    {
        $preference = static::where('user_id', $user->id)->first();

        if ($preference) {
            return $preference;
        }

        // Get phone from user if not provided
        $phone = $phone ?? $user->phone ?? '';

        // Parse country code from phone number
        $countryCode = '+1'; // Default to US
        if (str_starts_with($phone, '+')) {
            // Try to extract country code (simple extraction for common formats)
            preg_match('/^\+(\d{1,3})/', $phone, $matches);
            if (! empty($matches[1])) {
                $countryCode = '+'.$matches[1];
                $phone = substr($phone, strlen($matches[0]));
            }
        }

        return static::create([
            'user_id' => $user->id,
            'phone_number' => $phone,
            'country_code' => $countryCode,
            'whatsapp_enabled' => false,
            'sms_enabled' => true,
            'preferred_channel' => self::CHANNEL_SMS,
            'verified' => false,
            'transactional_opt_in' => true,
            'urgent_alerts_opt_in' => true,
            'marketing_opt_in' => false,
        ]);
    }
}
