<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FIN-015: Fraud Signal Model
 *
 * Records individual fraud detection signals for users.
 *
 * @property int $id
 * @property int $user_id
 * @property string $signal_type
 * @property string $signal_code
 * @property int $severity
 * @property array|null $signal_data
 * @property string|null $ip_address
 * @property string|null $device_fingerprint
 * @property string|null $user_agent
 * @property bool $is_resolved
 * @property string|null $resolution_notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class FraudSignal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'signal_type',
        'signal_code',
        'severity',
        'signal_data',
        'ip_address',
        'device_fingerprint',
        'user_agent',
        'is_resolved',
        'resolution_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signal_data' => 'array',
            'is_resolved' => 'boolean',
            'severity' => 'integer',
        ];
    }

    /**
     * Signal type constants.
     */
    public const TYPE_VELOCITY = 'velocity';

    public const TYPE_DEVICE = 'device';

    public const TYPE_LOCATION = 'location';

    public const TYPE_BEHAVIOR = 'behavior';

    public const TYPE_IDENTITY = 'identity';

    public const TYPE_PAYMENT = 'payment';

    /**
     * Signal code constants.
     */
    public const CODE_RAPID_SIGNUPS = 'RAPID_SIGNUPS';

    public const CODE_RAPID_APPLICATIONS = 'RAPID_APPLICATIONS';

    public const CODE_DEVICE_MISMATCH = 'DEVICE_MISMATCH';

    public const CODE_MULTIPLE_DEVICES = 'MULTIPLE_DEVICES';

    public const CODE_BLOCKED_DEVICE = 'BLOCKED_DEVICE';

    public const CODE_UNUSUAL_LOCATION = 'UNUSUAL_LOCATION';

    public const CODE_RAPID_LOCATION_CHANGE = 'RAPID_LOCATION_CHANGE';

    public const CODE_SUSPICIOUS_LOGIN_PATTERN = 'SUSPICIOUS_LOGIN_PATTERN';

    public const CODE_RAPID_PROFILE_CHANGES = 'RAPID_PROFILE_CHANGES';

    public const CODE_IDENTITY_MISMATCH = 'IDENTITY_MISMATCH';

    public const CODE_DUPLICATE_IDENTITY = 'DUPLICATE_IDENTITY';

    public const CODE_PAYMENT_VELOCITY = 'PAYMENT_VELOCITY';

    public const CODE_FAILED_PAYMENTS = 'FAILED_PAYMENTS';

    public const CODE_SUSPICIOUS_PAYMENT_PATTERN = 'SUSPICIOUS_PAYMENT_PATTERN';

    public const CODE_ACCOUNT_TAKEOVER = 'ACCOUNT_TAKEOVER';

    public const CODE_BOT_ACTIVITY = 'BOT_ACTIVITY';

    /**
     * Get all signal types.
     *
     * @return array<string, string>
     */
    public static function getSignalTypes(): array
    {
        return [
            self::TYPE_VELOCITY => 'Velocity',
            self::TYPE_DEVICE => 'Device',
            self::TYPE_LOCATION => 'Location',
            self::TYPE_BEHAVIOR => 'Behavior',
            self::TYPE_IDENTITY => 'Identity',
            self::TYPE_PAYMENT => 'Payment',
        ];
    }

    /**
     * Get all signal codes.
     *
     * @return array<string, string>
     */
    public static function getSignalCodes(): array
    {
        return [
            self::CODE_RAPID_SIGNUPS => 'Rapid Signups from Same IP',
            self::CODE_RAPID_APPLICATIONS => 'Rapid Shift Applications',
            self::CODE_DEVICE_MISMATCH => 'Device Fingerprint Mismatch',
            self::CODE_MULTIPLE_DEVICES => 'Multiple Devices Detected',
            self::CODE_BLOCKED_DEVICE => 'Blocked Device Detected',
            self::CODE_UNUSUAL_LOCATION => 'Unusual Login Location',
            self::CODE_RAPID_LOCATION_CHANGE => 'Rapid Location Change',
            self::CODE_SUSPICIOUS_LOGIN_PATTERN => 'Suspicious Login Pattern',
            self::CODE_RAPID_PROFILE_CHANGES => 'Rapid Profile Changes',
            self::CODE_IDENTITY_MISMATCH => 'Identity Mismatch',
            self::CODE_DUPLICATE_IDENTITY => 'Duplicate Identity Detected',
            self::CODE_PAYMENT_VELOCITY => 'Payment Velocity Alert',
            self::CODE_FAILED_PAYMENTS => 'Multiple Failed Payment Attempts',
            self::CODE_SUSPICIOUS_PAYMENT_PATTERN => 'Suspicious Payment Pattern',
            self::CODE_ACCOUNT_TAKEOVER => 'Potential Account Takeover',
            self::CODE_BOT_ACTIVITY => 'Bot Activity Detected',
        ];
    }

    /**
     * Get the user associated with this signal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Scopes ==========

    /**
     * Scope for unresolved signals.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope for resolved signals.
     */
    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Scope by signal type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('signal_type', $type);
    }

    /**
     * Scope by signal code.
     */
    public function scopeWithCode($query, string $code)
    {
        return $query->where('signal_code', $code);
    }

    /**
     * Scope for high severity signals (7+).
     */
    public function scopeHighSeverity($query)
    {
        return $query->where('severity', '>=', 7);
    }

    /**
     * Scope for signals within a time period.
     */
    public function scopeWithinPeriod($query, string $period)
    {
        $date = match ($period) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay(),
        };

        return $query->where('created_at', '>=', $date);
    }

    // ========== Helpers ==========

    /**
     * Resolve this signal.
     */
    public function resolve(?string $notes = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Get severity label.
     */
    public function getSeverityLabelAttribute(): string
    {
        return match (true) {
            $this->severity >= 9 => 'Critical',
            $this->severity >= 7 => 'High',
            $this->severity >= 4 => 'Medium',
            default => 'Low',
        };
    }

    /**
     * Get severity badge class.
     */
    public function getSeverityBadgeClassAttribute(): string
    {
        return match (true) {
            $this->severity >= 9 => 'bg-red-600',
            $this->severity >= 7 => 'bg-orange-500',
            $this->severity >= 4 => 'bg-yellow-500',
            default => 'bg-green-500',
        };
    }

    /**
     * Get the signal code description.
     */
    public function getCodeDescriptionAttribute(): string
    {
        return self::getSignalCodes()[$this->signal_code] ?? $this->signal_code;
    }

    /**
     * Get the signal type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getSignalTypes()[$this->signal_type] ?? ucfirst($this->signal_type);
    }
}
