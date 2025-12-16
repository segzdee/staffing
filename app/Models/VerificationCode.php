<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Model for managing email/phone verification codes.
 */
class VerificationCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'identifier',
        'type',
        'code',
        'token',
        'attempts',
        'max_attempts',
        'is_used',
        'used_at',
        'expires_at',
        'ip_address',
        'user_agent',
        'purpose',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Default expiration times in minutes.
     */
    public const EXPIRY_MINUTES = [
        'email' => 1440, // 24 hours
        'phone' => 10,   // 10 minutes
        'password_reset' => 60, // 1 hour
        'two_factor' => 5, // 5 minutes
    ];

    /**
     * Code lengths by type.
     */
    public const CODE_LENGTHS = [
        'email' => 6,
        'phone' => 6,
        'password_reset' => 6,
        'two_factor' => 6,
    ];

    // ===================== Relationships =====================

    /**
     * Get the user this code belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===================== Scopes =====================

    /**
     * Scope to valid (not expired, not used) codes.
     */
    public function scopeValid($query)
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', now())
            ->whereColumn('attempts', '<', 'max_attempts');
    }

    /**
     * Scope to find by identifier and type.
     */
    public function scopeForIdentifier($query, string $identifier, string $type)
    {
        return $query->where('identifier', $identifier)
            ->where('type', $type);
    }

    /**
     * Scope to find by code.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope to find by token.
     */
    public function scopeByToken($query, string $token)
    {
        return $query->where('token', $token);
    }

    /**
     * Scope to recent codes (for rate limiting).
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>', now()->subMinutes($minutes));
    }

    /**
     * Scope to codes from a specific IP.
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    // ===================== Methods =====================

    /**
     * Generate a new verification code.
     */
    public static function generate(
        string $identifier,
        string $type,
        ?int $userId = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $purpose = null
    ): self {
        // Invalidate any existing codes for this identifier/type
        self::where('identifier', $identifier)
            ->where('type', $type)
            ->where('is_used', false)
            ->update(['is_used' => true, 'used_at' => now()]);

        $codeLength = self::CODE_LENGTHS[$type] ?? 6;
        $expiryMinutes = self::EXPIRY_MINUTES[$type] ?? 60;

        return self::create([
            'user_id' => $userId,
            'identifier' => $identifier,
            'type' => $type,
            'code' => self::generateNumericCode($codeLength),
            'token' => $type === 'email' ? Str::random(64) : null,
            'attempts' => 0,
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'purpose' => $purpose ?? 'verification',
        ]);
    }

    /**
     * Generate a numeric code.
     */
    public static function generateNumericCode(int $length = 6): string
    {
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        return (string) random_int($min, $max);
    }

    /**
     * Verify a code.
     */
    public function verify(string $code): bool
    {
        // Check if already used
        if ($this->is_used) {
            return false;
        }

        // Check if expired
        if ($this->isExpired()) {
            return false;
        }

        // Check if max attempts reached
        if ($this->attempts >= $this->max_attempts) {
            return false;
        }

        // Increment attempt counter
        $this->increment('attempts');

        // Check code match
        if ($this->code !== $code) {
            return false;
        }

        // Mark as used
        $this->markAsUsed();

        return true;
    }

    /**
     * Verify a token (for email links).
     */
    public function verifyToken(string $token): bool
    {
        if ($this->is_used) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->token !== $token) {
            return false;
        }

        $this->markAsUsed();

        return true;
    }

    /**
     * Check if the code has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the code is still valid.
     */
    public function isValid(): bool
    {
        return !$this->is_used
            && !$this->isExpired()
            && $this->attempts < $this->max_attempts;
    }

    /**
     * Mark the code as used.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
        ]);
    }

    /**
     * Get remaining attempts.
     */
    public function getRemainingAttempts(): int
    {
        return max(0, $this->max_attempts - $this->attempts);
    }

    /**
     * Get minutes until expiration.
     */
    public function getMinutesUntilExpiry(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInMinutes($this->expires_at);
    }

    /**
     * Check rate limit for an IP.
     */
    public static function checkRateLimit(string $ip, string $type, int $maxPerHour = 5): bool
    {
        $count = self::fromIp($ip)
            ->where('type', $type)
            ->recent(60)
            ->count();

        return $count < $maxPerHour;
    }

    /**
     * Get the rate limit count for an IP.
     */
    public static function getRateLimitCount(string $ip, string $type): int
    {
        return self::fromIp($ip)
            ->where('type', $type)
            ->recent(60)
            ->count();
    }

    /**
     * Clean up expired codes.
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now()->subDay())
            ->delete();
    }

    /**
     * Find valid code for verification.
     */
    public static function findValidCode(string $identifier, string $type, string $code): ?self
    {
        return self::forIdentifier($identifier, $type)
            ->byCode($code)
            ->valid()
            ->first();
    }

    /**
     * Find valid token for verification.
     */
    public static function findValidToken(string $token, string $type): ?self
    {
        return self::byToken($token)
            ->where('type', $type)
            ->valid()
            ->first();
    }
}
